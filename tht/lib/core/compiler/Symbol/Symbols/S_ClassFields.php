<?php

namespace o;

class S_ClassFields extends S_Statement {

    var $type = SymbolType::CLASS_FIELDS;

    // e.g. fields { someKey: 123 }
    function asStatement($p) {

        if (!$p->inClass) {
            $p->error('Keyword `fields` should only appear within a class.');
        }

        $p->space('*fields ');
        $p->next();
        $p->now('{');

        $sMap = $p->parseExpression(0);

        if ($sMap->value !== AstList::MAP) {
            Tht::error('Keyword `fields` must be followed by a Map.');
        }

        $this->addKid($sMap);

        return $this;
    }
}
