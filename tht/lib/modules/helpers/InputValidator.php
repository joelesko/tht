<?php

namespace o;

class u_InputValidator {

    private $allRules;

    private $defaultRule = [
        'required' => true,
        'type' => 'string',

        'min' => 4,
        'max' => 100,
        'regex' => '',
        'default' => '',

        'removeHtml'        => true,
        'removeNewlines'    => true,
        'removeExtraSpaces' => true,
        'removeQuotes'      => true,
    ];

    private $baseRules = [

        // Type Rules
        //-------------------------------------------

        'b' => [
            'min' => 1,
            'max' => 5,
            'default' => false,
        ],

        'i' => [
            'min' => 0,
            'max' => 'none',
            'regex' => '[0-9\-]+',
            'type' => 'int',
            'default' => 0,
        ],

        'f' => [
            'min' => 0,
            'max' => 'none',
            'regex' => '[0-9\-\.]+',
            'type' => 'float',
            'default' => '0',
        ],

        's' => [
            'min' => 1,
            'max' => 100,
            'removeQuotes' => false,
        ],

        // Semantic Rules
        //-------------------------------------------

        'id' => [
            'min' => 1,
            'max' => 100,
            'regex' => '[a-zA-Z0-9\-\._]+',
        ],

        'accepted' => [
            'min' => 1,
            'max' => 5,
            'default' => false,
        ],

        // same as reddit
        // https://www.reddit.com/r/help/comments/1ttv80/what_are_the_valid_usernamecharacters/
        'username' => [
            'min' => 3,
            'max' => 20,
            'regex' => '[a-zA-Z0-9\-_]+',
        ],

        'password' => [
            'min' => 8,
            'max' => 100,
            'removeQuotes' => false,
            'removeHtml'   => false,
        ],

        'url' => [
            'min' => 8,
            'max' => 200,
        ],

        'email' => [
            'min' => 4,
            'max' => 60,
            'regex' => '\S+?@[^@\s]+\.\S+',
        ],

        'phone' => [
            'min' => 6,
            'max' => 30,
            'regex' => '[0-9\(\)\.\-\+ext ]+',
        ],

        'body' => [
            'min' => 10,
            'max' => 'none',
            'removeNewlines' => false,
            'removeQuotes' => false,
        ],

        'json' => [
            'min' => 1,
            'max' => 'none',
            'removeQuotes'      => false,
            'removeExtraSpaces' => false,
            'removeNewlines'    => false,
            'removeHtml'        => false,
        ],

        'dangerdangerraw' => [
            'min' => 1,
            'max' => 'none',
            'removeQuotes'      => false,
            'removeExtraSpaces' => false,
            'removeNewlines'    => false,
            'removeHtml'        => false,
        ],

    ];

    private $modifierRules = [

        'optional' => [
            'required' => false,
        ],

        'dangerdangerhtml' => [
            'removeHtml' => false,
            'removeQuotes' => false,
        ],

        'civilize' => [],

        'list' => [],

    ];

    private $constraintRules = [
        'min', 'max', 'regex',
    ];

    private $variableRules = [
        'in', 'notin', 'same', 'notsame'
    ];

    function __construct() {
        $this->allRules = array_keys($this->baseRules);
        $this->allRules = array_merge($this->allRules, array_keys($this->modifierRules));
        $this->allRules = array_merge($this->allRules, $this->constraintRules);
        $this->allRules = array_merge($this->allRules, $this->variableRules);
        $this->allRules []= 'required';
    }

    function error($msg) {
        ErrorHandler::setErrorDoc('/reference/input-validation', 'Input Validation');
        Tht::error($msg);
    }

    public function validateFields($data, $fields) {

        $allFieldsOk = true;
        $errors = [];
        $results = [];

        foreach (uv($fields) as $fieldName => $rules) {

            $val = isset($data[$fieldName]) ? $data[$fieldName] : '';
            $result = $this->validateField($fieldName, $val, $rules);

            if (!$result['ok']) {
                $allFieldsOk = false;
                $result = OMap::create($result);
                $errors []= $result->u_slice(OList::create(['field', 'error']));
            }

            $results[$fieldName] = $result['value'];
        }

        return OMap::create([
            'ok' => $allFieldsOk,
            'errors' => OList::create($errors),
            'fields' => OMap::create($results),
        ]);
    }

