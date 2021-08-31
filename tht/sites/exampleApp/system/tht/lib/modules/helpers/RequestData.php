<?php

namespace o;

class u_RequestData {

    private $method = '';
    private $dataSource = '';

    function __construct($method, $allowRemote = false) {

        $this->method = $method;

        if ($method == 'get' || $method == 'post'){
            $this->dataSource = $method;
        }
        else {
            Tht::error('HTTP method must be `get` or `post`');
        }

        if (!$allowRemote) {
            Security::validatePostRequest();
        }
    }

    function getField($fieldName, $sRule) {

        $rawVal = Tht::getPhpGlobal($this->dataSource, $fieldName);

        $validator = new u_InputValidator ();
        $validated = $validator->validateField($fieldName, $rawVal, $sRule);

        return $validated;
    }

    function getFields($rulesMap) {

        $rawVals = Tht::getPhpGlobal($this->dataSource, '*');

        $validator = new u_InputValidator ();
        $validated = $validator->validateFields($rawVals, $rulesMap);

        return $validated;
    }

    function getFieldNames() {

        $rawVals = Tht::getPhpGlobal($this->dataSource, '*');

        return OList::create(
            array_keys($rawVals)
        );
    }

    function hasField($fieldName) {

        $rawVals = Tht::getPhpGlobal($this->dataSource, '*');

        return isset($rawVals[$fieldName]);
    }

    function getAllRawFields() {

        $raw = Tht::getPhpGlobal($this->dataSource, '*');

        return OMap::create($raw);
    }

}
