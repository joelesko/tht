<?php

namespace o;

// origin = base? (non-path part)

// Other APIs:
// https://nodejs.org/api/url.html#url_url_strings_and_url_objects
// https://docs.oracle.com/javase/7/docs/api/java/net/URI.html

class UrlTypeString extends OTypeString {

    protected $stringType = 'url';
    protected $errorClass = 'Url';

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

    function u_render_string() {

        $this->updateString();

        return parent::u_render_string();
    }

    function parse($sUrl) {

        // Shortcut:  url'this' --> Current URL
        if ($sUrl == 'this') {
            $sUrl = Tht::module('Request')->u_get_url()->u_to_relative()->str;
        }

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
            $this->str .= $this->query->u_render_string();
        }

        if (isset($this->parts['hash']) && $this->parts['hash']) {
            $this->str .= '#' . $this->parts['hash'];
        }
    }


    // Protected parts
    //---------------------------------

    function u_get_query() {

        $this->ARGS('', func_get_args());

        if (!$this->query) {
            $this->query = new UrlQuery ([]);
        }

        return $this->query;
    }

    function u_set_query($q) {

        $this->ARGS('*', func_get_args());

        if (!$this->query) {
            $this->query = new UrlQuery ([]);
        }

        $this->query->u_set($q);
        $this->updateString();

        return $this;
    }

    function u_clear_query() {

        $this->query = new UrlQuery ([]);
        $this->updateString();

        return $this;
    }




    // Part getter/setters
    //----------------------------------

    function updatePart($p, $v) {

        $this->parts[$p] = $v;
        $this->updateString();

        return $this;
    }

    function u_set_hash($v) {

        $this->ARGS('s', func_get_args());

        $v = Security::sanitizeUrlHash($v);

        $this->updatePart('hash', $v);

        return $this;
    }

    function u_get_hash() {

        $this->ARGS('', func_get_args());

        return $this->parts['hash'];
    }

    function u_set_host($v) {

        $this->ARGS('s', func_get_args());

        $v = strtolower($v);
        $v = preg_replace('/[^a-z0-9\.\-_]/', '', $v); // untaint

        $this->updatePart('host', $v);

        return $this;
    }

    function u_get_host() {

        $this->ARGS('', func_get_args());

        return $this->parts['host'];
    }

    function u_set_port($v) {

        $this->ARGS('i', func_get_args());

        $this->updatePart('port', $v);

        return $this;
    }

    function u_get_port() {

        $this->ARGS('', func_get_args());

        return $this->parts['port'];
    }

    function u_set_scheme($v) {

        $this->ARGS('s', func_get_args());

        $v = strtolower($v);
        $v = preg_replace('/[^a-z]/', '', $v); // untaint

        $this->updatePart('scheme', $v);

        return $this;
    }

    function u_get_scheme() {

        $this->ARGS('', func_get_args());

        return $this->parts['scheme'];
    }

    function u_set_path($v) {

        $this->ARGS('s', func_get_args());

        $v = preg_replace('#[^a-zA-Z0-9\-\.~/]#', '', $v); // SEC: untaint

        $this->updatePart('path', $v);

        return $this;
    }

    function u_get_path() {

        $this->ARGS('', func_get_args());

        return $this->parts['path'];
    }

    function u_get_path_parts() {

        $this->ARGS('', func_get_args());

        $path = $this->u_get_path();

        return v(v($path)->u_trim_left('/'))->u_split('/');
    }

    function u_get_origin() {

        $this->ARGS('', func_get_args());

        return $this->origin;
    }

    function u_get_file_parts() {

        $this->ARGS('', func_get_args());

        $path = $this->u_get_path();

        return Tht::module('File')->u_get_parts($path);
    }

    function u_get_file_path() {

        $this->ARGS('', func_get_args());

        $relPath = $this->u_get_path();

        return Tht::path('public', $relPath);
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

    function u_to_absolute($baseOrigin='') {

        $this->ARGS('*', func_get_args());

        if (!$baseOrigin) {
            $currentUrl = Tht::module('Request')->u_get_url();
            $baseOrigin = $currentUrl->u_get_origin();
        }

        $baseOrigin = rtrim($baseOrigin, '/');

        // remove any existing base
        $s = preg_replace('#.*//[^/]*#', '', $this->str);
        $s = ltrim($s, '/');

        $s = $baseOrigin . '/' . $s;

        $ns = new UrlTypeString ($s);
        $ns->u_set_query($this->u_get_query());

        return $ns;
    }

    function u_to_relative() {

        $this->ARGS('', func_get_args());

        if (strpos($this->str, '//') !== false) {
            $s = preg_replace('#.*//[^/]*#', '', $this->str);
            $ns = new UrlTypeString ($s);
            $ns->u_set_query($this->u_get_query());

            return $ns;
        }

        return $this;
    }

    function u_is_local() {

        $this->ARGS('', func_get_args());

        if ($this->u_is_relative()) {
            return true;
        }

        $currentUrl = Tht::module('Request')->u_get_url();
        $siteOrigin = $currentUrl->u_get_origin();

        return $this->origin == $siteOrigin;
    }

    function u_is_remote() {

        $this->ARGS('', func_get_args());

        return !$this->u_is_local();
    }

    function u_link($label, $params=null) {

        $this->ARGS('sm', func_get_args());

        return Tht::module('Web')->u_link($this, $label);
    }
}
