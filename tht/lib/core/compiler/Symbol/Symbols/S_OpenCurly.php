<?php

namespace o;

class S_OpenCurly extends Symbol {

    var $type = SymbolType::AST_LIST;
    public $value = '';

    // Map Literal { ... }
    function asLeft($p) {

        $sOpenBrace = $p->symbol;
        $p->next();

        $pairs = [];
        $hasKey = [];

        if ($p->symbol->isValue("}")) {
            $this->space('*{N');
        }
        else {
            $this->space('*{ ');
        }

        // Collect "key: value" pairs
        $isMultiline = false;
        $pos = 0;
        while (true) {

            $isMultiline = $p->parseElementSeparator($pos, $isMultiline, '}');
            $pos += 1;

            if ($p->symbol->isValue("}")) {
                break;
            }

            // key
            $key = $p->symbol;
            $strKey = $key->getValue();

            if (isset($hasKey[$strKey])) {
                $p->error("Duplicate key: `$strKey`");
            }
            else if ($key->type == SymbolType::USER_VAR) {
                $p->error("Variable not allowed as Map key.");
            }
            else if (!$key->allowAsMapKey) {
                $p->error("Map key must be a string, number, or word.", $key->token);
            }


            $key->updateType(SymbolType::MAP_KEY);
            $hasKey[$strKey] = true;
            $p->next();

            $sVal = null;

            if ($p->symbol->isValue(':')) {
                // explicit value
                $p->now(':', 'map.colon')->space('x:S')->next();
                $sVal = $p->parseExpression(0);
            }
            else {
                // implicit value: e.g. { foo } -> { foo: 'foo' }
                $word = $key->getValue();
                if (!preg_match('/^[a-zA-Z0-9]+$/', $word)) {
                    $p->error("Invalid token in Map: `$word`  Try: Add quotes.", $key->token);
                }
                $sVal = $p->makeSymbol(SymbolType::STRING, $key->getValue(), SymbolType::STRING);
            }

            $pair = $p->makeSymbol(SymbolType::MAP_PAIR, $key->getValue(), SymbolType::MAP_PAIR);
            $pair->addKid($sVal);

            $pairs []= $pair;
        }

        if (count($pairs) == 0) {
            // Single line should have no inner padding: {}
            $p->space($isMultiline ? 'B}*' : 'x}*');
        }
        else {
            $sOpenBrace->space($isMultiline ? '*{B' : '*{S');
            $p->space($isMultiline ? 'B}*' : 'S}*');
        }

        $p->next();

        $this->addKids($pairs);
        $this->value = AstList::MAP;

        return $this;
    }
}
