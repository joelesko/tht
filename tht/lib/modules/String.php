<?php

namespace o;

class u_String extends StdModule {

    function u_char_from_code ($num) {
        return iconv('UCS-4LE', 'UTF-8', pack('V', $num));
    }

    function u_repeat ($str, $num) {
        return str_repeat($str, $num);
    }

    // Length = final string length, not byte length
    function u_random ($len) {  // [security]
        $bytes = '';
        
        if (function_exists('random_bytes')) {
            $bytes = random_bytes($len);
        } else if (function_exists('mcrypt_create_iv')) {
            $bytes = mcrypt_create_iv($len, MCRYPT_DEV_URANDOM);
        } else {
            $bytes = openssl_random_pseudo_bytes($len);
        }
        
        $b64 = base64_encode($bytes);

        return substr($b64, 0, $len);
    }
}
