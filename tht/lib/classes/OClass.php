<?php

namespace o;

class OClass {

    private $_fieldsLocked = false;
    protected $u_state = null;

    function _init($args) {

        $this->u_state = OMap::create([]);

        if (method_exists($this, 'u_constructor')) {
            call_user_func_array([ $this, 'u_constructor' ], $args);
        }

        $this->_fieldsLocked = true;
    }

    function __toString () {
        // TODO: clean namespace & prefix (reflection class)
        return '[' . ltrim(get_called_class(), 'o\\') . ']';
    }

    // TODO: toJson

    function __get ($field) {

        if (method_exists($this, 'u_dynamic_get')) {
            return $this->u_dynamic_get($field);
        }

        $suggestion = '';
        if (method_exists($this, 'u_suggest_field')) {
            $suggestion = $this->u_suggest_field(unu_($field));
        }
        $suggest = $suggestion ? " Try: `"  . $suggestion . "`" : '';

        Tht::error("Unknown field: `$field` $suggest");
    }

    function __set ($field, $value) {

        if (method_exists($this, 'u_dynamic_set')) {
            return $this->u_dynamic_set($field, $value);
        }

        if ($this->_fieldsLocked) {
            Tht::error("Can not create field `$field` after object is constructed.");
        }
        $this->$field = $value;
    }

    function __call ($method, $args) {

        if (method_exists($this, 'u_dynamic_call')) {
            return $this->u_dynamic_call(unu_($method), $args);
        }

        $suggestion = '';
        if (method_exists($this, 'u_suggest_method')) {
            $suggestion = $this->u_suggest_method(unu_($method));
        }
        $suggest = $suggestion ? " Try: `"  . $suggestion . "()`" : '';

        $c = get_called_class();

        Tht::error("Unknown method `$method` for class `$c`. $suggest");
    }

    function u_call_method($method, $args) {
        $uMethod = u_($method);
        call_user_func_array([ $this, $uMethod ], $args);
    }

    function u_set_field($field, $vel) {
        $uField = u_($field);
        $this->$uField = $val;
    }

    function u_get_field($field) {
        $uField = u_($field);
        return $this->$uField;
    }

    function u_has_method ($method) {
        return method_exists($this, u_($method));
    }

    function u_has_field ($field) {
        return property_exists($this, u_($field));
    }

    function u_fields () {
        $fields = get_object_vars($this);
        return $this->userDefinedElements(array_keys($fields));
    }

    function u_methods () {
        $methods = get_class_methods(get_called_class());
        return $this->userDefinedElements($methods);
    }

    private function userDefinedElements($elements) {
        $userElements = [];
        foreach ($elements as $e) {
            if (hasu_($e)) {
                $userElements []= unu_($e);
            }
        }
        sort($userElements);
        return $userElements;
    }
}



