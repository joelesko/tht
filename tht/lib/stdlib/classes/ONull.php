<?php

namespace o;

class ONull extends OVar {

    protected $type = 'null';

    function __toString() {
        return 'null';
    }

    public function jsonSerialize():mixed {
        return null;
    }

    function u_z_to_print_string() {
        return 'null';
    }

    // Casting

    function u_to_number() {
        return 0;
    }

    function u_to_string() {
        return 'null';
    }

    function u_to_boolean() {
        return false;
    }

    function __call($fnName, $args) {
        return $this->error("Can't call method on null object: `$fnName`");
    }

    function __get($fieldName) {
        return $this->error("Can't get field on null object: `$fieldName`");
    }

    function __set($fieldName, $val) {
        return $this->error("Can't set field on null object: `$fieldName`");
    }
}


