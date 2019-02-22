<?php

namespace o;

class S_OpenBrace extends Symbol {

    var $type = SymbolType::SEQUENCE;

    // Map Literal { ... }
    function asLeft($p) {

        $p->next();

        $pairs = [];
        $hasKey = [];
        $sep = ',';

        if ($p->symbol->isValue("}")) {
            $this->space('*{N');
        }
        else {
            $this->space('*{ ');
        }

        // Collect "key: value" pairs
        while (true) {

            if ($p->symbol->isValue("}")) { break; }

            // key
            $key = $p->symbol;
            $sKey = $key->getValue();
            if (isset($hasKey[$sKey])) {
                $p->error("Duplicate key: `$sKey`");
            }
            $key->updateType(SymbolType::MAP_KEY);
            $hasKey[$sKey] = true;
            $p->next();

            // colon
            $p->now(':', 'Map key')->space('x: ', true)->next();

            // value
            $val = $p->parseExpression(0);
            $pair = $p->makeSymbol(SymbolType::PAIR, $key->getValue(), SymbolType::PAIR);
            $pair->addKid($val);
            $pairs []= $pair;

            // comma
            if (!$p->symbol->isValue($sep)) { break; }
            $p->space('x, ');
            $sSep = $p->symbol;
            $p->next();
        }

        if (count($pairs) > 0) {  $p->space(' }*');  }

        $p->now('}', 'Map - Missed a comma?')->next();
        $this->setKids($pairs);
        $this->value = SequenceType::MAP;
        return $this;
    }
}
