<?php

namespace o;

require_once('helpers/FormCreator.php');

class u_Form extends StdModule {

    use FormCreator;

    private $isOpen = false;
    private $wasOpen = false;
    private $isFileUpload = false;
    private $doJsValidation = true;
    private $validationResult = [];
    private $method = '';

    static private $includedFormJs = false;

    private $data = [];
    private $formConfig = [];
    private $formId = '';
    private $fillData = [];

    private $validators = [];

    private $strings = [
        'noscript' => 'Please turn on JavaScript to use this form.',
        'optional' => 'optional',
        'firstSelectOption' => 'Select...'
    ];

    function __construct($formId, $formConfig) {

        $this->initFormConfig($formConfig);
        $this->formId = $formId;

        if (preg_match('/[^a-zA-Z0-9\-]/', $formId)) {
            Tht::error("formId should contain only letters and numbers and dashes. Got: `$formId`");
        }
    }

    private function initFormConfig($formConfig) {

        // foreach (uv($formConfig) as $k => $v) {
        //     $formConfig[$k] = $this->initTagConfig($k, $v);
        // }
        $this->formConfig = $formConfig;
    }

    function u_config() {
        return v($this->formConfig);
    }

    function u_fill($data) {
        ARGS('m', func_get_args());
        $this->fillData = $data;
    }

    function u_strings($map) {
        ARGS('m', func_get_args());
        $map = uv($map);
        foreach ($map as $k => $v) {
            if (!isset($this->strings[$k])) {
                Tht::error("Invalid Form string key: `$k`");
            }
            $this->strings[$k] = $v;
        }
    }

    // Check if there is a default value defined in the tagConfig or fillData
    function getDefaultValue($fieldName) {

        $tagConfig = $this->formConfig[$fieldName];

        $defaultVal = '';

        if (isset($this->fillData[$fieldName])) {
            // fill data (e.g. from database record)
            $defaultVal = $this->fillData[$fieldName];
        }
        else if (isset($tagConfig['value'])) {
            $defaultVal = $tagConfig['value'];
        }

        return $defaultVal;
    }


    // Form Post Logic
    // --------------------------------------------------

    function u_done() {
        ARGS('', func_get_args());
        if (Tht::module('Session')->u_get_flash('form.done:' . $this->formId)) {
            return true;
        }
        return false;
    }

    function u_is_submitted() {

        ARGS('', func_get_args());

        $postData = Tht::getPhpGlobal('post', '*');

        if (Tht::module('Request')->u_method() !== 'post') {
            return false;
        }
        else if (!isset($postData['formId'])) {
            Tht::error('Missing formId in form data.');
        }
        else if ($postData['formId'] !== $this->formId) {
            return false;
        }
        return true;
    }

    function u_check() {

        ARGS('', func_get_args());

        if (!$this->u_is_submitted()) {
            return false;
        }


        return true;

        $v = Tht::module('FormValidator');
        $this->validationResult = $v->validateFields($postData, $this->formConfig);
        if ($this->validationResult['ok']) {
            return true;
        }
        else {
            $this->u_send_fail($this->validationResult['errors']);
            return false;
        }
    }

    function u_send_fail($errors=[]) {
        ARGS('l', func_get_args());
        Tht::module('Response')->u_send_json(OMap::create([
            'status' => 'fail',
            'errors' => uv($errors),
        ]));
        return new \o\ONothing('sendFail');
    }

    function u_send_ok($next = '') {
        ARGS('*', func_get_args());
        Tht::module('Session')->u_set_flash('form.done:' . $this->formId, true);
        Tht::module('Response')->u_send_json(OMap::create([
            'status' => 'ok',
            'next' => OTypeString::getUntagged($next, 'url'),
        ]));

        return new \o\ONothing('sendOk');
    }





    // Data Getters
    // --------------------------------------------------

    function u_data_fields() {
        $postData = Tht::getPhpGlobal('post', '*');
        return OList::create(array_keys($postData));
    }

    function u_data($fieldName=null) {

        ARGS('s', func_get_args());

        // if (!$this->u_ok()) {
        //     return is_null($fieldName) ? OMap::create([]) : '';
        // }

        if (!is_null($fieldName)) {
            if (!isset($this->formConfig[$fieldName])) {
                Tht::error("Unknown form field name: `" . $fieldName . "`");
            }
            return $this->getFieldData($fieldName);
        }
        else {
            $data = [];
            foreach (uv($this->formConfig) as $name => $s) {
                $data[$name] = $this->getFieldData($name);
            }
            return OMap::create($data);
        }
    }

    function getFieldData($name) {
        $val = Tht::getPhpGlobal('post', $name);
        $tagConfig = $this->formConfig[$name];

        if (isset($tagConfig['type']) && $tagConfig['type'] == 'password') {
            return Security::createPassword($val);
        }

        return $val;
    }
}



