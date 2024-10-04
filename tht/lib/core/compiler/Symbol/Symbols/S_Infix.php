<?php

namespace o;

// Infix, but with a lower binding power
class S_Infix extends Symbol {

    var $type = SymbolType::INFIX;
    var $isAssignment = false;

    function asInner($p, $left) {

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

        if ($this->token[TOKEN_VALUE] == ':=') {

            if (!$p->ifDepth) {
                // TODO: Would like to use it in `ternary` and `match`, but scope is harder to deal with there.
                $p->error("If-Assign `:=` can only be used in an `if` statement.", $this->token);
            }
            if ($left->type !== SymbolType::USER_VAR) {
                $p->error('Must define new variable on left side of If-Assign: `:=`', $left->token);
            }
            if ($p->validator->isDefined('$' . $left->token[TOKEN_VALUE], 'fuzzy')) {
                $p->error('Variable already defined: `$' . $left->token[TOKEN_VALUE] . '`  Try: Define a new variable name.', $left->token);
            }

            $p->validator->defineVar($left, true);
        }
        else if ($this->isAssignment && $p->expressionDepth >= 2 && !$p->allowAssignmentExpression) {
            $suggest = $this->token[TOKEN_VALUE] == '=' ? "Try: `==` (equality)" : '';
            $p->error("Can't assign variable in an expression.  $suggest", $this->token);
        }

        if ($this->isAssignment) {
            $p->assignmentLeftSide = $left;
        }

        $right = $p->parseExpression($this->bindingPower - 1);
        if (!$right) {
            $p->error('Missing right-hand expression.');
        }

        $p->assignmentLeftSide = null;


        $this->addKids([$left, $right]);

        if ($this->isValue('=')) {
            $p->validator->defineVar($left, false);
        }

        // Don't allow "yoda" expressions. (e.g. `if 123 == $var`)
        if ($this->token[TOKEN_VALUE] == '==' || $this->token[TOKEN_VALUE] == '!=') {
            if ($left->preventYoda && !$right->preventYoda) {
                $leftValue = $left->token[TOKEN_VALUE];
                $eqValue = $this->token[TOKEN_VALUE];
                $p->error("Literal value should go on the right side of the expression.  Try: (example) `\$a $eqValue $leftValue` instead of `$leftValue $eqValue \$a`", $left->token);
            }
        }

        return $this;
    }
}

// =, +=, etc.
class S_Assign extends S_Infix {
    var $type = SymbolType::ASSIGN;
    var $bindingPower = 10;
    var $isAssignment = true;
}

// #:
// class S_ListFilter extends S_Infix {

//     var $type = SymbolType::LISTFILTER;
//     var $bindingPower = 11;

//     function asInner($p, $left) {

//         $p->lambdaDepth += 1;
//         parent::asInner($p, $left);
//         $p->lambdaDepth -= 1;

//         return $this;
//     }
// }

// ||, &&
class S_Logic extends S_Infix {
    var $bindingPower = 20;
}

// !=, ==, >=, etc.
class S_Compare extends S_Infix {
    var $bindingPower = 40;
}

// <=>
class S_CompareSpaceship extends S_Infix {
    var $bindingPower = 41;
}

// &&:, ||:, etc.
class S_ValGate extends S_Infix {
    var $type = SymbolType::VALGATE;
    var $bindingPower = 42;
}

// +>, +<, etc.
class S_BitShift extends S_Infix {
    var $type = SymbolType::BITSHIFT;
    var $bindingPower = 45;
}

// +&, +|, etc.
class S_Bitwise extends S_Infix {
    var $type = SymbolType::BITWISE;
    var $bindingPower = 46;
}








