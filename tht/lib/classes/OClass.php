<?php

namespace o;


trait HookMethods {

    function u_equals($obj) {

        $this->ARGS('*', func_get_args());

        // compare with primitive type
        $otherType = gettype($obj);
        if ($otherType !== 'object') {
            if (property_exists($this, 'val')) {
                if (gettype($this->val) !== $otherType) {
                    return false;
                }
                return $this->val === $obj;
            }
            else {
                return false;
            }
        }

        if ($this->u_z_class_name() !== $obj->u_z_class_name()) {
            return false;
        }
        else if (method_exists($this, 'u_on_equals')) {
            return call_user_func_array([$this, 'u_on_equals'], [$obj]);
        }
        else {
            return $this->u_z_hash_code() === $obj->u_z_hash_code();
        }
    }

    // can override
    public function u_is_truthy() {
        return true;
    }

    function u_z_clone() {

        $this->ARGS('', func_get_args());

        $cl = clone $this;

        return $cl;
    }

    // Called on the newly created clone
    function __clone() {

        if (method_exists($this, 'u_on_clone')) {
            call_user_func_array([$this, 'u_on_clone'], []);
        }
    }

    function __destruct() {

        if (method_exists($this, 'u_on_destroy')) {
            call_user_func_array([$this, 'u_on_destroy'], []);
        }
    }

    function __wakeup () {

        if (method_exists($this, 'u_on_wakeup')) {
            call_user_func_array([$this, 'u_on_wakeup'], []);
        }
    }

    // Generally, we want to avoid the ambiguity of a general object.toString.
    // Except for base types, when representing an object as a string,
    // it should be in token format ⟪ MyClass ⟫.
    // Otherwise a class should provide methods for getting the exact string the user wants.
    //  e.g. via a format() method or getter.

    // When auto-cast as string (e.g. via join)
    function __toString() {

        return $this->toStringToken();
    }

    // Called when passed to json_encode or print
    // TODO: This should probably be handled by a onToJson hook.
    // Problem is right now, `print` serializes via json.
    // Print should always get the token, but json should actually serialize.
    function jsonSerialize():mixed {

        return $this->toStringToken();
    }

    final public function u_z_string_token() {

        $this->ARGS('', func_get_args());

        return $this->toStringToken();
    }

    // Override.  Returns object summary.
    public function u_on_string_token() {

        $this->ARGS('', func_get_args());

        return '';
    }

    public function u_to_sql_string() {

        $this->ARGS('', func_get_args());

        return $this->toStringToken();
    }

    function toStringToken() {

        $summary = $this->u_on_string_token();

        return self::getStringToken($this->bareClassName(), $summary);
    }
}




class OClass implements \JsonSerializable {

    use HookMethods; // , CompositionSystem;

    protected $type = 'object';

    private $fieldsLocked = false;
    private $initMap = null;
    private $bareClassName = '';

    protected $suggestMethod = [];
    protected $errorContext = '';
    protected $errorClass = '';
    protected $cleanClass = '';

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

    static public function getStringToken($className, $summary = null, $addQuotes = false) {

        $str = $className;

        if (!is_null($summary)) {
            $summary = preg_replace('/\s+/', ' ', trim($summary));
            $summary = v($summary)->u_limit(30, '…');
            if ($addQuotes) {
                $summary = "`$summary`";
            }
            $str .= " $summary";
        }

        return '⟪ ' . $str . ' ⟫';
    }

    // Print bare object without surrounding quotes
    static public function tokensToBareStrings($raw) {
         return preg_replace('/\'⟪(.+?)⟫\'/', '⟪$1⟫', $raw);
    }

