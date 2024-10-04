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
    private $formSchema = [];

    protected $suggestMethod = [
        'render' => 'toHtml()',
        'html'   => 'toHtml()',
    ];

    // Make sure helpLink points to module, not class
    function error($msg, $method = '') {
        Tht::module('Form')->error($msg, $method);
    }

    function __construct($formId, $formSchema) {

        $this->formId = $formId;

        $this->initFormSchema($formSchema);
    }

    private function initFormSchema($formSchema) {

        foreach ($formSchema as $fieldName => $fieldSchema) {
            $formSchema[$fieldName] = $this->initFieldSchema($fieldName, $fieldSchema);
        }

        $this->formSchema = $formSchema;
    }

    // TODO: Organize this a bit more.
    private function initFieldSchema($fieldName, $fieldSchema) {

        $validator = Tht::module('Input')->getValidator();

        // Derive the rule from the type, if missing.
        if (!isset($fieldSchema['rule'])) {
            $tag = $fieldSchema['tag'];
            if ($tag == 'checkbox' && isset($fieldSchema['options'])) {
                $tag = 'checkbox+options';
            }
            $fieldSchema['rule'] = $validator->getRuleForInputTag($tag);
        }

        // Convert rule string to map
        $fieldSchema['rule'] = $validator->getRuleMapForString($fieldName, $fieldSchema['rule']);

        $tagType = $fieldSchema['tag'];

        // Add rule depending on type of checkbox
        if ($tagType == 'checkbox') {
            if ($fieldSchema['options']) {
                // List of checkboxes
                $rule = $fieldSchema['rule'];
                $rule['list'] = true;
                $fieldSchema['rule'] = $rule;
            }
            else {
                // Boolean toggle
                $rule = $fieldSchema['rule'];
                $rule['b'] = true;
                $fieldSchema['rule'] = $rule;
            }
        }


        // Derive the tag from the rule
        if (!isset($fieldSchema['tag'])) {
            $fieldSchema['tag'] = $fieldSchema['rule']['zTagType'];
        }


        // Some rules only work with certain field tags. e.g. email
        $derivedFieldType = $fieldSchema['rule']['zTagType'];
        if (!isset($fieldSchema['options'])) {
            if ($derivedFieldType && $tagType !== 'hidden') {
                $fieldSchema['tag'] = $derivedFieldType;
                $tagType = $derivedFieldType;
                // Lets just take the derived type for convenience.
                // if ($tagType !== $derivedFieldType) {
                //     $this->error("Input field `$fieldName` needs a `tag` of: `$derivedFieldType` or `hidden`  Got: `$tagType`");
                // }
            }
        }


        // Auto-populate 'in' rule with option values
        if (isset($fieldSchema['options'])) {
            $rule = $fieldSchema['rule'];
            $options = $fieldSchema['options'];
            if (OList::isa($options)) {
                $rule['in'] = $options;
            }
            else if (OMap::isa($options)) {
                $rule['in'] = $options->u_keys();
            }

            $fieldSchema['rule'] = $rule;
        }

        if (!$fieldSchema['rule']) {
            $this->error("Need `rule` key for Form field: `$fieldName`");
        }

        // Require 'options' field for select, radio, checkbox
        if (in_array($tagType, ['select', 'radio'])) {
            if (!isset($fieldSchema['options']) || !OBag::isa($fieldSchema['options'])) {
                $this->error("Input field `$fieldName` needs an `options` field with a Map or List.");
            }
        }

        // If you have options, you need a compatible type
        if (isset($fieldSchema['options'])) {
            if (!in_array($tagType, ['select', 'checkbox', 'radio'])) {
                $this->error(
                    "Input field `$fieldName` has an `options` Map, so it needs a `type` of: `select`, `checkbox`, or `radio`"
                );
            }
        }


        // Pre-filled value
        if (isset($fieldSchema['value'])) {
            $this->u_set_values(OMap::create([$fieldName => $fieldSchema['value']]));
        }

        return $fieldSchema;
    }

    // Basic Methods
    // --------------------------------------------------

    function u_get_form_id() {

        $this->ARGS('', func_get_args());

        return $this->formId;
    }

    // filterForJs is undocumented
    function u_get_schema($filterForJs = false) {

        $this->ARGS('b', func_get_args());

        $schema = [];

        if ($filterForJs) {

            foreach ($this->formSchema as $fieldName => $fieldSchema) {

                $rules = $fieldSchema['rule'];
                $newRule = [
                    'min' => $rules['min'],
                    'max' => $rules['max'],
                    'optional' => $rules['optional'],
                ];
                if ($rules['step']) { $newRule['step'] = $rules['step']; }
                if ($rules['regex']) { $newRule['regex'] = $rules['regex']; }

                $schema[$fieldName] = [ 'rule' => $newRule ];
            }
        }
        else {
            $schema = $this->formSchema;
        }

        return v($schema);
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
                if (!isset($this->formSchema[$fieldName])) {
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
            $this->sendError($error['key'], $error['error']);

            return false;
        }
    }


    // Validation
    // --------------------------------------------------

    function validateAllFields() {

        $rawPostData = $this->getRawPostData();

        $fieldsToRules = OMap::create([]);
        foreach ($this->formSchema as $fieldName => $fieldSchema) {
            $fieldsToRules[$fieldName] = $fieldSchema['rule'];
        }

        return Tht::module('Input')->u_validate_values($rawPostData, $fieldsToRules);
    }

    function sendError($field, $message) {

        Tht::module('Output')->u_send_json(OMap::create([
            'status' => 'fail',
            'error' => ['field' => $field, 'message' => $message],
        ]));

        return false;
    }

    function sendOk($redirectUrl = '') {

        Tht::module('Output')->u_send_json(OMap::create([
            'status' => 'ok',
            'redirect' => $redirectUrl ? OTypeString::getUntyped($redirectUrl, 'url') : '',
        ]));
    }

    function sendOkHtml($html) {

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



