<?php

namespace o;

class S_Separator extends Symbol {
    var $kids = 0;
    var $type = SymbolType::SEPARATOR;
    function asLeft($p) {
        $p->next();
        return null;
    }
}

class S_End extends S_Separator {
    var $type = SymbolType::END;
}
