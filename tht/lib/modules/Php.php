<?php

namespace o;

class u_Php extends StdModule {

    private $isRequired = [];

    private $blacklist = [
        'assert',
        'call_user_func',
        'call_user_func_array',
        'create_function',
        'eval',
        'exec',
        'extract',
        'file',
        'file_get_contents',
        'file_put_contents',
        'fopen',
        'include',
        'include_once',
        'parse_str',
        'passthru',
        'phpinfo',
        'popen',
        'require',
        'require_once',
        'rmdir',
        'serialize',
        'shell_exec',
        'system',
        'unlink',
        'unserialize',
        'url_exec',
    ];

    private $blacklistMatch = '/pcntl_|posix_|proc_|ini_/i';

    private $phpFunctionOk = [];

    function checkPhpFunction ($func) {

        $func = strtolower($func);

        if (isset($this->phpFunctionOk[$func])) {  return true;  }

        if (!function_exists($this->name($func))) {
            Tht::error("PHP function does not exist: `$func`");
        }
        if (in_array($func, $this->blacklist)) {
            Tht::error("PHP function is blacklisted: `$func`");
        }
        if (preg_match($this->blacklistMatch, $func)) {
            Tht::error("PHP function is blacklisted: `$func`");
        }

        $this->phpFunctionOk[$func] = true;
    }

    function name ($n) {
        $n = str_replace('/', '\\', $n);
        $n = ltrim($n, '\\');
        return '\\' . $n;
    }

    function u_call ($func, $args=[]) {
        Tht::module('Meta')->u_no_template_mode();
        $func = OLockString::getUnlocked($func);

        // TODO: recursive unwrap
        $args = uv($args);
        
        $this->checkPhpFunction($func);

        $ret = call_user_func_array($this->name($func), $args);

        // TODO: wrap args
        return is_null($ret) ? false : $ret;
    }

    function u_options($options) {
        $n = 0;
        foreach ($options as $o) {
            $n |= constant($o);
        }
        return $n;
    }

    // function u_require($phpFile) {
    //     Tht::module('Meta')->u_no_template_mode();
    //     if (!isset($this->isRequired[$phpFile])) {
    //         try {
    //             require_once(Tht::path('phpLib', $phpFile));
    //         } catch (Exception $e) {
    //             Tht::error("Can not require PHP file: `$phpFile`");
    //         }
    //         $this->isRequired[$phpFile] = true;
    //     }
    // }

    // function u_new($cls, $args=null) {

    //     Tht::module('Meta')->u_no_template_mode();

    //     if (!class_exists($cls, false) && !isset($this->isRequired[$cls])) {
    //         try {
    //             $fileClass = str_replace('\\', '/', $cls);
    //             require_once(Tht::path('phpLib', $fileClass . ".php"));

    //         } catch (Exception $e) {
    //             Tht::error("Can not autoload PHP class: `$cls`");
    //         }
    //         $this->isRequired[$cls] = true;
    //     }

    //     $obj = new $cls (uv($args));
    //     return new PhpObject ($obj);
    // }

    // function u_object($obj) {
    //     return new PhpObject ($obj); 
    // }

    // function u_auto($cls, $args=null) {
    //     $obj = new $cls (uv($args));
    //     return new PhpObject ($obj);
    // }

    // function u_array($v) {
    //     return uv($v);
    // }
}


// class PhpObject {

//     private $obj = null;

//     function __construct ($obj) {
//         $this->obj = $obj;
//     }

//     // call function using object as 1st argument
//     function __call ($rawFuncName, $args) {
//         $funcName = unu_($rawFuncName); 

//         // if (!method_exists($this->obj, $funcName)) {
//         //     Tht::error("Method does not exist: `$funcName`");
//         // }
//         $ret = call_user_func_array([$this->obj, $funcName], uv($args));

//         return is_null($ret) ? false : v($ret);
//     }

//     function __get ($field) {
//         $plainField = unu_($field); 
//         return $this->u_get($plainField);
//     }
    
//     function __set ($field, $value) {
//         $plainField = unu_($field); 
//         return $this->u_set($plainField, $value);
//     }

//     // TODO: fix this
//     function u_getX($field) {
//         $props = get_object_vars($this->obj);
//         if (!isset($props[$field])) {
//             Tht::error("Unknown field: `$field`");
//         }
//         return is_null($props[$field]) ? false : $props[$field];
//     }

//     function u_set($field, $val) {
//         $props = get_object_vars($this->obj);
//         if (!isset($props[$field])) {
//             Tht::error("Unknown field: `$field`");
//         }
//         $this->obj->$field = $val;
//     }

//     function u_call() {
//         Tht::module('Meta')->u_no_template_mode();
//         $args = func_get_args();
//         $funcName = array_shift($args);
//         if (!method_exists($this->obj, $funcName)) {
//             Tht::error("Method does not exist: `$funcName`");
//         }
//         $ret = call_user_func_array([$this->obj, $funcName], uv($args));

//         return is_null($ret) ? false : v($ret);
//     }
// }

