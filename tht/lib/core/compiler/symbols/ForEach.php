<?php

namespace o;

class S_ForEach extends S_Statement {
    var $type = SymbolType::OPERATOR;

    // for (...) { ... }
    function asStatement ($p) {

        $this->space('*foreachS');

        $p->expressionDepth += 1; // prevent assignment

        $sFor = $p->symbol;
        $p->next();

        // optional parens
        $hasParen = false;
        if ($p->symbol->isValue('(')) {
            $hasParen = true;
            $p->next();
        }

        // iterator
        $this->addKid($p->parseExpression(0));

        $p->now('as', 'foreach.as');
        $p->validator->newScope();
        $p->next();

        // Item variable. foreach ($list as $item) { ... }
        if ($p->symbol->type !== SymbolType::USER_VAR) {
            $p->error('Expected a list variable.  Ex: `foreach $list as $item { ... }`');
        }

        $p->validator->defineVar($p->symbol, true);
        $this->addKid($p->symbol);
        $p->next();

        // $key:$value alias.  foreach ($map as $k:$v) { ... }
        if ($p->symbol->isValue(',')) {
            $p->space('x,S', true)->next();
            if ($p->symbol->type !== SymbolType::USER_VAR) {
                $p->error('Expected a key/value pair.  Ex: `foreach $users as $userName, $age { ... }`');
            }
            $p->validator->defineVar($p->symbol, true);
            $this->addKid($p->symbol);
            $p->next();
        }

        // Closing paren
        if ($hasParen) {
            if (!$p->symbol->isValue(')')) {
                $p->error('Expected closing paren `)`');
            }
            $p->next();
        }

        $p->breakableDepth += 1;
        $this->addKid($p->parseBlock(true));
        $p->breakableDepth -= 1;

        // Make sure next symbol is out of this scope
        $p->validator->popScope(); // block
        $p->validator->popScope(); // foreach
        $p->next();

        return $this;
    }
}
