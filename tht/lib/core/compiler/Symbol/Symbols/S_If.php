<?php

namespace o;

class S_If extends S_Statement {

    var $type = SymbolType::OPERATOR;
    var $allowAsMapKey = true;

    // if / else
    function asStatement($p) {

        $this->space('*ifS');

        // Start scope here so that new vars assigned (:=) inside condition
        // are captured in the underlying block.
        $p->validator->newScope();

        $p->next();

        $p->expressionDepth += 1;  // prevent assignment
        $p->ifDepth += 1;

        // Condition. if ... {
        $sCondition = $p->parseExpression(0, 'noOuterParen');
        $this->addKid($sCondition);

        $p->ifDepth -= 1;

        // block. { ... }
        $this->addKid($p->parseBlock(false, true));

        // else/if
        if ($p->symbol->isValue('else')) {

            $p->space(' else*');
            $p->next();

            if ($p->symbol->isValue('if')) {
                // `else if`
                $p->space(' if ');
                $this->addKid($p->parseStatement());
            }
            else {
                // final `else`
                $this->addKid($p->parseBlock());
            }
        }
        else {
            Validator::validateUnsupportedKeyword($p->symbol->token, false);
        }

        return $this;
    }
}
