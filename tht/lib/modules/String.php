<?php

namespace o;

class u_String extends StdModule {

    function u_char_from_code ($num) {
        return iconv('UCS-4LE', 'UTF-8', pack('V', $num));
    }

    function u_repeat ($str, $num) {
        return str_repeat($str, $num);
    }

    function u_random ($len) { 
        return Security::randomString($len);
    }
}
