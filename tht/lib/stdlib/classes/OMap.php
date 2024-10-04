<?php

namespace o;

class OMap extends OBag implements \ArrayAccess {

    protected $type = 'map';

    protected $suggestMethod = [
        'len'     => 'length()',
        'count'   => 'length()',
        'size'    => 'length()',
        'delete'  => 'remove()',
    ];

    function offsetGet($k):mixed {

        $k = $this->checkOffsetKey($k);

        if (!array_key_exists($k, $this->val)) {
            // soft get
            return $this->getDefault($k);
        }
        else {
            return $this->val[$k];
        }
    }

    function offsetSet($ak, $v):void {

        $k = $this->checkOffsetKey($ak);
        $this->val[$k] = $v;
    }

    function offsetExists($k):bool {

        $k = $this->checkOffsetKey($k);

        return array_key_exists($k, $this->val);
    }

    function offsetUnset($k):void {

        $k = $this->checkOffsetKey($k);

        unset($this->val[$k]);
    }

    function checkOffsetKey($k) {

        if (!is_string($k)) {
            if (is_int($k) || is_float($k)) {
                $k = '' . $k;
            }
            else if (is_null($k)) {
                $this->error("Can't use List append `#=` on Map.");
            }
            else {
                $this->error("Can't use non-String as a Map key.  Got: `" . v($k)->bareClassName() . '`');
            }
        }

        return $k;
    }

    public function jsonSerialize():mixed {

        if (!count($this->val)) {
            return '{EMPTY_MAP}';
        }

        return $this->val;
    }

    function __construct($ary = []) {
        $this->val = $ary;
    }

    static function create($ary) {

        // Have to check when casting incoming function arguments from php arrays.
        if (OMap::isa($ary)) {
            return $ary;
        }

        return new OMap ($ary);
    }

    // TODO: Lot of duplication with OList.  Maybe refactor this outside of these objects.
    static function createFromArg($fnName, $obj) {

        if (self::isa($obj)) {
            return $obj;
        }

        if (!is_array($obj)) {
            Tht::error("Function `$fnName` expects a Map argument.  Got: `" . v($obj)->u_type() . "`");
        }

        return self::create($obj);
    }

    function getDefault($k) {
        return is_null($this->default) ? NULL_NOTFOUND : $this->default;
    }

    function u_equals($otherMap) {
        $this->ARGS('*', func_get_args());
        if (!OMap::isa($otherMap)) {
            return false;
        }

        $otherMap = unv($otherMap);

        // Same number of keys
        if (count($this->val) !== count($otherMap)) {
            return false;
        }

        // All keys are the same
        foreach ($otherMap as $k => $v) {
            if (!isset($this->val[$k])) {
                return false;
            }

            // TODO: handle nested maps
            if (unv($this->val[$k]) !== unv($v)) {
                return false;
            }
        }

        return true;
    }

    function u_clear() {

        $this->ARGS('', func_get_args());

        $this->val = OMap::create([]);

        return $this;
    }

    function u_copy($flags = null) {

        $this->ARGS('m', func_get_args());

        $flags = $this->flags($flags, [
            'refs' => false,
        ]);

        // php apparently copies the array when assigned to a new var
        $a = $this->val;

        if (!$flags['refs']) {
            foreach ($a as $k => $el) {
                if (OBag::isa($el)) {
                    $a[$k] = $el->u_copy();
                }
            }
        }

        return OMap::create($a);
    }

    function u_is_empty() {
        $this->ARGS('', func_get_args());
        return count(unv($this->val)) === 0;
    }

    function u_remove($k) {

        $this->ARGS('S', func_get_args());

        $v = isset($this->val[$k]) ? $this->val[$k] : null;
        unset($this->val[$k]);
        if (is_null($v)) {
            return $this->getDefault($k);
        }
        return $v;
    }

    function u_has_key($key) {
        $this->ARGS('S', func_get_args());
        return isset($this->val[$key]);
    }

