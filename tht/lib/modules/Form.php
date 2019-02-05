<?php

namespace o;

class u_Form extends StdModule {

    private $isOpen = false;
    private $wasOpen = false;
    private $isFileUpload = false;
    private $doJsValidation = true;
    private $validationResult = [];

    static private $includedFormJs = false;

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

    function __construct($formId, $schema) {

        $this->schema = $schema;
        $this->formId = $formId;

        if (preg_match('/[^a-zA-Z0-9\-]/', $formId)) {
            Tht::error('formId should contain only letters, numbers, and dashes.');
        }
    }



    // Data getters

    function u_done() {
        if (Tht::module('Session')->u_get_flash('form:' . $this->formId) == 'done') {
            return true;
        }
        return false;
    }

    function u_is_ok () {

        $web = Tht::module('Web');

        if ($web->u_request()['method'] !== 'post') {
            return false;
        }

        $this->validateRequest();

        $postData = Tht::getPhpGlobal('post', '*');

        if ($postData['formId'] !== $this->formId) {
            return false;
        }
        if (!isset($postData['formId'])) {
            Tht::error('Missing formId in form data.');
        }

        $this->validationResult = Tht::module('FormValidator')->validateFields($postData, $this->schema);
        
        if ($this->validationResult['ok']) {
            return true;
        } 
        else {
            Tht::module('Web')->u_send_json(OMap::create([
                'status' => 'fail',
                'errors' => $this->validationResult['errors'],
            ]));
            return false;
        }
    }

