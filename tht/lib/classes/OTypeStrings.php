<?php

namespace o;

class JconTypeString extends OTypeString {  protected $type = 'jcon';  }

class HtmlTypeString extends OTypeString {
    protected $type = 'html';
    // protected function u_z_escape_param($v) {
    //     return Security::escapeHtml($v);
    // }
    function u_fill($params) {
        Tht::error('(Security) HtmlTypeString with placeholders not supported.  Try: `-Html` template function, or `Web.link`.');
    }
}

class JsTypeString extends OTypeString {
    protected $type = 'js';
    protected function u_z_escape_param($v) {
        return Tht::module('Js')->escape($v);
    }
}

class CssTypeString extends OTypeString {
    protected $type = 'css';
    protected function u_z_escape_param($v) {
        return Tht::module('Css')->escape($v);
    }
}

class SqlTypeString extends OTypeString {
    protected $type = 'sql';
    protected function u_z_escape_param($v) {
        Tht::error('SQL escaping must be handled internally.');
    }
    function u_stringify() {
        Tht::error('SqlTypeStrings can only be stringified internally, by the `Db` module.');
    }
}

class CmdTypeString extends OTypeString {
    protected $type = 'cmd';
    protected function u_z_escape_param($v) {
        return escapeshellarg($v);
    }
}

class PlainTypeString  extends OTypeString {
    protected $type = 'plain';
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

