<?php

namespace o;

class u_Result extends StdModule {

    static function u_ok ($v) {
        return new OResult ($v, true, 0);
    }

    static function u_fail ($code=1) {
        return new OResult ('', false, $code);
    }
}

class OResult extends OVar {

    private $rvalue = '';
    private $code = 0;
    private $ok = true;

    function __construct($val, $ok, $code) {
        $this->rvalue = $val;
        $this->ok = $ok;
        $this->code = $code;
    }

    function __toString() {
        return '(Result: ' . ($this->ok ? $this->rvalue : 'FAIL') . ')';
    }

    function u_get ($default=null) {
        if (!$this->ok) {
            if ($default !== null) {
                return $default;
            }
            Owl::error("Result object is in a failure state. Check .ok() or .code() method first.");
        }
        return $this->rvalue;
    }

    function u_ok () {
        return $this->ok;
    }

    function u_fail_code () {
        return $this->code;
    }
}

