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

        if ($p->symbol->isNewline()) {
            $infixValue = $this->getValue();
            $p->error("Unexpected newline.  Try: Put `$infixValue` on next line to continue statement.");
        }

        $right = $p->parseExpression($this->bindingPower - 1);
        if (!$right) {
            $p->error('Missing right operand.');
        }

        $this->setKids([$left, $right]);

        if ($this->isValue('=')) {
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

// #:
class S_ListFilter extends S_InfixWeak {

    var $type = SymbolType::LISTFILTER;
    var $bindingPower = 11;

    function asInner ($p, $left) {

        $p->lambdaDepth += 1;
        parent::asInner($p, $left);
        $p->lambdaDepth -= 1;

        return $this;
    }
}

// ||, &&
class S_Logic extends S_InfixWeak {
    var $bindingPower = 20;
}

// !=, ==, >=, etc.
class S_Compare extends S_InfixWeak {
    var $bindingPower = 40;
}

// <=>
class S_CompareSpaceship extends S_InfixWeak {
    var $bindingPower = 41;
}

// &&:, ||:, etc.
class S_ValGate extends S_InfixWeak {
    var $type = SymbolType::VALGATE;
    var $bindingPower = 42;
}

// +>, +<, etc.
class S_BitShift extends S_InfixWeak {
    var $type = SymbolType::BITSHIFT;
    var $bindingPower = 45;
}

// +&, +|, etc.
class S_Bitwise extends S_InfixWeak {
    var $type = SymbolType::BITWISE;
    var $bindingPower = 46;
}








