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

    function jsonSerialize():mixed {
        return $this->val;
    }

    function u_sign() {

        $this->ARGS('', func_get_args());

        if ($this->val > 0) {  return 1;  }
        if ($this->val < 0) {  return -1; }

        return 0;
    }

    function u_floor($precision=0) {

        $this->ARGS('I', func_get_args());

        if ($precision >= 1) {
            $mag = pow(10, $precision);
            $n = floor($this->val * $mag) / $mag;
            return (float) $n;
        }

        return (int) floor($this->val);
    }

    function u_ceiling($precision=0) {

        $this->ARGS('I', func_get_args());

        if ($precision >= 1) {
            $mag = pow(10, $precision);
            $n = ceil($this->val * $mag) / $mag;
            return (float) $n;
        }

        return (int) ceil($this->val);
    }

    function u_round($precision=0, $flags = null) {

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


    function u_clamp_min($n) {

        $this->ARGS('n', func_get_args());

        return max($this->val, $n);
    }

    function u_clamp_max($n) {

        $this->ARGS('n', func_get_args());

        return min($this->val, $n);
    }

    function u_clamp($min, $max) {

        $this->ARGS('nn', func_get_args());

        return min(max($min, $this->val), $max);
    }

    function u_absolute() {

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

    function u_format($flags=null) {

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

    private function wrapParens($val, $formattedVal, $zeroSign) {

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

    private function appendSign($val, $formattedVal, $zeroSign) {

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

    function u_to_roman() {

        $this->ARGS('', func_get_args());

        if (!is_int($this->val)) {
            $this->error('Method `toRoman` can only work on integers.  Got: `float`');
        }
        if ($this->val < 1) {
            $this->error('Method `toRoman` can only work on numbers greater than zero.  Got: `' . $this->val . '`');
        }

        $romanDigits = [
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1
        ];

        $n = $this->val;
        $out = '';
        foreach ($romanDigits as $roman => $dec) {
            $out .= str_repeat($roman, floor($n / $dec));
            $n = $n % $dec;
        }

        return $out;
    }

    function u_to_percent($numDecimals = 0) {

        $this->ARGS('i', func_get_args());

        $options = OMap::create([ 'numDecimals' => $numDecimals ]);
        $num = v($this->val * 100);

        return $num->u_format($options) . '%';
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

    function u_in_range($min, $max) {

        $this->ARGS('nn', func_get_args());

        return ($this->val >= $min) && ($this->val <= $max);
    }

    // https://wiki.ubuntu.com/UnitsPolicy
    function getByteValForUnit($unit) {

        // TODO: probably cache this
        $units = [
            'B'  => 1,
            'kB' => 1000,
            'MB' => 1_000_000,
            'GB' => 1_000_000_000,
            'TB' => pow(10, 12),

            'KiB' => 1024,
            'MiB' => 1_048_576,
            'GiB' => 1_073_741_824,
            'TiB' => pow(1024, 4),
        ];

        if (!isset($units[$unit])) {
            $try = implode(', ', array_keys($uits));
            $this->error("Unknown byte unit: `$unit`  Units: $try");
        }

        return $units[$unit];
    }

    function u_to_bytes_from($fromUnit) {

        $this->ARGS('s', func_get_args());

        return $this->val * $this->getByteValForUnit($fromUnit);
    }

    function u_from_bytes_to($toUnit) {

        $this->ARGS('s', func_get_args());

        return $this->val / $this->getByteValForUnit($toUnit);
    }

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

    function u_to_number() {
        $this->ARGS('', func_get_args());
        return $this->val;
    }

    function u_to_boolean() {
        $this->ARGS('', func_get_args());
        return $this->val ? true : false;
    }

    function u_to_string() {
        $this->ARGS('', func_get_args());
        return '' . $this->val;
    }
}

