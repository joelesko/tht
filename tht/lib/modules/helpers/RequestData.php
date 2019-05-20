<?php

namespace o;

class u_RequestData {

    private $matchesRequestMethod = false;
    private $method = '';
    private $dataSource = '';
    private $allowExternal = false;

    function __construct($method, $allowExternal=false) {

        $this->method = $method;
        $this->dataSource = $method == 'get' ? 'get' : 'post';
        $this->matchesRequestMethod = Tht::module('Request')->u_method() === $method;

        if (!$allowExternal) {
            Security::validatePostRequest();
        }
    }

    function field($fieldName, $sRule) {
        $rawVal = Tht::getPhpGlobal($this->dataSource, $fieldName);
        $validator = new u_InputValidator ();
        $validated = $validator->validateField($fieldName, $rawVal, $sRule);

        return $validated;
    }

    function fields($rules) {
        $rawVals = Tht::getPhpGlobal($this->dataSource, '*');
        $validator = new u_InputValidator ();
        $validated = $validator->validateFields($rawVals, $rules);

        return v($validated);
    }

    function fieldNames() {
        $rawVals = Tht::getPhpGlobal($this->dataSource, '*');
        return array_keys($rawVals);
    }

    function hasField($fieldName) {
        $rawVals = Tht::getPhpGlobal($this->dataSource, '*');
        return isset($rawVals[$fieldName]);
    }

}