    public function validateField($fieldName, $origVal, $rawRules, $inList=false) {

        $rules = $this->initRules($rawRules, $fieldName);
        $constraints = $this->initConstraints($fieldName, $rules);

        if (is_array($origVal) || OLIst::isa($origVal)) {
            if (!in_array('list', $rules)) {
                $origVal = '';
            }
            else if ($inList) {
                return $this->errorValue($fieldName, 'Nested lists not allowed.', '');
            }
            else {
                return $this->validateList($fieldName, $origVal, $rawRules);
            }
        }

        $val = $this->filterValue($origVal, $constraints);

        // Add Constraints
        foreach (['regex', 'min', 'max', 'required'] as $c) {
            $limit = $constraints[$c];
            array_unshift($rules, 'constraint_' . $c . ':' . $limit);
        }

        // Validate values
        foreach ($rules as $r) {
            $result = $this->validateRule($val, $r);
            if (!$result['ok']) {
                return $this->errorValue($fieldName, $result['error'], $constraints['default']);
            }
            $val = $result['cleanValue'];
        }

        return $this->okValue($fieldName, $val);
    }

    // TODO: make this error more useful, retain good values
    private function validateList($fieldName, $origVal, $rawRules) {
        $vals = [];
        foreach ($origVal as $v) {
            $listElResult = $this->validateField($fieldName, $v, $rawRules, true);
            if (!$listElResult['ok']) {
                return $this->errorValue($fieldName, $listElResult['error'], OList::create([]));
            }
            else {
                $vals []= $listElResult['value'];
            }
        }
        return $this->okValue($fieldName, OList::create($vals));
    }

    public function validateRule($val, $rule) {

        $rule = trim($rule);
        $arg = '';
        if (strpos($rule, ':') !== false) {
            $parts = explode(':', $rule, 2);
            $rule = trim($parts[0]);
            $arg = trim($parts[1]);
        }

        $fnValidate = 'validate_' . strtolower($rule);
        $checkRule = str_replace('constraint_', '', $rule);

        $result = $val;
        if (preg_match('/[^a-zA-Z_]/', $rule) || !in_array($checkRule, $this->allRules)) {
            $this->error("Unknown validation rule: `$checkRule`");
        } else {
            if (method_exists($this, $fnValidate)) {
                $result = call_user_func([$this, $fnValidate], $val, $arg);
            }
        }

        $isOk = !is_array($result);
        return [
            'ok' => $isOk,
            'cleanValue' => $isOk ? $result : '',
            'error' => $isOk ? '' : $result[0]
        ];
    }


    function okValue($fieldName, $value) {
        return [
            'ok'    => true,
            'error' => '',
            'field' => $fieldName,
            'value' => $value,
        ];
    }

    function errorValue($fieldName, $msg, $defaultValue) {
        return [
            'ok'    => false,
            'error' => $msg,
            'field' => $fieldName,
            'value' => $defaultValue,
        ];
    }

    function initRules($rawRules, $fieldName) {
        if ($rawRules == '') {
            $rawRules = [$this->autoRule($fieldName)];
        }

        $rawRules = explode('|', $rawRules);

        $rules = [];
        foreach ($rawRules as $r) {
            $r = trim($r);
            $rules []= $r;

            // TODO: handle arg/name splitting in one place, instead of in multiple places
            if (strpos($r, ':') !== false) {
                $parts = explode(':', $r, 2);
                $r = trim($parts[0]);
            }

            if (!in_array($r, $this->allRules)) {
                $this->error("Unknown validation rule for field `$fieldName`: `$r`");
            }
        }

        return $rules;
    }


    /*

        TODO:
        COMMON FIELDS (taken from browser autocomplete)

        initial
        name
        birth
        email
        address
        city
        state
        zip
        postal
        country
        areacode
        phone
        company

        password
        -Id
        accept

        subject
        title
        comment
        body

    */
    function autoRule($af) {

        $f = strtolower($af);

        if (preg_match('#(email)#', $f)) {
            return 'email';
        }
        else if (preg_match('#(password|passwd|pass)#', $f)) {
            return 'password';
        }
        else if (preg_match('#(user|username)#', $f)) {
            return 'userName';
        }
        else if (preg_match('#id$#', $f)) {
            return 'id';
        }
        else if (preg_match('#url#', $f)) {
            return 'url';
        }
        else if (preg_match('#phone#', $f)) {
            return 'phone';
        }
        else if (preg_match('#accept#', $f)) {
            return 'accepted';
        }
        else if (preg_match('#body#', $f)) {
            return 'body';
        }

        $this->error("Can't auto-detect validation rule for field `$af`");
    }

