<?php

namespace o;

class S_OpenBracket extends S_Infix {

    // Dynamic member.  foo[...]
    function asInner ($p, $left) {
        $p->next();
        $this->updateType(SymbolType::MEMBER);
        $this->space('x[x', true);
        $this->setKids([$left, $p->parseExpression(0)]);
        $p->now(']')->space('x]*')->next();
        return $this;
    }

    // List literal.  [ ... ]
    function asLeft($p) {
        $p->space('*[N')->next();
        $this->updateType(SymbolType::SEQUENCE);
        $els = [];
        while (true) {
            if ($p->symbol->isValue("]")) {
                break;
            }
            $els []= $p->parseExpression(0);
            if (!$p->symbol->isValue(',')) {
                $p->now(']', 'Missed a comma?');
                break;
            }
            $p->space('x, ');
            $p->next();
        }

        $p->space('N]*')->next();
        $this->setKids($els);
        return $this;
    }
}
