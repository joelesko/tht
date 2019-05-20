<?php

namespace o;

class JconTagString extends OTagString {  protected $type = 'jcon';  }

class HtmlTagString extends OTagString {
    protected $type = 'html';
    // protected function u_z_escape_param($v) {
    //     return Security::escapeHtml($v);
    // }
    function u_fill($params) {
        Tht::error('(Security) HtmlTagString with placeholders not supported.  Try: `-Html` template function, or `Web.link`.');
    }
}

class JsTagString extends OTagString {
    protected $type = 'js';
    protected function u_z_escape_param($v) {
        return Tht::module('Js')->escape($v);
    }
}

class CssTagString extends OTagString {
    protected $type = 'css';
    protected function u_z_escape_param($v) {
        return Tht::module('Css')->escape($v);
    }
}

class SqlTagString extends OTagString {
    protected $type = 'sql';
    protected function u_z_escape_param($v) {
        Tht::error('SQL escaping must be handled internally.');
    }
    function u_stringify() {
        Tht::error('SqlTagStrings can only be stringified internally, by the `Db` module.');
    }
}

class CmdTagString extends OTagString {
    protected $type = 'cmd';
    protected function u_z_escape_param($v) {
        return escapeshellarg($v);
    }
}

class PlainTagString  extends OTagString {
    protected $type = 'plain';
    protected function u_z_escape_param($k) {
        return $k;
    }
}

// Relying on File module security measures instead.
// class FileTagString extends OTagString {
//     protected $type = 'file';
//     protected function u_z_escape_param($v) {
//         return preg_replace('/[^A-Za-z0-9_]/', '_', $v);
//     }
// }

