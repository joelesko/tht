<?php

namespace o;

class OBag extends OVar implements \ArrayAccess, \Iterator, \Countable {

    public $val = [];
    protected $default = null;
    protected $isReadOnly = false;
    protected $hasNumericKeys = false;

    function __toString() {
        return json_encode($this->val);
    }

    function u_z_sql_string() {
        $this->error('Can not convert a Map or List to a SQL insert value.');
    }

    function __get ($field) {

        $plainField = unu_($field);
        $meth = 'u_get' . ucfirst($plainField);

        if (method_exists($this, $meth)) {
            return $this->$meth();
        }
        else if (isset($this->val[$plainField]) ) {
            return $this->val[$plainField];
        }
        else {
            $tip = $plainField == 'length' ? "  Try: `length()`" : '';
            foreach (array_keys($this->val) as $key) {
                if (strtolower($key) == strtolower($plainField)) {
                    $tip = "Try: `$key`";
                }
            }
            // ErrorHandler::addObjectDetails('Fields', array_keys($this->val));
            $this->error("Field does not exist: `$plainField`" . $tip);
        }
    }

    function __set ($field, $val) {

        $plainField = unu_($field);
        $meth = 'u_set' . ucfirst($plainField);

        if ($this->isReadOnly) {
            $this->error("Can not modify read-only " . get_class($this) . '.');
        }
        else if (method_exists($this, $meth)) {
            return $this->$meth($val);
        }
        else if (isset($this->val[$plainField]) ) {
            $this->val[$plainField] = $val;
            return $this;
        }
        else {
            $this->error("Unknown field `$plainField`. Try: Check spelling, or add field with e.g. `\$map['fieldName']`");
        }
    }

    function __call($fn, $args) {

        $plainFn = unu_($fn);

        if (isset($this->val[$plainFn]) && $this->val[$plainFn] instanceof \Closure) {
            // Treat closure as method
            return $this->val[$plainFn]->call($this, ...$args);
        }
        else {
            return parent::__call($fn, $args);
        }
    }

    function setVal ($v) {

        $this->val = $v;
    }

    function setReadOnly($isReadOnly) {

        $this->isReadOnly = $isReadOnly;
    }


    // Countable

    function count() {

        return count($this->val);
    }

    // ArrayAccess iterface (e.g. $var[1])

    function checkNumericKey ($k) {

        if ($this->hasNumericKeys) {

            if ($k === null) {
                return $k; // 'push' shortcut  `#=`
            }
            else if (!is_int($k)) {
                $this->error("List index must be numeric.  Saw `$k` instead.");
            }
            else if ($k < 0) {
                // Count negative indexes from the end.
                return count($this->val) + $k;
            }
            else if ($k == 0) {
                $this->error('Index `0` is not valid.  The first item has an index of `1`.');
            }
            else {
                return $k - ONE_INDEX;
            }
        }

        return $k;
    }

    function offsetGet ($k) {

        $k = $this->checkNumericKey($k);

        if (!isset($this->val[$k])) {
            // soft get
            return $this->getDefault();
        }
        else {
            return $this->val[$k];
        }
    }

    function offsetSet ($ak, $v) {

        $k = $this->checkNumericKey($ak);

        if (is_null($k)) {
            if ($this->hasNumericKeys) {
                $this->val []= $v;
            }
            else {
                $this->error("Left side of `#=` must be a list. Got: map");
            }
        }
        else {
            if ($this->hasNumericKeys) {
                $lastIndex = count($this->val) - ONE_INDEX;
                if ($k > $lastIndex) {
                    $this->error("Index `$ak` is greater than the last index `$lastIndex` in List.");
                }
            }
            $this->val[$k] = $v;
        }
    }

    function offsetExists ($k) {

        $k = $this->checkNumericKey($k);

        return isset($this->val[$k]);
    }

    function offsetUnset ($k) {

        $k = $this->checkNumericKey($k);

        unset($this->val[$k]);
    }

    //// Iterator

    function rewind () {

        return reset($this->val);
    }

    function current () {

        return current($this->val);
    }

    function next () {

        return next($this->val);
    }

    function key () {

        // Used for `foreach $list as $i, $v {...}`
        if ($this->hasNumericKeys && ONE_INDEX) {
            return key($this->val) + 1;
        }

        return key($this->val);
    }

    function valid () {

        $key = key($this->val);

        // if ($this->hasNumericKeys && ONE_INDEX) {
        //     $key -= 1;
        // }

        return isset($this->val[$key]);
    }



    //// Object

    public function u_is_truthy() {
        return count($this->val) > 0;
    }

    function u_length() {

        return count(array_values($this->val));
    }

    function u_is_empty () {

        $this->ARGS('', func_get_args());

        return count($this->val) === 0;
    }

    function u_default ($d) {

        $this->ARGS('*', func_get_args());

        $this->default = $d;
        return $this;
    }

    function getDefault () {

        return is_null($this->default) ? '' : $this->default;
    }

    function u_has($args) {

        return !is_null($this->u_get($args));
    }

    function u_get ($args, $default=null) {

        $this->ARGS('**', func_get_args());

        if (v($args)->u_type() != 'list') {
            $args = [$args];
        }

        $obj = $this->getDeep($args);

        if ($obj === null) {
            if ($default === null) {
                // built-in default
                return $this->getDefault();
            } else {
                // passed-in default
                return $default;
            }
        }
        return $obj;
    }

    function getDeep ($keys) {

        $obj = $this;
        foreach ($keys as $key) {
            if (! isset($obj[$key])) {
                return null;
            }
            $obj = $obj[$key];
        }
        return $obj;
    }


    // UNIONS
    //-----------------------------------------

    // TODO: test/document for Maps

    function u_intersection() {

        $args = func_get_args();
        $args = $this->checkListArgs('intersection', $args);

        return OList::create(
            array_intersect($this->val, ...$args)
        );
    }

    function u_difference() {

        $args = func_get_args();
        $args = $this->checkListArgs('difference', $args);

        return OList::create(
            array_diff($this->val, ...$args)
        );
    }

    function u_union() {

        $args = func_get_args();
        $args = $this->checkListArgs('union', $args);

        $combined = array_merge($this->val, ...$args);

        return OList::create(
            array_unique($combined)
        );
    }

    function checkListArgs($fnName, $args) {
        $unwrapped = [];
        $thisClass = $this->bareClassName();
        foreach ($args as $i => $arg) {
            if ($arg->bareClassName() != $thisClass) {
                $this->error("Argument to function `$fnName` must be a $thisClass.");
            }
            $unwrapped []= unv($arg);
        }
        return $unwrapped;
    }

}

