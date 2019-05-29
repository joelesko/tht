<?php

namespace o;

class OVar extends OClass implements \JsonSerializable {

    public $val = null;

    public function jsonSerialize() {
        return $this->val;
    }

    static function isa ($s) {
        if (is_object($s)) {
            $called = get_called_class();
            if ($called === get_class($s) || $called === get_parent_class($s)) {
                return true;
            }
        }
        return false;
    }
}

