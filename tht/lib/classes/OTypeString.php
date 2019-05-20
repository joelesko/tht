<?php

namespace o;

abstract class OTypeString extends OVar {

    protected $str = '';
    protected $type = 'text';
    protected $bindParams = [];
    protected $overrideParams = [];
    protected $appendedTypeStrings = [];

    function __construct ($s) {
        if (OTypeString::isa($s)) {
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
        return "<<<TypeString = $s>>>";
    }

    function jsonSerialize() {
        return $this->__toString();
    }

    static function concat($a, $b) {
        return $a->appendTypeString($b);
    }

    static function create ($tagType, $s) {
        $nsClassName = '\\o\\' . ucfirst($tagType) . 'TypeString';
        if (!class_exists($nsClassName)) {
            Tht::error("TypeString of type `$nsClassName` not supported.");
        }
        return new $nsClassName ($s);
    }

    static function getUntagged ($s, $type) {
        if (!OTypeString::isa($s)) {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
            Tht::error("`$caller` must be passed a TypeString.  Ex: `$type'...'`");
        }
        return self::_getUntagged($s, $type, false);
    }

    static function getUntaggedRaw ($s, $type) {
        if (!OTypeString::isa($s)) {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
            Tht::error("`$caller` must be passed a TypeString.  Ex: `$type'...'`");
        }
        return self::_getUntagged($s, $type, true);
    }

    private static function _getUntagged ($s, $type, $getRaw) {
        if ($type && $s->type !== $type) {
            Tht::error("TypeString must be of type `$type`. Got: `$s->type`");
        }
        return $getRaw ? $s->u_raw_string() : $s->u_stringify();
    }

    static function getUntaggedNoError ($s) {
        if (!OTypeString::isa($s)) {
            return $s;
        }
        return $s->u_raw_string();
    }

    private function escapeParams() {

        $escParams = [];
        foreach($this->bindParams as $k => $v) {
            if (OTypeString::isa($v)) {
                $plain = $v->u_stringify();
                if ($v->u_tag_type() === $this->u_tag_type()) {
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

    // TODO: support other TypeString types & regular strings
    function appendTypeString($l) {
        $t1 = $this->u_tag_type();
        $t2 = $l->u_tag_type();
        if ($t1 !== $t2) {
            Tht::error("Can only append TypeStrings of the same type. Got: `$t1` and `$t2`");
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

        if (count($this->appendedTypeStrings)) {
            $num = 0;
            foreach ($this->appendedTypeStrings as $s) {
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

    function u_tag_type() {
        ARGS('', func_get_args());
        return $this->type;
    }

    // Allow user to provide pre-escaped params
    function u_danger_danger_override_params($overrideParams=[]) {
        $this->overrideParams = $overrideParams;
    }
}

