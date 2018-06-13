<?php

namespace o;

class u_Form extends StdModule {

    private $isOpen = false;
    private $wasOpen = false;
    private $isFileUpload = false;
    private $doJsValidation = true;
    private $validationResult = [];

    private $includedFormJs = false;

    private $data = [];
    private $schema = [];
    private $formId = '';
    private $fillData = [];

    private $validators = [];

    private $strings = [
        'noscript' => 'Please turn on JavaScript to use this form.',
        'optional' => 'optional',
        'firstSelectOption' => 'Select...'
    ];

    function __construct($schema, $formId) {

        $this->schema = uv($schema);
        $this->formId = $formId;

        // $web = Tht::module('Web');

        // $rules = [];
        // foreach ($this->schema as $k => $v) {
        //     $rules[$k] = isset($v['rule']) ? $v['rule'] : '';
        // }
       // $this->u_reader($rules, $formId);
    }



    // Data getters

    function u_validate () {

        $web = Tht::module('Web');

        if ($web->u_request()['method'] === 'post') {
            $this->validateRequest();

            $postData = Tht::getPhpGlobal('post', '*');
            $this->validationResult = Tht::module('FormValidator')->validateFields($postData, $this->schema);

            return $this->validationResult['ok'];
        }

        return false;
    }

    function u_go_fail () {
        Tht::module('Web')->u_send_json(OMap::create([
            'status' => 'fail',
            'fields' => []
        ]));
    }

    function u_go_next ($nextPage) {

        Tht::module('Web')->u_send_json(OMap::create([
            'ok' => 'ok',
            'next' => $nextPage
        ]));

    }

    function validateRequest() {
        if (Security::isCrossOrigin()) {
            Tht::module('Web')->u_send_error(403, 'Remote Origin Not Allowed');
        }
        if (!Security::validateCsrfToken()) {
            Tht::module('Web')->u_send_error(403, 'Invalid or Missing CSRF Token');
        }
        // if (!Security::isFormSubmittedByHuman()) {
        //     $this->formRequestError('Too Many Requests');
        // }
    }

    // function u_data($fieldName=null) {

    //     ARGS('s', func_get_args());

    //     if (!$this->u_ok()) {
    //         return is_null($fieldName) ? OMap::create([]) : '';
    //     }

    //     if (!is_null($fieldName)) {
    //         if (!isset($this->schema[$fieldName])) {
    //             Tht::error("Unknown form field name: `" . $fieldName . "`");
    //         }
    //         return $this->getFieldData($fieldName);
    //     }
    //     else {
    //         $data = [];
    //         foreach ($this->schema as $name => $s) {
    //             $data[$name] = $this->getFieldData($name);
    //         }
    //         return OMap::create($data);
    //     }
    // }

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

