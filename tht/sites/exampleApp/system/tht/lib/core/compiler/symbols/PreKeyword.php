<?php

namespace o;

class S_PreKeyword extends S_Statement {

    var $type = SymbolType::PRE_KEYWORD;

    function asStatement($p) {

        $p->next();
        $this->space('*word ', true);

        $nextType = $p->symbol->type;
        if ($nextType !== 'PRE_KEYWORD' && $nextType !== 'NEW_FUN'  && $nextType !== 'NEW_TEMPLATE' && $nextType !== 'CLASS_FIELDS') {
            $p->error('Missing `fn` or `tm` keyword');
        }

        $this->addKid($p->parseStatement(0));

        return $this;
    }
}

