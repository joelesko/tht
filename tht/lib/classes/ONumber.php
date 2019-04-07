<?php

namespace o;

class ONumber extends OVar {

    // TODO: zero pad

    function u_format ($numDec=0, $thousandSep=',', $decimalPt='.') {
        return number_format($this->val, $numDec, $decimalPt, $thousandSep);
    }

    // function u_in_range ($min, $max) {
    //     return ($this->val >= $min) && ($this->val <= $max);
    // }

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

    function u_to_number () {
        return $this->val;
    }

    function u_to_boolean () {
        return $this->val ? true : false;
    }

    function u_to_string () {
        return '' . $this->val;
    }
}

