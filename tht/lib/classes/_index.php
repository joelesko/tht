<?php

namespace o;

class LibClasses {
    static public $files = [
        'OStdModule',
        'OClass',
        'OVar',
        'OBag',
        'OList',
        'OMap',
        'ONumber',
        'ORegex',
        'OString',
        'OBoolean',
        'OFunction',
        'OTypeString',
        'OTypeStrings',
        'ONothing',
        'OModule',
        'OTemplate',
        'OUrl',
        'OUrlQuery',
        'OPassword',
    ];

    static public function load () {

        foreach (LibClasses::$files as $lib) {
            require_once($lib . '.php');
        }

        Runtime::_initSingletons();
    }
}

LibClasses::load();
