<?php

namespace o;

class OPassword extends OClass {

    private $plainText = '';
    private $hash = '';

    function __construct ($plainText) {
        $this->plainText = $plainText;
    }

    function u_on_string_token() {

        return $this->u_x_danger_hash();
    }

    function u_to_sql_string() {

        $this->ARGS('', func_get_args());

        return $this->u_x_danger_hash();
    }

    function u_length() {

        $this->ARGS('', func_get_args());

        return mb_strlen($this->plainText);
    }

    function u_check_pattern($match) {

        $this->ARGS('*', func_get_args());

        if (!ORegex::isa($match)) {
            $this->error("1st argument must be a Regex string `r'...'`");
        }

        return v($this->plainText)->u_match($match) !== '';
    }

    function u_check($correctHash) {

        $this->ARGS('s', func_get_args());

        return Security::rateLimitedPasswordCheck($this->plainText, $correctHash);
    }

    function u_x_danger_hash() {

        $this->ARGS('', func_get_args());

        if (!$this->hash) {
            $this->hash = Security::hashPassword($this->plainText);
        }

        return $this->hash;
    }

    function u_x_danger_plain_text() {

        $this->ARGS('', func_get_args());

        return $this->plainText;
    }
}
