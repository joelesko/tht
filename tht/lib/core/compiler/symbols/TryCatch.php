<?php

namespace o;

class S_TryCatch extends S_Statement {
    var $type = SymbolType::TRY_CATCH;

    // try { ... } catch (e) { ... }
    function asStatement ($p) {

        $p->space(' tryS', true);

        $p->next();

        // try
        $this->addKid($p->parseBlock());

        // catch
        $p->now('catch')->space(' catchS', true)->next();

        // exception var
        $p->now('(', 'try/catch')->next();
        $p->validator->define($p->symbol);
        $this->addKid($p->symbol);
        $p->next();
        $p->now(')')->next();

        $this->addKid($p->parseBlock());

        // finally
        if ($p->symbol->isValue('finally')) {
            $p->space(' finally ', true);
            $p->next();
            $this->addKid($p->parseBlock());
        }

        return $this;
    }
}
