<?php

namespace o;

class S_OpenSquare extends S_Infix {

    // Dynamic member.  foo[...]
    function asInner ($p, $left) {

        $p->next();
        $this->updateType(SymbolType::MEMBER);
        $this->space('x[x');

        $this->setKids([$left, $p->parseExpression(0)]);
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
        $isMultiline = -1;
        while (true) {

            $isMultiline = $p->parseElementSeparator($pos, $isMultiline, $sOpenBracket);
            $pos += 1;

            if ($p->symbol->isValue("]")) {
                break;
            }

            $els []= $p->parseExpression(0);
        }

        $sOpenBracket->space($isMultiline ? '*[B' : '*[x');
        $p->space($isMultiline ? 'B]*' : 'x]*');
        $p->next();

        $this->setKids($els);

        return $this;
    }
}
