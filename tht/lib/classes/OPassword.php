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
        ARGS('', func_get_args());
        if (!$this->hash) {
            $this->hash = Security::hashPassword($this->plainText);
        }
        return $this->hash;
    }

    function u_match($correctHash) {
        ARGS('s', func_get_args());
        return password_verify($this->plainText, $correctHash);
    }

    function u_danger_danger_plain_text() {
        ARGS('', func_get_args());
        return $this->plainText;
    }
}
