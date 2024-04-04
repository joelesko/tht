<?php

namespace o;

class ONumber extends OVar {

    protected $type = 'number';

    protected $suggestMethod = [
        'abs'      => 'absolute()',
        'ceil'     => 'ceiling()',
        'trunc'    => 'toInt()',
        'min'      => 'clampMax()',
        'max'      => 'clampMin()',
        'zeropad'  => 'zeroPadLeft() or zeroPadRight()',
        'humanize' => 'humanizeCount()',
    ];

    function u_sign () {

        $this->ARGS('', func_get_args());

        if ($this->val > 0) {  return 1;  }
        if ($this->val < 0) {  return -1; }

        return 0;
    }

    // TODO: possibly add "precision" or "multiple" options
    function u_floor () {

        $this->ARGS('', func_get_args());

        return (int) floor($this->val);
    }

    // TODO: possibly add "precision" or "multiple" options
    function u_ceiling () {

        $this->ARGS('', func_get_args());

        return (int) ceil($this->val);
    }

    function u_round ($precision=0, $flags = null) {

        $this->ARGS('im', func_get_args());

        $flags = $this->flags($flags, [
            'half' => 'up|down|even|odd',
        ]);

        $halfMode = [
            'up'   => PHP_ROUND_HALF_UP,
            'down' => PHP_ROUND_HALF_DOWN,
            'even' => PHP_ROUND_HALF_EVEN,
            'odd'  => PHP_ROUND_HALF_ODD,
        ];

        $rn = round($this->val, $precision, $halfMode[$flags['half']]);

        return $precision == 0 ? (int) $rn : $rn;
    }

    // TODO: support rounding other than floor
    function u_round_to_step($interval) {

        $this->ARGS('n', func_get_args());

        if ($interval == 0) { return $this->val; }

        $interval = abs($interval);
        $n = floor($this->val / $interval) * $interval;

        return is_int($interval) ? (int)$n : (float)$n;
    }


    function u_clamp_min ($n) {

        $this->ARGS('n', func_get_args());

        return max($this->val, $n);
    }

    function u_clamp_max ($n) {

        $this->ARGS('n', func_get_args());

        return min($this->val, $n);
    }

    function u_clamp ($min, $max) {

        $this->ARGS('nn', func_get_args());

        return min(max($min, $this->val), $max);
    }

    function u_absolute () {

        $this->ARGS('', func_get_args());

        return abs($this->val);
    }

    function u_zero_pad_left($numLeadingZeros) {

        $this->ARGS('I', func_get_args());

        return sprintf('%0' . $numLeadingZeros . 'd', $this->val);
    }

    function u_zero_pad_right($numDecZeros) {

        $this->ARGS('I', func_get_args());

        $pattern = "%0.{$numDecZeros}f";

        return sprintf($pattern, $this->val);
    }

    function u_format ($flags=null) {

        $this->ARGS('m', func_get_args());

        // Have to do this because OMap.check() splits off of /\s*\|\s*/
        if (isset($flags['thousandSep']) && $flags['thousandSep'] == ' ') {
            $flags['thousandSep'] = '(space)';
        }
        if (isset($flags['decimalSep']) && $flags['decimalSep'] == ' ') {
            $flags['decimalSep'] = '(space)';
        }

        $flags = $this->flags($flags, [
            'sign'        => false,
            'parens'      => false,
            'zeroSign'    => '|+|-',
            'numDecimals' => 0,
            'thousandSep' => ",|.|'|_|(space)|",
            'decimalSep'  => ".|,|'|_|(space)|",
        ]);

        if ($flags['thousandSep'] == '(space)') {
            $flags['thousandSep'] = ' ';
        }
        if ($flags['decimalSep'] == '(space)') {
            $flags['decimalSep'] = ' ';
        }

        $formattedVal = number_format($this->val, $flags['numDecimals'], $flags['decimalSep'], $flags['thousandSep']);

        if ($flags['parens']) {
            $formattedVal = $this->wrapParens($this->val, $formattedVal, $flags['zeroSign']);
        }
        else if ($flags['sign']) {
            $formattedVal = $this->appendSign($this->val, $formattedVal, $flags['zeroSign']);
        }

        return $formattedVal;
    }

    private function wrapParens ($val, $formattedVal, $zeroSign) {

        if ($val === 0) {
            if ($zeroSign == '-') {
                return '(' . $formattedVal . ')';
            }
        }
        else if ($val < 0) {
            return '(' . trim($formattedVal, '-') . ')';
        }

        return $formattedVal;
    }

    private function appendSign ($val, $formattedVal, $zeroSign) {

        if ($val === 0) {
            if ($zeroSign == '+') {
                return '+' . $formattedVal;
            }
            else if ($zeroSign == '-') {
                return '-' . $formattedVal;
            }
        }
        else if ($val > 0) {
            return '+' . $formattedVal;
        }

        return $formattedVal;
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

