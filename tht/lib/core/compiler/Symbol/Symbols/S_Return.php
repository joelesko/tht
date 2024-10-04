<?php

namespace o;

class S_Return extends S_Command {

    var $allowAsMapKey = true;

    // e.g. return 1;
    function asStatement($p) {

        if ($p->functionDepth == 0) {
            $p->error('`return` not allowed outside of a function.');
        }

        $p->next();
        if (!$p->symbol->isNewline()) {
            $this->space('*return ');
            $p->expressionDepth += 1; // prevent assignment
            $sReturnVal = $p->parseExpression(0, 'noOuterParens');
            $this->addKid($sReturnVal);
        }

        $p->loopBreaks[count($p->loopBreaks) - 1] = true;

        // Don't check for orphan, to support a common debugging pattern of returning early

        return $this;
    }
}
