<?php

namespace o;

class OBag extends OVar implements \ArrayAccess, \Iterator {

    public $val = [];
    private $iKeys = [];
    private $iNumKeys = 0;
    private $iPos = 0;
    protected $default = null;
    protected $hasLockedKeys = false;
    protected $hasNumericKeys = false;

    function __get ($field) {
        $plainField = un_($field);  // remove 'u_'
        $meth = 'u_get' . ucfirst($plainField);
        if (method_exists($this, $meth)) {
            return $this->$meth();
        } else if (isset($this->val[$plainField]) ) {
            return $this->val[$plainField];
        } else {
            Owl::error('Invalid field: ' . $plainField);
        }
    }

    function __set ($field, $val) {
        $plainField = substr(v($field)->u_to_camel_case(), 2);  // remove 'u_'
        $meth = 'u_set' . ucfirst($plainField);
        if (method_exists($this, $meth)) {
            return $this->$meth($val);
        } else if (isset($this->val[$plainField]) ) {
            return $this->val[$plainField] = $val;
        } else {
            Owl::error('Invalid field: ' . $plainField);
        }
    }

    function setVal ($v) {
        $this->val = $v;
        $this->iKeys = array_keys($v);
        $this->iNumKeys = count($v);
    }

    function u_lock_keys ($isLocked) {
        $this->hasLockedKeys = $isLocked;
        return $this;
    }

    function u_default ($d) {
        $this->default = $d;
        return $this;
    }


    // ArrayAccess iterface

    function checkKey ($k) {
        if ($this->hasNumericKeys) {
            if ($k == '') {
                return;
            }
            if (!is_int($k)) {
                Owl::error("List keys must be numeric.  Saw '$k' instead.");
            }
            // else if ($k > $this->iNumKeys - 1) {
            //     Owl::error("List index ($k) out of bounds.  Size is: " . $this->iNumKeys, $this->val);
            // }
            // else if ($k < 0) {
            //     $endKey = $this->iNumKeys + $k;
            //     if ($endKey < 0) {
            //         Owl::error("Negative list index ($k) out of bounds.  Size is: " . $this->iNumKeys);
            //     }
            // }
        }
    }

    function offsetGet ($k) {
        $this->checkKey($k);
        if ($k < 0) { $k = count($this->val) + $k; }
        if (!isset($this->val[$k])) {
            return is_null($this->default) ? '' : $this->default;
        } else {
            return $this->val[$k];
        }
    }

    function offsetSet ($k, $v) {
        $this->checkKey($k);
        if ($k < 0) { $k = $this->iNumKeys + $k; }
        if (is_null($k)) {
            $this->val []= $v;
        } else {
            $this->val[$k] = $v;
        }

        //$this->iKeys = array_keys($this->val);
        //$this->iNumKeys = count($this->val);
    }

    function offsetExists ($k) {
        return isset($this->val[$k]);
    }

    function offsetUnset ($k) {
        unset($this->val[$k]);
    }

    //// Iterator

    function rewind () {
        $this->iPos = 0;
    }

    function current () {
        return $this->val[$this->key()];
    }

    function key () {
        return $this->iPos;
    }

    function next () {
        $this->iPos += 1;
    }

    function valid () {
        return isset($this->val[$this->iPos]);
    }


    //// Object

    function u_length() {
        return count(array_values($this->val));
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
                if (!is_null($this->default)) {
                    return $this->default;
                } else {
                    return '';
                }
                // $key = implode(', ', $args);
                // Owl::error("Missing value for key '$key'", [ 'elements' => $this->val ]);
            } else {
                return $default;
            }
        }
        return $obj;
    }

    // function u_slice ($keys) {
    //     $out = [];
    //     foreach ($keys as $k) {
    //         $out []= $this->getOne($k);
    //     }
    //     return $obj;
    // }

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

