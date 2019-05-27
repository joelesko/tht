<?php

namespace o;

class OTemplate {
    protected $chunks = [];
    protected $returnTagType = '_';

    function getString() {
        $str = '';

        foreach ($this->chunks as $c) {
            $str .= $c['type'] === 'static' ? $c['body'] : $this->handleDynamic($c['context'], $c['body']);
        }

        $str = $this->postProcess($str);
        if (is_string($str)) {
            $str = v($str)->u_trim_indent() . "\n";
        }

        if ($this->returnTagType) {
            return OTypeString::create($this->returnTagType, $str);
        } else {
            return $str;
        }

    }

    function addStatic($s) {
        $this->chunks []= ['type' => 'static', 'body' => $s];
    }

    function addDynamic ($context, $s) {
        $this->chunks []= ['type' => 'dynamic', 'body' => $s, 'context' => $context];
    }

    function handleDynamic($context, $in) {

        $out = '';
        if (OList::isa($in)) {
            foreach ($in as $chunk) {
                $out .= $this->handleDynamic($context, $chunk);
            }
        }
        else if (OTypeString::isa($in)) {
            $out = $this->handleTypeString($context, $in);
        }
        else {
            $out = $this->escape($context, $in);
        }

        return $out;
    }

    function escape($context, $in) {
        return $in;
    }

    function postProcess($s) {
        return $s;
    }

    // Hot Path
    function handleTypeString($context, $s) {
        $plain = OTypeString::getUntyped($s, '');
        if ($s->u_tag_type() == $this->returnTagType) {
            return $plain;
        }
        else {
            return $this->escape($context, $plain);
        }
    }
}



////////// TYPES //////////

class TemplateHtml extends OTemplate {
    protected $returnTagType = 'html';

    function escape($context, $in) {
        $esc = Security::escapeHtml($in);
        if ($context == 'tag') {
            $esc = Security::sanitizeHtmlPlaceholder($esc);
        }
        return $esc;
    }

    function handleTypeString($context, $s) {

        // if js or css, wrap in appropriate block tags
        $plain = OTypeString::getUntyped($s, '');

        $type = $s->u_tag_type();
        if ($type == 'html') {
            return $plain;
        }
        else if ($type == 'css') {
            return Tht::module('Css')->wrap($plain);
        }
        else if ($type == 'js') {
            return Tht::module('Js')->wrap($plain);
        }
        else if ($type == 'url') {
            return $this->escape('url', $plain);
        }

        return $this->escape($context, $plain);
    }
}

class TemplateLite extends TemplateHtml {}

class TemplateJs extends OTemplate {
    protected $returnTagType = 'js';
    function escape($context, $in) {
        return Tht::module('Js')->escape($in);
    }
}

class TemplateCss extends OTemplate {
    protected $returnTagType = 'css';
    function escape($context, $in) {
        return Tht::module('Css')->escape($in);
    }
}

class TemplateJcon extends OTemplate {
    protected $returnTagType = '';

    function escape($context, $in) {
        if (is_bool($in)) {
            return $in ? 'true' : 'false';
        } else if (is_numeric($in) || is_string($in)) {
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
    protected $returnTagType = '';

    function postProcess($s) {
        return $s;
    }
}


