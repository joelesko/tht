<?php

namespace o;

require_once('helpers/RequestData.php');

class u_Input extends StdModule {

    // Misc Getters

    function u_route ($key) {
        ARGS('s', func_get_args());
        return WebMode::getWebRouteParam($key);
    }

    function u_form ($formId, $schema=null) {
        ARGS('sm', func_get_args());
        if (is_null($schema)) {
            if (isset($this->forms[$formId])) {
                return $this->forms[$formId];
            }
            else {
                Tht::error("Unknown formId `$formId`");
            }
        }

        $f = new u_Form ($formId, $schema);
        $this->forms[$formId] = $f;
        return $f;
    }


    // Get Single Field

    function u_get($name, $sRules='') {
        ARGS('ss', func_get_args());
        $getter = new u_RequestData ('get');
        return $getter->field($name, $sRules)['value'];
    }

    function u_post($name, $sRules='') {
        ARGS('ss', func_get_args());
        $getter = new u_RequestData ('post');
        return $getter->field($name, $sRules)['value'];
    }

    function u_remote($method, $fieldName, $sRules='') {
        ARGS('sss', func_get_args());
        $getter = new u_RequestData ($method);
        return $getter->field($fieldName, $sRules)['value'];
    }


    // Get Map of Fields

    function u_get_all($rules) {
        ARGS('m', func_get_args());
        $getter = new u_RequestData ('get');
        return $getter->fields($rules);
    }

    function u_post_all($rules) {
        ARGS('m', func_get_args());
        $getter = new u_RequestData ('post');
        return $getter->fields($rules);
    }

    function u_remote_all($method, $rules) {
        ARGS('sm', func_get_args());
        $getter = new u_RequestData ($method, true);
        return $getter->fields($rules);
    }

    function u_log_all($method) {
        ARGS('s', func_get_args());
        $all = OMap::create(Tht::getPhpGlobal($method, '*'));
        Tht::module('File')->u_log($all);
    }


    // Meta-Getters

    function u_fields($method) {
        ARGS('s', func_get_args());
        $getter = new u_RequestData ($method);
        return OList::create($getter->fieldNames());
    }

    function u_has_field($method, $fieldName) {
        ARGS('ss', func_get_args());
        $getter = new u_RequestData ($method);
        return $getter->hasField($fieldName);
    }

    function u_validate($fieldName, $val, $rules) {
        ARGS('s*s', func_get_args());
        $validator = new u_InputValidator ();
        $validated = $validator->validateField($fieldName, $val, $rules);

        return OMap::create($validated);
    }
}
