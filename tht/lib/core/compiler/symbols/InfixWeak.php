<?php

namespace o;

// Infix, but with a lower binding power
class S_InfixWeak extends Symbol {
    var $type = SymbolType::INFIX;
    var $isAssignment = false;

    function asInner ($p, $left) {
        $p->next();
        if ($this->isAssignment && $p->expressionDepth >= 2 && !$p->allowAssignmentExpression) {
            $tip = $this->token[TOKEN_VALUE] == '=' ? "Did you mean `==`?" : '';
            $p->error("Assignment can not be used as an expression.  $tip", $this->token);
        }
        $this->space(' = ');
        $this->setKids([$left, $p->parseExpression($this->bindingPower - 1)]);

        if ($this->token[TOKEN_VALUE] == '=') {
            $p->validator->defineVar($left);
        }

        return $this;
    }
}

// =, +=, etc.
class S_Assign extends S_InfixWeak {
    var $type = SymbolType::ASSIGN;
    var $bindingPower = 10;
    var $isAssignment = true;
}

// e.g. ||, &&
class S_Logic extends S_InfixWeak {
    var $bindingPower = 20;
}

// e.g. +&, +|
class S_Bitwise extends S_InfixWeak {
    var $type = SymbolType::BITWISE;
    var $bindingPower = 30;
}

// e.g. !=, ==
class S_Compare extends S_InfixWeak {
    var $bindingPower = 40;
}

// e.g. &&:, ||:
class S_ValGate extends S_InfixWeak {
    var $type = SymbolType::VALGATE;
    var $bindingPower = 41;
}

// e.g. +>, +<
class S_BitShift extends S_InfixWeak {
    var $type = SymbolType::BITSHIFT;
    var $bindingPower = 45;
}