    protected function cleanPackageName($p) {

        $p = preg_replace('/\\\\+/', '/', $p);

        $parts = explode('/', $p);

        $name = array_pop($parts);
        $name = unu_($name);
        $parts []= ucfirst($name);

        $p = implode('/', $parts);
        $p = str_replace('o/', '', $p);
        $p = str_replace('tht/', '', $p);

        return $p;
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

        if ($this->errorClass) {
            return $this->errorClass;
        }

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

    function error($msg, $contextMethod='') {

        $context = $this->errorContext ? $this->errorContext : $this->type;
        ErrorHandler::addOrigin($context);

        $addedError = $this->addErrorHelpLink($contextMethod);

        if (!$addedError) {
            $msg .= ' Object: `' . $this->bareClassName() . '`';
        }

        Tht::error($msg);
    }

    // Add manual link for std classes
    function addErrorHelpLink($method = '') {

        $className = $this->getErrorClass(true);
        if (!$className) {
            return false;
        }

        ErrorHandler::setStdLibHelpLink('class', $className, $method);

        return true;
    }

    function oopError($msg) {

        ErrorHandler::setHelpLink('/language-tour/oop/classes-and-objects', 'Classes & Objects');
        $this->error($msg);
    }

    function argumentError($msg, $method) {

        $methodToken = v(unu_($method))->u_slug();
        $methodLabel = unu_($method);

        $label = $this->bareClassName() . '.' . $methodLabel;

        ErrorHandler::setHelpLink('/manual/class/' . strtolower($this->bareClassName()) . '/' . $methodToken, $label);
        ErrorHandler::addOrigin('stdClass.' . strtolower($this->bareClassName()));

        Tht::error($msg);
    }

    function ARGS($sig, $args) {

        $err = validateFunctionArgs($sig, $args);
        if ($err) {
            $this->argumentError($err['msg'], $err['function']);
        }
    }

    // Validate a map of arguments
    function flags($userFlags, $config) {

        if (is_null($userFlags)) {
            $userFlags = OMap::create([]);
        }

        try {
            $userMap = OMap::create($userFlags);
            $configMap = OMap::create($config);
            $userMap->u_check($configMap);

            return $userMap;
        }
        catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    function initObject($args) {

        // Fields
        $this->initFields('___init_fields', false);
        $this->initFields('___init_public_fields', true);

        // Intializer Map
        $this->handleInitializerMap($args);

        // // EmbeddedObjects
        // if (method_exists($this, '___init_embedded_objects')) {
        //     $this->___init_embedded_objects();
        // }

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
                    $origType = v($this->$uk)->u_type();
                    $newType = v($v)->u_type();
                    if ($origType != $newType) {
                        $this->oopError("Field `$k` must be of type `$origType`.  Got: `$newType`");
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

    public function u_is_list() {

        $this->ARGS('', func_get_args());
        return $this->type == 'list';
    }

    public function u_is_map() {

        $this->ARGS('', func_get_args());
        return $this->type == 'map';
    }

    public function u_is_string() {

        $this->ARGS('', func_get_args());
        return $this->type == 'string';
    }

    public function u_is_number() {

        $this->ARGS('', func_get_args());
        return $this->type == 'number';
    }

    public function u_is_function() {

        $this->ARGS('', func_get_args());
        return $this->type == 'function';
    }

    public function u_is_regex() {

        $this->ARGS('', func_get_args());
        return $this->type == 'regex';
    }

    public function u_is_type_string() {

        $this->ARGS('', func_get_args());
        return $this->type == 'typeString';
    }

    function u_z_class_name() {

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

        // User-created dynamic getter
        if (method_exists($this, 'u_on_get_missing_field')) {
            $result = $this->u_on_get_missing_field($field);
            if ($result->u_ok()) {
                return $result->u_get();
            }
        }

        // $objWithField = $this->deepFindField($ufield);
        // if ($objWithField) {
        //     return $objWithField->$ufield;
        // }

        if (property_exists($this, $ufield)) {
            if (isset($this->isPublic[$ufield])) {
                return $this->$ufield;
            }
        }

        // Field exists but is not public
        if (property_exists($this, $ufield)) {
            $this->oopError("Can not read private field `$plainField`.");
        }

        // If there is a method of the same name, suggest that
        $suggest = '';
        if (method_exists($this, $ufield)) {
            $suggest = 'Try: Call method `' . $ufield . '()`';
        }

        $myType = $this->bareClassName();
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


    function __call ($aMethod, $args) {

        $method = unu_($aMethod);

        if (method_exists($this, $aMethod)) {
            $this->oopError("Can not call private method `$method`.");
        }

        // Look up method in embeddedObjects
        // $embeddedObject = $this->findEmbeddedObjectWithMethod($aMethod);
        // if ($embeddedObject) {
        //     return call_user_func_array([$embeddedObject, $aMethod], $args);
        // }

        // See if there is a dynamic call handler
        if (method_exists($this, 'u_on_call_missing_method')) {
            $result = $this->u_on_call_missing_method($method, v($args));
            if ($result->u_ok()) {
                return $result->u_get();
            }
        }

        $suggestion = $this->getSuggestedMethod($method);

        $suggest = $suggestion ? " Try: $suggestion" : '';

        $c = $this->bareClassName();

        $this->error("Unknown method `$method` for object `$c`. $suggest");
    }

    function getSuggestedMethod($method) {

        $suggestion = '';

        $umethod = strtolower($method);

        if (method_exists($this, $umethod)) {
            $suggest = 'Try: Call method `' . $ufield . '()`';
        }

        // Common alias,  e.g. explode -> split
        if (property_exists($this, 'suggestMethod')) {
            $fuzzyMethod = strtolower($method);
            if (isset($this->suggestMethod[$fuzzyMethod])) {
                $suggestion = '`' . $this->suggestMethod[$fuzzyMethod] . '`';
            }
        }

        // Fuzzy match with existing methods
        if (!$suggestion) {
            $mismatchMethod = $this->findMismatchMethod($method);
            if ($mismatchMethod) {
                $suggestion = '`' . unu_($mismatchMethod) . '` (exact case)';
            }
        }

        return $suggestion;
    }

    function fuzzyToken($token) {

        $fuzzy = str_replace('z_', '', $token);
        $fuzzy = preg_replace('/_/', '',  unu_($fuzzy));

        return strtolower($fuzzy);
    }

    function findMismatchMethod($findMethod) {

        $reflect = new \ReflectionClass($this);
        $findMethod = $this->fuzzyToken($findMethod);
        $methods = $reflect->getMethods();

        foreach ($methods as $m) {
            if (!hasu_($m->name)) { continue; }

            $fuzzy = $this->fuzzyToken($m->name);

            // mymethod --> myMethod
            if ($fuzzy == $findMethod) {
                return $m->name;
            }

            // foo --> getFoo
            $findMethodGet = 'get' . $findMethod;
            if ($fuzzy == $findMethodGet) {
                return $m->name;
            }

            // getFoo --> foo
            $fuzzyGet = 'get' . $fuzzy;
            if ($fuzzyGet == $findMethod) {
                return $m->name;
            }

            // foo --> setFoo
            $findMethodSet = 'set' . $findMethod;
            if ($fuzzy == $findMethodSet) {
                return $m->name;
            }
        }

        return '';
    }

    function u_z_call_method($method, $args=[]) {

        $this->ARGS('sl', func_get_args());
        $uMethod = u_($method);

        return call_user_func_array([ $this, $uMethod ], unv($args));
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

        foreach (unv($fieldMap) as $k => $v) {
            $this->u_z_set_field($k, $v);
        }
    }

    function u_z_get_methods () {

        $this->ARGS('', func_get_args());

        if ($this instanceof OModule) {
            // TODO: fix case
            $methods = $this->getExportedFunctions();
        }
        else {
            $methods = get_class_methods(get_called_class());
        }

        return $this->userDefinedElements($methods);
    }

    private function userDefinedElements($elements) {

        $userElements = [];

        foreach ($elements as $e) {
            if (hasu_($e)) {
                $userElements []= v(unu_($e))->u_camel_case();
            }
        }

        sort($userElements);

        return $userElements;
    }

    function u_z_hash_code() {

        $this->ARGS('', func_get_args());

        if (method_exists($this, 'u_on_hash_code')) {
            return call_user_func_array([$this, 'u_on_hash_code'], []);
        }
        else if (property_exists($this, 'val')){
            return md5(serialize($this->val));
        }
        else {
            return spl_object_hash($this);
        }
    }

}
