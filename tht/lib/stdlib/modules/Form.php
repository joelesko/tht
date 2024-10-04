<?php

namespace o;

require_once('Form/FormObject.php');


// Form Module
class u_Form extends OStdModule {

    private $forms = [];

    private $strings = [
        'optional' => 'Optional',
        'firstSelectOption' => 'Select...',
        'showPassword' => 'Show Password',
        'passwordHelp' => '✓ 8+ letters &nbsp; ✓ At least 1 number or symbol'
    ];

    function u_create($formId, $formSchema) {

        $this->ARGS('sm', func_get_args());

        if (preg_match('/[^a-zA-Z0-9\-]/', $formId)) {
            $this->error("Form ID should contain only letters and numbers and dashes.  Got: `" . $formId . "`");
        }

        foreach ($formSchema as $k => $v) {
            if (!OMap::isa($v)) {
                $this->error("Form schema field `$k` must contain a Map.");
            }
        }

        $form = new Form ($formId, $formSchema);
        $this->forms[$formId] = $form;

        return $form;
    }

    function u_set_help_strings($map=null) {

        $this->ARGS('m', func_get_args());

        if (!$map) { return $this->strings; }

        $map = unv($map);
        foreach ($map as $k => $v) {

            if (!isset($this->strings[$k])) {
                $this->error("Invalid Form string key: `$k`");
            }

            $this->strings[$k] = $v;
        }
    }

    function getString($key) {

        return $this->strings[$key];
    }

    function u_get_submitted_form_id() {

        $this->ARGS('', func_get_args());

        $postData = Tht::getPhpGlobal('post', '*');

        return isset($postData['formId']) ? ('' . $postData['formId']) : '';
    }

    function u_csrf_tag() {

        $this->ARGS('', func_get_args());

        $t = Security::getCsrfToken();

        $tag = '<input type="hidden" name="csrfToken" value="' . $t . '" />';

        return new HtmlTypeString($tag);
    }
}
