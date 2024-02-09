<?php

namespace o;

class JconTypeString extends OTypeString {

    protected $stringType = 'jcon';
}

class HtmlTypeString extends OTypeString {

    protected $stringType = 'html';

    protected function u_z_escape_param($v) {
        return Security::escapeHtml($v);
    }
    // TODO: Security - review how this compares to security in HtmlTemplateTransformer (inTag context)
    // function u_fill($params) {
    //     $this->error('(Security) HtmlTypeString with placeholders not supported.  Try: `-Html` template function, or `Web.link`.');
    // }
}

class JsTypeString extends OTypeString {

    protected $stringType = 'js';

    protected function u_z_escape_param($v) {

        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        else if (is_object($v)) {
            return json_encode($v->val);
        }
        else if (is_array($v)) {
            return json_encode($v);
        }
        else if (vIsNumber($v)) {
            return $v;
        }
        else {
            $v = '' . $v;
            $v = str_replace('"', '\\"', $v);
            $v = str_replace("\n", '\\n', $v);
            return "\"$v\"";
        }
    }
}

class CssTypeString extends OTypeString {

    protected $stringType = 'css';

    protected function u_z_escape_param($v) {
        return Tht::module('Output')->escapeCss($v);
    }
}

class SqlTypeString extends OTypeString {

    protected $stringType = 'sql';

    protected function u_z_escape_param($v) {
        $this->error('SQL escaping must be handled internally.');
    }

    // This is only used for debugging. We rely on PDO to do escaping, etc.
    function u_render_string() {
        return $this->str;
    }
}

class CmdTypeString extends OTypeString {

    protected $stringType = 'cmd';

    protected function u_z_escape_param($v) {
        return escapeshellarg($v);
    }
}

class PlainTypeString  extends OTypeString {

    protected $stringType = 'plain';

    protected function u_z_escape_param($k) {
        return $k;
    }
}

class LmTypeString  extends OTypeString {

    protected $stringType = 'lm';

    protected function u_z_escape_param($k) {
        return $k;
    }
}

class JsonTypeString  extends OTypeString {

    protected $stringType = 'json';

    protected function u_z_escape_param($k) {
        $this->error('JSON params should be added to the data before being converted to a String.');
    }
}

// Relying on File module security measures instead.
// class FileTypeString extends OTypeString {
//     protected $type = 'file';
//     protected function u_z_escape_param($v) {
//         return preg_replace('/[^A-Za-z0-9_]/', '_', $v);
//     }
// }

