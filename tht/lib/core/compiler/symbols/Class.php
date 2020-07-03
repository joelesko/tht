<?php

namespace o;

class S_Class extends S_Statement {
    var $type = SymbolType::NEW_CLASS;

    // e.g. class Foo { ... }
    function asStatement ($p) {

        if ($p->numClasses) {
            $p->error('Only one class allowed per file.');
        }
        $p->numClasses += 1;

        $p->next();

        // Class name
        $sName = $p->symbol;
        $this->space('*classS', true);
        if ($sName->token[TOKEN_TYPE] != TokenType::WORD) {
            $p->error("Expected a class name.  Ex: `class User { ... }`");
        }
        else {
            $sName->updateType(SymbolType::PACKAGE);
            $this->addKid($sName);
        }

        $p->next();

        // X these out, but need to keep for now because Emitter expects these symbols downstream
        $this->readParentPackage($p, 'XextendsX');
        $this->readParentPackage($p, 'XimplementsX');

        // class block
        $p->inClass = true;
        $this->addKid($p->parseBlock());
        $p->inClass = false;

        return $this;
    }

    function readParentPackage($p, $relation) {

        if ($p->symbol->isValue($relation)) {

            // TODO allow comma
            $p->next();
            $sRelationClassName = $p->symbol;
            if ($sRelationClassName->token[TOKEN_TYPE] !== TokenType::WORD) {
                $p->error("Expected a class name.  Ex: `class MyClass $relation OtherClass { ... }`");
            }
            $sRelationClassName->updateType(SymbolType::FULL_PACKAGE);
            $this->addKid($sRelationClassName);

            $p->next();
        }
        else {
            $this->addEmptyKid($p);
        }
    }

    function addEmptyKid($p) {
        $sNull = $p->makeSymbol(
            TokenType::WORD,
            '',
            SymbolType::FULL_PACKAGE
        );
        $this->addKid($sNull);
    }
}
