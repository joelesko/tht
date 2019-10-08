<?php

namespace o;

require_once('helpers/RequestData.php');

class u_Input extends OStdModule {

    private $forms = [];

    // Misc Getters

    function u_is_post() {
        return Tht::module('Request')->u_method() == 'post';
    }

    function u_route ($key) {
        $this->ARGS('s', func_get_args());
        return WebMode::getWebRouteParam($key);
    }

    function u_form ($formId, $schema=null) {
        $this->ARGS('sm', func_get_args());
        if (is_null($schema)) {
            if (isset($this->forms[$formId])) {
                return $this->forms[$formId];
            }
            else {
                Tht::error("Unknown formId `$formId`");
            }
        }

        require_once('Form.php');
        $f = new u_Form ($formId, $schema);
        $this->forms[$formId] = $f;
        return $f;
    }


    // Get Single Field

    function u_get($name, $sRules='') {
        $this->ARGS('ss', func_get_args());
        $getter = new u_RequestData ('get');
        return $getter->field($name, $sRules)['value'];
    }

    function u_post($name, $sRules='') {
        $this->ARGS('ss', func_get_args());
        $getter = new u_RequestData ('post');
        return $getter->field($name, $sRules)['value'];
    }

    function u_remote($method, $fieldName, $sRules='') {
        $this->ARGS('sss', func_get_args());
        $getter = new u_RequestData ($method);
        return $getter->field($fieldName, $sRules)['value'];
    }


    // Get Map of Fields

    function u_get_all($rules) {
        $this->ARGS('m', func_get_args());
        $getter = new u_RequestData ('get');
        return $getter->fields($rules);
    }

    function u_post_all($rules) {
        $this->ARGS('m', func_get_args());
        $getter = new u_RequestData ('post');
        return $getter->fields($rules);
    }

    function u_remote_all($method, $rules) {
        $this->ARGS('sm', func_get_args());
        $getter = new u_RequestData ($method, true);
        return $getter->fields($rules);
    }

    function u_print_all($method, $printToLog = false) {
        $this->ARGS('sf', func_get_args());
        $all = OMap::create(Tht::getPhpGlobal($method, '*'));
        if ($printToLog) {
            Tht::module('File')->u_log($all);
        } else {
            Tht::module('*Bare')->u_print($all);
        }
    }


    // Meta-Getters

    function u_fields($method) {
        $this->ARGS('s', func_get_args());
        $getter = new u_RequestData ($method);
        return OList::create($getter->fieldNames());
    }

    function u_has_field($method, $fieldName) {
        $this->ARGS('ss', func_get_args());
        $getter = new u_RequestData ($method);
        return $getter->hasField($fieldName);
    }

    function u_validate($fieldName, $val, $rules) {
        $this->ARGS('s*s', func_get_args());
        $validator = new u_InputValidator ();
        $validated = $validator->validateField($fieldName, $val, $rules);

        return OMap::create($validated);
    }
}
