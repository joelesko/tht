<?php

namespace o;

class S_If extends S_Statement {
    var $type = SymbolType::OPERATOR;

    // if / else
    function asStatement ($p) {

        $this->space('*ifS');

        $p->next();

        $p->expressionDepth += 1;  // prevent assignment

        // conditional. if (...)
       // $p->now('(', 'if')->space('S(x', true)->next();
        $this->addKid($p->parseExpression(0));
       // $p->now(')', 'if')->space('x) ', true)->next();

        if ($p->symbol->isValue('=>')) {
            $p->error('Invalid operator: `=>` Try: `>=` (greater or equal)');
        }

        // block. { ... }
        $this->addKid($p->parseBlock());

        // else/if
        if ($p->symbol->isValue('else')) {
            $p->space(' else*');
            $p->next();
            if ($p->symbol->isValue('if')) {
                $p->space(' if ');
                $this->addKid($p->parseStatement());
            } else {
                $this->addKid($p->parseBlock());
            }
        }
        else {
            $nextWord = $p->symbol->token[TOKEN_VALUE];
            if (in_array($nextWord, ['elseif', 'elif', 'elsif'])) {
                $p->error("Unknown token: `$nextWord` Try: `else if`");
            }
        }


        return $this;
    }
}
