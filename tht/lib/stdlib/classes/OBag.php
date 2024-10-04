<?php

namespace o;

class OBag extends OVar implements \Iterator, \Countable {

    public $val = [];
    protected $default = null;
    protected $isReadOnly = false;
    protected $isList = false;

    // overrided
    function jsonSerialize():mixed {}

    function __toString() {
        return json_encode($this->val);
    }

    function u_z_to_sql_string() {
        $this->error("Can't convert a Map or List to a SQL insert value.");
    }

    function u_z_to_print_string() {

        $jsonTypeString = Tht::module('Json')->u_encode($this->val);
        $pstr = Tht::module('Json')->u_format($jsonTypeString)->u_render_string();
        $pstr = OClass::tokensToBareStrings($pstr);

        // Remove escaped quotes.  Ugly, but need to undo JSON serialized string.
        $pstr = preg_replace("/\\\\([\"\'])/", '$1', $pstr);

        return $pstr;
    }

    // override
    // function cloneArg() {

    //     $a = [];
    //     foreach ($this->val as $k => $el) {
    //         $a[$k] = Runtime::cloneArg($el);
    //     }

    //     return $this->isList ? OList::create($a) : OMap::create($a);
    // }

    function u_z_clone() {

        $a = [];
        foreach ($this->val as $k => $el) {
            $a[$k] = v($el)->u_z_clone();
        }

        return $this->isList ? OList::create($a) : OMap::create($a);
    }

    function __get($field) {

        $plainField = unu_($field);

        if (array_key_exists($plainField, $this->val) ) {
            return $this->val[$plainField];
        }
        else {
            $suggest = '';

            if (method_exists($this, u_($plainField))) {
                $suggest = '  Try: Call method `' . $plainField . '()`';
            }
            else if ($this->isList) {
                $suggest = $this->getSuggestedMethod($plainField);
                $this->error("Can't get field from List: `$plainField` $suggest");
            }
            else {
                $suggest = ErrorHandler::getFuzzySuggest($plainField, array_keys($this->val));
            }

            $this->error("Map key does not exist: `$plainField`  " . $suggest);
        }
    }

    function __set($field, $val) {

        $plainField = unu_($field);
        $meth = 'u_set' . ucfirst($plainField);

        if ($this->isReadOnly) {
            $this->error("Can't modify read-only " . get_class($this) . '.');
        }
        else if (method_exists($this, $meth)) {
            return $this->$meth($val);
        }
        else if (array_key_exists($plainField, $this->val)) {
            $this->val[$plainField] = $val;
            return $this;
        }
        else {
            $suggest = ErrorHandler::getFuzzySuggest($plainField, array_keys($this->val));
            if (!$suggest) {
                $suggest = "Try: ";
            }
            else {
                $suggest .= "\n\nOr:  ";
            }
            $suggest .= "Add dynamic field with `\$map['$plainField']`";
            $this->error("Unknown field: `$plainField`  $suggest");
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

    function setReadOnly($isReadOnly) {

        $this->isReadOnly = $isReadOnly;
    }


    // Countable

    function count():int {

        return count($this->val);
    }

    // ArrayAccess iterface (e.g. $var[1])





    //// Iterator

    function rewind():void {
        reset($this->val);
    }

    function current():mixed {

        return current($this->val);
    }

    function next():void {

        next($this->val);
    }

    function key():mixed {

        // Used for `foreach $list as $i, $v {...}`
        if ($this->isList && ONE_INDEX) {
            return key($this->val) + 1;
        }

        return key($this->val);
    }

    function valid():bool {

        // if ($this->isList && ONE_INDEX) {
        //     $key -= 1;
        // }

        $key = key($this->val);

        return array_key_exists($key, $this->val);
    }



    //// Object

    public function u_to_boolean() {
        return count($this->val) > 0;
    }

    function u_length() {

        return count(array_values($this->val));
    }

    function u_is_empty() {

        $this->ARGS('', func_get_args());

        return count($this->val) === 0;
    }

    // function u_has($args) {

    //     return !is_null($this->u_get($args));
    // }

    // TODO: undocumented
    function u_get($args, $default=null) {

        $this->ARGS('**', func_get_args());

        if (is_string($args)) {
            $args = explode('.', $args);
        }
        else if (v($args)->u_type() != 'list') {
            $args = [$args];
        }

        $obj = $this->getDeep($args);

        if ($obj === null) {
            if ($default === null) {
                // built-in default
                return NULL_NOTFOUND;
            } else {
                // passed-in default
                return $default;
            }
        }
        return $obj;
    }

    function getDeep($keys) {

        $obj = $this;
        foreach ($keys as $key) {
            if (!isset($obj[$key])) {
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

