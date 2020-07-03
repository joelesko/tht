<?php

namespace o;

class OClass implements \JsonSerializable {

    use HookMethods, CompositionSystem;

    protected $type = 'object';

    private $fieldsLocked = false;
    private $initMap = null;
    private $bareClassName = '';

    protected $suggestMethod = [];
    protected $errorContext = '';

    protected $isPublic = null;

    static function isa ($s) {
        if (is_object($s)) {
            $called = get_called_class();
            if ($called === get_class($s) || $called === get_parent_class($s)) {
                return true;
            }
        }
        return false;
    }

    public function bareClassName() {
        if ($this->bareClassName) {
            return $this->bareClassName;
        }
        $name = unu_ns_(get_class($this));
        $name = preg_replace('/^[O]([A-Z])/', '$1', $name);
        $this->bareClassName = $name;

        return $this->bareClassName;
    }

    // TODO: refactor with bareClassName
    function getErrorClass($filterUserClasses = false) {
        $raw = get_called_class();
        $plain = preg_replace('/o\\\\O?/', '', $raw);
        if (preg_match('/typeString/i', $plain)) {
            return 'TypeString';
        }
        if ($filterUserClasses && $plain === $raw) {
            return '';
        }
        return $plain;
    }

    // TODO: clean this up
    function error($msg, $args=null, $contextMethod='') {
        $c = $this->errorContext ? $this->errorContext : $this->type;
        ErrorHandler::addOrigin($c);
        $className = $this->getErrorClass(true);
        if ($className) {
            // Add manual link for std classes
            $classToken = strtolower(v($className)->u_to_token_case('-'));
            $docUrl = '/manual/class/' . $classToken;
            $label = $this->getErrorClass();
            if ($contextMethod) {
                $methToken = strtolower(v($contextMethod)->u_to_token_case('-'));
                $docUrl .= '/' . $methToken;
                $label .= '.' . v(unu_($contextMethod))->u_to_camel_case();
            }
            ErrorHandler::setErrorDoc($docUrl, $label);
        }
        Tht::error($msg, $args);
    }

    function oopError($msg) {
        ErrorHandler::setErrorDoc('/language-tour/classes-and-objects', 'Classes & Objects');
        $this->error($msg);
    }

    function ARGS($sig, $args) {
        $err = ARGS($sig, $args);
        if ($err) {
            $this->error($err['msg'], $args, $err['function']);
        }
    }

    function initObject($args) {

        // Fields
        $this->initFields('___init_fields', false);
        $this->initFields('___init_public_fields', true);

        // Intializer Map
        $this->handleInitializerMap($args);

        // OuterObjects
        if (method_exists($this, '___init_outer_objects')) {
            $this->___init_outer_objects();
        }

        // InnerObjects
        if (method_exists($this, '___init_inner_objects')) {
            $this->___init_inner_objects();
        }

        $this->fieldsLocked = true;

        // Call onCreate()
        if (method_exists($this, 'u_on_create')) {
            call_user_func_array([$this, 'u_on_create'], [$this->initMap]);
        }
    }

    // Automatically assign fields
    private function handleInitializerMap($args) {

        $numArgs = count($args);
        if (!$numArgs) {
            $this->initMap = OMap::create([]);
        }
        else if ($numArgs > 1) {
            $this->oopError('Constructor expects only one argument.');
        }
        else {

            $this->initMap = $args[0];

            if (!OMap::isa($this->initMap)) {
                $this->oopError('First argument must be a Map of field values.');
            }

            $myType = $this->bareClassName();

            foreach ($this->initMap as $k => $v) {
                $uk = u_($k);
                if (property_exists($this, $uk) && isset($this->isPublic[$uk])) {
                    $origType = v($this->$uk)->bareClassName();
                    $newType = v($v)->bareClassName();
                    if ($origType != $newType) {
                        $this->oopError("Field `$k` for class `$myType` must be of type `$origType`.  Got: `$newType`");
                    }
                    $this->$uk = $v;
                }
            }
        }
    }

    private function initFields($fn, $isPublic) {
        if (method_exists($this, $fn)) {
            $fields = $this->$fn();
            foreach ($fields as $k => $v) {
                $uk = u_($k);
                $this->$uk = $v;
                if ($isPublic) { $this->isPublic[$uk] = true; }
            }
        }
    }