    function u_has_value($value) {
        $this->ARGS('s', func_get_args());
        return array_search($value, $this->val, true) !== false;
    }

    function u_keys_for_value($value) {

        $this->ARGS('*', func_get_args());

        $keys = [];
        foreach ($this->val as $k => $v) {
            if ($value === $v) {
                $keys []= $k;
            }
        }

        return OList::create($keys);
    }

    function u_values() {
        $this->ARGS('', func_get_args());
        return OList::create(array_values($this->val));
    }

    function u_keys() {
        $this->ARGS('', func_get_args());
        return OList::create(array_keys($this->val));
    }

    function u_to_list() {
        $this->ARGS('', func_get_args());
        $out = [];
        foreach ($this->val as $k => $v) {
            $out []= $k;
            $out []= $v;
        }
        return OList::create($out);
    }

    function u_reverse() {

        $this->ARGS('', func_get_args());

        return OMap::create(array_flip($this->val));
    }

    function u_merge($other, $flags = null) {

        $this->ARGS('mm', func_get_args());

        $flags = $this->flags($flags, [
            'deep' => false,
        ]);

        $isDeep = $flags['deep'];

        $merged = $this->u_copy();
        foreach ($other as $k => $ov) {
            if (OMap::isa($merged[$k]) && $isDeep) {
                if (OMap::isa($other)) {
                    $merged[$k] = $merged[$k]->u_merge($ov, $flags);
                }
            } else {
                $merged[$k] = $ov;
            }
        }

        return $merged;
    }

    function u_slice($keys) {
        $this->ARGS('l', func_get_args());
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = $this[$k];
        }
        return OMap::create($out);
    }

    function u_default($d) {

        $this->ARGS('*', func_get_args());

        $this->default = $d;

        return $this;
    }

    function u_length() {

        $this->ARGS('', func_get_args());

        return parent::u_length();
    }

    function u_rename_key($origKey, $newKey) {

        $this->ARGS('ss', func_get_args());

        $v = $this->u_remove($origKey);
        $this->val[$newKey] = $v;

        return $this;
    }

    function u_check($schema) {

        $this->ARGS('m', func_get_args());

        // Check for invalid keys
        foreach ($this->val as $k => $v) {
            if (!isset($schema[$k])) {
                $okKeys = implode(', ', array_keys(unv($schema)));
                $okKeys = preg_replace('/([a-zA-Z0-9]+)/', '`$1`', $okKeys);
                $this->error("Invalid option map key: `$k`  Try: $okKeys");
            }
        }

        foreach ($schema as $k => $defaultVal) {

            // Enum for strings: val1|val2
            $enums = '';
            if (is_string($defaultVal) && str_contains($defaultVal, '|')) {
                $enums = preg_split('/\s*\|\s*/', $defaultVal);
                $defaultVal = $enums[0];
            }

            if (!isset($this->val[$k])) {

                // Internal only
                if (is_null($defaultVal)) {
                    $this->error("Missing required key in option map: `" . $k . '`');
                }

                $this->val[$k] = $defaultVal;
            }
            else {
                // Derive required type from default value
                $okType = v($defaultVal)->u_type();
                $gotType = v($this->val[$k])->u_type();

                if ($okType == 'boolean' && $gotType == 'string' && $k === $this->val[$k]) {
                    // Allow { someFlag }
                    $this->val[$k] = true;
                }
                else if ($okType == $gotType) {
                    $this->val[$k] = $this->val[$k];
                }
                else {
                    $this->error("Option value `$k` must be of type: `$okType`  Got: `$gotType`");
                }

                $v = $this->val[$k];

                if ($enums && !in_array($v, $enums)) {
                    $tryEnums = array_map(function($a){ return "`" . $a . "`"; }, $enums);
                    $this->error("Invalid option map value for key: `$k`  Got: `" . $v . "`  Try: " . implode(', ', $tryEnums));
                }
            }
        }

        return $this;
    }

    // TODO: integrate with OList.sort?
    // function u_sort() {

    // }
}

