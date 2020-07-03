<?php

namespace o;

trait FormCreator {

    private $form = null;

        // *confirm
    // validate fieldName
    //
    private function initTagConfig($tagName, $tagConfig) {
        if ($tagName == 'password') {
            $tagConfig['type'] == 'password';
            $tagConfig['rules'] = 'password|min:8';
        }
        else if ($tagName == 'email') {
            $tagConfig['type'] == 'text';
            $tagConfig['rules'] = 'email|min:4|max:100';
        }
        else if ($tagName == 'userName') {
            $tagConfig['type'] == 'text';
            $tagConfig['rules'] = 'userName|min:4|max:20';
        }
        else if ($tagName == 'id') {
            $tagConfig['rules'] = 'max:100';
        }
        return $tagConfig;
    }

    function u_tag($fieldName, $atts=[]) {

        $this->ARGS('sm', func_get_args());

        if (!isset($this->formConfig[$fieldName])) {
            Tht::error('Unknown form tag name: `' . $fieldName . '`');
        }
        $tagConfig = $this->formConfig[$fieldName];

        $type = isset($tagConfig['type']) ? $tagConfig['type'] : 'text';

        if ($type == 'password' && $this->method !== 'post') {
            Tht::error("Forms with a `password` field must have a form method=`post`.");
        }

        if ($type == 'textarea') {
            $lInputTag = $this->textareaTag($fieldName, $atts);
        }
        else if ($type == 'select') {
            $lInputTag = $this->selectTag($fieldName, $tagConfig, $atts);
        }
        else if ($type == 'checkbox' || $type == 'radio') {
            $lInputTag = $this->checkboxTags($type, $fieldName, $tagConfig, $atts);
        }
        else {
            $lInputTag = $this->inputTag($type, $fieldName, $atts);
        }


        if ($type == 'hidden') {
            return $lInputTag;
        }
        else {

            // Include full field lockup: label, optional tag, help text.
            $labelTag = '';
            if (isset($tagConfig['label']) ) {
                $labelTag = $this->labelTag($fieldName, $tagConfig['label'])->u_stringify();
            }

            $optional = '';
            if (isset($tagConfig['rule']) && preg_match('/optional/', $tagConfig['rule'])) {
                $optional = '<small class="form-optional">' . Security::escapeHtml($this->strings['optional']) . '</small>';
            }

            $help = '';
            if (isset($tagConfig['help'])) {
                $help = '<small class="form-help">' . Security::escapeHtml($tagConfig['help']) . '</small>';
            }

            $combined = $labelTag . $optional . $lInputTag->u_stringify() . $help;

            return new HtmlTypeString ('<div class="form-field">' . $combined . '</div>');
        }
    }

    function u_tags($tags=null) {

        if (is_null($tags)) {
            $tags = $this->formConfig->u_keys();
        }

        $this->ARGS('l', func_get_args());

        $html = '';
        foreach ($tags as $tag) {
            $html .= $this->u_tag($tag)->u_stringify();
        }

        return new HtmlTypeString ($html);
    }

    function u_open ($actionUrl='', $atts=[]) {

        $this->ARGS('sm', func_get_args());

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

        if (!isset($atts['method'])) {
            $atts['method'] = 'post';
        }
        $this->method = strtolower($atts['method']);

        $atts['id'] = $this->formId;

        $formTag = $this->openTag('form', $atts, true);

        // Always include formId
        $formTag .= '<input type="hidden" name="formId" value="' . Security::escapeHtml($this->formId) . '">';

        $html = new HtmlTypeString ($formTag);

        Tht::module('Session')->u_set('formLoadTime', time());

        return $html;
    }

    function u_close () {

        if (!$this->isOpen) { Tht::error("Form is not currently open."); }
        $this->isOpen = false;

        $closeTag = $this->closeTag('form', true);

        // Form JS
        $web = Tht::module('Web');
        $nonce = $web->u_nonce();

        $formJs = $this->formJs($this->formId)->u_stringify();

        $valJsTag = "<script nonce='$nonce'>" . $formJs . "</script>";
        $errorMsgTag = '<div class="form-error-message"></div>';

        $html = new HtmlTypeString ($errorMsgTag  . $closeTag . $valJsTag);

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
            $paramsOut []= $k . '="' . Security::escapeHtml($v) . '"';
        }
        $inner = implode(' ', $paramsOut);
        $inner .= ($name == 'input') ? ' /' : '';


        $out = "<$name $inner>";

        if ($name == 'form') {
            $out = $out . $this->noscriptTag();
        }
        else if ($name == 'button') {
            $out .= Security::escapeHtml($params['value']) . '</button>';
        }

