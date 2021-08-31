<?php

namespace o;

class ORegex extends OVar {

    protected $type = 'regex';
    static public $ALLOWED_FLAGS = 'msix';

    private $pattern = '';
    private $flags = '';
    private $startIndex = ONE_INDEX;

    function __toString() {
        return $this->getPattern();
    }

    function __construct ($pat, $flags='') {

        $this->pattern = $pat;
        $this->flags = $this->validateFlags($flags);
    }

    function getPattern () {

        $pat = str_replace('/', '\\/', $this->pattern);

        return '/' . $pat . '/' . $this->flags;
    }

    function getRawPattern() {

        $pat = str_replace('/', '\\/', $this->pattern);

        return $pat;
    }

    function u_flags ($f) {

        $this->ARGS('s', func_get_args());
        $this->flags = $this->validateFlags($f);

        return $this;
    }

    function u_start_index ($startIndex = ONE_INDEX) {

        $this->ARGS('i', func_get_args());
        $this->startIndex = $startIndex;

        return $this;
    }

    function getStartIndex() {

        return $this->startIndex;
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
            if (strpos(self::$ALLOWED_FLAGS, $f) === false) {
                $this->error("Invalid Regex flag `$f`.  Allowed flags: `m s i x`");
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