    function initConstraints($fieldName, $rules) {

        // Build up constraints (min, max, regex, required)
        $constraints = $this->defaultRule;

        // Base Rules
        $baseRule = '';
        foreach ($rules as $rule) {
            if (isset($this->baseRules[$rule])) {
                $constraints = array_merge($constraints, $this->baseRules[$rule]);
                break;
            }
        }

        // Modifier Rules
        foreach ($rules as $rule) {
            if (isset($this->modifierRules[$rule])) {
                $constraints = array_merge($constraints, $this->modifierRules[$rule]);
            }
        }

        // Constraint Rules
        foreach ($rules as $rawRule) {
            $parts = explode(':', $rawRule, 2);
            if (in_array($parts[0], $this->constraintRules)) {
                if (count($parts) !== 2) {
                    $this->error("Rule for field `$fieldName` is missing an argument. Tip: `" . $parts[0] . ':argument`');
                }
                $constraints[$parts[0]] = $parts[1];
            }
        }

        return $constraints;
    }

    function filterValue($val, $constraints) {

        $val = trim($val);

        if ($constraints['removeQuotes']) {
            $val = preg_replace('/[\'"]/', '', $val);
        }

        if ($constraints['removeHtml']) {
            $val = preg_replace('/<.*?>/', '', $val);
        }

        if ($constraints['removeNewlines']) {
            $val = preg_replace('/\n+/', ' ', $val);
        }

        if ($constraints['removeExtraSpaces']) {
            $val = preg_replace('/[\t ]+/', ' ', $val);
        }

        if ($val !== '') {
            if ($constraints['type'] == 'int') {
                $val = intval($val);
            }
            else if ($constraints['type'] == 'float') {
                $val = floatval($val);
            }
        }

        return $val;
    }



    //  Validation Rules
    //     Return the filtered value, or a List with an error message
    // -------------------------------------------------------------------


    function validate_constraint_required($val, $required) {
        if ($val == '') {
            if (!$required) {
                return $val;
            }
            else {
                return ['Must be filled.'];
            }
        }
        return $val;
    }

    function validate_constraint_min($val, $limit) {
        if ($limit === 'none') {
            return $val;
        }

        $limit = intval($limit);
        if (is_int($val) || is_float($val)) {
            if ($val < $limit) {
                return ["Must be $limit or more."];
            }
        } else {
            if (mb_strlen($val) < $limit) {
                return ["Must be $limit letters or longer."];
            }
        }
        return $val;
    }

    function validate_constraint_max($val, $limit) {
        if ($limit === 'none') {
            return $val;
        }
        $limit = intval($limit);
        if (is_int($val) || is_float($val)) {
            if ($val > $limit) {
                return ["Must be $limit or less."];
            }
        } else {
            if (mb_strlen($val) > $limit) {
                return ["Must be $limit letters or less."];
            }
        }
        return $val;
    }

    function validate_constraint_regex($val, $pattern) {

        if ($pattern === '') {
            return $val;
        }

        $pattern = str_replace(':OR:', '|', $pattern);
        if (!preg_match('#^' . $pattern . '$#', $val)) {
            return ["Please double-check."];
        }
        return $val;
    }

    function validate_b($val) {
        return ($val === 'true' || $val === '1');
    }

    function validate_body($val) {
        $val = preg_replace('/ +/', ' ', $val);
        $val = preg_replace('/\n{2,}/', "\n\n", $val);

        return $val;
    }

    function validate_url($val) {
        if (!Security::validateUserUrl($val)) {
            return ['Please provide a valid URL:'];
        }
        return $val;
    }

    function validate_accepted($val) {
        $val = $this->validate_b($val);
        if ($val === true) {
            return true;
        }
        else {
            return ['Please accept this field:'];
        }
    }

    function validate_password($val) {
        if (!Security::validatePasswordStrength($val)) {
            return ["Please choose a harder password:"];
        }
        return new \o\OPassword ($val);
    }


    function validate_list($val) {
        return $val;
    }

    function validate_civilize($val) {
        return v($val)->u_civilize();
    }


    //===== Variable Rules

    function validate_same($val, $arg) {
        if (!isset($this->data[$arg])) {
            return ["RULE ERROR: 'same:$arg'."];
        }
        if (trim($val) !== trim($this->data[$arg])) {
            $arg = ucfirst($arg);
            return ["Please make sure this matches '$arg':"];
        }
        return $val;
    }

    function validate_notsame($val, $arg) {
        if (!isset($this->data[$arg])) {
            return ["RULE ERROR: 'notsame:$arg'."];
        }
        if (trim($val) === trim($this->data[$arg])) {
            $arg = ucfirst($arg);
            return ["Please make this field different than '$arg':"];
        }
        return $val;
    }

    function validate_in($val, $arg) {
        $ary = preg_split('/\s*,\s*/', $arg);
        if (!in_array('' + $val, $ary)) {
            return ['Please double-check this field.'];
        }
        return $val;
    }

    function validate_notin($val, $arg) {
        $ary = preg_split('/\s*,\s*/', $arg);
        if (!in_array('' + $val, $ary)) {
            return ['Please double-check this field.'];
        }
        return $val;
    }

}

