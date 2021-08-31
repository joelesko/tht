<?php

namespace o;

class S_TemplateString extends S_Statement {

    // Default text in template function.
    var $type = SymbolType::TMSTRING;

    function asStatement ($p) {
        $p->next();
        return $this;
    }
}
