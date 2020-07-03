<?php

namespace o;

class S_Lambda extends S_Statement {

    var $type = SymbolType::OPERATOR;

    // e.g. => $a == 1
    // function asLeft ($p) {
    //     $p->next();
    //     $this->space('*=>S', true);
    //     $p->expressionDepth += 1; // prevent assignment
    //     $this->addKid($p->parseExpression(0));

    //     return $this;
    // }

    function asLeft ($p) {
        $p->next();
        $this->space('*xx', true);

        $p->expressionDepth += 1; // prevent assignment
        $p->lambdaDepth += 1;

        $p->now('{', 'lambda.open')->space('x{S', true)->next();
        $this->addKid($p->parseExpression(0));
        $p->now('}', 'lambda.close')->space('S}N', true)->next();

        $p->lambdaDepth -= 1;

        return $this;
    }
}
