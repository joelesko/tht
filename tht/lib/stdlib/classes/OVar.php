<?php

namespace o;

class OVar extends OClass implements \JsonSerializable {

    public $val = null;
    protected $allowAutoGetSet = false;

    public function jsonSerialize():mixed {
        return $this->val;
    }

    function u_z_to_print_string() {
        return $this->val;
    }

    public function u_to_string() {
        return $this->val;
    }

    public function __toString() {
        return $this->val;
    }

    public function u_z_clone() {
        return $this->val;
    }
}

