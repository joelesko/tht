<?php

namespace o;


trait HookMethods {

    function u_equals($obj) {

        $this->ARGS('_', func_get_args());

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
            return $this->u_z_object_id() === $obj->u_z_object_id();
        }
    }

    // can override
    public function isNull() {
        return false;
    }

    // can override
    public function u_to_boolean() {
        return true;
    }

    // pass by copy
    public function cloneArg() {
        return clone $this;
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

    function __wakeup() {

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

        return $this->toObjectString();
    }

    function jsonSerialize():mixed {

        return $this->u_z_to_json_string();
    }

    // << MyObject `summary` >>
    function toObjectString() {
        return self::getObjectString(
            $this->bareClassName(),
            $this->u_z_get_object_summary()
        );
    }

    public function u_z_get_object_summary() {

        $this->ARGS('', func_get_args());

        return '';
    }

    final public function u_z_to_object_string() {

        $this->ARGS('', func_get_args());

        return $this->toObjectString();
    }

    public function u_z_to_json_string() {

        $this->ARGS('', func_get_args());

        return $this->toObjectString();
    }

    public function u_z_to_print_string() {

        $this->ARGS('', func_get_args());

        return $this->toObjectString();
    }

    public function u_z_to_sql_string() {

        $this->ARGS('', func_get_args());

        return $this->toObjectString();
    }
}



// TODO: probably create a OStdClass class, like OStdModule
class OClass implements \JsonSerializable {

    use HookMethods; // , CompositionSystem;

    static $CURRENT_OBJECT_ID = 1000;
    private $objectId = 0;

    protected $type = 'object';

    private $fieldsLocked = false;
    private $initMap = null;
    private $bareClassName = '';

    protected $suggestMethod = [];
    protected $errorContext = '';
    protected $errorClass = '';
    protected $cleanClass = '';

    protected $isPublic = null;
    protected $allowAutoGetSet = true;

    private $coreSuggestMethod = [
        'class'     => 'zClassName()',
        'getclass'  => 'zClassName()',
    ];

    static function isa($child) {

        if (is_object($child)) {
            $parent = get_called_class();
            if ($parent === get_class($child) || is_subclass_of($child, $parent, true)) {
                return true;
            }
        }
        return false;
    }

    static public function getObjectString($className, $summary = null, $addQuotes = false) {

        $str = $className;

        if (!is_null($summary)) {
            $summary = preg_replace('/\s+/', ' ', trim($summary));
            $summary = v($summary)->u_limit(30, '…');
            if ($addQuotes) {
                $summary = "`$summary`";
            }
            if ($summary) {
                $str .= " $summary";
            }
        }

        return '《 ' . $str . ' 》';
    }