    function u_set_fields($data) {
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

        ARGS('sm', func_get_args());

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

    function u_tags($tags=null) { 

        if (is_null($tags)) {
            $tags = array_keys($this->schema);
        }

        ARGS('l', func_get_args());

        $html = '';
        foreach ($tags as $tag) {
            $html .= $this->u_tag($tag)->u_unlocked();
        }

        return new HtmlLockString ($html);
    }

    function u_open ($actionUrl='', $atts=[]) {

        ARGS('sm', func_get_args());

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

        if (isset($atts['jsValidation'])) {
            if (!$atts['jsValidation']) {
                $this->doJsValidation = false;
            }
            unset($atts['jsValidation']);
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

        // Form JS
        $web = Tht::module('Web');
        $nonce = $web->u_nonce();

        $formJs = $this->u_form_js($this->formId, $this->doJsValidation)->u_unlocked();
        
        $valJsTag = "<script nonce='$nonce'>" . $formJs . "</script>";
        $errorMsgTag = '<div class="form-error-message"></div>';

        $html = new HtmlLockString ($errorMsgTag  . $closeTag . $valJsTag);

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
        $out = "</$name>";
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
        $def = $this->getDefaultValue($name);

        $isChecked = $this->initCheckableDefaultMap($def);

        foreach ($schema['options'] as $o) {
            if (isset($isChecked[$o]) && $isChecked[$o]) {

            }
            $html .= $this->checkableTag($type, $name, $o, $o, $def, $atts);
        }

        return new HtmlLockString ($html);
    }

    function checkableTag($type, $name, $value, $label, $def, $atts) {
        if (isset($atts['on'])) {
            $atts['checked'] = 'checked';
            unset($atts['on']);
        }
        $atts = [
            'value' => $value
        ];

        $html = '<label class="checkable-option">';
        $html .= $this->inputTag($type, $name, $atts)->u_unlocked();
        $html .= '<span>' . htmlspecialchars($label) . '</span></label>';

        return  $html;
    }

    function initCheckableDefaultMap($def1) {
        $isChecked = [];
        if (is_array($def1)) {
            foreach ($def1 as $v) {
                $isChecked[$v] = true;
            }
        }
        return $isChecked;
    }





    /////////////////////////





    function u_form_js($formId="defaultForm", $doValidation=true) {

        ARGS('sf', func_get_args());

        $CSRF_TOKEN = Tht::module('Web')->u_csrf_token();
        $FORM_ID = $formId;
        $DO_VALIDATION = $doValidation ? 'true' : 'false';

        // TODO: validate/sanitize formId

        $formRegisterJs = "FormValidator.registerForm('$FORM_ID', {});\n";

        // About 2kb gzipped
        $formJs = $this->includedFormJs ? '' : <<<EOJS

            (function(){

                addFormStyles();
                listen();

                function listen() {
                    document.addEventListener('submit', function(e){ 
                        e.preventDefault(); 
                        FormValidator.submitFormIfOk(e.target);
                        return false; 
                    });
                }

                function addFormStyles() {
                    
                    var css = '@keyframes submit-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(359deg); } }\\n';
                    css += '.form-spinner { display: inline-block; vertical-align: text-top; height: 1.2em; width: 1.2em; border-radius: 50%; border: solid 0.2em rgba(0,0,0,0.2); border-right-color: rgba(0,0,0,0.4); animation: submit-spin 600ms linear 0s infinite }\\n';
                    css += '.button-primary .form-spinner { border-color: rgba(255,255,255,0.5); border-right-color: rgba(255,255,255,0.8); }\\n';
                    css += 'form .form-field-invalid, form .form-field-invalid:focus { outline: 2px solid #e33; }\\n';
                    css += '.form-error-message { margin-top: 1em; color: #e33; }\\n';

                    var style = document.createElement('style');
                    style.innerHTML = css;
                    document.getElementsByTagName('head')[0].appendChild(style);
                }

                window.FormValidator = (function() {

                    var STATE = {};
                    var FORMS = {};

                    var CSRF_TOKEN = '$CSRF_TOKEN';
                    var DO_VALIDATION = $DO_VALIDATION;

                    return {

                        registerForm: function(formId, fieldRules) {
                            if (!FORMS[formId]) {
                                FORMS[formId] = fieldRules;
                            } 
                        },

                        submitFormIfOk: function(form) {
                            if (STATE.isSubmitting) {
                                return false;
                            }

                            if (this.validateForm(form)) {
                                this.submitForm(form);
                            }
                        },

                        validateForm: function(form) {

                            if (!DO_VALIDATION) { return true; }

                            var formId = form.id;
                            var fields = form.querySelectorAll('*[name]');
                            for (var i=0; i < fields.length; i++) {
                                var field = fields[i];
                                if (!this.validateField(form, formId, field)) {
                                    return false;
                                }
                            }

                            return true;
                        },

                        submitForm: function(form) {
                            STATE.isSubmitting = true;
                            var hasSpinner = this.addSpinner(form);
                            this.setCsrfToken(form);

                            var minTimeMs = 600;
                            var startTime = Date.now();
                   
                            var formData = new FormData(form);
                            this.callAjax(form.action, formData, function(res){
                                var elapsed = Date.now() - startTime;
                                setTimeout(function(){
                                    if (res.status == 'ok') {
                                        if (res['next']) {
                                            location.href = res['next'];
                                        } else {
                                            form.dispatchEvent('submitComplete', res);
                                        } 
                                    }
                                    else {
                                        form.dispatchEvent('submitComplete', res);
                                    }
                                }, elapsed < minTimeMs ? minTimeMs - elapsed : 1);
                            });
                        },

                        setCsrfToken: function(form) {
                            var csrfField = document.createElement('input'); 
                            csrfField.type = 'hidden';
                            csrfField.name = 'csrfToken';
                            csrfField.value = CSRF_TOKEN;
                            form.appendChild(csrfField);
                        },

                        validateRequiredField: function(elField){
                            if (elField.type == 'checkbox') {
                                if (!elField.checked) {
                                    return false;
                                }
                            }
                            else if (elField.value.trim() == '') {
                                return false;
                            }
                            return true;
                        },

                        validateFieldRule: function(elField, rule) {

                            var value = elField.value.trim();

                            if (RULE_PATTERNS[rule] && value !== '') {
                                var re = new RegExp(RULE_PATTERNS[rule]);
                                if (!value.match(re)) {
                                    return false;
                                }
                            }
                            else if (rule.indexOf(':') !== -1) {
                                var pair = rule.split(':');
                                if (pair[0] == 'min' && value.length < 0 + pair[1]) {
                                    return false;
                                } 
                                else if (pair[0] == 'max' && value.length > 0 + pair[1]) {
                                    return false;
                                } 
                            }
                            return true;
                        },

                        validateField: function(form, formId, elField) {

                            if (elField.type == 'hidden') {
                                return true;
                            }

                            var isOk = true;
                            var isRequired = true;
                            var errorMessage = '';

                            var fieldRules = FORMS[formId][elField.name];

                            var rules = fieldRules.split(/\\s*\\|\\s*/);
                            for (var i=0; i < rules.length; i++) {
                                var rule = rules[i];
                                if (rule === 'optional') {
                                    isRequired = false;
                                }
                                else if (!this.validateFieldRule(elField, rule)) {
                                    isOk = false;
                                }
                            }

                            if (elField.type == 'password') {
                                if (!this.passwordStrengthOk(elField.value)) {
                                    errorMessage = 'Please use a stronger password.';
                                    isOk = false;
                                }
                                if (!this.validateFieldRule(elField, 'min:8')) {
                                    errorMessage = 'Please use a longer password. (8+ letters)';
                                    isOk = false;
                                }
                            }

                            if (isRequired && !this.validateRequiredField(elField)) {
                                errorMessage = 'Please fill the required field.'; 
                                isOk = false;
                            }

                            if (!isOk) {

                                if (!elField.instantValidate) {
                                    elField.instantValidate = true;
                                    var self = this;
                                    elField.addEventListener('keyup', function(){
                                        self.validateField(form, formId, elField);
                                    });
                                    elField.addEventListener('change', function(){
                                        self.validateField(form, formId, elField);
                                    });
                                }
                                elField.focus();
                            }

                            elField.classList.toggle('form-field-invalid', !isOk);
                            if (!isOk && !errorMessage) { errorMessage = 'Please check the highlighted field.'; }
                            form.querySelector('.form-error-message').innerText = errorMessage;

                            return isOk;
                        },  

                        passwordStrengthOk: function(v) {

                            v = v.trim();

                            // all same character
                            if (v.match(/^(.)\\1{1,}$/)) {
                                return false;
                            }
                            // all digits
                            if (v.match(/^\d+$/)) {
                                return false;
                            }
                            // most common patterns
                            if (v.match(/^(abcd|abc1|qwer|asdf|1qaz|passw|admin|login|welcome|access)/i)) {
                                return false;
                            }
                            // most common passwords
                            if (v.match(/^(football|baseball|princess|starwars|trustno1|superman|iloveyou)$/i)) {
                                return false;
                            }
                            
                            return true;
                        },

                        addSpinner: function(form) {
                            var submit = form.querySelector('*[type="submit"]');
                            if (!submit) { return false; }
                                    
                            var rect = submit.getBoundingClientRect();
                            submit.innerHTML = '';
                            submit.style.width = (Math.floor((rect.right - rect.left) / 2) * 2) + 'px';

                            var spinner = document.createElement('div');
                            spinner.classList.add('form-spinner');
                            submit.appendChild(spinner);

                            return true;
                        },

                        callAjax: function(url, data, cb) {
                            var xhr = new XMLHttpRequest();
                            xhr.onreadystatechange = function() {
                                if (xhr.readyState == 4) {
                                   if (xhr.status == 200) {
                                        var res = JSON.parse(xhr.responseText);
                                        cb(res);
                                    } else {
                                        console.error('AJAX server error', url, data);
                                    }
                                }
                            };
                            xhr.open('POST', url, true);
                            xhr.setRequestHeader('X-Requested-With', 'XmlHttpRequest');
                            xhr.send(data);
                        }
                    };
                })();

            })();

EOJS;

        $this->includedFormJs = true;

        return new JsLockString($formJs . $formRegisterJs);

    }

}