    function unlockFields() {
        $this->fieldsLocked = false;
    }

    function u_type() {
        $this->ARGS('', func_get_args());
        return $this->type;
    }

    function u_class() {
        $this->ARGS('', func_get_args());
        return $this->bareClassName();
    }

    function __get ($ufield) {

        $plainField = unu_($ufield);

        // Getter override
        $autoGetter = u_('get' . ucfirst($plainField));
        if (method_exists($this, $autoGetter)) {
            return $this->$autoGetter();
        }

        // Look for field in outerObjects
        $outerObject = $this->deepFindField($ufield);
        if ($outerObject) {
            $this->requireDirectReference($outerObject, $plainField);
            return $outerObject->$ufield;
        }

        // User-created dynamic getter
        if (method_exists($this, 'u_on_get_missing_field')) {
            $result = $this->u_on_get_missing_field($field);
            if ($result->u_ok()) {
                return $result->u_get();
            }
        }

        // Field exists but is not public
        if (property_exists($this, $ufield)) {
            $this->oopError("Can not read private field `$plainField`.");
        }

        // Missing field error
        // $suggestion = '';
        // if (method_exists($this, 'u_zz_suggest_field')) {
        //     $suggestion = $this->u_zz_suggest_field($plainField);
        // }
        // $suggest = $suggestion ? " Try: `"  . $suggestion . "`" : '';

        if (!$suggest) {
            // If there is a method of the same name, suggest that
            if (method_exists($this, $ufield)) {
                $suggest = 'Try: Call method `' . $ufield . '()`';
            }
        }

        $this->error("Unknown field: `$plainField` $suggest");
    }

    function __set ($ufield, $value) {

        $plainField = unu_($ufield);

        if (property_exists($this, $ufield)) {
            $setter = 'set' . ucfirst($plainField);
            $this->oopError("Can not write directly to field `$plainField`. Try: Using a setter function, ex: `$setter`");
        }

        if ($this->fieldsLocked) {
            $this->oopError("New field `$plainField` can not be created after object is constructed.");
        }

        $this->$ufield = $value;
    }

    // Don't let host object rely on forwarding. It must call explicitly.
    function requireDirectReference($outerObject, $method) {

        if ($outerObject === $this) {
            return;
        }

        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
        if (isset($caller['file'])) {
            $callerFile = $caller['file']; // e.g. 'MyClass.tht.php'

            // include '_' as preceding delimiter in cache filename
            $thisClassMatch = '_' . $this->bareClassName . '.tht.php';

            // If caller is the same class hosting the outerObject, don't allow delegation to happen
            if (strpos($callerFile, $thisClassMatch) !== false) {
                $outerObjectName = $outerObject->bareClassName;
                $this->error("Must call innerObject method directly. Ex: `@.$outerObjectName.$method`");
            }
        }
    }

    function __call ($aMethod, $args) {

        $method = unu_($aMethod);

        if (method_exists($this, $aMethod)) {
            $this->oopError("Can not call private function `$method`.");
        }

        // Look up method in outerObjects
        $outerObject = $this->findOuterObjectWithMethod($aMethod);
        if ($outerObject) {
            $this->requireDirectReference($outerObject, $method);
            return call_user_func_array([$outerObject, $aMethod], $args);
        }

        // See if there is a dynamic call handler
        if (method_exists($this, 'u_on_call_missing_method')) {
            $result = $this->u_on_call_missing_method($method, v($args));
            if ($result->u_ok()) {
                return $result->u_get();
            }
        }

        // Error: Missing method
        $suggestion = '';
        if (property_exists($this, 'suggestMethod')) {
            $umethod = strtolower($method);
            if (isset($this->suggestMethod[$umethod])) {
                $suggestion = '`' . $this->suggestMethod[$umethod] . '`';
            }
        }

        // Fuzzy match with existing methods
        if (!$suggestion) {
            $mismatchMethod = $this->findMismatchMethod($aMethod);
            if ($mismatchMethod) {
                $suggestion = '`' . $mismatchMethod . '` (exact case)';
            }
        }

        $suggest = $suggestion ? " Try: $suggestion" : '';

        $c = $this->bareClassName;
        $this->error("Unknown method `$method` for class `$c`. $suggest");
    }

