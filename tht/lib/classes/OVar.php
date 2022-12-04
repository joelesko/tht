<?php

namespace o;

class OVar extends OClass implements \JsonSerializable {

    public $val = null;

    public function jsonSerialize():mixed {
        return $this->val;
    }

    public function u_to_string() {
        return $this->jsonSerialize();
    }

    public function __toString() {
        return $this->val;
    }
}

