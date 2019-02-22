<?php

namespace o;

class S_Ternary extends Symbol {
    var $type = SymbolType::TERNARY;
    var $bindingPower = 20;

    // e.g. test ? result1 : result2
    function asInner ($p, $left) {
        $p->next();

        if ($p->inTernary) {
            $p->error("Nested ternary operator `a ? b : c`. Try an `if/else` instead.");
        }
        $p->inTernary = true;

        $this->addKid($left);
        $this->space(' ? ');
        $this->addKid($p->parseExpression(0));
        $p->now(':')->space(' : ')->next();
        $this->addKid($p->parseExpression(0));

        $p->inTernary = false;

        return $this;
    }
}
