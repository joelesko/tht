<?php

namespace o;

class OFlag extends OVar {

    protected $type = 'flag';

    public $val = [];
    private $allowed = null;
  
    protected $errorContext = 'flag';

    public static function create() {

        return self::createFromList(func_get_args());
    }

    static function createFromList($list) {

        $fl = new OFlag();

        foreach ($list as $arg) {
            $fl->add($arg);
        }

        return $fl;
    }

    static function createFromArg ($fnName, $obj, $allowed=null) {

        if (self::isa($obj)) {
            return $obj;
        }

        // Allow string '-someFlag' mainly for internal convenience
        if (is_string($obj)) {
            $obj = [$obj];
        }

        if (!is_array($obj)) {
            Tht::error("Function `$fnName` expects a Flag argument. Got: `" . v($obj)->u_type() . "`");
        }

        $flag = self::createFromList($obj);

        // Pre-define allowlist
        if ($allowed) {
            $flag->allow(
                is_string($allowed) ? [$allowed] : $allowed
            );
        }

        return $flag;
    }

    public function __toString() {
        return $this->toStringToken();
    }

    function toStringToken() {

        $fmt = $this->u_to_string();

        return OClass::getStringToken('Flag', $fmt);
    }

    public function jsonSerialize():mixed {

        return $this->toStringToken();
    }

    function u_to_boolean() {

        $this->ARGS('', func_get_args());

        return !$this->isDefault();
    }

    function u_to_string() {

        $this->ARGS('', func_get_args());

        if ($this->isDefault()) {
            return '-default';
        }

        return implode('|', array_keys($this->val));
    }

    // Internal - handle flag arguments as strings
    function is($flagStr) {

        return isset($this->val[$flagStr]);
    }

    function u_is($checkFlag) {

        $this->ARGS('f', func_get_args());

        if ($checkFlag->isDefault() && $this->isDefault()) {
            return true;
        }

        if (!$this->allowed) {
            // Auto-add flag to allow list
            $this->u_allow(OList::create([$checkFlag]));
        }
        else {
            if (!$this->isAllowed($checkFlag)) {
                $sFlag = $checkFlag->u_to_string();
                $msg = "Unknown flag: `$sFlag`  To support more than one possible value, call the `allow` method. Try: `\$flag->allow([-flag1, -flag2])`";
                ErrorHandler::setHelpLink('/manual/module/class/flag/allow', 'Flag.allow');
                Tht::error($msg);
            }
        }

        $matches = 0;
        foreach ($this->val as $fl => $x) {
            foreach ($checkFlag->val as $cfl => $x) {
                if ($fl == $cfl) { $matches += 1; }
            }
        }

        return $matches > 0 && count($checkFlag->val) == $matches;
    }

    function isDefault() {
        return count($this->val) == 0;
    }

    function u_add($addFlag) {

        $this->ARGS('f', func_get_args());

        $newFlag = clone $this;
        foreach ($addFlag->val as $fl => $x) {
            $newFlag->add($fl);
        }

        return $newFlag;
    }

    // Internal - mutates
    function add($flag) {

        if ($flag != '-default') {
            $this->val[$flag] = true;
        }

        return $this;
    }

    function u_remove($removeFlag) {

        $this->ARGS('f', func_get_args());

        $newFlag = clone $this;
        foreach ($removeFlag->val as $fl => $x) {
            $newFlag->remove($fl);
        }

        return $newFlag;
    }

    function u_keep($keepFlag) {

        $this->ARGS('f', func_get_args());

        $newFlag = clone $this;
        $newFlag->val = [];
        foreach ($keepFlag->val as $fl => $x) {
            if ($keepFlag->is($fl)) { $newFlag->add($fl); }
        }

        return $newFlag;
    }

    // Internal - mutates
    function remove($flag) {

        unset($this->val[$flag]);

        return $this;
    }

    function u_allow($listOfFlags) {

        $this->ARGS('l', func_get_args());

        $this->allowed = [];

        foreach ($listOfFlags as $okFlag) {
            foreach ($okFlag->val as $okFl => $x) {
                $this->allowed[$okFl] = true;
            }
        }

        if (!$this->isAllowed($this)) {
            $msg = "Unknown flag: `$fl` Try: " . implode(', ', array_keys($this->allowed));
            // Not using $this->error because we want the error link to point to the host object.
            Tht::error($msg);
        }

        return $this;
    }

    // Internal - handle flag arguments as strings
    function allow($flagList) {

        $this->allowed = [];

        foreach ($flagList as $fl) {
            $this->allowed[$fl] = true;
        }

        if (!$this->isAllowed($this)) {
            $msg = "Unknown flag: `$fl` Try: " . implode(', ', array_keys($this->allowed));
            $this->error($msg);
        }

        return $this;
    }

    function isAllowed($flag) {

        if (!$this->allowed) { return true; }

        foreach ($flag->val as $fl => $x) {

            if ($fl == '-default') { continue; }

            if (!isset($this->allowed[$fl])) {
                return false;
            }
        }

        return true;
    }
}


