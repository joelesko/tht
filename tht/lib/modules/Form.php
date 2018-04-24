<?php

namespace o;

class u_Form extends StdModule {

    private $isOpen = false;
    private $wasOpen = false;
    private $isFileUpload = false;

    private $data = [];
    private $schema = [];
    private $formId = '';
    private $fillData = [];

    // TODO: populate validator JS
    private $strings = [
        'noscript' => 'Please turn on JavaScript to use this form.',
        'optional' => 'optional',
        'firstSelectOption' => 'Select...'
    ];

    function __construct($schema, $formId) {

        $this->schema = uv($schema);
        $this->formId = $formId;

        $web = Tht::module('Web');

        $rules = [];
        foreach ($this->schema as $k => $v) {
            $rules[$k] = isset($v['rule']) ? $v['rule'] : '';
        }
        $web->u_reader($rules, $formId);
    }



    // Data getters

    function u_ok () {

        $web = Tht::module('Web');
        if ($web->u_request()['method'] === 'post') {
            return Security::validateCsrfToken();
        }
        return false;
    }

    function u_data($fieldName=null) {

        if (!$this->u_ok()) {
            return is_null($fieldName) ? OMap::create([]) : '';
        }

        if (!is_null($fieldName)) {
            if (!isset($this->schema[$fieldName])) {
                Tht::error("Unknown form field name: `" . $fieldName . "`");
            }
            return $this->getFieldData($fieldName);
        }
        else {
            $data = [];
            foreach ($this->schema as $name => $s) {
                $data[$name] = $this->getFieldData($name);
            }
            return OMap::create($data);
        }
    }

    function getFieldData($name) {
        $val = Tht::getPhpGlobal('post', $name);
        $schema = $this->schema[$name];    
    
        if ($schema['type'] == 'password') {
            return Security::createPassword($val);
        }

        return $val;
    }


    // Form construction

    function u_schema() {
        return v($this->schema);
    }

    function u_fill($data) {
        // TODO: validate map
        $this->fillData = $data;
    }

    function u_strings($map) {
        // TODO: validate keys
        $this->strings = $map;
    }


    function getDefaultValue($fieldName) {

        $schema = $this->schema[$fieldName];

        // default from schema
        $defaultVal = isset($schema['value']) ? $schema['value'] : '';

        // fill data (e.g. from database record)
        $defaultVal = isset($this->fillData[$fieldName]) ? $this->fillData[$fieldName] : '';

        // from current submission
        $defaultVal = Tht::getPhpGlobal('post', $fieldName, $defaultVal);

        return $defaultVal;
    }

    function u_tag($fieldName, $atts=[]) {

        if (!isset($this->schema[$fieldName])) {
            Tht::error('Unknown form tag name: `' . $fieldName . '`');
        }
        $schema = $this->schema[$fieldName];

        $type = isset($schema['type']) ? $schema['type'] : 'text';

        if ($type == 'textarea') {
            $lInputTag = $this->textareaTag($fieldName, $atts);
        } 
        else if ($type == 'select') {
            $lInputTag = $this->selectTag($fieldName, $schema, $atts);
        } 
        else if ($type == 'checkbox' || $type == 'radio') {
            $lInputTag = $this->checkboxTags($type, $fieldName, $schema, $atts);
        } 
        else {
            $lInputTag = $this->inputTag($type, $fieldName, $atts);
        }
        

        if ($type == 'hidden') {
            return $lInputTag;
        } 
        else {

            // Include full field lockup: label, optional tag, help text.

            $labelTag = $this->labelTag($fieldName, $schema['label'])->u_unlocked();

            $optional = '';
            if (isset($schema['rule']) && preg_match('/optional/', $schema['rule'])) {
                $optional = '<small class="form-optional">' . htmlspecialchars($this->strings['optional']) . '</small>';
            }

            $help = '';
            if (isset($schema['help'])) {
                $help = '<small class="form-help">' . htmlspecialchars($schema['help']) . '</small>';
            }

            $combined = $labelTag . $optional . $lInputTag->u_unlocked() . $help;

            return new HtmlLockString ('<div class="form-field">' . $combined . '</div>');
        }
    }

