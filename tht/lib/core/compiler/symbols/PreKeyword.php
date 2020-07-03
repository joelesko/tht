<?php

namespace o;

class S_PreKeyword extends S_Statement {
    var $type = SymbolType::PRE_KEYWORD;
    function asStatement($p) {
        $p->next();
        $this->space(' word ', true);

        $this->addKid($p->parseStatement(0));

        return $this;
    }
}
