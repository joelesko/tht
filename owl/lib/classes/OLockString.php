<?php

namespace o;

class OLockString extends OVar {

    private $str = '';
    protected $type = 'text';
    private $bindParams = null;

    function __construct ($s) {
        if (OLockString::isa($s)) {
            $s = $s->getString();
        }
        $this->str = $s;
    }

    function __toString() {
        return '[LockString]';
    }

    static function create ($className, $s) {
        return new $className ($s);
    }

    static function getUnlocked ($s, $skipError=false) {
        if (!OLockString::isa($s)) {
            if ($skipError) { return $s; }
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
            Owl::error("`$caller` must be passed a LockString.  Ex: `L'...'`", $s);
        }
        return $s->getString();
    }

    function getString () {
        return $this->str;
    }

    function getParams () {
        return $this->bindParams;
    }

    function u_get_string_type() {
        return $this->type;
    }

    function u_unlocked () {
        if ($this->bindParams) {
            return v($this->str)->u_fill($this->bindParams);
        }
        return $this->str;
    }

    function u_fill ($params) {
        $p = uv($params);
        if (!is_array($p)) {
            $params = func_get_args();
        }
        $this->bindParams = uv($params);
        return $this;
    }

    function u_is_lock_string () {
        return true;
    }
}

class HtmlLockString  extends OLockString {  protected $type = 'html';  }
class LiteLockString  extends OLockString {  protected $type = 'lite';  }
class SqlLockString   extends OLockString {  protected $type = 'sql';   }
class JsLockString    extends OLockString {  protected $type = 'js';    }
class CssLockString   extends OLockString {  protected $type = 'css';   }
class JconLockString  extends OLockString {  protected $type = 'jcon';  }


