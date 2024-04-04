<?php

namespace o;

// Infix, but with a lower binding power
class S_InfixWeak extends Symbol {

    var $type = SymbolType::INFIX;
    var $isAssignment = false;

    function asInner ($p, $left) {

        $this->space(' = ', 'infix');

        if ($this->hasNewlineAfter()) {
            $infixValue = $this->getValue();
            if ($this->isAssignment) {
                // Don't alow dangling assignment
                $this->space(' =S');
            }
            else {
                $p->error("Unexpected newline.  Try: Put `$infixValue` on next line to continue statement.");
            }
        }

        $p->next();

        if ($this->isAssignment && $p->expressionDepth >= 2 && !$p->allowAssignmentExpression) {
            $tip = $this->token[TOKEN_VALUE] == '=' ? "Did you mean `==`?" : '';
            $p->error("Assignment can not be used as an expression.  $tip", $this->token);
        }

        if ($this->isAssignment) {
            $p->assignmentLeftSide = $left;
        }

        $right = $p->parseExpression($this->bindingPower - 1);
        if (!$right) {
            $p->error('Missing right-hand expression.');
        }

        $p->assignmentLeftSide = null;


        $this->setKids([$left, $right]);

        if ($this->isValue('=')) {
            $p->validator->defineVar($left);
        }

        // Don't allow "yoda" expressions.
        if ($this->token[TOKEN_VALUE] == '==' || $this->token[TOKEN_VALUE] == '!=') {

            $leftClass = get_class($left);
            $leftIsLiteral = $leftClass == 'o\S_Literal' || $leftClass == 'o\S_Boolean';
            $rightClass = get_class($right);
            $rightIsLiteral = $rightClass == 'o\S_Literal' || $rightClass == 'o\S_Boolean';

            if ($leftIsLiteral && !$rightIsLiteral) {
                $p->error("Literal value should go on the right side of the expression. Try: (example) `\$a == 'foo'` instead of `'foo' == \$a`", $left->token);
            }
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








