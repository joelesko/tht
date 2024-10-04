<?php

namespace o;

class S_Literal extends Symbol {

    function asLeft($p) {

        $p->next();

        return $this;
    }
}

// TODO: Name and Var are probably not actually "literals".
class S_Name extends S_Literal {
    var $allowAsMapKey = true;
}

class S_Var extends S_Literal {
}

class S_Constant extends S_Literal {

    var $type = SymbolType::CONSTANT;

    function asLeft($p) {

        $p->next();

        if ($this->isValue('@')) {
            if (!$p->inClass && !$p->anonFunctionDepth && !$p->lambdaDepth) {
                $p->error("Can't use `@` outside of an object.", $this->token);
            }
        }

        return $this;
    }
}



class S_Boolean extends S_Literal {
    var $type = SymbolType::BOOLEAN;
    var $preventYoda = true;
}

class S_Number extends S_Literal {
    var $type = SymbolType::NUMBER;
    var $allowAsMapKey = true;
    var $preventYoda = true;
}

class S_Null extends S_Literal {
    var $type = SymbolType::NULL;
    var $allowAsMapKey = true;
    var $preventYoda = true;
}

class S_Flag extends S_Literal {

    var $type = SymbolType::FLAG;
    var $preventYoda = true;

    function asLeft($p) {

        $p->next();

        // TODO: this should be moved upstream, like in the Tokenizer
        $p->validator->validateFlagFormat($this->getValue(), $this->token);

        return $this;
    }
}


// Strings

class S_String extends S_Literal {

    var $allowAsMapKey = true;
    var $preventYoda = true;

    // Don't let strings match internal parser values. e.g. else != 'else'
    function isValue($val) {
        return false;
    }

    var $type = SymbolType::STRING;
}

class S_TString extends S_String {
    var $type = SymbolType::T_STRING;
    var $allowAsMapKey = false;
}

class S_TemString extends S_String {
    var $type = SymbolType::TEM_STRING;
    var $allowAsMapKey = false;
}

class S_RxString extends S_String {
    var $type = SymbolType::RX_STRING;
    var $allowAsMapKey = false;
}