    // Print bare object without surrounding quotes
    static public function tokensToBareStrings($raw) {
        return preg_replace('/[\'\"]《(.+?)》[\'\"]/', '《$1》', $raw);
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

        if ($this->cleanClass) {
            return $this->cleanClass;
        }

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

    static function hasMethod($method) {
        return method_exists(get_called_class(), u_($method));
    }

    function error($msg) {

        $context = $this->errorContext ? $this->errorContext : $this->type;
        ErrorHandler::addOrigin($context);

        $detail = OMap::create([
            'methods' => $this->u_z_get_methods(),
            'fields' => $this->u_z_get_fields(),
        ]);

        if (isset($this->val)) {
            $detail['val'] = $this->val;
        }

        ErrorHandler::addObjectDetails($detail, $this->bareClassName());

        $this->addErrorHelpLink();

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

        $methodToken = v(unu_($method))->u_to_token_case('-');
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
    function flags($userFlagMap, $config) {

        if (is_null($userFlagMap)) {
            $userFlagMap = OMap::create([]);
        }

        try {
            $configMap = OMap::create($config);
            $userFlagMap->u_check($configMap);

            return $userFlagMap;
        }
        catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    // Validate an enum flag
    // TODO: allow
    function enumFlag($userFlag, $flags) {

        if (is_null($userFlag)) {
            return $flags[0];
        }

        $keys = $userFlag->u_keys();
        if (count($keys) !== 1) {
            return $flags[0];
        }
        $userFlag = $keys[ONE_INDEX];

        if (!in_array($userFlag, $flags)) {
            $suggest = ErrorHandler::getFuzzySuggest($userFlag, $flags);
            $this->error("Invalid flag: `' . $userFlag . '` $suggest");
        }

        return $userFlag;
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
                        $this->oopError("Field `$k` must be of type: `$origType`   Got: `$newType`");
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

    function __get($ufield) {

        $plainField = unu_($ufield);

        // Getter override
        $autoGetter = u_('get' . ucfirst($plainField));
        if (method_exists($this, $autoGetter) && $this->allowAutoGetSet) {
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
            $this->oopError("Can't read private field: `$plainField`");
        }

        // If there is a method of the same name, suggest that
        $suggest = '';
        if (method_exists($this, $ufield)) {
            $suggest = 'Try: Call method `' . unu_($ufield) . '()`';
        }

        // Fuzzy match on fields
        if (!$suggest) {
            $reflect = new \ReflectionClass($this);
            $fields = $reflect->getProperties();
            $fields = array_map(function($a) { return $a->name; }, $fields);
            $fields = ErrorHandler::filterUserlandNames($fields);

            $suggest = ErrorHandler::getFuzzySuggest($plainField, $fields);
        }

        // Suggest a matching method instead
        if (!$suggest) {
            $suggest = $this->getSuggestedMethod($plainField);
            if ($suggest) {
                $suggest = preg_replace('/Try: /', 'Try: Call method ', $suggest);
            }
        }

        $myType = $this->bareClassName();
        $this->error("Unknown field: `$plainField`  $suggest");
    }

    function __set($ufield, $value) {

        $plainField = unu_($ufield);

        if (property_exists($this, $ufield)) {
            $setter = 'set' . ucfirst($plainField);
            $this->oopError("Can't write directly to field: `$plainField`  Try: Using a setter function, ex: `$setter`");
        }

        if ($this->fieldsLocked) {
            $this->oopError("Can't create field after object is constructed: `$plainField`");
        }

        $this->$ufield = $value;
    }


    function __call($aMethod, $args) {

        $method = unu_($aMethod);

        if (method_exists($this, $aMethod)) {
            $this->oopError("Can't call private method: `$method`");
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

        $suggest = $this->getSuggestedMethod($method);

        $c = $this->errorClass ?: $this->bareClassName();

        $this->error("Unknown object method: `$c.$method()`  $suggest");
    }

    function getSuggestedMethod($method) {

        // Common alias,  e.g. explode -> split
        $fuzzyMethod = strtolower($method);
        if (isset($this->coreSuggestMethod[$fuzzyMethod])) {
            return 'Try: `' . $this->coreSuggestMethod[$fuzzyMethod] . '`';
        }
        else if (property_exists($this, 'suggestMethod')) {
            if (isset($this->suggestMethod[$fuzzyMethod])) {
                return 'Try: `' . $this->suggestMethod[$fuzzyMethod] . '`';
            }
        }

        // Fuzzy match with existing methods
        $reflect = new \ReflectionClass($this);
        $methods = $reflect->getMethods();
        $methods = array_map(function($a) { return $a->name; }, $methods);
        $methods = ErrorHandler::filterUserlandNames($methods);

        $suggest = ErrorHandler::getFuzzySuggest($method, $methods, 'isMethod');

        return $suggest;
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

        $fields = [];
        if (!is_null($this->isPublic)) {
            foreach ($this->isPublic as $k => $v) {
                $fields[unu_($k)] = $this->$k;
            }
        }

        return OMap::create($fields);
    }

    function u_z_set_fields($fieldMap) {

        $this->ARGS('m', func_get_args());

        foreach (unv($fieldMap) as $k => $v) {
            $this->u_z_set_field($k, $v);
        }
    }

    function u_z_get_methods() {

        $this->ARGS('', func_get_args());

        if ($this instanceof OModule) {
            // TODO: fix case
            $methods = $this->getExportedFunctions();
        }
        else {
            $class = new \ReflectionClass($this);
            $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

            $localMethods = [];
            foreach ($methods as $m) {
                $name = $m->name;
                $declaringClass = $m->getDeclaringClass()->name;
                if ($declaringClass != 'o\OClass') {
                    $localMethods []= $name;
                }
            }
            $methods = $localMethods;
        }

        return OList::create($this->userDefinedElements($methods));
    }

    private function userDefinedElements($elements) {

        $userElements = [];

        foreach ($elements as $e) {
            if (hasu_($e)) {
                $userElements []= unu_($e);
            }
        }

        sort($userElements);

        return $userElements;
    }

    function u_z_object_id() {

        $this->ARGS('', func_get_args());

        if (!$this->objectId) {
            self::$CURRENT_OBJECT_ID += 1;
            $this->objectId = self::$CURRENT_OBJECT_ID;
        }

        return '{object#' . $this->objectId . '}';
    }

}
