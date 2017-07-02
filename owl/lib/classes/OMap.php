<?php

namespace o;

class OMap extends OBag {

    static function create ($ary) {
        $m = new OMap ();
        $m->setVal($ary);
        if (count($ary)) {
            $m->u_lock_keys(true);
        }
        return $m;
    }

    function u_clear() {
        $this->val = OMap::create([]);
        return $this->val;
    }

    function u_copy() {
        // php apparently copies the array when assigned to a new var
        $a = uv($this->val);
        return OMap::create($a);
    }

    function u_is_empty () {
        return count(uv($this->val)) === 0;
    }

    function u_remove ($k) {
        $v = isset($this->val[$k]) ? $this->val[$k] : null;
        unset($this->val[$k]);
        if (is_null($v)) {
            if (isset($this->default)) {
                return $this->default;
            } else {
                Owl::error("Map key not found: '$k'");
            }
        }
        return $v;
    }

    function u_has_key ($key) {
		return isset($this->val[$key]);
    }

    function u_has_value ($value) {
		return array_search($value, $this->val, true) !== false;
    }

    function u_get_key ($value) {
		$found = array_search($value, $this->val, true);
        if ($found === false) {
            $v = v($value)->u_limit(20);
            Owl::error("Map value not found: '$v'");
        }
    }

    function u_values () {
        return array_values($this->val);
    }

    function u_keys () {
        return array_keys($this->val);
    }

    function u_reverse () {
		return array_flip($this->val);
    }

    function u_merge ($a2) {
        return array_merge($this->val, $a2->val);
    }
}

