<?php

namespace o;

class u_Input extends StdModule {

    function u_route ($key) {
        ARGS('s', func_get_args());
        return WebMode::getWebRouteParam($key);
    }

    function u_get($name, $sRules='id') {
        $getter = new u_RequestData ('get');
        return $getter->field($name, $sRules)['value'];
    }

    function u_post($rules=null, $sRules='id') {
        $getter = new u_RequestData ('post');
        return $getter->field($name, $sRules)['value'];
    }

    function u_get_all($rules) {
        $getter = new u_RequestData ('get');
        return $getter->fields($name, $rules);
    }

    function u_post_all($rules=null) {
        $getter = new u_RequestData ('post');
        return $getter->fields($name, $rules);
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
}

class u_RequestData {

    private $matchesRequestMethod = false;
    private $dataSource = '';
    private $rules = null;

    function __construct($method, $rules=null) {

        $this->dataSource = $method;
        $this->matchesRequestMethod = Tht::module('Request')->u_method() === $method;

        Security::validatePostRequest();

        $this->rules = $rules;
    }

    function field($fieldName, $sRules) {

        if (!$this->matchesRequestMethod) {
            return '';
        }

        $rawVal = Tht::getPhpGlobal($this->dataSource, $fieldName);

        $schema = ['rule' => $sRules];
        $validator = new u_FormValidator ();
        $validated = $validator->validateField($fieldName, $rawVal, $schema);

        return $validated;
    }

    function fields() {
        $rawVals = Tht::getPhpGlobal($this->dataSource, '*');
        $validator = new u_FormValidator ();
        $validated = $validator->validateFields($rawVals, $this->rules);

        return v($validated);
    }

}