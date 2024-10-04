<?php

namespace o;

// isrelative, isabsolute, torelative, to absolute?,
// origin = base? (non-path part)

// https://nodejs.org/api/url.html#url_url_strings_and_url_objects
// https://docs.oracle.com/javase/7/docs/api/java/net/URI.html

class UrlQuery extends OClass {

    private $query = null;  // Map

    private $origQueryString = '';
    private $isParsed = false;

    function __construct($query) {

        if (is_string($query)) {
            $this->origQueryString = $query;
        }
        else {
            $this->query = $query;
        }
    }

    // Don't parse unless necessary.
    // This allows non-strict literal strings (e.g. Google Font URLs that have duplicate keys)
    function initQuery() {

        if (is_null($this->query)) {
            $this->query = $this->parseQuery($this->origQueryString);
            $this->isParsed = true;
        }
    }

    function parseQuery($s, $multiKeys=[]) {

        $ary = [];
        $pairs = explode('&', $s);

        foreach ($pairs as $i) {
            $parts = explode('=', $i, 2);
            if (count($parts) > 1) {
                list($name, $value) = $parts;
            }
            else {
                $name = $parts[0];
                $value = '';
            }

            $value = urldecode($value);

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

    function u_set($q) {

        $this->ARGS('*', func_get_args());

        return $this->set($q, false);
    }

    function u_set_default($q) {

        $this->ARGS('*', func_get_args());

        return $this->set($q, true);
    }

    function set($q, $isDefault) {

        $this->initQuery();

        $qClass = get_class($q);

        if ($qClass == 'o\UrlTypeString') {
            $q = $q->u_get_query()->u_x_danger_get_all();
        }
        else if ($qClass == 'o\UrlQuery') {
            $q = $q->u_x_danger_get_all();
        }

        foreach ($q as $k => $v) {
            if ($v === '') {
                unset($this->query[$k]);
            }
            else {
                if (!$isDefault || !isset($this->query[$k])) {
                    $this->query[$k] = v($v)->u_to_string();
                }
            }
        }

        return $this;
    }

    function u_get($name, $rule='id') {

        $this->ARGS('ss', func_get_args());

        $this->initQuery();

        $rawVal = isset($this->query[$name]) ? $this->query[$name] : '';

        $validator = new u_InputValidator ();
        $validated = $validator->validateField($name, $rawVal, $rule);

        return $validated['value'];
    }

    function u_get_all($rulesMap) {

        $this->ARGS('m', func_get_args());

        $this->initQuery();

        $validator = new u_InputValidator ();
        $validated = $validator->validateFields($this->query, $rulesMap);

        return $validated;
    }

    function u_get_names() {

        $this->ARGS('', func_get_args());

        $this->initQuery();

        return OList::create(array_keys($this->query));
    }

    function u_has($key) {

        $this->ARGS('s', func_get_args());

        $this->initQuery();

        return isset($this->query[$key]);
    }

    function u_x_danger_get_all() {

        $this->ARGS('', func_get_args());

        $this->initQuery();

        return OMap::create($this->query);
    }

    function u_render_string() {

        $this->ARGS('', func_get_args());

        if ($this->origQueryString && !$this->isParsed) {
            return '?' . $this->origQueryString;
        }

        return Security::stringifyQuery($this->query);
    }

    function u_delete($key) {

        $this->ARGS('s', func_get_args());

        $this->initQuery();

        unset($this->query[$key]);

        return $this;
    }

    function u_keep($keepKeys) {

        $this->ARGS('l', func_get_args());

        $this->initQuery();

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
