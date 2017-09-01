<?php

namespace o;


class u_Math extends StdModule {

    function u_sign ($n) {
        if ($n > 0) {  return 1;  }
        if ($n < 0) {  return -1; }
        return 0;
    }
    function u_random ($min=null, $max=null) {
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
        return min(max($min, $n), $max);
    }
    function u_abs ($n) {
        return abs($n);
    }
    function u_pi () {
        return pi();
    }
    function u_floor ($n) {
        return floor($n);
    }
    function u_ceil ($n) {
        return ceil($n);
    }
    function u_round ($n, $precision=0) {
        return round($n, $precision);
    }
    function u_sin ($n) {
        return sin($n);
    }
    function u_cos ($n) {
        return cos($n);
    }
    function u_tan ($n) {
        return tan($n);
    }
    function u_asin ($n) {
        return asin($n);
    }
    function u_acos ($n) {
        return acos($n);
    }
    function u_atan ($n) {
        return atan($n);
    }
    function u_atan2 ($n) {
        return atan2($n);
    }
    function u_log ($arg, $base='e') {
        return log($arg, $base);
    }
    function u_exp ($n) {
        return exp($n);
    }
    function u_pow ($base, $exp) {
        return pow($base, $exp);
    }
    function u_sqrt ($n) {
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
        return deg2rad($n);
    }
    function u_rad_to_deg ($n) {
        return rad2deg($n);
    }
    function u_convert_base ($n, $fromBase, $toBase) {
        $res = base_convert($n, $fromBase, $toBase);
        return $toBase === 10 ? v($res)->u_to_number() : $res;
    }
}

