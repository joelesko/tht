<?php

namespace o;

class S_TemplateExpr extends S_Statement {
    var $type = SymbolType::TEMPLATE_EXPR;

    // {{ expr }}
    function asStatement ($p) {
       // $p->space('*{{ ')->next();
        $p->next();
        $this->addKid($p->symbol);
        $p->next(); // skip '+' dividing template context and expression
        $p->next();
        $this->addKid($p->parseExpression(0));
     //   $p->space(' }}*');
        $p->now(Glyph::TEMPLATE_EXPR_END, 'template.expr.end')->next();
        return $this;
    }
}
