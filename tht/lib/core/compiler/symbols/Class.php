<?php

namespace o;

class S_Class extends S_Statement {
    var $type = SymbolType::NEW_CLASS;

    // TODO: figure out how to allow 'class' as an accepted user variable name
    // function asLeft($p) {
    //     $this->updateType(SymbolType::USER_VAR);
    //     $p->next();
    //     return $this;
    // }

    // e.g. class Foo { ... }
    function asStatement ($p) {

        // qualifiers and class keyword
        // $quals = [];
        // while (true) {
        //     $this->space('*keywordS', true);
        //     $s = $p->symbol;
        //     $keyword = $s->token[TOKEN_VALUE];
        //     if (in_array($keyword, CompilerConstants::$QUALIFIER_KEYWORDS)) {
        //         $quals []= $keyword;
        //         $p->next();
        //     }
        //     else {
        //         break;
        //     }
        // }
        // $sQuals = $p->makeSymbol(
        //     TokenType::WORD,
        //     implode(' ', $quals),
        //     SymbolType::PACKAGE_QUALIFIER
        // );
        // $this->addKid($sQuals);

        $p->next();

        // Class name
        $sName = $p->symbol;
        if ($sName->token[TOKEN_TYPE] == TokenType::WORD) {
            $this->space('*classS', true);
            $sName->updateType(SymbolType::PACKAGE);
            $this->addKid($sName);
        }
        else {
            $p->error("Expected a class name.  Ex: `class User { ... }`");
        }

        $p->next();


        // if ($p->symbol->isValue('extends')) {

        //     $p->next();
        //     $sParentClassName = $p->symbol;
        //     if ($sParentClassName->token[TOKEN_TYPE] !== TokenType::WORD) {
        //         $p->error("Expected a parent class name.  Ex: `class MyClass extends MyParentClass { ... }`");
        //     }
        //     $sParentClassName->updateType(SymbolType::PACKAGE);
        //     $this->addKid($sParentClassName);

        //     $p->next();
        // }
        // else {
        //     $sNull = $p->makeSymbol(
        //         TokenType::WORD,
        //         '',
        //         SymbolType::PACKAGE
        //     );
        //     $this->addKid($sNull);
        // }

        $p->inClass = true;
        $this->addKid($p->parseBlock());
        $p->inClass = false;


        return $this;
    }
}
