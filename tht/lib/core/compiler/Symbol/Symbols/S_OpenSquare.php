<?php

namespace o;

class S_OpenSquare extends S_InfixSticky {

    // Dynamic member.  foo[...]
    function asInner($p, $left) {

        $p->next();
        $this->updateType(SymbolType::MEMBER);
        $this->space('x[x');

        $this->addKids([$left, $p->parseExpression(0)]);
        $p->now(']', 'index.close')->space('x]*')->next();

        return $this;
    }

    // List literal.  [ ... ]
    function asLeft($p) {

        $sOpenBracket = $p->symbol;

        $p->next();
        $this->updateType(SymbolType::AST_LIST);
        $els = [];

        $pos = 0;
        $isMultiline = false;

        while (true) {

            $isMultiline = $p->parseElementSeparator($pos, $isMultiline, ']');
            $pos += 1;

            if ($p->symbol->isValue("]")) {
                break;
            }

            $els []= $p->parseExpression(0);

            if ($p->symbol->isValue(":")) {
                $p->error("Unexpected token in List: `:`  Try: Convert `[…]` to `{…}`");
            }
        }

        $sOpenBracket->space($isMultiline ? '*[B' : '*[x');
        $p->space($isMultiline ? 'B]*' : 'x]*');
        $p->next();

        $this->addKids($els);

        return $this;
    }
}
