<?php

namespace o;

class S_Literal extends Symbol {
    var $kids = 0;
    function asLeft($p) {
        $p->next();

        if ($this->isValue('@')) {
            // allow use inside anon functions
            if (!$p->inClass && !$p->anonFunctionDepth && !$p->lambdaDepth) {
                $p->error("Can not use `@` outside of an object.", $this->token);
            }
        }

        return $this;
    }
}

class S_Name extends S_Literal {
}

class S_Var extends S_Literal {
}

class S_Constant extends S_Name {
    var $type = SymbolType::CONSTANT;
}

class S_Boolean extends S_Literal {
    var $type = SymbolType::BOOLEAN;
}

class S_Flag extends S_Literal {

    var $type = SymbolType::FLAG;

    function asLeft($p) {

        $p->next();

        $p->validator->validateFlagFormat($this->getValue(), $this->token);

        return $this;
    }
}
