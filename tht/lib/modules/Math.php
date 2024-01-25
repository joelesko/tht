<?php

namespace o;

// TODO: should these be ONumber methods instead?

class u_Math extends OStdModule {

    protected $suggestMethod = [
        'abs'   => '$number.absolute()',
        'floor' => '$number.floor()',
        'ceil'  => '$number.ceiling()',
        'round' => '$number.round()',
        'sign'  => '$number.sign()',
    ];

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


    // LISTS
    //-----------------------------------------

    function u_max($nums) {

        $this->ARGS('l', func_get_args());

        $nums = $this->assertNumericList($nums, 'max');

        return count($nums) ? max($nums) : 0;
    }

    function u_min($nums) {

        $this->ARGS('l', func_get_args());

        $nums = $this->assertNumericList($nums, 'min');

        return count($nums) ? min($nums) : 0;
    }

    function u_product($nums) {

        $this->ARGS('l', func_get_args());

        $nums = $this->assertNumericList($nums, 'product');

        return array_product($nums);
    }

    function u_sum($nums) {

        $this->ARGS('l', func_get_args());

        $nums = $this->assertNumericList($nums, 'sum');

        return array_sum($nums);
    }

    function assertNumericList($nums, $meth) {

        $nums = unv($nums);

        $i = ONE_INDEX;
        foreach ($nums as $n) {
            $type = gettype($n);
            // Don't allow numeric strings.
            if ($type != 'integer' && $type != 'double') {
                $this->argumentError("Every list item must be a number. Got: `$type` at index #$i", $meth);
            }
            $i += 1;
        }

        return $nums;
    }



    function u_pi () {

        $this->ARGS('', func_get_args());

        return pi();
    }

    // function u_inf($sign = 1) {

    //     $this->ARGS('i', func_get_args());

    //     if ($sign == 1) {  return INF;  }
    //     if ($sign == -1) {  return -1 * INF;  }

    //     $this->argumentError('Argument `$sign` must be `1` or `-1`.');
    // }

    function u_z_int_max() {
        $this->ARGS('', func_get_args());
        return PHP_INT_MAX;
    }

    function u_z_int_min() {
        $this->ARGS('', func_get_args());
        return PHP_INT_MIN;
    }

    // PHP_FLOAT_MIN is actually the smallest possible POSITIVE float.
    function u_z_float_min() {
        $this->ARGS('', func_get_args());
        return -1 * PHP_FLOAT_MAX;
    }

    function u_z_float_max() {
        $this->ARGS('', func_get_args());
        return PHP_FLOAT_MAX;
    }

    // function u_float_epsilon() {
    //     return defined(PHP_FLOAT_EPSILON) ? PHP_FLOAT_EPSILON : 0.00001;
    // }

    function u_convert_base ($n, $fromBase, $toBase) {

        $this->ARGS('snn', func_get_args());

        $res = base_convert($n, $fromBase, $toBase);

        return $toBase === 10 ? v($res)->u_to_number() : $res;
    }

    function u_dec_to_hex ($n) {

        $this->ARGS('n', func_get_args());

        return strtoupper(dechex($n));
    }

    function u_hex_to_dec ($s) {

        $this->ARGS('s', func_get_args());

        $s = ltrim($s, '#');

        if (preg_match('/[^#xA-Za-z0-9]/', $s)) {
            $this->error("Invalid character in hex string. Got: `$s`");
        }

        return hexdec($s);
    }
}

