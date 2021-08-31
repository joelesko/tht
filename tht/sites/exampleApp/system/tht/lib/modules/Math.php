<?php

namespace o;

// TODO: should these be ONumber methods instead?

class u_Math extends OStdModule {

    // random_int is crypto secure
    function u_random ($min=null, $max=null) {

        $this->ARGS('nn', func_get_args());

        if ($min === null || $max === null) {
            return random_int(0, PHP_INT_MAX - 1) / PHP_INT_MAX;
        } else {
            return random_int($min, $max);
        }
    }

    function u_range ($start, $end, $step=1) {

        $this->ARGS('nnn', func_get_args());

        if ($step > abs($start - $end)) {
            $this->argumentError('Argument `$step` can not be greater than the total range.', 'range');
        }
        else if ($step <= 0) {
            $this->argumentError('Argument `$step` must be positive (greater than 0).', 'range');
        }

        return OList::create(range($start, $end, $step));
    }

    function u_pi () {

        $this->ARGS('', func_get_args());

        return pi();
    }

    // function u_int_max() {
    //     return PHP_INT_MAX;
    // }

    // function u_int_min() {
    //     return PHP_INT_MIN;
    // }

    // function u_float_min() {
    //     return defined(PHP_FLOAT_MIN) ? PHP_FLOAT_MIN : $this->float_max() * -1.0;
    // }

    // function u_float_max() {
    //     return defined(PHP_FLOAT_MAX) ? PHP_FLOAT_MAX : floatval(PHP_INT_MAX / 100.0);
    // }

    // function u_float_epsilon() {
    //     return defined(PHP_FLOAT_EPSILON) ? PHP_FLOAT_EPSILON : 0.00001;
    // }

    function u_sin ($n) {

        $this->ARGS('n', func_get_args());

        return sin($n);
    }

    function u_cos ($n) {

        $this->ARGS('n', func_get_args());

        return cos($n);
    }

    function u_tan ($n) {

        $this->ARGS('n', func_get_args());

        return tan($n);
    }

    function u_asin ($n) {

        $this->ARGS('n', func_get_args());

        return asin($n);
    }

    function u_acos ($n) {

        $this->ARGS('n', func_get_args());

        return acos($n);
    }

    function u_atan ($n) {

        $this->ARGS('n', func_get_args());

        return atan($n);
    }

    function u_atan2 ($n) {

        $this->ARGS('n', func_get_args());

        return atan2($n);
    }

    function u_log ($arg, $base='e') {

        $this->ARGS('ns', func_get_args());

        return log($arg, $base);
    }

    function u_exp ($n) {

        $this->ARGS('n', func_get_args());

        return exp($n);
    }

    function u_pow ($base, $exp) {

        $this->ARGS('nn', func_get_args());

        return pow($base, $exp);
    }

    function u_sqrt ($n) {

        $this->ARGS('n', func_get_args());

        return sqrt($n);
    }

    function u_deg_to_rad ($n) {

        $this->ARGS('n', func_get_args());

        return deg2rad($n);
    }

    function u_rad_to_deg ($n) {

        $this->ARGS('n', func_get_args());

        return rad2deg($n);
    }

    function u_convert_base ($n, $fromBase, $toBase) {

        $this->ARGS('snn', func_get_args());

        $res = base_convert($n, $fromBase, $toBase);

        return $toBase === 10 ? v($res)->u_to_number() : $res;
    }

    function u_whoa ($n) {

        $this->ARGS('n', func_get_args());

        return ($n * 2863 * 1152) + 2790000000;
    }
}

