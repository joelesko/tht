<?php

namespace o;

class OVar extends OClass implements \JsonSerializable {

    public $val = null;

    public function jsonSerialize() {
        return $this->val;
    }
}

