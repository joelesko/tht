<?php

namespace o;

class S_Command extends S_Statement {

    var $bindingPower = 0;
    var $type = SymbolType::COMMAND;

    // e.g. continue, break
    function asStatement ($p) {
        $sCommand = $p->symbol;
        $p->next();

        if ($p->breakableDepth == 0) {
            $p->error('`' . $sCommand->getValue() . '` not allowed outside of a loop.', $this->token);
        }

        if ($sCommand->isValue('break')) {
            $p->loopBreaks[count($p->loopBreaks) - 1] = true;
        }

        return $this;
    }
}

