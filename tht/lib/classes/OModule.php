<?php

namespace o;

class OModule extends OClass implements \JsonSerializable {

    private $namespace;
    private $fullName;
    private $baseName;
    private $fields = [];
    private $exportedFunctions = [];

    private $inUserFactoryMethod = false;

    function u_type() {
        return 'module';
    }

    function error($msg, $args=null, $contextMethod='') {

        ErrorHandler::setHelpLink('/language-tour/modules', 'Modules');
        Tht::error($msg);
    }

    function __construct ($namespace, $path) {

        $this->namespace = $namespace;
        $base = basename($path, '.' . Tht::getThtExt());
        $this->baseName = $base;
        $this->fullName = $namespace . "\\" . u_($base);
    }

    function isConstant($k) {
        return preg_match('/^[A-Z]/', $k);
    }

    function __call ($uf, $args) {

        $qf = $this->namespace . "\\" . $uf;
        $m = $this->baseName;
        $f = unu_($uf);

        if (!in_array($uf, $this->exportedFunctions)) {
            if (!function_exists($qf)) {
                $this->error("Unknown function `$f` for module `$m`.");
            }
            else if (!$this->isLocalReference()) {
                $this->error("Can not call non-public function `$f` in module `$m`.");
            }
        }

        return call_user_func_array($qf, $args);
    }

    function __set($uk, $v) {

        $k = unu_($uk);

        if (!$this->isLocalReference()) {
            $n = $this->baseName;
            $this->error("Can not set field `$k` from outside of module `$n`.");
        }

        if ($this->isConstant($k)) {
            if (isset($this->fields[$uk])) {
                $this->error("Can not re-assign to constant field `$k`.");
            }
            else {
                if (OBag::isa($v)) {
                    $v->setReadOnly(true);
                }
            }
        }

        $this->fields[$uk] = $v;
    }

    function __get($uk) {

        $k = unu_($uk);
        if (!isset($this->fields[$uk])) {
            $this->error("Unknown module variable: `$k`");
        }
        else if (!$this->isConstant($k) && !$this->isLocalReference()) {
            $this->error("Can not read private module variable: `$k`");
        }

        return $this->fields[$uk];
    }

    function isLocalReference() {

        if ($this->baseName == '_page') {
            return true;
        }

        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
        if (isset($caller['file'])) {
            $callerFile = $caller['file']; // e.g. 'MyClass.tht.php'

            // include '_' as preceding delimiter in cache filename
            $thisClassMatch = '_' . $this->baseName . '.tht.php';

            return strpos($callerFile, $thisClassMatch) !== false;
        }
        return false;
    }

    function __invoke($args) {
        return $this->newObject($this->fullName, $args);
    }

    function newObject($className, $args) {

        $qc = $this->namespace . "\\" . u_($className);

        // Call user-defined factory method instead
        $qfactory = $this->namespace . "\\u_on_Create_Object";
        if (function_exists($qfactory) && !$this->inUserFactoryMethod) {
            $this->inUserFactoryMethod = true;
            $obj = call_user_func_array($qfactory, $args);
            $this->inUserFactoryMethod = false;
            if (!$obj) {
                Tht::error('Function `zNewObject()` must return an object.');
            }
            return $obj;
        }

        $o = new $qc ();
        $o->initObject($args);
        return $o;
    }

    function newAutoObject($args=[]) {

        $o = new $this->fullName ();
        $o->initObject($args);
        return $o;
    }

    function baseName() {
        return $this->baseName;
    }

    function toStringToken() {
        return OClass::getStringToken(
            $this->cleanPackageName($this->baseName) . ' Module'
        );
    }

    function bareClassName() {
        return $this->baseName();
    }

    function exportFunction($fn) {
        $this->exportedFunctions []= $fn;
    }

    function getExportedFunctions() {
        return $this->exportedFunctions;
    }
}

// Adapter for sideloaded module calls
class OModulePhpAdapter extends OModule {

    private $mod;

    function __construct ($mod) {
        $this->mod = $mod;
    }

    function __call ($f, $args) {
        $fnCall = function() use ($f, $args) {
            $uf = u_($f);
            $ret = $this->mod->__call($uf, $args);
            return unv($ret);
        };
        return ErrorHandler::catchErrors($fnCall);
    }
}
