<?php

namespace o;

// Infix, but with a lower binding power
class S_InfixRight extends Symbol {
    var $type = SymbolType::INFIX;
    var $isAssignment = false;

    function asInner ($p, $left) {
        $p->next();
        if ($this->isAssignment && $p->expressionDepth >= 2) {
            $tip = $this->token[TOKEN_VALUE] == '=' ? "Did you mean `==`?" : '';
            $p->error("Assignment can not be used as an expression.  $tip", $this->token);
        }
        $this->space(' = ');
        $this->setKids([$left, $p->parseExpression($this->bindingPower - 1)]);
        return $this;
    }
}

class S_BitShift extends S_InfixRight {
    // e.g. +>, +<
    var $type = SymbolType::BITSHIFT;
    var $bindingPower = 45;
}

class S_ValGate extends S_InfixRight {
    // e.g. &&:, ||:
    var $type = SymbolType::VALGATE;
    var $bindingPower = 41;
}

class S_Compare extends S_InfixRight {
    // e.g. !=, ==
    var $bindingPower = 40;
}

class S_Bitwise extends S_InfixRight {
    // e.g. +&, +|
    var $type = SymbolType::BITWISE;
    var $bindingPower = 30;
}

class S_Logic extends S_InfixRight {
    // e.g. ||, &&
    var $bindingPower = 20;
}

class S_Assign extends S_InfixRight {
    // =, +=, etc.
    var $type = SymbolType::ASSIGN;
    var $bindingPower = 10;
    var $isAssignment = true;
}
