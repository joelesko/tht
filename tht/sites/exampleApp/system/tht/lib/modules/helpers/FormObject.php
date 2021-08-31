<?php

namespace o;

require_once('FormBuilder.php');

// Form Object
class Form extends OClass {

    use FormBuilder;

    protected $errorClass = 'Form';
    protected $cleanClass = 'Form';

    private $method = '';
    private $formId = '';

    private $data = [];
    private $formConfig = [];

    // Make sure helpLink points to module, not class
    function error($msg, $method = '') {
        Tht::module('Form')->error($msg, $method);
    }

    function __construct($formId, $formConfig) {

        $this->formId = $formId;

        $this->initFormConfig($formConfig);
    }

    private function initFormConfig($formConfig) {

        foreach ($formConfig as $fieldName => $fieldConfig) {
            $formConfig[$fieldName] = $this->initFieldConfig($fieldName, $fieldConfig);
        }

        $this->formConfig = $formConfig;
    }

    // TODO: Organize this a bit more.
    private function initFieldConfig($fieldName, $fieldConfig) {

        $validator = new u_InputValidator();

        // Require HTML input type
        if (!isset($fieldConfig['tag'])) {
            $this->error("Input field `$fieldName` needs a `tag`.");
        }

        // Derive the rule from the type, if missing.
        if (!isset($fieldConfig['rule'])) {
            $tag = $fieldConfig['tag'];
            if ($tag == 'checkbox' && isset($fieldConfig['options'])) {
                $tag = 'checkbox+options';
            }
            $derivedRule = $validator->getRuleForInputTag($tag);
            if (!$derivedRule) {
                $this->error("Input field `$fieldName` needs a `rule`.");
            }
            $fieldConfig['rule'] = $derivedRule;
        }

        // Process Rule
        $fieldConfig['rule'] = $validator->initFieldRules($fieldName, $fieldConfig['rule']);


        // Some rules only work with certain field tags. e.g. email
        $tagType = $fieldConfig['tag'];
        $derivedFieldType = $fieldConfig['rule']['fieldType'];
        if (!isset($fieldConfig['options'])) {
            if ($derivedFieldType && $tagType !== 'hidden') {
                if ($tagType !== $derivedFieldType) {
                    $this->error("Input field `$fieldName` needs a `tag` of `$derivedFieldType` or `hidden`. Got: `$tagType`");
                }
            }
        }

        // Require 'options' field for select, radio, checkbox
        if (in_array($tagType, ['select', 'radio'])) {
            if (!isset($fieldConfig['options']) || !OBag::isa($fieldConfig['options'])) {
                $this->error("Input field `$fieldName` needs an `options` field with a Map or List.");
            }
        }

        // If you have options, you need a compatible type
        if (isset($fieldConfig['options'])) {
            if (!in_array($tagType, ['select', 'checkbox', 'radio'])) {
                $this->error(
                    "Input field `$fieldName` has an `options` Map, so it needs a `type` of `select`, `checkbox`, or `radio`."
                );
            }
        }

        // Add rule depending on type of checkbox
        if ($tagType == 'checkbox') {
            if ($fieldConfig['options']) {
                // List of checkboxes
                $rule = $fieldConfig['rule'];
                $rule['list'] = true;
                $fieldConfig['rule'] = $rule;
            }
            else {
                // Boolean toggle
                $rule = $fieldConfig['rule'];
                $rule['b'] = true;
                $fieldConfig['rule'] = $rule;
            }
        }

        // Auto-populate 'in' rule with option values
        if (isset($fieldConfig['options'])) {
            $rule = $fieldConfig['rule'];
            $options = $fieldConfig['options'];
            if (OList::isa($options)) {
                $rule['in'] = $options;
            }
            else if (OMap::isa($options)) {
                $rule['in'] = $options->u_keys();
            }

            $fieldConfig['rule'] = $rule;
        }

        // Pre-filled value
        if (isset($fieldConfig['value'])) {
            $this->u_set_values(OMap::create([$fieldName => $fieldConfig['value']]));
        }

        return $fieldConfig;
    }


    // Basic Methods
    // --------------------------------------------------

    function u_get_form_id() {

        $this->ARGS('', func_get_args());

        return $this->formId;
    }

