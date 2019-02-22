<?php

namespace o;

class S_Infix extends Symbol {
    var $bindingPower = 80;
    var $type = SymbolType::INFIX;
    function asInner ($p, $left) {
        $this->space(' + ');
        $p->next();

        $right = $p->parseExpression($this->bindingPower);
        $this->setKids([$left, $right]);

        return $this;
    }
}

class S_Add extends S_Infix {
    var $bindingPower = 51;

    // Unary + and -
    function asLeft($p) {
        $p->next();
        $this->updateType(SymbolType::PREFIX);
        $this->setKids([$p->parseExpression(70)]);
        return $this;
    }
}

class S_Multiply extends S_Infix {
    var $bindingPower = 52;
}

class S_Concat extends S_Infix {
    var $bindingPower = 50;
    var $type = SymbolType::OPERATOR;
}
