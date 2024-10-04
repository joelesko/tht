<?php

namespace o;

class OFunction extends OVar {
    protected $type = 'function';

    function jsonSerialize():mixed {
        return $this->toObjectString();
    }

    public function __toString() {
        return $this->toObjectString();
    }
}

