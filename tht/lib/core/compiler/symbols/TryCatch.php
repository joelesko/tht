<?php

namespace o;

class S_TryCatch extends S_Statement {
    var $type = SymbolType::TRY_CATCH;

    // try { ... } catch (e) { ... }
    function asStatement ($p) {

        $p->space('*tryS', true);

        $p->next();

        // try
        $this->addKid($p->parseBlock());

        // catch

        $p->validator->newScope();
        $p->now('catch', 'try.catch')->space(' catchS', true)->next();

        $errorVar = $p->parseExpression();
        $p->validator->defineVar($errorVar);
        $this->addKid($errorVar);

        $this->addKid($p->parseBlock(true));

        $p->validator->popScope(); // block
        $p->validator->popScope(); // catch
        $p->next();

        // finally
        if ($p->symbol->isValue('finally')) {
            $p->space(' finally ', true);
            $p->next();
            $this->addKid($p->parseBlock());
        }

        return $this;
    }
}
