<?php

namespace o;

class S_ShortPrint extends S_Command {

    // e.g. >> 'some message'
    function asStatement ($p) {

        if (!$this->hasNewlineBefore()) {
            ErrorHandler::setHelpLink('/language-tour/intermediate/shortcuts#print', 'Print Shortcut');
            $p->error('Print shortcut `>>` can only be used at the beginning of a line.  Try: `+>` (bit-shift right)');
        }

        $p->next();

        $this->space('B>> ');

        $p->expressionDepth += 1; // prevent assignment
        $sReturnVal = $p->parseExpression(0, 'noOuterParens');
        $this->addKid($sReturnVal);

        return $this;
    }
}
