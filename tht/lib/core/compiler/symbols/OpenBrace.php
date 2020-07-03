<?php

namespace o;

class S_OpenBrace extends Symbol {

    var $type = SymbolType::AST_LIST;

    // Map Literal { ... }
    function asLeft($p) {

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
        $hasNewline = false;
        while (true) {

            $hasNewline = $hasNewline | $p->skipNewline();
            if ($p->symbol->isValue("}")) {
                break;
            }
            $hasNewline = $hasNewline | $p->skipNewline();

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
                $p->now(':', 'map.colon')->space('x: ', true)->next();
                $sVal = $p->parseExpression(0);
            }
            else {
                // value is same as key (set/enum)
                $sVal = $p->makeSymbol(SymbolType::STRING, $key->getValue(), SymbolType::STRING);
            }

            $pair = $p->makeSymbol(SymbolType::PAIR, $key->getValue(), SymbolType::PAIR);
            $pair->addKid($sVal);
            $pairs []= $pair;

            $p->parseElementSeparator();

            // comma
            // if (!$p->symbol->isValue(',')) {
            //     if ($hasNewline && count($pairs) > 1) {
            //         ErrorHandler::addSubOrigin('formatChecker');
            //         $p->error('Please add a comma `,` after the key/value pair.');
            //     }
            //     break;
            // }
            // $p->space('x, ');
            // $p->next();
        }
        $p->skipNewline();

        if (count($pairs) > 0) {  $p->space(' }*');  }
        $p->now('}', 'map.close')->next();

        $this->setKids($pairs);
        $this->value = AstList::MAP;

        return $this;
    }
}
