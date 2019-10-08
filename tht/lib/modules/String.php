<?php

namespace o;

class u_String extends OStdModule {

    function u_char_from_code ($num) {
        $this->ARGS('n', func_get_args());
        return iconv('UCS-4LE', 'UTF-8', pack('V', $num));
    }

    function u_repeat ($str, $num) {
        $this->ARGS('sn', func_get_args());
        return str_repeat($str, $num);
    }

    function u_random ($len) {
        $this->ARGS('n', func_get_args());
        return Security::randomString($len);
    }
}
