<?php

namespace o;

class OBag extends OVar implements \ArrayAccess, \Iterator {

    public $val = [];
    protected $default = null;
    protected $hasLockedKeys = false;
    protected $hasNumericKeys = false;

    function __get ($field) {

        $plainField = unu_($field);
        $meth = 'u_get' . ucfirst($plainField);

        if (method_exists($this, $meth)) {
            return $this->$meth();
        } else if (isset($this->val[$plainField]) ) {
            return $this->val[$plainField];
        } else {
            $tip = $plainField == 'length' ? "  Try: `length()`" : '';
            Tht::error("Invalid field: `$plainField`" . $tip);
        }
    }

    function __set ($field, $val) {

        $plainField = unu_($field);
        $meth = 'u_set' . ucfirst($plainField);

        if (method_exists($this, $meth)) {
            return $this->$meth($val);
        } else if (isset($this->val[$plainField]) ) {
            return $this->val[$plainField] = $val;
        } else {
            Tht::error("Can't directly set new field: `$plainField`.  Tip: Use `[]` instead of dot `.`");
        }
    }

    function setVal ($v) {
        $this->val = $v;
    }


    // ArrayAccess iterface

    function checkKey ($k) {
        if ($this->hasNumericKeys) {
            if ($k == '') {
                return;
            }
            if (!is_int($k)) {
                Tht::error("List keys must be numeric.  Saw `$k` instead.");
            }
        }
    }

    function offsetGet ($k) {
        $this->checkKey($k);
        if ($k < 0) { $k = count($this->val) + $k; }
        if (!isset($this->val[$k])) {
            // soft get
            return is_null($this->default) ? '' : $this->default;
        } else {
            return $this->val[$k];
        }
    }

    function offsetSet ($k, $v) {
        $this->checkKey($k);

        // negative index counts from the end
        if ($k < 0 && $this->hasNumericKeys) {
            $k = count($this->val) + $k;
        }

        if (is_null($k)) {
            if ($this->hasNumericKeys) {
                $this->val []= $v;
            }
            else {
                Tht::error("Can't append item to Map.");
            }
        } else {
            $this->val[$k] = $v;
        }
    }

    function offsetExists ($k) {
        return isset($this->val[$k]);
    }

    function offsetUnset ($k) {
        unset($this->val[$k]);
    }

    //// Iterator

    function rewind () {
        return reset($this->val);
    }

    function current () {
        return current($this->val);
    }

    function key () {
        return key($this->val);
    }

    function next () {
        return next($this->val);
    }

    function valid () {
        return isset($this->val[key($this->val)]);
    }


    //// Object

    function u_length() {
        return count(array_values($this->val));
    }

    function u_lock_keys ($isLocked) {
        ARGS('f', func_get_args());
        $this->hasLockedKeys = $isLocked;
        return $this;
    }

    function u_default ($d) {
        $this->default = $d;
        return $this;
    }

    function u_get ($args, $default=null) {
        if (! \o\v($args)->u_is_list()) {
            $args = [ $args ];
        } else {
            $args = $args->val;
        }
        $obj = $this->getDeep($args);
        if ($obj === null) {
            if ($default === null) {
                // soft get
                return s_null($this->default) ? '' : $this->default;
            } else {
                return $default;
            }
        }
        return $obj;
    }

    function getDeep ($ary) {
        $obj = $this;
        foreach ($ary as $a) {
            $obj = \o\v($obj)->getOne($a);
            if ($obj == null) {
                return null;
            }
        }
        return $obj;
    }

    function getOne ($key) {
		if (! isset($this->val[$key])) {
            return null;
        }
        return $this->val[$key];
    }
}

