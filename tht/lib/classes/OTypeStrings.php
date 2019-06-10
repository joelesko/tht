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
    //     Tht::error('(Security) HtmlTypeString with placeholders not supported.  Try: `-Html` template function, or `Web.link`.');
    // }
}

class JsTypeString extends OTypeString {
    protected $stringType = 'js';
    protected function u_z_escape_param($v) {
        return Tht::module('Js')->escape($v);
    }
}

class CssTypeString extends OTypeString {
    protected $stringType = 'css';
    protected function u_z_escape_param($v) {
        return Tht::module('Css')->escape($v);
    }
}

class SqlTypeString extends OTypeString {
    protected $stringType = 'sql';
    protected function u_z_escape_param($v) {
        Tht::error('SQL escaping must be handled internally.');
    }
    function u_stringify() {
        Tht::error('SqlTypeStrings can only be stringified internally, by the `Db` module.');
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

// Relying on File module security measures instead.
// class FileTypeString extends OTypeString {
//     protected $type = 'file';
//     protected function u_z_escape_param($v) {
//         return preg_replace('/[^A-Za-z0-9_]/', '_', $v);
//     }
// }

