<?php

namespace o;

abstract class OTypeString extends OClass implements \JsonSerializable {

    protected $type = 'typeString';

    protected $suggestMethod = [
        'tostring'   => 'renderString()',
    ];

    protected $str = '';
    protected $stringType = 'text';
    protected $bindParams = [];
    protected $overrideParams = [];
    protected $appendedTypeStrings = [];

    function __construct($s) {

        if (OTypeString::isa($s)) {
            $s = $s->u_raw_string();
        }

        $this->str = $s;
    }

    function __toString() {
        return $this->toObjectString();
    }

    function u_to_string() {

        $this->ARGS('', func_get_args());

        return $this->toObjectString();
    }

    function u_append($otherTypeString) {

        $this->ARGS('*', func_get_args());

        OTypeString::assertType($otherTypeString, $this->stringType);

        return $this->appendTypeString($otherTypeString);
    }

    function toObjectString() {

        $c = preg_replace('/.*\\\\/', '', get_class($this));

        return OClass::getObjectString($c, $this->u_render_string(), true);
    }

    static function staticError($msg) {

        ErrorHandler::addOrigin('typeString');

        Tht::error($msg);
    }

    // Called from Emitter
    static function create($type, $s) {

        // TODO: this is not good
        if ($type == 'date') {
            return Tht::module('Date')->u_create($s);
        }

        $nsClassName = '\\o\\' . ucfirst($type) . 'TypeString';
        if (!class_exists($nsClassName)) {
            self::staticError("Unknown TypeString type: `$type`");
        }

        return new $nsClassName ($s);
    }

    static function assertType($s, $type) {
        if (!OTypeString::isa($s) || ($type && $s->stringType !== $type)) {
            $gotType = v($s)->u_z_class_name();
            $try = $gotType == 'string' ? "Try: `$type'...'`" : '';
            $msg = "must be passed a `$type` TypeString.  Got: `$gotType`  $try";
            Tht::callerError($msg);
        }
    }

    static function getUntyped($s, $type, $getRaw=false) {
        self::assertType($s, $type);
        return $getRaw ? $s->u_raw_string(true) : $s->u_render_string();
    }

    static function getUntypedNoError($s) {

        if (!OTypeString::isa($s)) {
            return $s;
        }

        return $s->u_render_string();
    }

    private function escapeParams() {

        $escParams = [];

        foreach(unv($this->bindParams) as $k => $v) {

            if (OTypeString::isa($v)) {
                $plain = $v->u_render_string();
                if ($v->u_string_type() === $this->u_string_type()) {
                    // If same lock type, don't escape
                    $escParams[$k] = $plain;
                } else {
                    $escParams[$k] = $this->u_z_escape_param($plain);
                }
            }
            else if ($this->overrideParams) {
                if (isset($this->overrideParams[$k])) {
                    $this->error("Must provide an update value for key: `$k`");
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

    // TODO: Allow any string, but escape ones that don't match
    function appendTypeString($l) {

        $t1 = $this->u_string_type();
        $t2 = $l->u_string_type();
        if ($t1 !== $t2) {
            $this->error("Can only append TypeStrings of the same type.  Got: `$t1` and `$t2`");
        }

        $this->appendedTypeStrings []= $l;

        return $this;
    }

    // override
    protected function u_z_escape_param($k) {

        return $k;
    }

    function u_render_string() {

        $this->ARGS('', func_get_args());
        $out = $this->str;

        if (count($this->bindParams)) {
            $escParams = $this->escapeParams();
            $out = v($this->str)->u_fill($escParams);
        }

        foreach ($this->appendedTypeStrings as $s) {
            $out .= $s->u_render_string();
        }

        return $out;
    }

    // Some consumers, like the Db module don't use renderString, so the
    // string and params must be pre-merged.
    function mergeAppendedStrings() {

        foreach ($this->appendedTypeStrings as $s) {
            $this->str .= ' ' . $s->u_raw_string();

            // TODO: this is probably not ideal, if mixing lists and maps
            $this->bindParams = OMap::create(
                array_merge(unv($this->bindParams), unv($s->u_params()))
            );
        }

        $this->appendedTypeStrings = [];

        return $this;
    }

    function u_fill($params) {

        if (!OList::isa($params) && !OMap::isa($params)) {
            $params = OList::create(func_get_args());
        }
        $this->bindParams = $params;

        return $this;
    }

    function u_raw_string($doMerge = false) {

        $this->ARGS('b', func_get_args());

        if ($doMerge) { $this->mergeAppendedStrings(); }

        return $this->str;
    }

    function u_params() {

        $this->ARGS('', func_get_args());

        return $this->bindParams ? $this->bindParams : OList::create([]);
    }

    function u_string_type() {

        $this->ARGS('', func_get_args());

        return $this->stringType;
    }

    // Allow user to provide pre-escaped params
    function u_x_danger_override_params($overrideParams) {

        $this->ARGS('*', func_get_args());

        $this->overrideParams = $overrideParams;

        return $this;
    }
}

