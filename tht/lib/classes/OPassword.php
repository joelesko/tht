<?php

namespace o;

// Wrapper for incoming passwords to prevent leakage of plaintext
class OPassword extends OClass {

    private $plainText = '';
    private $hash = '';

    function __construct ($plainText) {
        $this->plainText = $plainText;
    }

    function __toString() {
        return $this->u_hash();
    }

    function u_hash() {
        $this->ARGS('', func_get_args());
        if (!$this->hash) {
            $this->hash = Security::hashPassword($this->plainText);
        }
        return $this->hash;
    }

    function u_match($correctHash) {
        $this->ARGS('s', func_get_args());
        return Security::verifyPassword($this->plainText, $correctHash);
    }

    function u_x_danger_plain_text() {
        $this->ARGS('', func_get_args());
        return $this->plainText;
    }
}
