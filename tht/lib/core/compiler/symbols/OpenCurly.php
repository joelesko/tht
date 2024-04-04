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
        $isMultiline = -1;
        $pos = 0;
        while (true) {

            $isMultiline = $p->parseElementSeparator($pos, $isMultiline, $sOpenBrace);
            $pos += 1;

            if ($p->symbol->isValue("}")) {
                break;
            }

            // key
            $key = $p->symbol;
            $sKey = $key->getValue();
            if (isset($hasKey[$sKey])) {
                $p->error("Duplicate key: `$sKey`");
            }
            else if ($key->type == SymbolType::USER_VAR) {
                $p->error("Variable not allowed as Map key.");
            }

            $key->updateType(SymbolType::MAP_KEY);
            $hasKey[$sKey] = true;
            $p->next();

            $sVal = '';

            if ($p->symbol->isValue(':')) {
                // explicit value
                $p->now(':', 'map.colon')->space('x:S', true)->next();
                $sVal = $p->parseExpression(0);
            }
            else {
                // implicit value: same as key (for set/enum)
                $sVal = $p->makeSymbol(SymbolType::STRING, $key->getValue(), SymbolType::STRING);
            }

            $pair = $p->makeSymbol(SymbolType::PAIR, $key->getValue(), SymbolType::PAIR);
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

        $this->setKids($pairs);
        $this->value = AstList::MAP;

        return $this;
    }
}