    function u_get_config($filterForJs = false) {

        $this->ARGS('b', func_get_args());

        $config = [];

        if ($filterForJs) {

            foreach ($this->formConfig as $fieldName => $fieldConfig) {

                $rules = $fieldConfig['rule'];
                $newRule = [
                    'min' => $rules['min'],
                    'max' => $rules['max'],
                    'optional' => $rules['optional'],
                ];
                if ($rules['step']) { $newRule['step'] = $rules['step']; }
                if ($rules['regex']) { $newRule['regex'] = $rules['regex']; }

                $config[$fieldName] = [ 'rule' => $newRule ];
            }
        }
        else {
            $config = $this->formConfig;
        }

        return v($config);
    }

    function u_set_values($data) {

        $this->ARGS('m', func_get_args());

        $this->fillData($data);

        return $this;
    }




    // Form Post Logic
    // --------------------------------------------------

    // Undocumented
    function u_is_submitted() {

        $this->ARGS('', func_get_args());

        $postData = $this->getRawPostData();

        return !is_null($postData);
    }

    function u_process($cb) {

        $this->ARGS('c', func_get_args());

        $result = $this->validateAllFields();

        if ($result['ok']) {

            $cbReturn = $cb($result['fields']);

            if ($cbReturn === false) {
                // noop
            }
            else if ($cbReturn === true) {
                // Redirect to same URL
                $url = OTypeString::create(
                    'url', Tht::getPhpGlobal('server', 'SCRIPT_NAME')
                );
                $this->sendOk($url);
            }
            else if (OList::isa($cbReturn)) {

                if (count($cbReturn) != 2) {
                    $this->error("Returned list must have two elements: `[$fieldName, $errorMessage]`");
                }

                $fieldName = $cbReturn[ONE_INDEX];
                if (!isset($this->formConfig[$fieldName])) {
                    $this->error("Unknown input field `$fieldName` when returning validation error.");
                }

                $this->sendError($cbReturn[ONE_INDEX], $cbReturn[ONE_INDEX + 1]);
            }
            else if (UrlTypeString::isa($cbReturn)) {
                // Redirect to custom URL
                $this->sendOk($cbReturn);
            }
            else if (HtmlTypeString::isa($cbReturn)) {
                // Send HTML snippet to replace form.
                $this->sendOkHtml($cbReturn);
            }
            else if (OMap::isa($cbReturn)) {
                // Return JSON object
                Tht::module('Output')->u_send_json($cbReturn);
            }
            else {
                $this->error(
                    'Callback `process` function must return a boolean, Map (json), URL TypeString, or HTML TypeString.'
                );
            }

            return true;
        }
        else {

            $error = $result['errors'][ONE_INDEX];
            $this->sendError($error['field'], $error['error']);

            return false;
        }
    }


    // Validation
    // --------------------------------------------------

    function validateAllFields() {

        $rawPostData = $this->getRawPostData();

        $iv = new u_InputValidator();

        return $iv->validateFields(
            $rawPostData,
            $this->formConfig
        );
    }

    function sendError($field, $message) {

        $this->ARGS('ss', func_get_args());

        Tht::module('Output')->u_send_json(OMap::create([
            'status' => 'fail',
            'error' => ['field' => $field, 'message' => $message],
        ]));

        return false;
    }

    function sendOk($redirectUrl = '') {

        $this->ARGS('*', func_get_args());

        Tht::module('Output')->u_send_json(OMap::create([
            'status' => 'ok',
            'redirect' => $redirectUrl ? OTypeString::getUntyped($redirectUrl, 'url') : '',
        ]));
    }

    function sendOkHtml($html) {

        $this->ARGS('*', func_get_args());

        Tht::module('Output')->u_send_json(OMap::create([
            'status' => 'ok',
            'html' => $html->u_render_string(),
        ]));
    }



    // Data Getters
    // --------------------------------------------------

    function getRawPostData() {

        $postData = Tht::getPhpGlobal('post', '*');

        if (Tht::module('Request')->u_get_method() !== 'post') {
            $postData = [];
        }
        else if (!isset($postData['formId'])) {
            $this->error('Missing formId in form data.');
        }
        else if ($postData['formId'] !== $this->formId) {
            $postData = [];
        }

        return OMap::create($postData);
    }
}



