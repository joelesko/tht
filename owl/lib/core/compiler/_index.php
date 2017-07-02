<?php

class OwlLib {

    static public $files = [
        'TemplateTransformers',
        'Tokenizer',
        'Parser',
        'Validator',
        'Emitter',
        'EmitterPHP'
    ];

    static public function load () {
        $libDir = dirname(__FILE__);
        foreach (OwlLib::$files as $lib) {
            require_once($libDir . '/' . $lib . '.php');
        }
    }
}

OwlLib::load();

