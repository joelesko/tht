<?php

namespace o;

class S_Loop extends S_Statement {

    var $type = SymbolType::OPERATOR;

    var $allowAsMapKey = true;

    // loop { ... }
    function asStatement($p) {

        $this->space('*loopS');

        $sFor = $p->symbol;
        $p->next();

        $p->loopBreaks []= false;

        $p->breakableDepth += 1;
        $this->addKid($p->parseBlock());
        $p->breakableDepth -= 1;

        $hasBreak = array_pop($p->loopBreaks);
        if (!$hasBreak) {
            $p->error("`loop` needs a `break` or `return` statement.", $sFor->token);
        }
        return $this;
    }
}
