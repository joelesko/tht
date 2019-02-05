<?php

class ThtLib {

    static public $files = [
        'Constants',
        'TemplateTransformers',
        'Tokenizer',
        'SymbolTable',
        'Symbols',
        'Parser',
        'Validator',
        'Emitter',
        'EmitterPHP',
    ];

    static public function load () {
        $libDir = dirname(__FILE__);
        foreach (ThtLib::$files as $lib) {
            require_once($libDir . '/' . $lib . '.php');
        }
    }
}

ThtLib::load();

