<?php

namespace o;

class S_TemplateExpr extends S_Statement {
    var $type = SymbolType::TEMPLATE_EXPR;

    // {{ expr }}
    function asStatement($p) {

        $p->space('{{ ');

        $p->next();

        $this->addKid($p->symbol); // context string

        $p->next();
        $p->next(); // skip ','

        $this->addKid($p->symbol); // indent level

        $p->next();
        $p->next(); // skip ','

        $this->addKid($p->parseExpression(0));

        $p->space(' }}*');
        $p->now(Glyph::TEMPLATE_EXPR_END, 'template.expr.end')->next();

        return $this;
    }
}
