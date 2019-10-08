<?php

namespace o;

class S_Literal extends Symbol {
    var $kids = 0;
    function asLeft($p) {
        $p->next();
        if ($this->isValue('@') || $this->isValue('this')) {
            if (!$p->inClass) {
                $tok = $this->isValue('@') ? '@' : '\$this';
                $p->error('`' . $tok . '` must be used inside of a class.', $this->token);
            }
        }
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
