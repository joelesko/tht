<?php

namespace o;

class S_Command extends S_Statement {
    var $type = SymbolType::COMMAND;

    // e.g. continue, break
    function asStatement ($p) {
        $sCommand = $p->symbol;
        $p->next();
        $this->checkForOrphan($p);

        if ($sCommand->isValue('break') || $sCommand->isValue('return')) {
            $p->foreverBreaks[count($p->foreverBreaks) - 1] = true;
        }

        return $this;
    }

    function checkForOrphan ($p) {
         $p->now(';')->next();
         if ($p->symbol->isValue("}")) {
             return;
         }
         if (!$p->symbol->isValue("}")) {
             $p->error("Unreachable statement after `" . $this->getValue() . "`.");
         }
    }
}
