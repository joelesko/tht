<?php

namespace o;

class ThtLib {

    static private $files = [

        'CompilerConstants',

        '1_Tokenizer',
        '2_Parser',
        '3_Emitter',

        'Symbol/SymbolTable',
        'Symbol/Symbol',

        'Parser/Validator',
        'SourceAnalyzer/SourceMap',
        'SourceAnalyzer/SourceAnalyzer',

        'Emitter/EmitterPHP',

        'TemplateTransformers/TemplateTransformers',
    ];

    static public function load() {
        foreach (self::$files as $lib) {
            require_once(Tht::systemPath('lib/core/compiler/' . $lib . '.php'));
        }
    }
}

ThtLib::load();

