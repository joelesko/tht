<?php

namespace o;

class S_TemplateString extends S_Statement {

    // Default text in template function.
    var $type = SymbolType::TEM_STRING;

    function asStatement($p) {
        $p->next();
        return $this;
    }
}
