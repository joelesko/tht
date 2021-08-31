<?php

namespace o;

class OMap extends OBag {

    protected $type = 'map';

    protected $suggestMethod = [
        'count'   => 'length()',
        'size'    => 'length()',
        'empty'   => 'isEmpty()',
        'delete'  => 'remove()',
    ];

    public function jsonSerialize() {
        if (!count($this->val)) {
            return '{EMPTY_MAP}';
        }
        return $this->val;
    }

    static function create ($ary) {
        $m = new OMap ();
        $m->setVal($ary);
        // if (count($ary)) {
        //     $m->u_lock_keys(true);
        // }
        return $m;
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

    function u_copy($useReferences = false) {
        $this->ARGS('b', func_get_args());

        // php apparently copies the array when assigned to a new var
        $a = $this->val;

        if (!$useReferences) {
            foreach ($a as $k => $el) {
                if (OBag::isa($el)) {
                    $a[$k] = $el->u_copy(false);
                }
            }
        }

        return OMap::create($a);
    }

    function u_is_empty () {
        $this->ARGS('', func_get_args());
        return count(unv($this->val)) === 0;
    }

    function u_remove ($k) {
        $this->ARGS('s', func_get_args());
        $v = isset($this->val[$k]) ? $this->val[$k] : null;
        unset($this->val[$k]);
        if (is_null($v)) {
            if (isset($this->default)) {
                return $this->default;
            } else {
                return '';
            }
        }
        return $v;
    }

    function u_has_key ($key) {
        $this->ARGS('s', func_get_args());
        return isset($this->val[$key]);
    }

    function u_has_value ($value) {
        $this->ARGS('s', func_get_args());
        return array_search($value, $this->val, true) !== false;
    }

    function u_get_key ($value) {
        $this->ARGS('s', func_get_args());
        $found = array_search($value, $this->val, true);
        if ($found === false) {
            $v = v($value)->u_limit(20);
            $this->error("Map value not found: `$v`");
        }
    }

    function u_values () {
        $this->ARGS('', func_get_args());
        return OList::create(array_values($this->val));
    }

    function u_keys () {
        $this->ARGS('', func_get_args());
        return OList::create(array_keys($this->val));
    }

    function u_to_list () {
        $this->ARGS('', func_get_args());
        $out = [];
        foreach ($this->val as $k => $v) {
            $out []= $k;
            $out []= $v;
        }
        return OList::create($out);
    }

    function u_reverse () {
        $this->ARGS('', func_get_args());
        return OMap::create(array_flip($this->val));
    }

    function u_merge($other, $isSoft = false, $isDeep = false) {

        $this->ARGS('mbb', func_get_args());

        $merged = $this->u_copy();
        foreach ($other as $k => $ov) {
            if (OMap::isa($merged[$k]) && $isDeep) {
                if (OMap::isa($other)) {
                    $merged[$k] = $merged[$k]->u_merge($ov, $isSoft, false);
                }
            } else {
                if (!$isSoft || ($isSoft && !isset($merged[$k]))) {
                    $merged[$k] = $ov;
                }
            }
        }

        return $merged;
    }

    function u_slice ($keys) {
        $this->ARGS('l', func_get_args());
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = $this[$k];
        }
        return OMap::create($out);
    }

    // forwarding call so that errors are attributed to Map
    function u_default ($d) {

        $this->ARGS('*', func_get_args());

        return parent::u_default($d);
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

    function u_validate($rules, $context='Map') {

        foreach ($rules as $k => $rule) {
            $default = null;
            if (is_array($rule)) {
                $default = $rule[1];
                $rule = $rule[0];
                $rules[$k] = $rule;
            }
            if (!isset($this->val[$k])) {
                if (is_null($default)) {
                    $this->error("Missing required key in $context: `" . $k . '`');
                }
                $this->val[$k] = $default;
            }
        }

        foreach ($this->val as $k => $v) {
            $rule = isset($rules[$k]) ? $rules[$k] : '';
            if (!$rule) {
                $this->error("Unknown key in $context: `$k`");
            }

            $err = ARGS($rule, [$v]);
            if ($err) {
                $this->error("Parameter `" . $k . "` in $context must be type `" . $err['needType'] . "`.");
            }
        }
    }

    // TODO: integrate with OList.sort?
    // function u_sort() {

    // }
}

