<?php

namespace o;

class S_Var extends S_Statement {
    var $type = SymbolType::NEW_VAR;

    // e.g. let a = 1;
    function asStatement ($p) {

        $this->space('*letS');

        // var name
        $p->validator->setPaused(true);
        $p->next();
        $sNewVarName = $p->symbol;
        $this->addKid($sNewVarName);
        $p->validator->setPaused(false);

        if ($p->inClass && $p->blockDepth == 1) {
            //$this->updateType(SymbolType::NEW_OBJECT_VAR);
            $p->error("Class fields should be defined in the `new` method. e.g. `this.num = 123`");
        }

        $p->next();
        $p->now('=')->space(' = ')->next();

        $p->expressionDepth += 1;
        $this->addKid($p->parseExpression(0));
        $p->expressionDepth -= 1;

        // define after statement, to prevent e.g. 'let a = a + 1;'
        $p->validator->define($sNewVarName);

        $p->now(';')->next();

        return $this;
    }
}
