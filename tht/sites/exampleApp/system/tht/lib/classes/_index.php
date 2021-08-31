<?php

namespace o;

class LibClasses {
    static public $files = [
        'OClass',
        'OStdModule',
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

        // Runtime::_initAutoboxObjects();
    }

    public static function isa ($cls) {
        return in_array($cls, LibClasses::$files);
    }
}

LibClasses::load();
