<?php

namespace o;

abstract class OTagString extends OVar {

    protected $str = '';
    protected $type = 'text';
    protected $bindParams = [];
    protected $overrideParams = [];
    protected $appendedTagStrings = [];

    function __construct ($s) {
        if (OTagString::isa($s)) {
            $s = $s->getString();
        }
        $this->str = $s;
    }

    function __toString() {
        $maxLen = 30;
        $len = strlen($this->str);
        $s = substr(trim($this->str), 0, $maxLen);
        if ($len > $maxLen) {
            $s .= '…';
        }
        // This format is recognized by the Json formatter
        return "<<<TagString = $s>>>";
    }

    function jsonSerialize() {
        return $this->__toString();
    }

    static function concat($a, $b) {
        return $a->appendTagString($b);
    }

    static function create ($tagType, $s) {
        $nsClassName = '\\o\\' . ucfirst($tagType) . 'TagString';
        if (!class_exists($nsClassName)) {
            Tht::error("TagString of type `$nsClassName` not supported.");
        }
        return new $nsClassName ($s);
    }

    static function getUntagged ($s, $type) {
        if (!OTagString::isa($s)) {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
            Tht::error("`$caller` must be passed a TagString.  Ex: `$type'...'`");
        }
        return self::_getUntagged($s, $type, false);
    }

    static function getUntaggedRaw ($s, $type) {
        if (!OTagString::isa($s)) {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
            Tht::error("`$caller` must be passed a TagString.  Ex: `$type'...'`");
        }
        return self::_getUntagged($s, $type, true);
    }

    private static function _getUntagged ($s, $type, $getRaw) {
        if ($type && $s->type !== $type) {
            Tht::error("TagString must be of type `$type`. Got: `$s->type`");
        }
        return $getRaw ? $s->u_raw_string() : $s->u_stringify();
    }

    static function getUntaggedNoError ($s) {
        if (!OTagString::isa($s)) {
            return $s;
        }
        return $s->u_raw_string();
    }

    private function escapeParams() {

        $escParams = [];
        foreach($this->bindParams as $k => $v) {
            if (OTagString::isa($v)) {
                $plain = $v->u_stringify();
                if ($v->u_lock_type() === $this->u_lock_type()) {
                    // If same lock type, don't escape
                    $escParams[$k] = $plain;
                } else {
                    $escParams[$k] = $this->u_z_escape_param($plain);
                }
            }
            else if ($this->overrideParams) {
                if (isset($this->overrideParams[$k])) {
                    Tht::error("Must provide an update value for key `$k`.");
                }
                $escParams[$k] = $this->overrideParams[$k];
            }
            else {
                $escParams[$k] = $this->u_z_escape_param($v);
            }
        }
        $escParams = OMap::isa($this->bindParams)
            ? OMap::create($escParams)
            : OList::create($escParams);

        return $escParams;
    }

    function u_is_tag_string () {
        ARGS('', func_get_args());
        return true;
    }

    // TODO: support other TagString types & regular strings
    function appendTagString($l) {
        $t1 = $this->u_lock_type();
        $t2 = $l->u_lock_type();
        if ($t1 !== $t2) {
            Tht::error("Can only append TagStrings of the same type. Got: `$t1` and `$t2`");
        }
        $this->str .= $l->u_raw_string();
        return $this;
    }

    // override
    protected function u_z_escape_param($k) {
        return $k;
    }

    function u_stringify () {

        ARGS('', func_get_args());
        $out = $this->str;

        if (count($this->bindParams)) {
            $escParams = $this->escapeParams();
            $out = v($this->str)->u_fill($escParams);
        }

        if (count($this->appendedTagStrings)) {
            $num = 0;
            foreach ($this->appendedTagStrings as $s) {
                $us = $s->u_stringify();
                $out = str_replace("[LOCK_STRING_$num]", $us, $out);
                $num += 1;
            }
        }

        return $out;
    }