    function fuzzyToken($token) {
        $fuzzy = preg_replace('/_/', '',  unu_(strtolower($token)));
        return $fuzzy;
    }

    function findMismatchMethod($findMethod) {
        $reflect = new \ReflectionClass($this);
        $findMethod = $this->fuzzyToken($findMethod);
        $methods = $reflect->getMethods();
        foreach ($methods as $m) {
            if (!hasu_($m->name)) { continue; }
            $fuzzy = $this->fuzzyToken($m->name);
            if ($fuzzy == $findMethod) {
                return $m->name;
            }
        }
        return '';
    }

    function u_z_call_method($method, $args=[]) {
        $this->ARGS('sl', func_get_args());
        $uMethod = u_($method);
        return call_user_func_array([ $this, $uMethod ], uv($args));
    }

    function u_z_set_field($field, $value) {
        $this->ARGS('s*', func_get_args());
        $uField = u_($field);
        $this->$uField = $value;
    }

    function u_z_get_field($field) {
        $this->ARGS('s', func_get_args());
        $uField = u_($field);
        return $this->$uField;
    }

    function u_z_has_method($method) {
        $this->ARGS('s', func_get_args());
        return method_exists($this, u_($method));
    }

    function u_z_has_field($field) {
        $this->ARGS('s', func_get_args());
        return property_exists($this, u_($field));
    }

    function u_z_get_fields() {
        $this->ARGS('', func_get_args());

        // When cast as an array, we can look at the prop keys.
        // Private and protected keys start with a null \0 character.
        // Have to do this for runtime reflection.
        $fields = (array)$this;
        $uFields = [];
        foreach ($fields as $k => $v) {
            if ($k[0] !== "\0" && hasu_($k)) {
                $uFields[unu_($k)] = $v;
            }
        }
        return OMap::create($uFields);
    }

    function u_z_set_fields ($fieldMap) {
        $this->ARGS('m', func_get_args());
        foreach (uv($fieldMap) as $k => $v) {
            $this->u_z_set_field($k, $v);
        }
    }

    function u_z_get_methods () {
        $this->ARGS('', func_get_args());
        if ($this instanceof OModule) {
            // TODO: fix case
            $methods = $this->getExportedFunctions();
        } else {
            $methods = get_class_methods(get_called_class());
        }
        return $this->userDefinedElements($methods);
    }

    private function userDefinedElements($elements) {
        $userElements = [];
        foreach ($elements as $e) {
            if (hasu_($e)) {
                $userElements []= v(unu_($e))->u_to_camel_case();
            }
        }
        sort($userElements);
        return $userElements;
    }

    function u_z_hash_code() {
        $this->ARGS('', func_get_args());

        if (method_exists($this, 'u_on_hash_code')) {
            return call_user_func_array([$this, 'u_on_hash_code'], []);
        } else {
            return spl_object_hash($this);
        }
    }

}

trait HookMethods {

    // function getObjectValString() {
    //     $s = '';
    //     foreach (get_object_vars($this) as $k => $v) {
    //         if ($k !== 'outerObjectParent') {
    //             $s .= v($v)->getObjectValString();
    //         }
    //     }
    // }

    // function u_equals($obj) {
    //     $this->ARGS('*', func_get_args());

    //     if (method_exists($this, 'u_on_equals')) {
    //         return call_user_func_array([$this, 'u_on_equals'], [$obj]);
    //     } else {
    //         return $this->u_z_hash_code() === $obj->u_z_hash_code();
    //     }
    // }

    // function u_z_clone() {

    //     $cl = clone $this;

    //     foreach (get_object_vars($this) as $k => $v) {
    //         if (gettype($v) == 'object' && $k !== 'outerObjectParent') {
    //             $cl->setCloneField($k, $v);
    //         }
    //     }

    // function setCloneField($k, $v) {
    //     $this->$k = v($v)->u_z_clone();
    // }

    function __clone() {
        if (method_exists($this, 'u_on_clone')) {
            call_user_func_array([$this, 'u_on_clone'], []);
        }
    }

    function __destruct() {
        if (method_exists($this, 'u_on_destroy')) {
            call_user_func_array([$this, 'u_on_destroy'], []);
        }

        $this->releaseParents();
    }

    function __wakeup () {
        if (method_exists($this, 'u_on_wakeup')) {
            call_user_func_array([$this, 'u_on_wakeup'], []);
        }
    }

