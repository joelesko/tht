<?php

namespace o;

class S_ClassPlugin extends S_Statement {

    var $type = SymbolType::CLASS_PLUGIN;

    // e.g. plugin SomeClass, OtherClass
    function asStatement ($p) {

        if (!$p->inClass) {
            $p->error('Keyword `' . $p->symbol->getValue() . '` should only appear within a class.');
        }

        $p->space('*pluginS', true);
        $p->next();

        // Plugin list
        $plugins = [];
        while (true) {
            // $sClassName = $p->symbol;
            // if (! $sClassName->token[TOKEN_TYPE] === TokenType::WORD) {
            //     $p->error("Expected a class name.");
            // }
            // $sClassName->updateType(SymbolType::PACKAGE);
            // $plugins []= $sClassName;

            $plugins []= $p->parseExpression(0);

           // $p->next();
            if (!$p->symbol->isValue(",")) { break; }
            $p->space('x, ')->next();
        }
        $sPlugins = $p->makeAstList(AstList::FLAT, $plugins);
        $this->addKid($sPlugins);

        return $this;
    }
}
