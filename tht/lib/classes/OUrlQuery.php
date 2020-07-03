<?php

namespace o;

// isrelative, isabsolute, torelative, to absolute?,
// origin = base? (non-path part)

// https://nodejs.org/api/url.html#url_url_strings_and_url_objects
// https://docs.oracle.com/javase/7/docs/api/java/net/URI.html

class UrlQuery extends OClass {

    private $query;

    function __construct($query) {
        if (is_string($query)) {
            $this->query = $this->parseQuery($query);
        }
        else {
            $this->query = $query;
        }
    }

    function parseQuery ($s, $multiKeys=[]) {

        $ary = [];
        $pairs = explode('&', $s);

        foreach ($pairs as $i) {
            $parts = explode('=', $i, 2);
            if (count($parts) > 1) {
                list($name, $value) = $parts;
            } else {
                $name = $parts[0];
                $value = '';
            }
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

    function u_set($q, $isSoft = false) {
        $this->ARGS('*f', func_get_args());

        $qClass = get_class($q);
        if ($qClass == 'o\UrlTypeString') {
            $q = $q->u_query()->u_x_danger_get_raw();
        } else if ($qClass == 'o\UrlQuery') {
            $q = $q->u_x_danger_get_raw();
        }

        foreach ($q as $k => $v) {
            if ($v === '') {
                unset($this->query[$k]);
            } else {
                if (!$isSoft || !isset($this->query[$k])) {
                    $this->query[$k] = $v;
                }
            }
        }

        return $this;
    }

    function u_get($name, $rule='id') {
        $this->ARGS('ss', func_get_args());

        $v = isset($this->query[$name]) ? $this->query[$name] : '';

        $validator = new u_InputValidator ();
        $validated = $validator->validateField($name, $v, $rule);

        return $validated['value'];
    }

    function u_get_all($rules=null) {
        // TODO
    }

    function u_fields() {
        $this->ARGS('', func_get_args());
        return OList::create(array_keys($this->query));
    }

    function u_has_field($key) {
        $this->ARGS('s', func_get_args());
        return isset($this->query[$key]);
    }

    function u_x_danger_get_raw() {
        $this->ARGS('', func_get_args());
        return OMap::create($this->query);
    }

    function u_stringify() {
        $this->ARGS('', func_get_args());
        return Security::stringifyQuery($this->query);
    }

    function u_keep($keepKeys) {
        $this->ARGS('l', func_get_args());
        $params = [];
        foreach($keepKeys as $k) {
            if (isset($this->query[$k])) {
                $params[$k] = $this->query[$k];
            }
        }
        $this->query = $params;

        return $this;
    }
}
