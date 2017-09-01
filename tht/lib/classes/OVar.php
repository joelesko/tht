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

    function getVal () {
        return $this->val;
    }

    function setVal ($v) {
        $this->val = $v;
    }

    function u_is_list () {
        return is_array($this->val) && \get_called_class() !== 'o\OMap';
    }

    function u_is_string () {
        return is_string($this->val);
    }

    function u_is_lock_string () {
        return false;
    }

    function u_is_number () {
        return is_numeric($this->val);
    }

    function u_is_flag () {
        return is_bool($this->val);
    }

    function u_is_function () {
        return is_callable($this->val);
    }

    function u_is_empty () {
        return $this->val ? false : true;
    }

    function u_is_regex () {
        return ORegex::isa($this->val);
    }

    function u_is_map () {
        return \get_called_class() === 'o\OMap';
    }

    function u_compare_to ($a) {
        if ($this->val === $a) {  return 0;  }
        return $this->val > $a ? 1 : -1;
    }
}

