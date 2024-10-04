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

        ErrorHandler::addObjectDetails(OMap::create([
            'exportedFunctions' => $this->exportedFunctions,
            'fields' => $this->fields,
        ]), 'Module');

        ErrorHandler::setHelpLink('/language-tour/modules', 'Modules');
        Tht::error($msg);
    }

    function __construct($namespace, $path) {

        $this->namespace = $namespace;
        $base = basename($path, '.' . Tht::getThtExt());
        $this->baseName = $base;
        $this->fullName = $namespace . "\\" . u_($base);
    }

    function isConstant($k) {
        return preg_match('/^[A-Z]/', $k);
    }

    function __call($uf, $args) {

        $qf = $this->namespace . "\\" . $uf;
        $m = $this->baseName;
        $f = unu_($uf);

        if (!in_array($uf, $this->exportedFunctions)) {
            if (!function_exists($qf)) {
                $funs = ErrorHandler::filterUserlandNames($this->exportedFunctions);
                $suggest = ErrorHandler::getFuzzySuggest($f, $funs, 'isMethod');
                $this->error("Unknown function `$f` for module: `$m`  $suggest");
            }
            else if (!$this->isLocalReference()) {
                $this->error("Can't call non-public function `$f` in module: `$m`");
            }
        }

        return call_user_func_array($qf, $args);
    }

    function __set($uk, $v) {

        $k = unu_($uk);

        if (!$this->isLocalReference()) {
            $n = $this->baseName;
            $this->error("Can't set field `$k` from outside of module: `$n`");
        }

        if ($this->isConstant($k)) {
            if (isset($this->fields[$uk])) {
                $this->error("Can't write to read-only constant field: `$k`");
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

            $suggest = '';
            if (in_array($uk, $this->exportedFunctions)) {
                $suggest = 'Try: Call method `' . $k . '()`';
            }
            else {
                $suggest = ErrorHandler::getFuzzySuggest($k, array_keys($this->fields));
            }

            $this->error("Unknown module variable: `$k`  $suggest");
        }
        else if (!$this->isConstant($k) && !$this->isLocalReference()) {
            $this->error("Can't read private module variable: `$k`");
        }

        return $this->fields[$uk];
    }

    function u_z_call_method($method, $args=[]) {

        $this->ARGS('sl', func_get_args());

        return $this->__call(u_($method), unv($args));
    }

    function u_z_set_field($field, $value) {

        $this->error("Module fields are read-only and can not be set.");
    }

    function u_z_get_field($field) {

        $this->ARGS('s', func_get_args());

        $uField = u_($field);
        if (!isset($this->fields[$uField])) {
            $this->error("Module field does not exist: `$field`");
        }

        return $this->fields[$uField];
    }

    function u_z_get_fields() {

        $this->ARGS('', func_get_args());

        $fields = $this->fields;
        $uFields = [];
        foreach ($fields as $k => $v) {
            $uFields[unu_($k)] = $v;
        }

        return OMap::create($uFields);
    }

    function u_z_has_method($method) {

        $this->ARGS('s', func_get_args());

        return in_array(u_($method), $this->exportedFunctions);
    }

    function u_z_has_field($field) {

        $this->ARGS('s', func_get_args());

        return isset($this->fields[u_($field)]);
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

            return str_contains($callerFile, $thisClassMatch);
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

    function toObjectString() {
        return OClass::getObjectString(
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

