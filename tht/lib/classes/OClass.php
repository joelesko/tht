<?php

namespace o;

class OClass {

    private $_fieldsLocked = false;
    protected $u_state = null;

    function _init($args) {

        $this->u_state = OMap::create([]);

        if (method_exists($this, 'u_new')) {
            call_user_func_array([ $this, 'u_new' ], $args);
        }

        $this->_fieldsLocked = true;
    }

    function __toString () {
        // TODO: clean namespace & prefix (reflection class)
        return '[' . ltrim(get_called_class(), 'o\\') . ']';
    }

    // TODO: toJson

    function __get ($field) {

        $field = unu_($field);

        if (method_exists($this, 'u_z_dynamic_get')) {
            $result = $this->u_z_dynamic_get($field);
            if ($result->u_ok()) {
                return $result->u_get();
            }
        }

        $suggestion = '';
        if (method_exists($this, 'u_z_suggest_field')) {
            $suggestion = $this->u_z_suggest_field(unu_($field));
        }
        $suggest = $suggestion ? " Try: `"  . $suggestion . "`" : '';

        Tht::error("Unknown field: `$field` $suggest");
    }

    function __set ($field, $value) {

        if (method_exists($this, 'u_z_dynamic_set')) {
            return $this->u_z_dynamic_set($field, $value);
        }

        if ($this->_fieldsLocked) {
            Tht::error("Can not create field `$field` after object is constructed.");
        }
        $this->$field = $value;
    }

    function __call ($method, $args) {

        if (method_exists($this, 'u_z_dynamic_call')) {
            $result = $this->u_z_dynamic_call(unu_($method), v($args));
            if ($result->u_ok()) {
                return $result->u_get();
            }
        }

        $suggestion = '';
        if (method_exists($this, 'u_z_suggest_method')) {
            $suggestion = $this->u_z_suggest_method(unu_($method));
        }
        $suggest = $suggestion ? " Try: `"  . $suggestion . "()`" : '';

        $c = get_called_class();

        Tht::error("Unknown method `$method` for class `$c`. $suggest");
    }

    function u_z_call_method($method, $args=[]) {
        $uMethod = u_($method);
        return call_user_func_array([ $this, $uMethod ], uv($args));
    }

    function u_z_set_field($field, $value) {
        $uField = u_($field);
        $this->$uField = $value;
    }

    function u_z_get_field($field) {
        $uField = u_($field);
        return $this->$uField;
    }

    function u_z_has_method ($method) {
        return method_exists($this, u_($method));
    }

    function u_z_has_field ($field) {
        return property_exists($this, u_($field));
    }

    function u_z_fields () {
        $fields = get_object_vars($this);
        $uFields = $this->userDefinedElements(array_keys($fields));
        $fieldMap = [];
        foreach ($uFields as $f) {
            $u = u_($f);
            $fieldMap[$f] = $this->$u; 
        }
        return OMap::create($fieldMap);
    }

    function u_z_methods () {
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



