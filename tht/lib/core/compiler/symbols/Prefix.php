<?php

namespace o;

class S_Prefix extends Symbol {

    var $type = SymbolType::PREFIX;

    function asLeft($p) {
        $p->next();
        $this->space('*!x', true);
        $this->setKids([$p->parseExpression(70)]);
        return $this;
    }
}
