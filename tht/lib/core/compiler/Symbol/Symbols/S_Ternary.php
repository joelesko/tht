<?php

namespace o;

class S_Ternary extends Symbol {
    var $type = SymbolType::TERNARY;
    var $bindingPower = 20;

    // e.g. test ? result1 : result2
    function asInner($p, $left) {

        $sQuestion = $p->symbol;

        $p->next();

        if ($p->inTernary) {
            $p->error("Nested ternary operator not allowed: `\$a ? \$b : \$c`  Try: `if/else`");
        }
        $p->inTernary = true;

        $this->addKid($left);
        $this->space(' ? ');

        $result1 = $p->symbol->token;
        $this->addKid($p->parseExpression(0));

        $p->now(':', 'ternary.colon')->space(' : ')->next();

        $result2 = $p->symbol->token;
        $this->addKid($p->parseExpression(0));

        $p->inTernary = false;

        // Teachable moment
        if ($result1[TOKEN_TYPE] == TokenType::WORD && $result2[TOKEN_TYPE] == TokenType::WORD) {
            $vals = $result1[TOKEN_VALUE] . '|' . $result2[TOKEN_VALUE];
            if ($vals == 'true|false') {
                $p->error('Unnecessary ternary. You can just use a standalone boolean expression.  Try: (example) `$a == $b` instead of `$a == $b ? true : false`', $sQuestion->token);
            }
            else if ($vals == 'false|true') {
                $p->error('Unnecessary ternary. You can just use a standalone boolean expression.  Try: (example) `$a != $b` instead of `$a == $b ? false : true`', $sQuestion->token);
            }
        }

        return $this;
    }
}
