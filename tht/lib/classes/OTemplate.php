<?php

namespace o;

class OTemplate {
    protected $chunks = [];
    protected $returnLockType = '_';

    function getString() {
        $str = '';
        foreach ($this->chunks as $c) {
            $str .= $c['type'] === 'static' ? $c['body'] : $this->handleDynamic($c['context'], $c['body']);
        }
        $str = $this->postProcess($str);
        if (is_string($str)) {
            $str = v($str)->u_trim_indent() . "\n";
        }

        if ($this->returnLockType) {
            return OLockString::create($this->returnLockType, $str);
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

        if (OList::isa($in)) {
            $out = '';
            foreach ($in as $chunk) {
                $out .= $this->handleDynamic($context, $chunk);
            }
            return $out;
        }
        else if (OLockString::isa($in)) {
            return $this->handleLockString($context, $in);
        }
        else {
            return $this->escape($context, $in);
        }
    }

    function escape($context, $in) {
        return $in;
    }

    function postProcess($s) {
        return $s;
    }

    function handleLockString($context, $s) {
        $plain = OLockString::getUnlocked($s, '');
        if ($s->u_lock_type() == $this->returnLockType) {
            return $plain;
        }
        else {
            return $this->escape($context, $plain);
        }
    }
}



////////// TYPES //////////

class TemplateLite extends TemplateHtml {}

class TemplateHtml extends OTemplate {
    protected $returnLockType = 'html';

    function escape($context, $in) {
        $esc = htmlspecialchars($in, ENT_QUOTES|ENT_HTML5, 'UTF-8');
        if ($context == 'tag') {
            $alpha = preg_replace('/[^a-z:]/', '', strtolower($in));
            if (strpos($alpha, 'javascript:') !== false) {
                $esc = '(REMOVED:UNSAFE_URL)';
            }
            $esc = '"' . $esc . '"';
        }
        return $esc;
    }

    function handleLockString($context, $s) {

        // if js or css, wrap in appropriate block tags
        $unlocked = OLockString::getUnlocked($s, '');

        $type = $s->u_lock_type();
        if ($type == 'html') {
            return $unlocked;
        }
        else if ($type == 'css') {
            $nonce = Tht::module('Web')->u_nonce();
            return "<style nonce=\"$nonce\">" . Tht::module('Css')->u_minify($unlocked) . "</style>\n";
        }
        else if ($type == 'js') {
            $nonce = Tht::module('Web')->u_nonce();
            return "<script nonce=\"$nonce\">\n(function(){\n" . Tht::module('Js')->u_minify($unlocked) . "\n})();\n</script>\n";
        }

        return $this->escape($context, $unlocked);
    }
}

class TemplateJs extends OTemplate {
    protected $returnLockType = 'js';
    function escape($context, $in) {
        return Tht::module('Js')->escape($in);
    }
}

class TemplateCss extends OTemplate {
    protected $returnLockType = 'css';
    function escape($context, $in) {
        return Tht::module('Css')->escape($in);
    }
}

class TemplateJcon extends OTemplate {
    protected $returnLockType = '';

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
    protected $returnLockType = '';

    function postProcess($s) {
        return $s;
    }
}