        return $getRaw ? $out : new HtmlTypeString ($out);
    }

    function closeTag($name, $getRaw=false) {
        $out = "</$name>";
        return $getRaw ? $out : new HtmlTypeString ($out);
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
        $html .= Security::escapeHtml($val) . '</textarea>';
        return new HtmlTypeString ($html);
    }

    function labelTag($name, $text, $atts=[]) {
        $atts = uv($atts);
        $atts['for'] = $this->getFieldId($name);
        $html = $this->openTag('label', $atts, true) . $text . '</label>';
        return new HtmlTypeString ($html);
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
        return 'field--' . $this->formId . '--' . $fieldName;
    }


    /////////////////////////


    // Select Tag

    function selectTag($name, $tagConfig, $atts=[]) {

        if (!isset($tagConfig['options'])) {
            Tht::error('Missing `options` key for `select` field: `' . $name . '`');
        }

        $atts = uv($atts);
        $atts['name'] = $name;

        $options = $this->prepOptions($name, $tagConfig);

        $html = $this->openTag('select', $atts, true);
        $html .= $this->optionTags($options)->u_stringify();
        $html .= '</select>';

        return new HtmlTypeString ($html);
    }

    function optionTags($ops) {
        $html = '';
        foreach ($ops as $op) {
            if ($op['on']) {
                unset($op['on']);
                $op['selected'] = true;
            }
            $html .= $this->openTag('option', $op, true);
            $html .= Security::escapeHtml($op['label']);
            $html .= '</option>';
        }
        return new HtmlTypeString ($html);
    }

    function prepOptions($name, $tagConfig) {

        $items = $tagConfig['options'];

        $ops = [];
        if ($items->u_type() == 'map') {
            $ops = $this->options_from_map(uv($items));
        } else {
            $ops = $this->options_from_list(uv($items));
        }

        $firstOption = $this->strings['firstSelectOption'];
        if (isset($tagConfig['firstOption'])) {
            $firstOption = $tagConfig['firstOption'];
        }
        array_unshift($ops, [ 'value' => '', 'label' => $firstOption ]);

        $default = $this->getDefaultValue($name);
        $ops = $this->setOptionDefaults($ops, $default);

        return $ops;
    }

    function setOptionDefaults($ops, $default) {
        if ($default === '') {
            $default = $ops[0]['value'];
        }
        foreach ($ops as &$op) {
            $op['on'] = $op['value'] === $default;
        }
        return $ops;
    }

    function options_from_list($items) {
        $ops = [];
        $num = 0;
        foreach ($items as $i) {
            $ops []= [ 'value' => $num, 'label' => $i ];
            $num += 1;
        }
        return $ops;
    }

    function options_from_map($items) {
        $ops = [];
        foreach ($items as $k => $v) {
            $ops []= [ 'value' => $k, 'label' => $v ];
        }
        return $ops;
    }



    /////////////////////////


    function checkboxTags($type, $name, $tagConfig, $atts=[]) {
        $html = '';
        $def = $this->getDefaultValue($name);

        $isChecked = $this->initCheckableDefaultMap($def);

        // todo: only allow one radio check

        $html = '<fieldset>';
        foreach ($tagConfig['options'] as $k => $o) {
            if (isset($isChecked[$o]) && $isChecked[$o]) {
                $atts['on'] = true;
            }
            if ($type == 'checkbox') {
                $value = 'true';
            } else {
                $value = $k;
            }
            $html .= $this->checkableTag($type, $name, $value, $o, $def, $atts);
        }
        $html .= '</fieldset>';

        return new HtmlTypeString ($html);
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
        $html .= $this->inputTag($type, $name, $atts)->u_stringify();
        $html .= '<span class="checkable-option-label">' . Security::escapeHtml($label) . '</span></label>';

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

        $this->ARGS('sf', func_get_args());

        $CSRF_TOKEN = Tht::module('Web')->u_csrf_token();
        $FORM_ID = $this->formId;


        $fieldToRule = [];
        foreach (uv($this->formConfig) as $k => $r) {
            $fieldToRule[$k] = isset($r['rule']) ? $r['rule'] : '';
        }
        $rules = json_encode($fieldToRule);

        $formRegisterJs = "FormValidator.registerForm('$FORM_ID', '$CSRF_TOKEN', $rules);\n";

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

            var css = [
                '@keyframes submit-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(359deg); } }',
                '.form-spinner { display: inline-block; vertical-align: text-top; height: 1.2em; width: 1.2em; border-radius: 50%; border: solid 0.2em rgba(0,0,0,0.2); border-right-color: rgba(0,0,0,0.4); animation: submit-spin 600ms linear 0s infinite }',
                '.button-primary .form-spinner { border-color: rgba(255,255,255,0.5); border-right-color: rgba(255,255,255,0.8); }',
                '.form-field-invalid, .form-field-invalid:focus { outline: 2px dashed #ff9900; }',
                '.form-error-message { display: none; padding: 1rem 2rem; background-color: #fff2cc; border-left: solid 4px #ffb856; margin: 1em 0; font-weight: bold; color: #222; }'
            ].join('');

            var style = document.createElement('style');
            style.innerHTML = css;
            document.getElementsByTagName('head')[0].appendChild(style);
        }

        window.FormValidator = (function() {

            var STATE = {};
            var FORMS = {};

            return {

                registerForm: function(formId, csrfToken, fieldRules) {
                    if (!FORMS[formId]) {
                        FORMS[formId] = fieldRules;
                    }
                    this.csrfToken = csrfToken;
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

                    var formData = new FormData(this.form);
                    formData.set('csrfToken', this.csrfToken);
                    console.log('csrf', formData.get('csrfToken'));

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

                        this.updateErrorState(this.form.querySelector('#field--' + this.form.id + '--' + firstError.field), false, firstError.error);
                        this.removeSpinner();
                        STATE.isSubmitting = false;
                    }
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


        return new JsTypeString($js);

    }
}