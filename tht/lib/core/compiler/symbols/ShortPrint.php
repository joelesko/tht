<?php

namespace o;

class S_ShortPrint extends S_Command {

    // e.g. >> 'some message'
    function asStatement ($p) {

        $p->next();

        $this->space('B>> ', true);

        $p->expressionDepth += 1; // prevent assignment
        $sReturnVal = $p->parseExpression(0, 'noOuterParens');
        $this->addKid($sReturnVal);

        return $this;
    }
}
