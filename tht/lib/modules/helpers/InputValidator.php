<?php

/*

modifiers
  list

civilize

*/


namespace o;

class u_InputValidator {

    private $data = [];

    private $defaultRule = [
        'required' => true,
        'type' => 'string',

        'min' => 4,
        'max' => 100,
        'regex' => '',

        'removeHtml'        => true,
        'removeNewlines'    => true,
        'removeExtraSpaces' => true,
        'removeQuotes'      => true,
    ];

    private $baseRules = [

        'flag' => [
            'min' => 1,
            'max' => 5,
        ],

        'int' => [
            'min' => 0,
            'max' => 10000,
            'regex' => '[0-9\-]+',
            'type' => 'int',
        ],

        'float' => [
            'min' => 0,
            'max' => 10000,
            'chars' => '[0-9\-\.]+',
            'type' => 'float',
        ],

        'text' => [
            'min' => 4,
            'max' => 100,
            'removeQuotes' => false,
        ],

        'textbody' => [
            'min' => 10,
            'max' => 'none',
            'removeNewlines' => false,
            'removeQuotes' => false,
        ],

        //----------

        'id' => [
            'min' => 1,
            'max' => 100,
            'regex' => '[a-zA-Z0-9\-\._]+',
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

        'json' => [
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
        ],

    ];

    private $overrideRules = [
        'min', 'max', 'regex',
    ];

    private $variableRules = [
        'in', 'notin', 'same', 'different'
    ];

    function validateFields($data, $schema) {

        $this->data = $data;

        $allFieldsOk = true;
        $errors = [];
        $results = [];

        foreach (uv($schema) as $fieldName => $fieldSchema) {

            if (!isset($data[$fieldName])) {
                // Tht::error("Missing form data for field: `$fieldName`");
                $val = '';
            }
            else {
                $val = $data[$fieldName];
            }

            $result = $this->validateField($fieldName, $val, $fieldSchema);

            if (!$result['ok']) {
                $allFieldsOk = false;
                $errors []= $result;
                $results[$fieldName] = '';
            }
            else {
                $results[$fieldName] = $result['value'];
            }

        }

        return [
            'ok' => $allFieldsOk,
            'errors' => $errors,
            'fields' => $results,
        ];
    }

    function validateField($fieldName, $origVal, $rawRules) {

        $rules = $this->initRules($rawRules);

        $constraints = $this->initConstraints($fieldName, $rules);

        $val = $this->filterValue($origVal, $constraints);

        if ($val == '') {
            if (!$constraints['required']) {
                return $this->okValue($fieldName, $val);
            }
            else {
                return $this->errorValue($fieldName, 'Field is required.');
            }
        }

        // Add Constraints
        foreach (['min', 'max', 'regex'] as $c) {
            $limit = $constraints[$c];
            array_unshift($rules, 'constraint_' . $c . ':' . $limit);
        }

        // Validate rules
        foreach ($rules as $r) {
            $result = $this->validateRule($val, $r);
            if (!$result['ok']) {
                return $this->errorValue($fieldName, $result['error']);
            }
            $val = $result['cleanValue'];
        }

        return $this->okValue($fieldName, $val);
    }

    function okValue($fieldName, $value) {
        return [
            'ok'    => true,
            'error' => '',
            'field' => $fieldName,
            'value' => $value,
        ];
    }

    function errorValue($fieldName, $msg) {
        return [
            'ok'    => false,
            'error' => $msg,
            'field' => $fieldName,
            'value' => '',
        ];
    }

    function initRules($rawRules) {
        if (!is_array($rawRules)) {
            $rawRules = explode('|', $rawRules);
        }
        $rules = [];
        foreach ($rawRules as $r) {
            $rules []= trim($r);
        }

        return $rules;
    }

    function initConstraints($fieldName, $rules) {

        // Build up constraints
        $constraints = $this->defaultRule;

        // Base Rules
        $baseRule = '';
        foreach ($rules as $rule) {
            if (isset($this->baseRules[$rule])) {
                if ($baseRule) {
                   Tht::error("Can't have more than 1 base rule for field: `$fieldName`. Got: `$baseRule` and `$rule`");
                }
                $contraints = array_merge($constraints, $this->baseRules[$rule]);
                $baseRule = $rule;
            }
        }
        if ($baseRule === '') {
            Tht::error("Must have at least one base rule for field: `$fieldName`.");
        }

        // Modifier Rules
        foreach ($rules as $rule) {
            if (isset($this->modifierRules[$rule])) {
                $contraints = array_merge($constraints, $this->modifierRules[$rule]);
            }
        }

        // Override Rules
        foreach ($rules as $rule) {
            if (isset($this->overrideRules[$rule])) {
                $parts = explode(':', $rules, 2);
                if (count($parts) !== 2) {
                    Tht::error('Rule is missing an argument. Tip: `' . $parts[0] . ':argument`');
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
            $val = preg_replace('/<.*>/', '', $val);
        }

        if ($constraints['removeNewlines']) {
            $val = preg_replace('/\n+/', '', $val);
        }

        if ($constraints['removeExtraSpaces']) {
            $val = preg_replace('/\s+/', ' ', $val);
        }

        if ($constraints['type'] == 'int' && $val !== '') {
            $val = intval($val);
        }
        else if ($constraints['type'] == 'float' && $val !== '') {
            $val = floatval($val);
        }

        return $val;
    }

    function validateRule($val, $rule) {

        $rule = trim($rule);
        $arg = '';
        if (strpos($rule, ':') !== false) {
            $parts = explode(':', $rule, 2);
            $rule = trim($parts[0]);
            $arg = trim($parts[1]);
        }

        $fnValidate = 'validate_' . strtolower($rule);

        $result = [];
        if (preg_match('/[^a-zA-Z_]/', $rule) || !method_exists($this, $fnValidate)) {
            $result = ["Unknown validation rule: `$fnValidate`"];
        } else {
            $result = call_user_func([$this, $fnValidate], $val, $arg);
        }

        $isOk = !is_array($result);
        return [
            'ok' => $isOk,
            'cleanValue' => $isOk ? $result : '',
            'error' => $isOk ? '' : $result[0]
        ];
    }



    //  Validation Rules
    // -----------------------------------------------------------


    function validate_constraint_min($val, $limit) {
        if ($limit === 'none') {
            return $val;
        }

        $limit = intval($limit);
        if (is_numeric($val)) {
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
        if (is_numeric($val)) {
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
            return ['Did not match pattern: `$pattern`'];
        }
        return $val;
    }

    function validate_username($val) {
        return $val;
    }
    function validate_text($val) {
        return $val;
    }
    function validate_id($val) {
        return $val;
    }
    function validate_int($val) {
        return $val;
    }
    function validate_float($val) {
        return $val;
    }


    function validate_flag($val) {
        return ($val === 'true' || $val === '1');
    }

    function validate_textbody($val) {
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

    function validate_accept($val) {
        $val = $this->validate_flag($val);
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

    function validate_different($val, $arg) {
        if (!isset($this->data[$arg])) {
            return ["RULE ERROR: 'different:$arg'."];
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




