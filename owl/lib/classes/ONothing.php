<?php

namespace o;

class ONothing {
    private $fun = '';

    function __construct ($f='(unknown)') {
        $this->fun = $f;
    }

    function __toString () {
        return '';
    }

    function __call ($a, $b) {
        Owl::error("You have a Nothing value returned from: " . $this->fun);
    }
}
