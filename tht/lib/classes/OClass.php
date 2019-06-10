<?php

namespace o;

class OClass implements \JsonSerializable {

    private $_fieldsLocked = false;
    private $_fields = [];
    protected $u_state = null;

    protected $suggestMethod = [];

    protected $type = 'object';

    static private $types = [
        'boolean', 'string', 'number', 'list', 'map', 'object', 'regex', 'function', 'typeString', 'nothing'
    ];

    static function isa ($s) {
        if (is_object($s)) {
            $called = get_called_class();
            if ($called === get_class($s) || $called === get_parent_class($s)) {
                return true;
            }
        }
        return false;
    }

    function _init($args) {

        $this->u_state = OMap::create([]);

        // convert PHP arrays to THT
        // foreach (get_object_vars($this) as $k => $v) {
        //     if (is_array($this->$k)) {
        //         if (isset($this->$k['___map'])) {
        //             unset($this->$k['___map']);
        //             $this->$k = OMap::create($this->$k);
        //         } else {
        //             $this->$k = OList::create($this->$k);
        //         }
        //     }
        // }

        if (method_exists($this, 'u_new')) {
            call_user_func_array([ $this, 'u_new' ], $args);
        }

        $this->_fieldsLocked = true;
    }

    function __toString () {
        if (method_exists($this, 'u_z_to_string')) {
            return call_user_func_array([ $this, 'u_z_to_string' ], []);
        }
        // TODO: clean namespace & prefix (reflection class)
        return '<<<' . Tht::cleanPackageName(get_called_class()) . '>>>';
    }

    function jsonSerialize() {
        if (method_exists($this, 'u_z_to_json')) {
            return call_user_func_array([ $this, 'u_z_to_json' ], []);
        }
        return $this->__toString();
    }

    function __destruct() {
        if (method_exists($this, 'u_z_on_destroy')) {
            call_user_func_array([ $this, 'u_z_on_destroy' ], []);
        }
    }

    function u_type() {
        ARGS('', func_get_args());
        return $this->type;
    }

    function u_is_type($t) {
        ARGS('s', func_get_args());
        $type = $this->u_type();
        if ($t === $type) {
            return true;
        } else {
            if (!in_array($t, self::$types)) {
                if (preg_match('/^[^a-z]/', $t)) {
                    Tht::error("Type `$t` must be lower camelCase.");
                }
                Tht::error("Unknown type: `$t`");
            }
            return false;
        }
    }



    // TODO: toJson

    function __get ($field) {

        $field = unu_($field);

        $autoGetter = u_('get' . ucfirst($field));
        if (method_exists($this, $autoGetter)) {
            return $this->$autoGetter();
        }

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

        if (!$suggest) {
            if (method_exists($this, u_($field))) {
                $suggest = 'Try: `' . $field . '()`';
            }
        }

        Tht::error("Unknown field: `$field` $suggest");
    }

    function __set ($field, $value) {

        $unfield = unu_($field);
        $autoSetter = u_('set' . ucfirst($unfield));
        if (method_exists($this, $autoSetter)) {
            return $this->$autoSetter($value);
        }
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
        if (property_exists($this, 'suggestMethod')) {
            $umethod = strtolower(unu_($method));
            $suggestion = isset($this->suggestMethod[$umethod]) ? $this->suggestMethod[$umethod] : '';
        }
        $suggest = $suggestion ? " Try: `"  . $suggestion . "`" : '';

        $c = get_called_class();

        Tht::error("Unknown method `$method` for class `$c`. $suggest");
    }

    function u_z_call_method($method, $args=[]) {
        ARGS('sl', func_get_args());
        $uMethod = u_($method);
        return call_user_func_array([ $this, $uMethod ], uv($args));
    }

    function u_z_set_field($field, $value) {
        ARGS('s*', func_get_args());
        $uField = u_($field);
        $this->$uField = $value;
    }

    function u_z_get_field($field) {
        ARGS('s', func_get_args());
        $uField = u_($field);
        return $this->$uField;
    }

    function u_z_has_method ($method) {
        ARGS('s', func_get_args());
        return method_exists($this, u_($method));
    }

    function u_z_has_field ($field) {
        ARGS('s', func_get_args());
        return property_exists($this, u_($field));
    }

    function u_z_get_fields () {
        ARGS('', func_get_args());

        // When cast as an array, we can look at the prop keys.
        // Private and protected keys start with a null \0 character.
        // Have to do this for runtime reflection.
        $fields = (array)$this;
        $uFields = [];
        foreach ($fields as $k => $v) {
            if ($k[0] !== "\0" && hasu_($k)) {
                $uFields[unu_($k)] = $v;
            }
        }
        return OMap::create($uFields);
    }

    function u_z_set_fields ($fieldMap) {
        ARGS('m', func_get_args());
        foreach (uv($fieldMap) as $k => $v) {
            $this->u_z_set_field($k, $v);
        }
    }

    function u_z_get_methods () {
        ARGS('', func_get_args());
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