    function u_redirect($next) {
        Tht::module('Session')->u_set_flash('form:' . $this->formId, true);
        Tht::module('Web')->u_send_json(OMap::create([
            'status' => 'ok',
            'next' => $next,
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

    function u_data($fieldName=null) {

        ARGS('s', func_get_args());

        // if (!$this->u_ok()) {
        //     return is_null($fieldName) ? OMap::create([]) : '';
        // }

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
    
        if (isset($schema['type']) && $schema['type'] == 'password') {
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
            $tags = $this->schema->u_keys();
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

        $atts['id'] = $this->formId; 
 
        $formTag = $this->openTag('form', $atts, true);

        // Always include formId
        $formTag .= '<input type="hidden" name="formId" value="' . htmlspecialchars($this->formId) . '">';

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

        $formJs = $this->formJs($this->formId)->u_unlocked();
        
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
            if ($v !== '') {
                $paramsOut []= $k . '="' . htmlspecialchars($v) . '"';
            }  
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
        $atts['id'] = ($type == 'checkbox' || $type == 'radio') ? '' : $this->getFieldId($name);
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
        $atts['id'] = $this->getFieldId($name);
        $atts['name'] = $name;
        $val = $this->getDefaultValue($name);

        $html = $this->openTag('textarea', $atts, true);
        $html .= htmlspecialchars($val) . '</textarea>';
        return new HtmlLockString ($html);
    }

    function labelTag($name, $text, $atts=[]) {
        $atts = uv($atts);
        $atts['for'] = $this->getFieldId($name);
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

    function getFieldId($fieldName) {
        return 'field_' . $this->formId . '_' . $fieldName;
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

    function optionTags($items, $default=null, $key=null, $value=null) {
        if ($items->u_is_map()) {
            return $this->options_from_map(uv($items), $default);
        } else if (!is_null($key)) {
            return $this->options_from_rows(uv($items), $default, $key, $value);
        } else {
            return $this->options_from_list(uv($items), $default);
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

        $html = '<div class="form-field-options">';
        foreach ($schema['options'] as $o) {
            if (isset($isChecked[$o]) && $isChecked[$o]) {

            }
            $html .= $this->checkableTag($type, $name, $o, $o, $def, $atts);
        }
        $html .= '</div>';

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



    function formJs() {

        ARGS('sf', func_get_args());

        $CSRF_TOKEN = Tht::module('Web')->u_csrf_token();
        $FORM_ID = $this->formId;

        
        $fieldToRule = [];
        foreach ($this->schema as $k => $r) {
            $fieldToRule[$k] = $r['rule'];
        }
        $rules = json_encode($fieldToRule);
        $formRegisterJs = "FormValidator.registerForm('$FORM_ID', $rules);\n";

        // About 2kb gzipped
        $formJs = self::$includedFormJs ? '' : <<<EOJS

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
            css += 'form .form-field-invalid, form .form-field-invalid:focus { outline: 2px dashed #ff9900; }\\n';
            css += '.form-error-message { display: none; padding: 1rem 2rem; background-color: #fff2cc; margin: 1em 0; font-weight: bold; color: #222; }\\n';

            var style = document.createElement('style');
            style.innerHTML = css;
            document.getElementsByTagName('head')[0].appendChild(style);
        }

        window.FormValidator = (function() {

            var STATE = {};
            var FORMS = {};

            var CSRF_TOKEN = '$CSRF_TOKEN';

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
                    this.form = form;
                    var fields = form.querySelectorAll('*[name]');
                    for (var i=0; i < fields.length; i++) {
                        if (!this.validateField(fields[i])) {
                            return false;
                        }
                    }
                    return true;
                },

                validateField: function(elField) {

                    if (elField.type == 'hidden') { return true; }
                    var fieldRules = FORMS[this.form.id][elField.name];
                    var isRequired = !fieldRules || fieldRules.indexOf('optional') == -1;
                    var isOk = true;
                    if (isRequired && !this.validateRequiredField(elField)) {
                        isOk = false;
                    }
                    this.updateErrorState(elField, isOk, 'Please fill out this field:');
                    return isOk;
                },  

                validateRequiredField: function(elField){
                    if (elField.type == 'checkbox') {
                        if (!elField.checked) { return false; }
                    }
                    else if (elField.value.trim() == '') {
                        return false;
                    }
                    return true;
                },

                updateErrorState: function(elField, isOk, errorMessage) {
                    elField.classList.toggle('form-field-invalid', !isOk);
                    if (errorMessage) { errorMessage = errorMessage + ' &nbsp;' + elField.name.toUpperCase() }
                    var elMsg = this.form.querySelector('.form-error-message');
                    elMsg.innerHTML = errorMessage || '';
                    elMsg.style.display = isOk ? 'none' : 'block';
                },

                clearErrors: function() {
                    var invalidFields = this.form.querySelectorAll('.form-field-invalid');
                    for (var i=0; i < invalidFields.length; i += 1) {
                        invalidFields[i].classList.toggle('form-field-invalid', false);
                    }
                },

                addSpinnerToButton: function() {
                    var button = this.form.querySelector('*[type="submit"]');
                    if (!button) { return; }
                            
                    var rect = button.getBoundingClientRect();
                    let initialValue = button.tagName == 'input' ? button.value : button.innerHTML;
                    button.innerHTML = '';
                    button.value = '';
                    button.setAttribute('data-value', initialValue);
                    button.style.width = (Math.floor((rect.right - rect.left) / 2) * 2) + 'px';

                    var spinner = document.createElement('div');
                    spinner.classList.add('form-spinner');
                    button.appendChild(spinner);
                },

                removeSpinner: function() {
                    var button = this.form.querySelector('*[type="submit"]');
                    if (!button) { return; }

                    let prop = button.tagName == 'input' ? 'value' : 'innerHTML';
                    button[prop] = button.getAttribute('data-value');
                },

                submitForm: function() {

                    STATE.isSubmitting = true;
                    this.addSpinnerToButton();
                    this.setCsrfToken();

                    var formData = new FormData(this.form);
                    var minTimeMs = 600;
                    var startTime = Date.now();
                    var self = this;

                    this.callAjax(this.form.action, formData, function(res){
                        var elapsed = Date.now() - startTime;
                        setTimeout(function(){
                            self.ajaxResponse(res);
                        }, elapsed < minTimeMs ? minTimeMs - elapsed : 1);
                    });
                },

                ajaxResponse: function(res) {
                    if (res.status == 'ok') {
                        if (res['next']) {
                            location.href = res['next'];
                        }
                    } else {
                        var firstError = res['errors'].shift();
                        this.clearErrors();

                        this.updateErrorState(this.form.querySelector('#field_' + this.form.id + '_' + firstError.field), false, firstError.error);
                        this.removeSpinner(); 
                        STATE.isSubmitting = false;
                    }
                },

                setCsrfToken: function() {
                    var f = document.createElement('input'); 
                    f.type = 'hidden';
                    f.name = 'csrfToken';
                    f.value = CSRF_TOKEN;
                    this.form.appendChild(f);
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

        self::$includedFormJs = true;

        $js = Tht::module('Js')->u_minify($formJs . $formRegisterJs);

        return new JsLockString($js);

    }

}