    function u_open ($actionUrl='', $atts=[]) {

        if ($this->isOpen) { Tht::error("A form is already open."); }
        $this->isOpen = true;
        $this->wasOpen = true;

        // Target current page by default
        if (!$actionUrl) { 
            $actionUrl = Tht::getPhpGlobal('server', 'SCRIPT_NAME'); 
        }

        $atts = uv($atts);
        $atts['action'] = $actionUrl;

        // TODO
        // Extra attributes to allow file uploads
        // if (isset($atts['fileUpload']) && $atts['fileUpload']) {
        //     $this->isFileUpload = true;
        //     $atts['enctype'] = "multipart/form-data";
        //     $atts['method'] = 'POST';
        //     unset($atts['files']);
        // }

        // Default method: POST
        if (!isset($atts['method'])) { 
            $atts['method'] = 'POST'; 
        }

        
        $atts['id'] = $this->formId; 

        $formTag = $this->openTag('form', $atts, true);

        $html = new HtmlLockString ($formTag);

        Tht::module('Session')->u_set('formLoadTime', time());

        return $html;
    }

    function u_close () {

        if (!$this->isOpen) { Tht::error("A form is not currently open."); }
        $this->isOpen = false;

        $closeTag = $this->closeTag('form', true);
        $errorMsg = '<div class="form-error-message"></div>';

        $html = new HtmlLockString ($errorMsg . $closeTag);

        return $html;
    }

    function u_button($val, $atts=[]) {
        return $this->buttonTag('button', $val, $atts);
    }

    function u_submit($val='Submit', $atts=[]) {
        return $this->buttonTag('submit', $val, $atts);
    }


    /// Tags

    function openTag($name, $params, $getRaw=false) {

        if (!$this->isOpen && $name !== 'form') {
            if ($this->wasOpen) {
                Tht::error("This form has already been closed.");
            } else {
                Tht::error("Call `Form.open()` before adding form fields.");
            }  
        }

        // construct key/value params
        $paramsOut = [];
        foreach ($params as $k => $v) {
            if ($v === true) { $v = $k; }
            if ($v === false) { $v = ''; }
            $paramsOut []= $k . '="' . htmlspecialchars($v) . '"';
        }
        $inner = implode(' ', $paramsOut);
        $inner .= ($name == 'input') ? ' /' : '';


        $out = "<$name $inner>";

        if ($name == 'form') {
            $out = $out . $this->noscriptTag();
        }
        else if ($name == 'button') {
            $out .= htmlspecialchars($params['value']) . '</button>';
        }

        return $getRaw ? $out : new HtmlLockString ($out);
    }

    function closeTag($name, $getRaw=false) {

        // Add validation JS script
        $web = Tht::module('Web');
        $valJs = $web->u_validate_js($this->formId)->u_unlocked();
        $nonce = $web->u_nonce();
        $out = "</$name><script nonce='$nonce'>" . $valJs . "</script>";

        return $getRaw ? $out : new HtmlLockString ($out);
    }

    function inputTag($type, $name, $atts=[]) {
        $atts = uv($atts);
        $atts['id'] = 'field_' . $name;
        $atts['name'] = $name;
        $atts['value'] = isset($atts['value']) ? $atts['value'] : $this->getDefaultValue($name);
        $atts['type'] = $type;
        return $this->openTag('input', $atts);
    }

    function buttonTag($type, $value, $atts=[]) {
        $atts = uv($atts);
        $atts['value'] = $value;
        $atts['type'] = $type;
        return $this->openTag('button', $atts);
    }

    function textareaTag($name, $atts=[]) {
        $atts = uv($atts);
        $atts['id'] = 'field_' . $name;
        $atts['name'] = $name;
        $val = $this->getDefaultValue($name);

        $html = $this->openTag('textarea', $atts, true);
        $html .= htmlspecialchars($val) . '</textarea>';
        return new HtmlLockString ($html);
    }

    function labelTag($name, $text, $atts=[]) {
        $atts = uv($atts);
        $atts['for'] = 'field_' . $name;
        $html = $this->openTag('label', $atts, true) . $text . '</label>';
        return new HtmlLockString ($html);
    }

    function noscriptTag() {
        $msg = $this->strings['noscript'];
        return '
            <noscript style="display: block; margin: 16px 0; padding: 12px 24px; border: solid 1px #c00">
                ' . $msg . '
                <style> input, select { opacity: 0.3; pointer-events: none } </style>
            </noscript>
        ';
    }


    /////////////////////////


    // Select Tag

