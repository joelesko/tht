<?php

namespace o;

class S_TryCatch extends S_Statement {

    var $type = SymbolType::TRY_CATCH;

    var $allowAsMapKey = true;

    // try { ... } catch (e) { ... }
    function asStatement($p) {

        $p->space('*tryS');

        $p->next();

        // try
        $this->addKid($p->parseBlock());

        // catch

        $p->validator->newScope();
        $p->now('catch', 'try.catch')->space(' catchS')->next();

        $errorVar = $p->parseExpression(0, 'noOuterParen');
        $p->validator->defineVar($errorVar, true);
        $this->addKid($errorVar);

        $this->addKid($p->parseBlock(true));

        $p->validator->popScope(); // block
        $p->validator->popScope(); // catch
        $p->next();

        // finally
        if ($p->symbol->isValue('finally')) {
            $p->space(' finally ');
            $p->next();
            $this->addKid($p->parseBlock());
        }

        return $this;
    }
}
