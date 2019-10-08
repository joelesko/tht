<?php

namespace o;

// isrelative, isabsolute, torelative, to absolute?,
// origin = base? (non-path part)

// https://nodejs.org/api/url.html#url_url_strings_and_url_objects
// https://docs.oracle.com/javase/7/docs/api/java/net/URI.html

class UrlTypeString extends OTypeString {

    protected $stringType = 'url';
    private $query = null;
    private $parts = null;
    private $host = '';
    private $origin = '';

    protected function u_z_escape_param($v) {
        return urlencode($v);
    }

    function __construct($sUrl) {
        $sUrl = $this->parse($sUrl);
        parent::__construct($sUrl);
    }

    function u_stringify() {
        $this->updateString();
        return parent::u_stringify();
    }

    function parse($sUrl) {

        if (preg_match('!\?.*\{.*\}!', $sUrl)) {
            $this->error("UrlTypeString should use `query()` for dynamic queries.  Try: `url'/my-page'.query({ foo: 123 }))`");
        }

        $u = Security::parseUrl($sUrl);

        // remove hash
        $sUrl = preg_replace('!#.*!', '', $sUrl);

        // move query to internal data
        if (isset($u['query'])) {
            $this->query = new UrlQuery ($u['query']);
            unset($u['query']);
        }
        $sUrl = preg_replace('!\?.*!', '', $sUrl);

        $this->parts = OMap::create($u);

        $this->updateOrigin();

        return $sUrl;
    }

    function updateOrigin() {
        $origin = '';
        if (isset($this->parts['host'])) {
            $origin = $this->parts['host'];

            $scheme = isset($this->parts['scheme']) ? $this->parts['scheme'] : 'https';
            $origin = $scheme . '://' . $origin;

            if (isset($this->parts['port']) && $this->parts['port'] !== 80 && $this->parts['port'] !== 443) {
                $origin .= ':' . $this->parts['port'];
            }
        }
        $this->origin = $origin;
        return $origin;
    }

    function updateString () {

        $this->str = $this->updateOrigin();

        $this->str .= '/';
        if (isset($this->parts['path'])) {
            $this->str .= ltrim($this->parts['path'], '/');
        }
        if ($this->query) {
            $this->str .= $this->query->u_stringify();
        }
        if (isset($this->parts['hash']) && $this->parts['hash']) {
            $this->str .= '#' . $this->parts['hash'];
        }
    }


    // Protected parts
    //---------------------------------

    function u_query($q=null) {
        $this->ARGS('*', func_get_args());

        // lazy init
        if (!$this->query) {
            $this->query = new UrlQuery ([]);
        }

        if ($q === null) {
            return $this->query;
        }
        else {
            $this->query->u_set($q);
            $this->updateString();
            return $this;
        }
    }

    function u_clear_query() {
        $this->query = new UrlQuery ([]);
        $this->updateString();
        return $this;
    }




    // Part getter/setters
    //----------------------------------

    function updatePart($p, $v) {
        if (is_null($v)) {
            return $this->parts[$p];
        } else {
            $this->parts[$p] = $v;
            $this->updateString();
            return $this;
        }
    }

    function u_hash($v = null) {
        $this->ARGS('s', func_get_args());
        if (!is_null($v)) {
            $v = Security::sanitizeUrlHash($v);
        }
        return $this->updatePart('hash', $v);
    }

    function u_host($v = null) {
        $this->ARGS('s', func_get_args());
        if (!is_null($v)) {
            $v = strtolower($v);
            $v = preg_replace('/[^a-z0-9\.\-_]/', '', $v);
        }
        return $this->updatePart('host', $v);
    }

    function u_port($v = null) {
        $this->ARGS('n', func_get_args());
        return $this->updatePart('port', $v);
    }

    function u_scheme($v = null) {
        $this->ARGS('s', func_get_args());
        if (!is_null($v)) {
            $v = strtolower($v);
            $v = preg_replace('/[^a-z]/', '', $v);
        }
        return $this->updatePart('scheme', $v);
    }

    function u_path($v = null) {
        $this->ARGS('s', func_get_args());
        if (!is_null($v)) {
            $v = preg_replace('#[^a-zA-Z0-9\-\.~/]#', '', $v);
        }
        return $this->updatePart('path', $v);
    }

    function u_path_parts($v = null) {
        $this->ARGS('s', func_get_args());
        if (!is_null($v)) {
            $v = preg_replace('#[^a-zA-Z0-9\-\.~/]#', '', $v);
        }
        return $this->updatePart('pathParts', $v);
    }

    function u_origin() {
        $this->ARGS('', func_get_args());
        return $this->origin;
    }


    // Utils
    //----------------------------------

    function u_is_absolute() {
        $this->ARGS('', func_get_args());
        return !!$this->parts['host'];
    }

    function u_is_relative() {
        $this->ARGS('', func_get_args());
        return !$this->parts['host'];
    }

    function u_to_absolute($baseUrl=null) {

    }

    function u_to_relative() {
        $this->ARGS('', func_get_args());

        $parts = $this->parts;

        if (strpos($this->str, '//') !== false) {
            $s = preg_replace('#.*//[^/]*#', '', $this->str);
            return new UrlTypeString ($s);
        }

        return $this;
    }

    function u_link($label, $params=[]) {
        $this->ARGS('sm', func_get_args());
        return Tht::module('Web')->u_link($this, $label, $params);
    }
}
