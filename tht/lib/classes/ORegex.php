<?php

namespace o;

class ORegex extends OVar {

    protected $type = 'regex';

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
        return '/' . $pat . '/' . $this->flags;
    }

    function u_flags ($f) {
        $this->ARGS('s', func_get_args());
        $this->flags = $this->validateFlags($f);
        return $this;
    }

    function addFlag($flag) {
        if (strpos($this->flags, $flag) === false) {
            $this->flags .= $flag;
        }
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

class u_Regex extends OClass {

    function newObject ($className, $args) {
        if (!isset($args[1])) {
            $args[1] = '';
        }
        $this->ARGS('ss', $args);
        return new ORegex ($args[0], $args[1]);
    }
}

