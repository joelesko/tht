<?php

namespace o;

class OTemplate {
    protected $chunks = [];
    protected $returnClass = 'OLockString';

    function getString() {
        $str = '';
        foreach ($this->chunks as $c) {
            $str .= $c['type'] === 'static' ? $c['body'] : $this->handleDynamic($c['body']);
        }
        $str = $this->postProcess($str);
        if (is_string($str)) {
            $str = v($str)->u_trim_indent() . "\n";
        }

        if ($this->returnClass) {
            $retClass = 'o\\' . $this->returnClass;
            return OLockString::create($retClass, $str);
        } else {
            return $str;
        }

    }

    function addStatic($s) {
        $this->chunks []= ['type' => 'static', 'body' => $s];
    }

    function addDynamic ($s) {
        $this->chunks []= ['type' => 'dynamic', 'body' => $s];
    }

    function handleDynamic($in) {

        if (OList::isa($in)) {
            $out = '';
            foreach ($in as $chunk) {
                $out .= $this->handleDynamic($chunk);
            }
            return $out;
        }
        else if (OLockString::isa($in)) {
            return $this->handleLockString($in);
        }
        else {
            return $this->escape($in);
        }
    }

    function escape($in) {
        return $in;
    }

    function postProcess($s) {
        return $s;
    }

    function handleLockString($s) {
        return OLockString::getUnlocked($s);
    }
}


////////// TYPES //////////



class TemplateHtml extends OTemplate {
    protected $returnClass = 'HtmlLockString';

    function escape($in) {
        // TODO: if in tag, wrap in quotes
        return htmlspecialchars($in, ENT_QUOTES, 'UTF-8');
    }

    function handleLockString($s) {
        // if js or css, wrap in appropriate block tags
        $cls = get_class($s);

        $unlocked = OLockString::getUnlocked($s);

        if ($cls === 'o\\CssLockString') {
            $nonce = Owl::data('cspNonce');
            return "<style nonce=\"$nonce\">" . $unlocked . "</style>\n";
        }
        if ($cls === 'o\\JsLockString') {
            $nonce = Owl::data('cspNonce');
            return "<script nonce=\"$nonce\">\n(function(){\n" . $unlocked . "\n})();\n</script>\n";
        }

        return $unlocked;
    }
}


class TemplateJs extends OTemplate {
    protected $returnClass = 'JsLockString';

    function escape($in) {
        if (is_bool($in)) {
            return $in ? 'true' : 'false';
        } else if (is_object($in)) {
            return json_encode($in->val);
        } else if (is_array($in)) {
            return json_encode($in);
        } else if (is_string($in)) {
            $in = str_replace('"', '\\"', $in);
            $in = str_replace("\n", '\\n', $in);
            return "\"$in\"";
        } else if (is_numeric($in)){
            return $in;
        } else {
            Owl::error('Unable to escape expression in JS template', $in);
        }
    }
}

class TemplateCss extends OTemplate {
    protected $returnClass = 'CssLockString';

    function escape($in) {
        $in = preg_replace('/[;\{\}]/', '', $in);
        return $in;
    }
}


class TemplateLite extends TemplateHtml {}

class TemplateJcon extends OTemplate {
    protected $returnClass = '';

    function escape($in) {
        if (is_bool($in)) {
            return $in ? 'true' : 'false';
        } else if (is_numeric($in) || is_string($in)) {
            $in = str_replace("\n", '\\n', $in);
            return $in;
        } else {
            Owl::error('Unable to escape expression in Jcon template.  Only Flags, Numbers, and Strings are supported.', $in);
        }
    }

    function postProcess($s) {
        return Owl::module('Jcon')->u_parse($s);
    }
}

class TemplateText extends OTemplate {
    protected $returnClass = '';

    function postProcess($s) {
        return $s;
    }
}






