<?php

namespace o;


class OClass {

//	protected $ufield = [];
//    protected $plugins = [];

    function __toString () {
        // TODO: clean namespace & prefix (reflection class)
        return '[' . ltrim(get_called_class(), 'o\\') . ']';
    }

    function __get ($field) {

        $plainField = unu_($field);
        $getterMethod = 'u_get_' . v($plainField)->u_to_token_case();

        if (method_exists($this, $getterMethod)) {
            return $this->$getterMethod();
        }
        else if (property_exists($this, $field)) {
            return $this->$field;
        }
        else {

            // TODO: allow user fallback method? 

            $suggestions = [
                'len'    => 'length()',
                'length' => 'length()',
                'size'   => 'length()',
                'count'  => 'length()',
            ];

            $suggest = isset($suggestions[$plainField]) ? " Try: `" . $suggestions[$plainField] . '`' : '';

            Tht::error("Unknown field: `$field` $suggest");
        }
    }

    function __set ($field, $value) {
        
        $plainField = unu_($field);
        $setterMethod = 'u_set' . v($plainField)->u_to_token_case();

        if (method_exists($this, $setterMethod)) {
            $this->$setterMethod($value);
     //   } else if (property_exists($this, $field)) {
       } else {
            // $this->ufield[$plainField] = $value;
            $this->$field = $value;
        }
        // else {
        //     // TODO: allow user fallback method? 
        //   //  Tht::error("Unknown field: `$field`");
        // }

        return $this;
    }

    function __call ($method, $args) {

        $c = \get_called_class();

        if ($method === 'new') {
            $c = \get_called_class();
            return $c::u_new();
        }

        // TODO: dedupe with callPlugin
        // foreach (array_reverse($this->plugins) as $h) {
        //     if ($h->u_has_function($method)) {
        //         array_unshift($args, $this);
        //         return $h->u_call($method, $args);
        //     }
        // }

        // type check: only return bool for isObject
        // if (substr($method, 0, 2) === 'is') {
        //     return $method === 'isObject' ? true : false;
        // }

        //$val = ($c == 'o\ONumber' || $c == 'o\OString' || $c == 'o\OFlag') ? '(' . $this->val . ')' : '';


        // TODO: move to each class
        $suggestions = [
            'o\OString::toUpperCase' => 'upperCase',
            'o\OString::toLowerCase' => 'lowerCase',
            'o\OList::count' => 'length',
            'o\OMap::count' => 'length',
        ];

        $k = $c . '::' . $method;
        $suggest = isset($suggestions[$k]) ? " Try: `"  . $suggestions[$k] . "()`" : '';

        Tht::error("Unknown method `$method` for class `$c`. $suggest");
    }
    //
    static function u_new () {
        $c = \get_called_class();
        $o = new $c ();
        if (method_exists($o, 'u_setup')) {
            call_user_func_array([ $o, 'u_setup' ], func_get_args());
        }
        return $o;
    }
    //
    // function xu_clone () {
    //     return clone $this;
    // }
    //
    // function xu_addData ($data) {
    //     $this->u_data = array_merge($this->u_data, $data->val);
    // }
    //
    // function u_call ($meth, $args=[]) {
    //     $meth = 'u_' . $meth;
    //     return call_user_func_array([ $this, $meth ], $args);
    // }
    //
    // function u_has_method ($method) {
    //     if (method_exists($this, 'u_' . $method)) {
    //         return true;
    //     } else {
    //         foreach (array_reverse($this->plugins) as $d) {
    //             if ($d->u_can($method)) {
    //                 return true;
    //             }
    //         }
    //         return false;
    //     }
    // }



    function u_methods () {
        $methods = get_class_methods(get_called_class());
        $userMethods = [];
        foreach ($methods as $m) {
            if (hasu_($m)) {
                $userMethods []= substr($m, 2);
            }
        }
        sort($userMethods);
        return $userMethods;
    }


    // Plugins

    // function xu_addPlugin ($h) {
    //     if (is_string($h)) {
    //         $h = new $h ();  // or call static?
    //     }
    //     $this->plugins []= $h;
    // }
    //
    // function xu_callPlugin ($method, $args=[]) {
    //     foreach (array_reverse($this->plugins) as $h) {
    //         if ($h->u_has_function($method)) {
    //             array_unshift($args, $this);
    //             return $h->u_call($method, $args);
    //         }
    //     }
    //     Tht::error("Unknown method '$method'");
    // }
    //
    // function xu_getPlugins () {
    //     return $this->plugins;
    // }
}
