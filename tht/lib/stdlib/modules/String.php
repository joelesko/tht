<?php

namespace o;

class u_String extends OStdModule {

    public $lastReplaceCount = -1;

    function u_unicode_to_char($num) {

        $this->ARGS('n', func_get_args());

        return iconv('UCS-4LE', 'UTF-8', pack('V', $num));
    }

    // function u_range($from, $to) {
    //     $this->ARGS('ss', func_get_args());
    //     return OList::create(range($from, $to));
    // }

    // Undocumented
    function u_x_danger_password($plaintext) {

        $this->ARGS('s', func_get_args());

        return Security::createPassword($plaintext);
    }

    function u_random_token($len) {

        $this->ARGS('n', func_get_args());

        $s = Security::randomString($len);

        return $s;
    }

    function u_scramble_id($idToScramble) {
        $this->ARGS('n', func_get_args());
        return Security::encodeHashId($idToScramble);
    }

    function u_unscramble_id($scrambledId) {
        $this->ARGS('s', func_get_args());
        return Security::decodeHashId($scrambledId);
    }

    function u_unique_id() {

        $this->ARGS('', func_get_args());

        return Security::createUuid();
    }

    function u_char_list($listId) {

        $this->ARGS('s', func_get_args());

        $s = '';
        switch ($listId) {
            case 'all':          $s = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; break;
            case 'letters':      $s = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; break;
            case 'lettersLower': $s = 'abcdefghijklmnopqrstuvwxyz'; break;
            case 'lettersUpper': $s = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; break;
            case 'digits':       $s = '0123456789'; break;
            case 'hex':          $s = '0123456789abcdefABCDEF'; break;
            case 'hexUpper':     $s = '0123456789ABCDEF'; break;
            case 'hexLower':     $s = '0123456789abcdef'; break;
            case 'octal':        $s = '01234567'; break;
        }

        if (!$s) {
            $this->error('Unknown charList: `' . $listId . '`');
        }

        return OList::create(str_split($s));
    }

    function u_from_bytes($bytes) {

        $this->ARGS('l', func_get_args());

        foreach ($bytes as $b) {
            if (!is_int($b) || $b < 0) {
                $this->error('Argument #1 must be a list of positive integers.');
            }
        }

        $str = pack('C*', ...$bytes);

        return $str;
    }

    function u_last_replace_count() {

        $this->ARGS('', func_get_args());

        if ($this->lastReplaceCount < 0) {
            $this->error('No replacement method has been called yet.');
        }

        return $this->lastReplaceCount;
    }
}
