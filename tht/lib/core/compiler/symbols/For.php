<?php

namespace o;

class S_For extends S_Statement {
    var $type = SymbolType::OPERATOR;

    // for (...) { ... }
    function asStatement ($p) {

        $this->space('*forS');

        $p->expressionDepth += 1; // prevent assignment

        $sFor = $p->symbol;
        $p->next();

        // Forever block. for { ... }
        if ($p->symbol->isValue('{')) {
            $p->foreverBreaks []= false;
            $this->addKid($p->parseBlock());
            $hasBreak = array_pop($p->foreverBreaks);
            if (!$hasBreak) {
                $p->error("Infinite `for` loop needs a `break` or `return` statement.", $sFor->token);
            }
            return $this;
        }

        $p->validator->newScope();

        $p->now('(')->space(' (x', true)->next();

        if ($p->symbol->isValue('let')) {
            $p->error("Unexpected `let`.  Try: `for (item in items) { ... }`");
        }

        // Temp variable. for (_temp_ in list) { ... }
        if ($p->symbol->type !== SymbolType::USER_VAR) {
            $p->error('Expected a list variable.  Ex: `for (item in items) { ... }`');
        }
        $p->validator->define($p->symbol);
        $this->addKid($p->symbol);
        $p->next();

        // key:value alias.  for (_k:v_ in map) { ... }
        if ($p->symbol->isValue(':')) {
            $p->space('x:x', true)->next();
            if ($p->symbol->type !== SymbolType::USER_VAR) {
                $p->error('Expected a key:value pair.  Ex: `for (userName:age in users) { ... }`');
            }
            $p->validator->define($p->symbol);
            $this->addKid($p->symbol);
            $p->next();
        }

        $p->now('in', 'for/in')->next();


        // Iterator.  for (a in _iterator_) { ... }
        $this->addKid($p->parseExpression(0));

        $p->now(')')->space('x) ', true)->next();

        $this->addKid($p->parseBlock());

        $p->validator->popScope();

        return $this;
    }
}