    function selectTag($name, $schema, $atts=[]) {
        $atts = uv($atts);
        $atts['name'] = $name;

        $html = $this->openTag('select', $atts, true);

        $firstOption = isset($schema['firstOption']) ? $schema['firstOption'] : $this->strings['firstSelectOption'];
        $html .= $this->openTag('option', ['value' => ''], true) . htmlspecialchars($firstOption) . '</option>';

        if (!isset($schema['options'])) {
            Tht::error('Missing `options` key for `select` field: `' . $name . '`');
        }
        $default = $this->getDefaultValue($name);
        $html .= $this->optionTags($schema['options'], $default)->u_unlocked();
        $html .= '</select>';

        return new HtmlLockString ($html);
    }

    function optionTags($items1, $default=null, $key=null, $value=null) {
        $items = uv($items1);
        if ($items1->u_is_map()) {
            return $this->options_from_map($items, $default);
        } else if (!is_null($key)) {
            return $this->options_from_rows($items, $default, $key, $value);
        } else {
            return $this->options_from_list($items, $default);
        }
    }

    // Value = 'name' and 'value'
    function options_from_list($items, $default=null) {
        $ops = [];
        if (count($items)) {
            if (v(reset($items))->u_is_map()) {
                Tht::error('Need a `key` argument to create options from a List of Maps.');
            }
        }
        $num = 0;
        foreach ($items as $i) {
            $ops []= [ '_k' => $num, '_v' => $i ];
            $num += 1;
        }
        return $this->options($ops, $default, '_k', '_v');
    }

    // Key = 'name', value = 'value'
    function options_from_map($items, $default=null) {
        $ops = [];
        foreach ($items as $k => $v) {
            $ops []= [ '_k' => $k, '_v' => $v ];
        }
        return $this->options($ops, $default, '_k', '_v');
    }

    function options_from_rows($items, $default, $k, $v) {
        return $this->options($items, $default, $k, $v);
    }

    function options($items, $def=null, $keyName=null, $valName=null) {
        $html = '';
        foreach ($items as $i) {
            $i = uv($i);
            $op = isset($i['atts']) ? $i['atts'] : [];
            $op['value'] = $i[$keyName];

            if (!is_null($def) && $def === $i[$keyName]) { 
                $op['selected'] = true; 
            }

            $html .= $this->openTag('option', $op, true);
            $html .= htmlspecialchars($i[$valName]);
            $html .= '</option>';
        }
        return new HtmlLockString ($html);
    }


    /////////////////////////


    function checkboxTags($type, $name, $schema, $atts=[]) {
        $html = '';
        foreach ($schema['options'] as $o) {
            $html .= $this->checkableTag($type, $name, $o, $o, $atts);
        }

        return new HtmlLockString ($html);
    }

    function checkableTag($type, $name, $value, $label) {
        // $atts = uv($atts);
        // if (isset($atts['on'])) {
        //     $atts['checked'] = 'checked';
        //     unset($atts['on']);
        // }
        $atts = [
            'value' => $value
        ];

        $html = '<label class="checkable-option">';
        $html .= $this->inputTag($type, $name, $atts)->u_unlocked();
        $html .= '<span>' . htmlspecialchars($label) . '</span></label>';

        return  $html;
    }





    /////////////////////////





    // function u_text($name, $label, $val='', $atts=[]) {
    //     $l = $this->u_label($name, $label);
    //     $f = $this->u_input('text', $name, $val, $atts);

    //     return new HtmlLockString ($l->u_unlocked() . $f->u_unlocked());
    // }

    // function u_email($name, $label='Email', $val='', $atts=[]) {
    //     $l = $this->u_label($name, $label);
    //     $f = $this->u_input('email', $name, $val, $atts);

    //     return new HtmlLockString ($l->u_unlocked() . $f->u_unlocked());
    // }

    // function u_password($name, $label='Password', $val='', $atts=[]) {
    //     $l = $this->u_label($name, $label);
    //     $f = $this->u_input('email', $name, $val, $atts);

    //     return new HtmlLockString ($l->u_unlocked() . $f->u_unlocked());
    // }

    // function u_hidden($name, $label, $val='', $atts=[]) {
    //     return $this->u_input('hidden', $name, $val, $atts);
    // }

    // function u_button($val='', $atts=[]) {
    //     return $this->u_input('button', '', $val, $atts);
    // }

    // function u_file($name, $atts=[]) {
    //     if (!$this->isFileUpload) {
    //         Tht::error('`Form.open()` needs `{ fileUpload: true }` to support file uploads.');
    //     }
    //     return $this->u_input('file', $name);
    // }



}



