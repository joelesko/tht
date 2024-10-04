<?php

namespace o;

class u_Php extends OStdModule {

    protected $suggestMethod = [
        'import'  => 'require()',
        'include' => 'require()',
    ];

    private $isRequired = [];
    private $phpFunctionOk = [];

    function checkPhpFunction($func) {

        $func = strtolower($func);

        if (isset($this->phpFunctionOk[$func])) {  return true;  }

        Security::validatePhpFunction($func);

        $isStatic = preg_match('/^(\S+?)::(.*)/', $func, $m);
        if ($isStatic) {
            $ns = $m[1];
            $fun = $m[2];
            if (!method_exists($ns, $fun)) {
                Tht::error("Static method does not exist for PHP class `$ns`: `$func`");
            }
        }
        else if (!function_exists($func)) {
            Tht::error("PHP function does not exist: `" . $func . "`");
        }

        $this->phpFunctionOk[$func] = true;
    }

    function name($n) {
        $n = str_replace('/', '\\', $n);
        $n = ltrim($n, '\\');
        return '\\' . $n;
    }

    function u_get_version($flags=null) {

        $this->ARGS('m', func_get_args());

        $flags = $this->flags($flags, [
            'num' => false,
        ]);

        return $flags['num'] ? PHP_VERSION_ID : phpversion();
    }

    function u_call() {
        Tht::module('Meta')->u_fail_if_template_mode();

        $args = func_get_args();
        $func = array_shift($args);

        // Have to wrap it so unv works.
        $args = unv(OList::create($args));

        $cleanName = $this->name($func);
        $this->checkPhpFunction($cleanName);

        $ret = call_user_func_array($cleanName, $args);

        if (is_object($ret)) {
            return new PhpObject ($ret);
        }

        return convertReturnValue($ret);
    }

    function u_get_constant($c) {
        $this->ARGS('s', func_get_args());
        if (!defined($c)) {
            $this->error("Undefined PHP constant: `$c`");
        }
        return constant($c);
    }

    function u_options($options) {
        $this->ARGS('l', func_get_args());
        $n = 0;
        foreach ($options as $o) {
            $n |= $this->u_get_constant($o);
        }
        return $n;
    }

    function u_require($phpFile) {

        Tht::module('Meta')->u_fail_if_template_mode();
        if (!isset($this->isRequired[$phpFile])) {
            try {
                require_once(Tht::path('phpLib', $phpFile));
            } catch (\Exception $e) {
                $this->error("Unable to require PHP file: `$phpFile`");
            }
            $this->isRequired[$phpFile] = true;
        }

        return NULL_NORETURN;
    }

    function u_new($cls) {

        Tht::module('Meta')->u_fail_if_template_mode();

        $cls = str_replace('/', '\\', $cls);

        if (!class_exists($cls, false)) {
            $fileClass = str_replace('\\', '/', $cls) . '.php';
            if (!isset($this->isRequired[$cls])) {
                $this->u_require($fileClass);
            }
        }

        $args = func_get_args();
        array_shift($args);

        $or = new \ReflectionClass($cls);
        $obj = $or->newInstanceArgs($args);

        return new PhpObject ($obj);
    }

    function u_wrap_object($obj) {
        return new PhpObject ($obj);
    }

    function u_function_exists($f) {
        $f = str_replace('/', '\\', $f);
        return function_exists($f);
    }

    function u_class_exists($c) {
        $c = str_replace('/', '\\', $c);
        return class_exists($c);
    }
}

class PhpObject {

    private $obj = null;

    function __construct($obj) {
        $this->obj = $obj;
    }

    function __call($rawFuncName, $args) {
        $funcName = unu_($rawFuncName);
        return $this->u_z_call($funcName, $args);
    }

    function __get($field) {
        $rawField = unu_($field);
        return $this->u_z_get($rawField);
    }

    function __set($field, $value) {
        $rawField = unu_($field);
        return $this->u_z_set($rawField, $value);
    }

    function u_z_get($rawField) {
        $v = $this->obj->$rawField;
        return convertReturnValue($v);
    }

    function u_z_set($rawField, $value) {
        $this->obj->$rawField = $value;
        return $value;
    }

    function u_z_call($rawFuncName, $args=[]) {
        foreach ($args as $k => $v) {
            $args[$k] = unv($v);
        }
        $ret = call_user_func_array([$this->obj, $rawFuncName], $args);

        return convertReturnValue($ret);
    }
}

function convertReturnValue($val) {

    $phpType = gettype($val);

    if ($phpType == 'array') {

        if (count($val) > 0) {

            foreach ($val as $k => $v) {
                $val[$k] = convertReturnValue($v);
            }

            // Naive (but fast) way to check that an array is associative or sequential.
            // If first key is 0, then we assume it is sequential.
            reset($val);
            if (key($val) !== 0) {
                return OMap::create($val);
            } else {
                return OList::create($val);
            }
        }

    } else if ($phpType == 'object') {
        return new PhpObject ($val);
    }
    else if (is_null($val)) {
        return NULL_NORETURN;
    }
    else {
        return $val;
    }
}

