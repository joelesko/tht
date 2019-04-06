<?php

namespace o;


class u_RequestData {

    private $matchesRequestMethod = false;
    private $method = '';
    private $dataSource = '';
    private $rules = null;

    function __construct($method, $rules=null) {

        $this->rules = $rules;
        $this->method = $method;
        $this->dataSource = $method == 'get' ? 'get' : 'post';
        $this->matchesRequestMethod = Tht::module('Request')->u_method() === $method;

        Security::validatePostRequest();
    }

    function field($fieldName, $sRules) {

        if (!$this->matchesRequestMethod) {
            return '';
        }

        $rawVal = Tht::getPhpGlobal($this->dataSource, $fieldName);

        $schema = ['rule' => $sRules];
        $validator = new u_InputValidator ();
        $validated = $validator->validateField($fieldName, $rawVal, $schema);

        return $validated;
    }

    function fields() {
        $rawVals = Tht::getPhpGlobal($this->dataSource, '*');
        $validator = new u_InputValidator ();
        $validated = $validator->validateFields($rawVals, $this->rules);

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
