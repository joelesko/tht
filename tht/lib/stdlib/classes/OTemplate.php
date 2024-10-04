<?php

namespace o;

class OTemplate {

    protected $returnStringType = '_';

    protected $str = '';

    function getString() {

        $this->str = $this->postProcess($this->str);

        if (is_string($this->str)) {
            $this->str = trim($this->str);
            // Disabled for perf hit.
            // $str = v($str)->u_trim_indent(OMap::create(['keepRelative' => true])) . "\n";
        }

        if ($this->returnStringType) {
            return OTypeString::create($this->returnStringType, $this->str);
        } else {
            return $this->str;
        }
    }

    function addStatic($s) {
        $this->str .= $s;
    }

    function addDynamic($context, $indent, $s) {

        $s = $this->onAddDynamic($context, $s);
        $s = $this->handleDynamic($context, $indent, $s);

        $this->str .= $s;
    }

    function handleDynamic($context, $indent, $val) {

        $out = '';
        if (OList::isa($val)) {
            foreach ($val as $v) {
                $out .= $this->handleDynamic($context, $indent, $v);
            }
        }
        else if (OTypeString::isa($val)) {
            $out = $this->handleTypeString($context, $val);
        }
        else {
            $out = $this->escape($context, $val);
        }

        return $out;
    }

    function escape($context, $in) {
        return $in;
    }

    function postProcess($s) {
        return $s;
    }

    function onAddDynamic($context, $s) {
        return $s;
    }

    // Hot Path
    function handleTypeString($context, $s) {
        $plain = OTypeString::getUntyped($s, '');
        if ($s->u_string_type() == $this->returnStringType) {
            return $plain;
        }
        else {
            return $this->escape($context, $plain);
        }
    }
}



////////// TYPES //////////

class TemplateHtml extends OTemplate {

    protected $returnStringType = 'html';

    function escape($context, $in) {
        $esc = Security::escapeHtml($in);
        if ($context == 'tag') {
            $esc = Security::sanitizeHtmlPlaceholder($esc);
        }
        return $esc;
    }

    function onAddDynamic($context, $s) {
        return $s;
    }

    function handleTypeString($context, $s) {

        // if js or css, wrap in appropriate block tags
        $plain = OTypeString::getUntyped($s, '');

        $type = $s->u_string_type();
        if ($type == 'html') {
            return $plain;
        }
        else if ($type == 'css') {
            return Tht::module('Output')->wrapCss($plain);
        }
        else if ($type == 'js') {
            return Tht::module('Output')->wrapJs($plain);
        }
        else if ($type == 'url') {
            return $this->escape('url', $plain);
        }

        return $this->escape($context, $plain);
    }
}

class TemplatLm extends TemplateHtml {}

class TemplateJs extends OTemplate {
    protected $returnStringType = 'js';
    function escape($context, $in) {
        return Tht::module('Output')->escapeJs($in);
    }
}

class TemplateCss extends OTemplate {
    protected $returnStringType = 'css';
    function escape($context, $in) {
        return Tht::module('Output')->escapeCss($in);
    }
}

class TemplateJcon extends OTemplate {
    protected $returnStringType = '';

    function escape($context, $in) {
        if (is_bool($in)) {
            return $in ? 'true' : 'false';
        } else if (vIsNumber($in) || is_string($in)) {
            $in = str_replace("\n", '\\n', $in);
            return $in;
        } else {
            Tht::error('Unable to escape expression in Jcon template.  Only Flags, Numbers, and Strings are supported.', $in);
        }
    }

    function postProcess($s) {
        return Tht::module('Jcon')->u_parse($s);
    }
}

class TemplateText extends OTemplate {
    protected $returnStringType = '';

    function postProcess($s) {
        return $s;
    }
}


