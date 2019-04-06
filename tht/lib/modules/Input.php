<?php

namespace o;

require_once('helpers/RequestData.php');
require_once('helpers/InputValidator.php');

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

    function u_get($name, $sRules='id') {
        $getter = new u_RequestData ('get');
        return $getter->field($name, $sRules)['value'];
    }

    function u_post($name, $sRules='id') {
        $getter = new u_RequestData ('post');
        return $getter->field($name, $sRules)['value'];
    }

    function u_rest($method, $fieldName, $sRules='id') {
        $getter = new u_RequestData ($method);
        return $getter->field($fieldName, $sRules)['value'];
    }


    // Get Map of Fields

    function u_get_all($rules) {
        $getter = new u_RequestData ('get', $rules);
        return $getter->fields();
    }

    function u_post_all($rules=null) {
        $getter = new u_RequestData ('post', $rules);
        return $getter->fields();
    }

    function u_rest_all($method, $rules=null) {
        $getter = new u_RequestData ($method, $rules);
        return $getter->fields();
    }


    // Meta-Getters

    function u_fields($method) {
        $getter = new u_RequestData ($method);
        return OList::create($getter->fieldNames());
    }

    function u_has_field($method, $fieldName) {
        $getter = new u_RequestData ($method);
        return OList::create($getter->hasField($fieldName));
    }

    function u_validate($fieldName, $val, $rules) {
        $validator = new u_InputValidator ();
        $validated = $validator->validateField($fieldName, $val, $rules);

        return OMap::create($validated);
    }
}
