<?php

namespace o;

class OModule {
    private $ns;
    private $baseName;
    function __construct ($ns, $path) {
        $this->ns = $ns;
        $this->baseName = $ns . "\\u_" . basename($path, '.' . Owl::getExt());
    }
    function __call ($f, $args) {
        $qf = $this->ns . "\\" . $f;
        if (!function_exists($qf)) {
            Owl::error("Unknown function: `$f`");
        }
        return call_user_func_array($qf, $args);
    }
    function u_new () {
        return forward_static_call_array([$this->baseName, 'u_new'], func_get_args());
    }
}