    function __toString () {

        if (method_exists($this, 'u_on_to_string')) {
            return call_user_func_array([$this, 'u_on_to_string'], []);
        }

        $propMap = [
            'class'         => $this->bareClassName(),
            'privateFields' => OMap::create([]),
            'publicFields'  => OMap::create([]),
            'methods'       => OList::create([]),
            'innerObjects'  => OList::create([]),
            'outerObjects'  => OList::create([]),
        ];

        $reflect = new \ReflectionClass($this);
        $props = $reflect->getProperties();

        foreach ($props as $p) {
            if (!hasu_($p->name)) { continue; }
            $bucket = 'privateFields';
            if (isset($this->isPublic[$p->name])) { $bucket = 'publicFields'; }
            $plainField = unu_($p->name);
            $value = $this->{$p->name};
            if (is_object($value) && !(OMap::isa($value) || OList::isa($value))) {
                $value = '<<<' . $value->bareClassName() . '>>>';
            }
            $propMap[$bucket][$plainField] = $value;
        }
        ksort($propMap['publicFields']->val);
        ksort($propMap['privateFields']->val);

        $methods = $reflect->getMethods();
        foreach ($methods as $m) {
            if (!hasu_($m->name)) { continue; }
            $declareClass = unu_ns_($m->getDeclaringClass()->name);
            if ($declareClass !== $this->bareClassName) { continue; }
            $plainField = unu_($m->name);
            $propMap['methods'] []= $plainField;
        }
        sort($propMap['methods']->val);

        if ($this->outerObjects) {
            foreach ($this->outerObjects as $m) {
                $propMap['outerObjects'] []= $m->bareClassName;
            }
            sort($propMap['outerObjects']->val);
        }

        if ($this->innerObjects) {
            foreach ($this->innerObjects as $p) {
                $propMap['innerObjects'] []= $p->bareClassName;
            }
            sort($propMap['innerObjects']->val);
        }

        return OMap::create($propMap);
    }

    // called when passed to json_encode
    function jsonSerialize() {
        if (method_exists($this, 'u_on_to_json')) {
            return call_user_func_array([$this, 'u_on_to_json'], []);
        }
        return $this->__toString();
    }
}

trait CompositionSystem {

    private $parentObject = null;
    private $outerObjects = null;
    private $innerObjects = null;

  //  private $outerObjectByName = false;

    // public function u_outerObject($name) {
    //     $this->ARGS('S', func_get_args());
    //     if (!$this->outerObjects || !isset($this->outerObjectByName[$name])) {
    //         $this->error("Unknown outerObject: `$name`");
    //     }
    //     return $this->outerObjectByName[$name];
    // }

    private function releaseParents() {
        if ($this->outerObjects) {
            foreach ($this->outerObjects as $m) {
                $m->releaseParent();
            }
            foreach ($this->innerObjects as $p) {
                $p->releaseParent();
            }
        }
    }

    public function u_z_add_outer_object($obj) {
        $this->ARGS('*', func_get_args());
        return $this->addOuterObject($obj);
    }

    public function u_z_add_inner_object($obj) {
        $this->ARGS('*', func_get_args());
        return $this->addInnerObject($obj);
    }

    public function addOuterObjects(...$outerObjectObjs) {
        foreach ($outerObjectObjs as $obj) {
            $this->addOuterObject($obj);
        }
    }

    public function addInnerObjects(...$innerObjectObjs) {
        foreach ($innerObjectObjs as $obj) {
            $this->addInnerObject($obj);
        }
    }

