<?php

namespace o;

class S_Lambda extends S_Statement {

    var $type = SymbolType::OPERATOR;

    function asLeft ($p) {
        $p->next();
        $this->space('*xx');

        $p->expressionDepth += 1; // prevent assignment
        $p->lambdaDepth += 1;

        $p->now('{', 'lambda.open')->space('x{ ')->next();
        $p->skipNewline();
        $this->addKid($p->parseExpression(0));
        $p->skipNewline();
        $p->now('}', 'lambda.close')->space(' }N')->next();

        $p->lambdaDepth -= 1;

        return $this;
    }
}
