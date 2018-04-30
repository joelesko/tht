<?php

namespace o;


class u_Math extends StdModule {

    function u_sign ($n) {
        ARGS('n', func_get_args());
        if ($n > 0) {  return 1;  }
        if ($n < 0) {  return -1; }
        return 0;
    }
    function u_random ($min=null, $max=null) {
        ARGS('nn', func_get_args());
        if ($min === null || $max === null) {
            if (function_exists('random_int')) {
                return random_int(0, PHP_INT_MAX - 1) / PHP_INT_MAX;
            } else {
                return mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
            }
        } else {
            if (function_exists('random_int')) {
                return random_int($min, $max);
            } else {
                return mt_rand($min, $max);
            }
        }
    }
    function u_clamp ($n, $min, $max) {
        ARGS('nnn', func_get_args());
        return min(max($min, $n), $max);
    }
    function u_abs ($n) {
        ARGS('n', func_get_args());
        return abs($n);
    }
    function u_pi () {
        return pi();
    }
    function u_floor ($n) {
        ARGS('n', func_get_args());
        return floor($n);
    }
    function u_ceil ($n) {
        ARGS('n', func_get_args());
        return ceil($n);
    }
    function u_round ($n, $precision=0) {
        ARGS('nn', func_get_args());
        return round($n, $precision);
    }
    function u_sin ($n) {
        ARGS('n', func_get_args());
        return sin($n);
    }
    function u_cos ($n) {
        ARGS('n', func_get_args());
        return cos($n);
    }
    function u_tan ($n) {
        ARGS('n', func_get_args());
        return tan($n);
    }
    function u_asin ($n) {
        ARGS('n', func_get_args());
        return asin($n);
    }
    function u_acos ($n) {
        ARGS('n', func_get_args());
        return acos($n);
    }
    function u_atan ($n) {
        ARGS('n', func_get_args());
        return atan($n);
    }
    function u_atan2 ($n) {
        ARGS('n', func_get_args());
        return atan2($n);
    }
    function u_log ($arg, $base='e') {
        ARGS('ns', func_get_args());
        return log($arg, $base);
    }
    function u_exp ($n) {
        ARGS('n', func_get_args());
        return exp($n);
    }
    function u_pow ($base, $exp) {
        ARGS('nn', func_get_args());
        return pow($base, $exp);
    }
    function u_sqrt ($n) {
        ARGS('n', func_get_args());
        return sqrt($n);
    }
    function u_min () {
        $nums = func_get_args();
        if (count($nums) == 1) { $nums = uv($nums[0]); }
        return min($nums);
    }
    function u_max () {
        $nums = func_get_args();
        if (count($nums) == 1) { $nums = uv($nums[0]); }
        return max($nums);
    }
    function u_deg_to_rad ($n) {
        ARGS('n', func_get_args());
        return deg2rad($n);
    }
    function u_rad_to_deg ($n) {
        ARGS('n', func_get_args());
        return rad2deg($n);
    }
    function u_convert_base ($n, $fromBase, $toBase) {
        ARGS('snn', func_get_args());
        $res = base_convert($n, $fromBase, $toBase);
        return $toBase === 10 ? v($res)->u_to_number() : $res;
    }
}

