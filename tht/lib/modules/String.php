<?php

namespace o;

class u_String extends StdModule {

    function u_char_from_code ($num) {
        ARGS('n', func_get_args());
        return iconv('UCS-4LE', 'UTF-8', pack('V', $num));
    }

    function u_repeat ($str, $num) {
        ARGS('sn', func_get_args());
        return str_repeat($str, $num);
    }

    function u_random ($len) {
        ARGS('n', func_get_args());
        return Security::randomString($len);
    }
}