    private function addOuterObject($outerObject) {

        // Auto-instantiate
        if ($outerObject instanceof OModule) {
            $outerObject = $outerObject->newAutoObject([$this->initMap]);
        }

        $outerObject->setParentObject($this);

        $fns = get_class_methods($outerObject);

        // Check for duplicate functions across outerObjects
        foreach ($fns as $fn) {

            // Only check if public
            $ref = new \ReflectionMethod($outerObject, $fn);
            if (!$ref->isPublic()) { continue; }

            // Locally overrided & public
            if (method_exists($this, $fn)) {
                $ref = new \ReflectionMethod($this, $fn);
                if ($ref->isPublic()) { continue; }
            }

            $p1 = $this->findOuterObjectWithMethod($fn);
            if ($p1) {
                $prevOuterObjectName = $p1->bareClassName();
                $addedOuterObjectName = $outerObject->bareClassName();
                $this->oopError("Duplicate function `$fn()` found in outerObject `$prevOuterObjectName` while adding `$addedOuterObjectName`.
                    You must override it in the local class.");
            }
        }

        // Last in first
        if (!$this->outerObjects) {
            $this->outerObjects = [];
        }
        array_unshift($this->outerObjects, $outerObject);

        $outerObjectName = $outerObject->bareClassName();
        $uOuterObjectField = u_($outerObjectName);
        $this->forceCreateField($uOuterObjectField, $outerObject);
        $this->setFieldVisibility($uOuterObjectField, false);
    }

    private function addInnerObject($innerObject) {

        if ($innerObject instanceof OModule) {
            $innerObject = $innerObject->newAutoObject([$this->initMap]);
        }

        $innerObject->setParentObject($this);

        if (!$this->innerObjects) {
            $this->innerObjects = [];
        }
        array_unshift($this->innerObjects, $innerObject);

        $innerObjectName = $innerObject->bareClassName();
        $uInnerObjectField = u_($innerObjectName);
        $this->forceCreateField($uInnerObjectField, $innerObject);
        $this->setFieldVisibility($uInnerObjectField, false);
    }

    protected function forceCreateField($uKey, $val) {
        $this->fieldsLocked = false;
        $this->$uKey = $val;
        $this->fieldsLocked = true;
    }

    protected function setFieldVisibility($uField, $isPublic) {
        $refObject = new \ReflectionObject($this);
        $refProperty = $refObject->getProperty($uField);
        $refProperty->setAccessible($isPublic);
    }

    // public function u_has_outerObject ($outerObjectName) {

    //     if (!$this->outerObjects) {
    //         return false;
    //     }

    //     if ($outerObjectName instanceof OModule) {
    //         $outerObjectName = $outerObjectName->baseName();
    //     }

    //     if (isset($this->hasOuterObjectByName[$outerObjectName])) {
    //         return $this->hasOuterObjectByName[$outerObjectName];
    //     }

    //     $has = false;
    //     foreach ($this->outerObjects as $outerObject) {
    //         $c = $outerObject->bareClassName();
    //         if ($c == $outerObjectName) {
    //             $has = true;
    //             break;∂
    //         }
    //         $hasOuterObject = $outerObject->u_has($outerObjectName);
    //         if ($hasOuterObject) {
    //             $has = true;
    //             break;
    //         }
    //     }

    //     $this->hasOuterObjectByName[$outerObjectName] = $has;

    //     return $has;
    // }

    public function u_has_method($ufn) {

        $this->ARGS('s', func_get_args());
        if (method_exists($this, $ufn)) {
            $reflection = new \ReflectionMethod($this, $ufn);
            return $reflection->isPublic();
        }

        $outerObject = $this->findOuterObjectWithMethod($ufn);
        if ($outerObject) {
            return true;
        }

        return false;
    }

    private function findOuterObjectWithMethod($ufn) {

        if (!$this->outerObjects) {
            return false;
        }

        foreach ($this->outerObjects as $outerObject) {
            if ($outerObject->u_has_method($ufn)) {
                return $outerObject;
            }
        }

        return false;
    }

    private function deepFindField($ufield) {

        if (property_exists($this, $ufield)) {
            if (isset($this->isPublic[$ufield])) {
                return $this;
            }
            else {
                return false;
            }
        }

        if (!$this->outerObjects) {
            return false;
        }

        foreach ($this->outerObjects as $outerObject) {
            $outerObject = $outerObject->deepFindField($ufield);
            if ($outerObject) {
                return $outerObject;
            }
        }

        return false;
    }

    function setParentObject($parentObject) {
        $this->parentObject = $parentObject;
    }

    function getParentObject() {
        return $this->parentObject;
    }

    function u_parent($getTop = false) {

        $this->ARGS('f', func_get_args());

        if (!$this->parentObject) {
            Tht::error('This object has no parent object.');
        }

        if ($getTop) {
            $top = $this;
            while (true) {
                $p = $top->getParentObject();
                if (!$p) { return $top; }
                $top = $p;
            }
        } else {
            return $this->parentObject;
        }
    }

    public function releaseParent() {
        $this->parentObject = null;
    }
}



