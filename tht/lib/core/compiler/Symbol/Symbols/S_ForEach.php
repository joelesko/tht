<?php

namespace o;

class S_ForEach extends S_Statement {

    var $type = SymbolType::OPERATOR;

    var $allowAsMapKey = true;

    // for (...) { ... }
    function asStatement($p) {

        $this->space('*foreachS');

        $p->expressionDepth += 1; // prevent assignment

        $sFor = $p->symbol;
        $p->next();

        // catch outer parens
        $sOuterParen = $p->symbol->isValue('(') ? $p->symbol : null;
        if ($sOuterParen) { $p->next(); }

        // iterator
        $sIter = $p->parseExpression(0);
        $this->addKid($sIter);

        $p->now('as', 'foreach.as');
        $p->validator->newScope();
        $p->next();

        // Item variable. foreach ($list as $item) { ... }
        if ($p->symbol->type !== SymbolType::USER_VAR) {
            $p->error('Expected a list variable.  Ex: `foreach $list as $item { ... }`');
        }

        $p->validator->defineVar($p->symbol, true);
        $this->addKid($p->symbol);

        $peekToken = $p->peekNextToken();
        if ($peekToken[TOKEN_VALUE] == '=>') {
            $p->error('Unknown token: `=>`  Try: `foreach $map as $k/$v { ... }`', $peekToken);
        }


        $p->next();

        // `$key/$value` alias.  foreach ($map as $k/$v) { ... }
        if ($p->symbol->isValue('/')) {
            $p->space('x/x')->next();
            if ($p->symbol->type !== SymbolType::USER_VAR) {
                $p->error('Expected a key/value pair.  Ex: `foreach $users as $userName/$age { ... }`');
            }
            $p->validator->defineVar($p->symbol, true);
            $this->addKid($p->symbol);
            $p->next();
        }

        if ($p->symbol->isValue(')') && $sOuterParen) {
            $p->outerParenError($sOuterParen);
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
