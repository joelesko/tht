<?php

namespace o;

class ONumber extends OVar {

    protected $type = 'number';

    protected $suggestMethod = [
        'floor' => 'roundDown()',
        'ceil'  => 'roundUp()',
    ];

    function u_sign () {

        $this->ARGS('', func_get_args());

        if ($this->val > 0) {  return 1;  }
        if ($this->val < 0) {  return -1; }

        return 0;
    }

    function u_floor () {

        $this->ARGS('', func_get_args());

        return (int) floor($this->val);
    }

    function u_ceil () {

        $this->ARGS('', func_get_args());

        return (int) ceil($this->val);
    }

    function u_round ($precision=0) {

        $this->ARGS('n', func_get_args());

        $rn = round($this->val, $precision);

        return $precision == 0 ? (int) $rn : $rn;
    }

    function u_round_to_step($interval) {

        $this->ARGS('n', func_get_args());

        if ($interval == 0) { return $this->val; }

        $interval = abs($interval);
        $n = floor($this->val / $interval) * $interval;

        return is_int($interval) ? (int)$n : (float)$n;
    }


    function u_min ($n) {

        $this->ARGS('n', func_get_args());

        return min($this->val, $n);
    }

    function u_max ($n) {

        $this->ARGS('n', func_get_args());

        return max($this->val, $n);
    }

    function u_clamp ($min, $max) {

        $this->ARGS('nn', func_get_args());

        return min(max($min, $this->val), $max);
    }

    function u_abs () {

        $this->ARGS('', func_get_args());

        return abs($this->val);
    }








    function u_zero_pad($numLeadingZeros, $numDecZeros = 0) {

        $this->ARGS('II', func_get_args());

        if ($numDecZeros) {
            $decSep = ".";
            $adjLeadingZeros = $numLeadingZeros + mb_strlen($decSep) + $numDecZeros;
            $pattern = "%0{$adjLeadingZeros}{$decSep}{$numDecZeros}f";
            return sprintf($pattern, $this->val);
        }
        else {
            return sprintf('%0' . $numLeadingZeros . 'd', $this->val);
        }
    }

    function u_format ($numDec=0, $thousandSep=',', $decimalPt='.') {

        $this->ARGS('Iss', func_get_args());

        return number_format($this->val, $numDec, $decimalPt, $thousandSep);
    }

    // TODO: localization
    function u_humanize_count() {

        $this->ARGS('', func_get_args());

        $n = floor($this->val);

        $mod100 = $n % 100;
        if ($mod100 == 11 || $mod100 == 12 || $mod100 == 13) {
            return $n . 'th';
        }

        $mod10 = $n % 10;
        if ($mod10 == 1) {
            return $n . 'st';
        }
        if ($mod10 == 2) {
            return $n . 'nd';
        }
        if ($mod10 == 3) {
            return $n . 'rd';
        }

        return $n . 'th';
    }

    function u_is_odd() {

        $this->ARGS('', func_get_args());

        return intval($this->val, 10) & 1;
    }

    function u_is_even() {

        $this->ARGS('', func_get_args());

        return !(intval($this->val, 10) & 1);
    }

    function u_is_multiple_of($n) {

        $this->ARGS('I', func_get_args());

        if ($n == 0) {
            $this->error('First argument of `isMultipleOf` must be greater than zero.');
        }

        return abs($this->val % $n) == 0;
    }

    function u_in_range ($min, $max) {

        $this->ARGS('nn', func_get_args());

        return ($this->val >= $min) && ($this->val <= $max);
    }

    // http://stackoverflow.com/questions/2510434/format-bytes-to-kilobytes-megabytes-gigabytes
    // function u_format_bytes($bytes, $precision = 2) {
    //     $units = array('B', 'KB', 'MB', 'GB', 'TB');
    //     $bytes = max($bytes, 0);
    //     $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    //     $pow = min($pow, count($units) - 1);
    //     $bytes /= pow(1024, $pow);
    //
    //     return round($bytes, $precision) . ' ' . $units[$pow];
    // }

    // Casting

    function u_is_float() {
        $this->ARGS('', func_get_args());
        return is_float($this->val);
    }

    function u_is_int() {
        $this->ARGS('', func_get_args());
        return is_int($this->val) || intval($this->val) == floatval($this->val);
    }

    function u_to_int() {
        $this->ARGS('', func_get_args());
        return intval($this->val);
    }

    function u_to_float() {
        $this->ARGS('', func_get_args());
        return floatval($this->val);
    }

    function u_to_number () {
        $this->ARGS('', func_get_args());
        return $this->val;
    }

    function u_to_boolean () {
        $this->ARGS('', func_get_args());
        return $this->val ? true : false;
    }

    function u_to_string () {
        $this->ARGS('', func_get_args());
        return '' . $this->val;
    }
}

