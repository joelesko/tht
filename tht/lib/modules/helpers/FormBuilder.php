<?php

namespace o;

trait FormBuilder {

    private $isOpen = false;
    private $wasOpen = false;
    private $isFileUpload = false;
    private $doJsValidation = true;

    private $fillData = [];

    static private $includedFormJs = false;

    function fillData($data) {

        foreach ($data as $k => $v) {
            $this->fillData[$k] = $v;
        }
    }

    function getString($key) {

        return Tht::module('Form')->getString($key);
    }

    function addClass($atts, $cls) {

        if (!isset($atts['class'])) { $atts['class'] = ''; }

        $atts['class'] .= ' ' . $cls;

        return $atts;
    }

    function u_render($submitLabel, $atts=null) {

        $this->ARGS('*m', func_get_args());

        $out = '';

        $out .= $this->u_open($atts)->u_render_string();
        $out .= $this->u_all_tags()->u_render_string();

        if ($submitLabel) {
            $out .= $this->u_submit_tag($submitLabel)->u_render_string();
        }

        $out .= $this->u_close()->u_render_string();

        return new HtmlTypeString ($out);
    }

    // Write all tags in sequence
    function u_all_tags($tags=null) {

        if (is_null($tags)) {
            $formConfig = $this->u_get_config();
            $tags = $formConfig->u_keys();
        }

        $this->ARGS('l', func_get_args());

        $html = '';

        foreach ($tags as $tag) {
            $html .= $this->u_tag($tag)->u_render_string();
        }

        return new HtmlTypeString ($html);
    }

    function u_open ($atts=null) {

        if (is_null($atts)) { $atts = OMap::create([]); }

        $this->ARGS('m', func_get_args());

        if ($this->isOpen) { $this->error("A form is already open."); }

        $this->isOpen = true;
        $this->wasOpen = true;

        $atts = unv($atts);

        if (!isset($atts['method'])) {
            $atts['method'] = 'post';
        }
        if (!isset($atts['action'])) {
            $atts['action'] = Tht::getPhpGlobal('server', 'SCRIPT_NAME');
        }

        $this->method = strtolower($atts['method']);

        $atts['id'] = $this->u_get_form_id();

        $formTag = $this->openTag('form', $atts, true);

        return new HtmlTypeString ($formTag);
    }

    function u_close () {

        $this->ARGS('', func_get_args());

        if (!$this->isOpen) { $this->error("Form is not currently open."); }

        $this->isOpen = false;

        $closeTag = $this->closeTag('form', true);

        return new HtmlTypeString ($closeTag . $this->u_get_js()->u_render_string());
    }

    function openTag($name, $params, $getRaw=false) {

        if (!$this->isOpen && $name !== 'form') {

            if ($this->wasOpen) {
                $this->error("This form has already been closed.");
            }
            else {
                $this->error("Call `Form.open()` before adding form fields.");
            }
        }

        // construct key/value params
        $paramsOut = [];

        foreach ($params as $k => $v) {

            if ($v === true) { $v = $k; }
            if ($v === false) { $v = ''; }
            if ($k == 'label') {  $k = 'aria-label';  }

            if (OTypeString::isa($v)) {
                $v = $v->u_render_string();
            }

            $v = Security::escapeHtml($v, 'removeTags');

            $paramsOut []= $k . '="' . $v . '"';
        }

        $inner = implode(' ', $paramsOut);
        $inner .= ($name == 'input') ? '/' : '';

        $out = "<$name $inner>";

        return $getRaw ? $out : new HtmlTypeString ($out);
    }

    function closeTag($name, $getRaw=false) {

        $out = "</$name>";

        return $getRaw ? $out : new HtmlTypeString ($out);
    }


    // Button Tags
    //----------------------------------------------------------------------


    function u_button_tag($val, $atts=null) {

        if (is_null($atts)) { $atts = OMap::create([]); }
        $this->ARGS('sm', func_get_args());

        $atts = $this->addClass($atts, 'btn btn-default');

        return $this->buttonTag('button', $val, $atts);
    }

    function u_submit_tag($val, $atts=null) {

        if (is_null($atts)) { $atts = OMap::create([]); }
        $this->ARGS('*m', func_get_args());

        $atts = $this->addClass($atts, 'button-primary btn btn-primary');

        return $this->buttonTag('submit', $val, $atts);
    }

    function buttonTag($type, $value, $atts=[]) {

        $atts = unv($atts);
        //$atts['value'] = $value;
        $atts['type'] = $type;

        if (!isset($atts['class'])) { $atts['class'] = ''; }

        // For Bootstrap
        $atts['class'] = 'btn btn-default ' . $atts['class'];

        // Support HTML inside of button tag
        $html = $this->openTag('button', $atts, true);
        if (HtmlTypeString::isa($value)) {
            $html .= $value->u_render_string();
        }
        else {
            $html .= $value;
        }
        $html .= $this->closeTag('button', true);

        return new HtmlTypeString($html);
    }


