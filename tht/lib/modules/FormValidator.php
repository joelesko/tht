<?php


/*
    most common fields
    - userName
    - password
    - passwordConfirm

    - email
    - emailConfirm

    - comment

    - auto-forms: login, register

    ------

    new
        userName
        password

    builtin

        X id
        X url
        X email
        X password
        X accepted
        X phone

        date:format

    TYPES

        X text
        X textarea

        X flag: 1,0,true,false

        X number: 0-9
         ?  negative
         ?  decimal

        X dangerDangerRaw


    validators

        X optional
        X different:field
        X same:field

        unique: table.column

        X in:list...
        X notIn:list...

        XnotRegex:pattern
        Xregex:pattern

        between:min,max
        min
        max

        allowHtml


    meta
        list
        custom function

    file

    ========

    -- support required-if (and other dependencies)

*/


namespace o;

class u_FormValidator extends StdModule {

    private $data = [];

    function u_is_ok($val, $sRules) {

        ARGS('ss', func_get_args());

        $result = $this->validateField('', $val, [ 'rule' => $sRules ]);

        unset($result['field']);

        return OMap::create($result);
    }

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

         //   $val = $data[$fieldName];
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

    // TODO: validation order
    function validateField($fieldName, $val, $schema) {

        $rules = isset($schema['rule']) ? $schema['rule'] : 'id';

        if (!is_array($rules)) {
            $rules = explode('|', $rules);
        }

        $allRulesOk = true;
        $error = '';
        $cleanValue = trim($val);

        // TODO: disallow HTML tags

        if (in_array('optional', $rules) && !$cleanValue) {
            // skip validation
        } else {
            foreach ($rules as $r) {
                $result = $this->validateRule($cleanValue, $r);

                if (!$result['ok']) {
                    $cleanValue = '';
                    $allRulesOk = false;
                    $error = $result['error'];
                    break;
                }
                $cleanValue = $result['cleanValue'];
            }
        }

        return [
            'field' => $fieldName,
            'ok'    => $allRulesOk,
            'value' => $cleanValue,
            'error' => $error,
        ];
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
        if (preg_match('/[^a-zA-Z]/', $rule) || !method_exists($this, $fnValidate)) {
            $result = ["Unknown validation rule: `$rule`"];
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

    function lengthError($num) {
        return ["Field must be $num letters or less."];
    }

    function minLengthError($num) {
        return ["Field must be $num letters or more."];
    }



    //  Validation Rules
    // -----------------------------------------------------------

    function validate_dangerdangerraw($val) {
        return $val;
    }


    // By Type

    // TODO: floating point
    // TODO: min/max (negatives)
    // TODO: list
    // TODO: allowHtml for text/textarea
    // default min/max by type
    function validate_number($val) {

        // remove thousand separators
        $val = preg_replace('/[,\']/', '', $val);

        if (preg_match('/[^0-9]/', $val)) {
            return ['Please provide a valid number:'];
        }
        return intval($val);
    }

    function validate_flag($val) {
        return ($val === 'true' || $val === '1');
    }

    // one line of text
    function validate_text($val) {
        $val = preg_replace('/\s+/', ' ', $val);
        if (strlen($val) > 100) {
            return $this->lengthError(100);
        }

        if (preg_match('!<.*>!', $val)) {
            return ['HTML tags not allowed:'];
        }

        return $val;
    }

    // multiline text
    function validate_textarea($val) {
        $val = preg_replace('/ +/', ' ', $val);
        $val = preg_replace('/\n{2,}/', "\n\n", $val);

        if (preg_match('!<.*>!', $val)) {
            return ['HTML tags not allowed:'];
        }

        // TODO: check URLs

        return $val;
    }

    // By Constraint

    function validate_min($val, $arg) {
        if (strlen($val) < $arg) {
            return $this->minLengthError($arg);
        }
        return $val;
    }

    function validate_max($val, $arg) {
        if (strlen($val) > $arg) {
            return $this->lengthError($arg);
        }
        return $val;
    }


    // By Rule

    function validate_id($val) {
        if (preg_match('/[^a-zA-Z0-9_\.\-]/', $val)) {
            return ['Only letters, numbers, and dashes are allowed:'];
        }
        if (strlen($val) > 100) {
            return $this->lengthError(100);
        }
        return $val;
    }

    function validate_username($val) {

        if (!preg_match('/^[a-zA-Z0-9]+$/', $val)) {
            return ['Only letters & numbers are allowed:'];
        }
        else if (!preg_match('/^[^a-zA-Z]/', $val)) {
            return ['Must start with a letter:'];
        }
        else if (strlen($val) > 20) {
            return $this->lengthError(20);
        }
        return $val;
    }

    function validate_url($val) {

        if (!Security::validateUserUrl($val)) {
            return ['Please provide a valid URL:'];
        }
        else if (strlen($val) > 200) {
            return $this->lengthError(200);
        }

        return $val;
    }

    function validate_email($val) {
        if (!preg_match('/^\S+?@[^@\s]+\.\S+$/', $val)) {
            return ['Please re-check this field:'];
        }
        else if (strlen($val) > 100) {
            return $this->lengthError(100);
        }
        $val = preg_replace('/[\'\"]/', '', $val);

        return strtolower($val);
    }

    function validate_optional($val) {
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

    function validate_phone($val) {

        $val = preg_replace('/\s+/', ' ', $val);

        if (preg_match('/[^0-9\(\)\.\-\+ext ]/', $val)) {
            return ['Please check this field:'];
        }
        if (strlen($val) > 30) {
            return $this->lengthError(30);
        }
        return $val;
    }

    function validate_digits($val) {
        if (preg_match('/[^0-9]/', $val)) {
            return ['Please only use digits in field:'];
        }
        if (strlen($val) > 20) {
            return $this->lengthError(20);
        }
        return $val;
    }

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

    function validate_password($val) {
        if (!$Security::validatePasswordStrength($val)) {
            return ["Please use a more difficult password:"];
        }
        if (strlen($val) < 8) {
            return $this->minLengthError(8);
        }
        return new \o\OPassword ($val);
    }

    function validate_in($val, $arg) {
        $ary = preg_split('/\s*,\s*/', $arg);
        if (!in_array($val, $ary)) {
            return ['Please double-check this field.'];
        }
        return $val;
    }

    function validate_notin($val, $arg) {
        $ary = preg_split('/\s*,\s*/', $arg);
        if (!in_array($val, $ary)) {
            return ['Please double-check this field.'];
        }
        return $val;
    }

    function validate_regex($val, $arg) {
        $val = str_replace(':OR:', '|', $val);
        if (!preg_match($arg, $val)) {
            return ['Please double-check this field.'];
        }
        return $val;
    }

    function validate_notregex($val, $arg) {
        $val = str_replace(':OR:', '|', $val);
        if (preg_match($arg, $val)) {
            return ['Please double-check  this field.'];
        }
        return $val;
    }

}