    function u_fill ($params) {
        if (!OList::isa($params) && !OMap::isa($params)) {
            $params = OList::create(func_get_args());
        }
        $this->bindParams = $params;
        return $this;
    }

    function u_raw_string () {
        ARGS('', func_get_args());
        return $this->str;
    }

    function u_params () {
        ARGS('', func_get_args());
        return $this->bindParams;
    }

    function u_lock_type() {
        ARGS('', func_get_args());
        return $this->type;
    }

    // Allow user to provide pre-escaped params
    function u_danger_danger_override_params($overrideParams=[]) {
        $this->overrideParams = $overrideParams;
    }
}

class JconTagString extends OTagString {  protected $type = 'jcon';  }

class HtmlTagString extends OTagString {
    protected $type = 'html';
    protected function u_z_escape_param($v) {
        return htmlspecialchars($v);
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
        Tht::error('SqlTagStrings may only be stringified internally.');
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
        return $this->bindParams[$k];
    }
}

// Relying on File module security measures instead.
// class FileTagString extends OTagString {
//     protected $type = 'file';
//     protected function u_z_escape_param($v) {
//         return preg_replace('/[^A-Za-z0-9_]/', '_', $v);
//     }
// }

class UrlTagString extends OTagString {
    protected $type = 'url';
    private $query = null;
    private $hash = '';
    private $parts = null;
    protected function u_z_escape_param($v) {
        return urlencode($v);
    }

    function __construct($s) {
        $s = $this->init($s);
        parent::__construct($s);
    }

    function init($s) {

        if (preg_match('!\?.*\{.*\}!', $s)) {
            Tht::error("UrlTagString should use `query()` for dynamic queries.  Try: `url'/my-page'.query({ foo: 123 }))`");
        }

        $u = Security::parseUrl($s);

        // move hash to internal data
        if (isset($u['hash'])) {
            $this->hash = $u['hash'];
            unset($u['hash']);
        }
        $s = preg_replace('!#.*!', '', $s);

        // move query to internal data
        if (isset($u['query'])) {
            $this->query = $this->parseQuery($u['query']);
            unset($u['query']);
        }
        $s = preg_replace('!\?.*!', '', $s);

        $this->parts = OMap::create($u);

        return $s;
    }

    function u_parts() {
        return $this->parts;
    }

    function u_query($q) {
        ARGS('m', func_get_args());
        foreach ($q as $k => $v) {
            if ($v === '') {
                unset($this->query[$k]);
            } else {
                $this->query[$k] = $v;
            }
        }
        return $this;
    }

    function u_hash($h = null) {
        if (is_null($h)) {
            return $this->hash;
        }
        $h = Security::sanitizeHash($h);
        $this->hash = $h;
        return $this;
    }

    function u_clear_query() {
        ARGS('', func_get_args());
        $this->query = null;
        return $this;
    }

    function u_danger_danger_raw_query() {
        ARGS('', func_get_args());
        return OMap::create($this->query);
    }

    function u_stringify () {
        ARGS('', func_get_args());

        $s = parent::u_stringify();

        if (!is_null($this->query)) {
            $s .= Security::stringifyQuery($this->query);
        }

        // add hash
        if ($this->hash) {
            $s .= '#' . $this->hash;
        }

        return $s;
    }

    function u_link($label) {
        ARGS('s', func_get_args());
        return Tht::module('Web')->u_link($this, $label);
    }

    function parseQuery ($s, $multiKeys=[]) {

        $ary = [];
        $pairs = explode('&', $s);

        foreach ($pairs as $i) {
            list($name, $value) = explode('=', $i, 2);
            if (in_array($name, $multiKeys)) {
                if (!isset($ary[$name])) {
                    $ary[$name] = OList::create([]);
                }
                $ary[$name] []= $value;
            }
            else {
                $ary[$name] = $value;
            }
        }
        return $ary;
    }
}




