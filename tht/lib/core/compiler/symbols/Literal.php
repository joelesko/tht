<?php

namespace o;

class S_Literal extends Symbol {
    var $kids = 0;
    function asLeft($p) {
        $p->next();
        return $this;
    }
}

class S_Name extends S_Literal {
}

class S_Constant extends S_Name {
    var $type = SymbolType::CONSTANT;
}

class S_Flag extends S_Literal {
    var $type = SymbolType::FLAG;
}
