<?php

namespace o;

class u_Result extends OStdModule {

    function u_ok ($v) {
        $this->ARGS('*', func_get_args());
        return new OResult ($v, true, 0);
    }

    function u_fail ($code='general') {
        $this->ARGS('s', func_get_args());
        return new OResult ('', false, $code);
    }
}

class OResult extends OVar {

    private $rvalue = '';
    private $code = 'general';
    private $ok = true;

    function __construct($val, $ok, $code) {
        $this->rvalue = $val;
        $this->ok = $ok;
        $this->code = $code;
    }

    function __toString() {
        return '(Result: ' . ($this->ok ? $this->rvalue : 'FAIL:' . $this->code) . ')';
    }

    function u_get ($default=null) {
        $this->ARGS('*', func_get_args());
        if (!$this->ok) {
            if ($default !== null) {
                return $default;
            }
            Tht::error("Result object is in a failure state. Check `.failCode()` method first.");
        }
        return $this->rvalue;
    }

    function u_fail_code () {
        $this->ARGS('', func_get_args());
        return $this->code;
    }
}

