<?php

namespace o;

class PhpObject {

    var $prefix = '';
    var $resource = null;

    function __construct ($pre, $resource) {
        $this->prefix = $pre;
        $this->resource = $resource;
    }

    // call function using resource as first argument
    function __call ($func, $args) {
        $f = substr($func, 2);
        array_unshift($args, $this->resource);
        Tht::module('Php')->u_call($this->prefix . $f, $args);
    }
}

class u_Php extends StdModule {

    // TODO: match prefixes
    private $blacklist = [
        'eval',
        'assert',
        'phpinfo',
        'extract',
        'ini_set',
        'parse_str',
        'exec',
        'passthru',
        'system',
        'shell_exec',
        'popen',
        'proc_open',
        'pcntl_exec',
        'create_function',
        'include',
        'include_once',
        'require',
        'require_once',
        'call_user_func_array',
        'call_user_func',
        'file_get_contents',
        'file_put_contents',
        'file',
        'fopen',
        'unlink',
        'pcntl_',
        'posix_',
        'proc_',
        'rmdir',
        'ini_',
        'url_exec',
        'serialize',
        'unserialize'
    ];

    private $phpFunctionOk = [];

    // function __call ($func, $args) {
    //     $f = substr($func, 2);
    //     $ret = $this->u_call(new OLockString ($f), $args);
    //     return is_null($ret) ? false : $ret;
    // }

    function checkPhpFunction ($func) {
        if (isset($this->phpFunctionOk[$func])) {  return true;  }

        if (!function_exists($this->name($func))) {
            Tht::error("PHP function does not exist: `$func`");
        }
        if (in_array($func, $this->blacklist)) {
            Tht::error("PHP function is prohibited: `$func`");
        }
        $this->phpFunctionOk[$func] = true;
       // return;

        // $allowed = preg_split('/\s*,\s*/', Tht::getConfig('allowedPhpFunctions'));
        // foreach ($allowed as $fn) {
        //     $match = false;
        //     if (substr($fn, strlen($fn)-1, 1) === '*') {
        //         if (strlen($fn) < 3) {
        //             Tht::error("Wildcard '$fn' in 'allowedPhpFunctions' must be 2+ characters long.");
        //         }
        //         $leftMatch = substr($fn, 0, strlen($fn)-1);
        //         if (substr($func, 0, strlen($leftMatch)) === $leftMatch) {
        //             $match = true;
        //         }
        //     } else if ($func === $fn) {
        //         $match = true;
        //     }
        //
        //     if ($match) {
        //         if (!function_exists($this->name($func))) {
        //             Tht::error('PHP function does not exist: ' . $func);
        //         }
        //         if (in_array($func, $this->blacklist)) {
        //             Tht::error('PHP function is blacklisted: ' . $func);
        //         }
        //         $this->phpFunctionOk[$func] = true;
        //         return;
        //     }
        // }
        // Tht::error("PHP function '$func' not listed in 'allowedPhpFunctions' config.", $allowed);
    }

    function name ($n) {
        $n = str_replace('/', '\\', $n);
        $n = ltrim($n, '\\');
        return '\\' . $n;
    }

    function u_call ($func, $args=[]) {
        $func = OLockString::getUnlocked($func);
        $args = uv($args);
        $this->checkPhpFunction($func);
        $ret = call_user_func_array($this->name($func), uv($args));
        return is_null($ret) ? false : $ret;
    }

    function u_options($options) {
        $n = 0;
        foreach ($options as $o) {
            $n |= constant($o);
        }
        return $n;
    }

    function u_wrap ($prefix, $createFunction=null, $args=null) {
        $createFunction = OLockString::getUnlocked($createFunction);
        $resource = null;
        if (! is_null($createFunction)) {
            $resource = $this->u_call($prefix . $createFunction, $args);
            if ($resource === false || is_null($resource)) {
                Tht::error('Bad resource returned from `$prefix' . $createFunction . '`', [ 'resource' => $resource ]);
            }
        }
        return new PhpObject ($prefix, $resource);
    }
}