    // Tags
    //----------------------------------------------------------------------


    function u_bare_tag($fieldName, $atts=null) {

        $this->ARGS('sm', func_get_args());

        return $this->u_tag($fieldName, $atts, true);
    }

    // TODO: flags is undocumented
    function u_tag($fieldName, $atts=null, $flags=null) {

        $this->ARGS('smm', func_get_args());

        if (is_null($atts)) { $atts = OMap::create([]); }

        $formConfig = $this->u_get_config();

        if (!isset($formConfig[$fieldName])) {
            $this->error('Unknown form tag name: `' . $fieldName . '`');
        }

        $fieldConfig = $formConfig[$fieldName];

        $type = $fieldConfig['tag'];

        // Init label
        if (isset($fieldConfig['label'])) {
            $atts['label'] = $fieldConfig['label'];
        }
        else if ($type !== 'hidden') {
            $atts['label'] = $this->fieldNameToLabel($fieldName);
        }

        // Generate the Tag
        if ($type == 'textarea') {
            $htmlInputTag = $this->textareaTag($fieldName, $atts);
        }
        else if ($type == 'select') {
            $htmlInputTag = $this->selectTag($fieldName, $fieldConfig, $atts);
        }
        else if ($type == 'checkbox' && !$fieldConfig['options']) {
            return $this->singleCheckTag($fieldName, $fieldConfig, $atts);
        }
        else if ($type == 'checkbox' || $type == 'radio') {
            $htmlInputTag = $this->checkTags($type, $fieldName, $fieldConfig, $atts);
        }
        else {
            $htmlInputTag = $this->inputTag($type, $fieldName, $fieldConfig, $atts);
        }

        if (($flags && $flags['tagOnly']) || $type == 'hidden') {
            return $htmlInputTag;
        }

        return $this->fieldLockup($fieldName, $fieldConfig, $atts['label'], $htmlInputTag);
    }

    function fieldNameToLabel($fieldName) {

        $cleanName = preg_replace('/(Id|ID|_id)$/', '', $fieldName);

        return v(v($cleanName)->u_slug(' '))->u_title_case();
    }

    // Wrap input with label, optional tag, help text, etc.
    function fieldLockup($fieldName, $fieldConfig, $label, $htmlInputTag) {

        $labelTag = $this->labelTag($fieldName, $label);

        // Optional marker
        $optional = '';
        if ($fieldConfig['rule']['optional']) {
            $optional = '<small class="form-optional">'
                . $this->getString('optional')
                . '</small>';
        }

        // Password toggle
        $showPassword = '';
        if ($fieldConfig['tag'] == 'password') {
            $showPassword = '<span class="form-show-password"><input type="checkbox"> <span>'
                . Security::escapeHtml($this->getString('showPassword'))
                . '</span></span>';
        }

        // Help text
        $help = '';
        if (isset($fieldConfig['help'])) {
            $help = '<small class="form-help help-block">'
                . Security::escapeHtml($fieldConfig['help'])
                . '</small>';
        }
        else if (isset($fieldConfig['rule']) && isset($fieldConfig['rule']['newPassword'])) {
            $help = '<small class="form-help help-block">'
                . $this->getString('passwordHelp')
                . '</small>';
        }

        $combined = $labelTag->u_render_string()
            . $optional
            . $showPassword
            . $htmlInputTag->u_render_string()
            . $help;

        return new HtmlTypeString ('<div class="form-group">' . $combined . '</div>');
    }

    function inputTag($type, $name, $fieldConfig, $atts=[]) {

        $atts = unv($atts);

        if (isset($fieldConfig['placeholder'])) {
            $atts['placeholder'] = $fieldConfig['placeholder'];
        }

        // Attributes override fieldConfig
        $value = isset($fieldConfig['value']) ? $fieldConfig['value'] : '';

        if (isset($atts['value'])) {
            $value = $atts['value'];
        }

        $atts['name'] = $name;
        $atts['value'] = $value;
        $atts['type'] = $type;

        if ($type != 'radio' && $type != 'checkbox' && $type != 'hidden') {
            $atts = $this->addClass($atts, 'form-control');
        }

        return $this->openTag('input', $atts);
    }

    function textareaTag($name, $atts=[]) {

        $atts = unv($atts);
        $atts['name'] = $name;
        $atts = $this->addClass($atts, 'form-control');

        $html = $this->openTag('textarea', $atts, true);
        $html .= '</textarea>';

        return new HtmlTypeString ($html);
    }

