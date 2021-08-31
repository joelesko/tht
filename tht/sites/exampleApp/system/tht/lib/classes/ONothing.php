<?php

namespace o;

class ONothing extends OClass {
    private $fun = '';

    static private $nothings = [];

    static function create($methodName) {
        if (!isset(self::$nothings[$methodName])) {
            self::$nothings[$methodName] = new ONothing ($methodName);
        }
        return self::$nothings[$methodName];
    }

    function __construct ($f='(unknown)') {
        $this->fun = $f;
    }

    function __toString () {
        return '';
    }

    function __call ($a, $b) {
        Tht::error("You have a `Nothing` object returned from: `" . $this->fun . "`");
    }
}
