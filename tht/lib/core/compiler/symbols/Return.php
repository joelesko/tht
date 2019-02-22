<?php

namespace o;

class S_Return extends S_Command {

    // e.g. return 1;
    function asStatement ($p) {
        $p->next();
        if (!$p->symbol->isValue(';')) {
            $this->space('*returnS', true);
            $p->expressionDepth += 1; // prevent assignment
            $this->addKid($p->parseExpression(0));
        }

        // Don't check for orphan, to support a common debugging pattern of returning early

        return $this;
    }
}
