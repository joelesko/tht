<?php

namespace o;

class LibClasses {
    static public $files = [
        'OClass',
        'OVar',
        'OBag',
        'OBare',
        'OList',
        'OMap',
        'ONumber',
        'ORegex',
        'OString',
        'OFlag',
        'OFunction',
        'OTagString',
        'ONothing',
        'OModule',
        'OTemplate',
    ];

    static public function load () {

        foreach (LibClasses::$files as $lib) {
            require_once($lib . '.php');
        }

        Runtime::_initSingletons();
    }
}

LibClasses::load();
