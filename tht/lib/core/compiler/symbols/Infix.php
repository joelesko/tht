<?php

namespace o;

class S_Infix extends Symbol {

    var $bindingPower = 80;
    var $type = SymbolType::INFIX;

    function asInner ($p, $left) {

        $infixValue = $p->symbol->getValue();

        if ($infixValue == '=>') {
            $p->error('Invalid operator: `=>` Try: `>=` (greater or equal)');
        }

        $this->space(' + ', 'infix');
        $p->next();

        if ($p->symbol->isNewline()) {
            $v = $p->symbol->getValue();
            $p->error("Unexpected newline.  Try: Put `$infixValue` on next line to continue statement.");
        }

        $right = $p->parseExpression($this->bindingPower);
        if (!$right) {
            $p->error('Missing right operand.');
        }

        $this->setKids([$left, $right]);

        return $this;
    }
}

// ~
class S_Concat extends S_Infix {
    var $bindingPower = 50;
    var $type = SymbolType::OPERATOR;
}

// +, -, etc.
class S_Add extends S_Infix {
    var $bindingPower = 51;

    // Unary + and -
    function asLeft($p) {
        $this->space('*!x', true);
        $p->next();
        $this->updateType(SymbolType::PREFIX);
        $this->setKids([$p->parseExpression(70)]);
        return $this;
    }
}

// *, /, etc.
class S_Multiply extends S_Infix {
    var $bindingPower = 52;
}

// **, etc.
class S_Power extends S_Infix {
    var $bindingPower = 53;
}

