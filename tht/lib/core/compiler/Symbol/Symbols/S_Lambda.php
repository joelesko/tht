<?php

namespace o;

class S_Lambda extends S_Statement {

    use ClosureVarParser;

    var $isExpression = true;

    var $type = SymbolType::OPERATOR;

    function asLeft($p) {
        $p->next();
        $this->space('*xx');

        $p->expressionDepth += 1; // prevent assignment
        $p->lambdaDepth += 1;

        $p->now('{', 'lambda.open')->space('x{ ')->next();
        $p->skipNewline();
        $this->addKid($p->parseExpression(0));

        // Allow outer vars to be used inside expression
        // All this is similar to S_Function
        $outerScopeVars = $p->validator->getAllInScope();
        $p->validator->newFunctionScope();
        $p->validator->newScope();
        $closureVars = $this->parseClosureVars($p, $outerScopeVars);
        if ($closureVars) {
            $this->addKid($p->makeAstList(AstList::ARGS, $closureVars));
        }

        $p->skipNewline();
        $p->now('}', 'lambda.close')->space(' }N')->next();


        $p->validator->popScope();  // function
        $p->validator->popFunctionScope();

        $p->lambdaDepth -= 1;

        return $this;
    }
}