    function labelTag($name, $text, $atts=[]) {

        $atts = unv($atts);

        $html = $this->openTag('label', $atts, true) . Security::escapeHtml($text) . '</label>';

        return new HtmlTypeString ($html);
    }




    // Multi-option tags (select, radio, etc)
    //----------------------------------------------------------------------


    function selectTag($name, $fieldConfig, $atts=[]) {

        if (!isset($fieldConfig['options'])) {
            $this->error('Missing `options` key for `select` input field: `' . $name . '`');
        }

        $atts = unv($atts);
        $atts['name'] = $name;
        $atts = $this->addClass($atts, 'form-control');

        $options = $this->initOptions($name, $fieldConfig, true);

        $html = $this->openTag('select', $atts, true);
        $html .= $this->optionTags($options)->u_render_string();
        $html .= '</select>';

        return new HtmlTypeString ($html);
    }

    function optionTags($ops) {

        $html = '';

        foreach ($ops as $op) {
            $html .= $this->openTag('option', $op, true);
            $html .= Security::escapeHtml($op['label']);
            $html .= '</option>';
        }

        return new HtmlTypeString ($html);
    }

    function initOptions($name, $fieldConfig, $isPulldown=false) {

        $items = $fieldConfig['options'];

        $options = $this->optionsFromBag($name, $items);

        if ($isPulldown) {

            $firstOption = $this->getString('firstSelectOption');

            if (isset($fieldConfig['firstOption'])) {
                $firstOption = $fieldConfig['firstOption'];
            }

            array_unshift($options, [ 'value' => '', 'label' => $firstOption ]);
        }

        return $options;
    }

    function optionsFromBag($fieldName, $items) {

        $ops = [];

        // e.g. { 0: 'Green', 1: 'Blue' }
        if (OMap::isa($items)) {
            foreach ($items as $value => $label) {
                if (!is_string($label) && !OTypeString::isa($label)) {
                    $this->error("Option label `$value` for field `$fieldName` must be a string or HtmlTypeString.");
                }
                $ops []= [ 'value' => $value, 'label' => $label ];
            }
        }
        else if (OList::isa($items)) {
            foreach ($items as $it) {
                $label = v($it)->u_title_case();
                $ops []= [ 'value' => $it, 'label' => $label ];
            }
        }
        else {
            $this->error("Options for input field `$fieldName` must be a Map or List.");
        }

        return $ops;
    }

    function optionsError ($fieldName) {

    }

    function singleCheckTag($fieldName, $fieldConfig, $atts) {

        $html = '<div class="form-group form-checks">';

        if (!$fieldConfig['label']) {
            $this->error("Input field `$fieldName` needs a `label` because it is a single checkbox.");
        }

        $html .= $this->checkTag('checkbox', $fieldName, 'true', $fieldConfig['label'], $atts);
        $html .= '</div>';

        return new HtmlTypeString ($html);
    }

    function checkTags($type, $name, $fieldConfig, $atts=[]) {

        $html = '<div class="form-group form-checks">';

        $options = $this->initOptions($name, $fieldConfig, false);

        if ($type == 'checkbox') {
            $name .= '[]';
        }

        foreach ($options as $op) {
            $html .= $this->checkTag($type, $name, $op['value'], $op['label'], $atts);
        }

        $html .= '</div>';

        return new HtmlTypeString ($html);
    }

    function checkTag($type, $name, $value, $label, $atts=[]) {

        $atts = [
            'value' => $value,
            'class' => 'form-check-input',
            'label' => $label,
        ];

        $html = '<div class="' . $type . '">';
        $html .= '<label class="form-check">';
        $html .= $this->inputTag($type, $name, [], $atts)->u_render_string();
        $html .= '<span class="form-check-label">' . Security::escapeHtml($label) . '</span>';
        $html .= '</label>';
        $html .= '</div>';

        return $html;
    }


    // Validation JS
    //----------------------------------------------------

    function u_get_js() {

        $this->ARGS('', func_get_args());

        if ($this->method == 'get') { return ''; }

        // Form Register JS
        $reg = [
            'formId'    => $this->u_get_form_id(),
            'csrfToken' => Tht::module('Web')->u_csrf_token(),
            'config'    => $this->u_get_config(true),
            'fillData'  => $this->fillData,
        ];

        $regJson = json_encode($reg);
        $regJs = "window.addEventListener('load', ()=>{
            ThtForm.register(" . json_encode($reg) . ");
        });";

        $regJsTag = Tht::module('Output')->wrapJs($regJs, true);

        return new HtmlTypeString($regJsTag);
    }
}