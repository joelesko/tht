<?php

namespace o;

class ORegex extends OVar {
    private $pattern = '';
    private $flags = '';

    function __toString() {
        return $this->getPattern();
    }

    function __construct ($pat, $flags='') {
        $pat = str_replace('\\$', '$', $pat);  // unescape dollar from tokenizer

        $this->pattern = $pat;
        $this->flags = $this->validateFlags($flags);
    }

    function getPattern () {
        $pat = str_replace('/', '\\/', $this->pattern);
        return '/' . $pat . '/u' . $this->flags;
    }

    function u_flags ($f) {
        $this->flags = $this->validateFlags($f);
        return $this;
    }

    function validateFlags($flags) {
        $flags = trim($flags);
        if (!$flags) { return ''; }
        foreach (str_split($flags) as $f) {
            if (strpos('msix', $f) === false) {
                Tht::error("Invalid ORegex flag `$f`.  Allowed flags: `m s i x`");
            }
        }
        return $flags;
    }
}

class u_Regex {
    function u_new ($pattern, $flags) {
        return new ORegex ($pattern, $flags);
    }
}

