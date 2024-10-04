<?php

namespace o;

class StdLibClasses {

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
        'ONull',
        'OFunction',
        'OTypeString',
        'OModule',
        'OTemplate',
        'OUrlQuery',
        'OPassword',
    ];

    static public $typeStrings = [
        'OCore',
        'OUrl',
        'OPath',
        'OPath/OFile',
        'OPath/ODir',
    ];

    static public function load() {

        foreach (self::$files as $lib) {
            require_once($lib . '.php');
        }

        foreach (self::$typeStrings as $ts) {
            require_once('OTypeString/' . $ts . '.php');
        }
    }

    public static function isa($cls) {
        return in_array($cls, self::$files);
    }
}

StdLibClasses::load();
