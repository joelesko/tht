<?php


/*
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
    
        optional
        different:field
        same:field

        unique: table.column

        in:list...
        notIn:list...

        notRegex:pattern
        regex:pattern

        between:min,max
        min
        max

        allowHtml

        after:date ?   (or equal)
        before:date


    meta
        list
        custom function

    file

    ========

    -- support required-if (and other dependencies)
    
*/


namespace o;

class u_FormValidator extends StdModule {

    function u_validate($val, $sRules) {

        ARGS('ss', func_get_args());

        $result = $this->validateField('', $val, [ 'rule' => $sRules ]);

        unset($result['field']);

        return OMap::create($result);
    }

    function validateFields($data, $schema) {

    	$allFieldsOk = true;
    	$errors = [];

    	foreach ($schema as $fieldName => $fieldSchema) {
    		
    		if (!isset($data[$fieldName])) {
    			Tht::error("Missing form data for field: `$fieldName`");
    		}

    		$val = $data[$fieldName];

    		$result = $this->validateField($fieldName, $val, $fieldSchema);

    		if (!$result['ok']) {
    			$allFieldsOk = false;
    			$errors []= $result;
    		}
    	}

    	return [
    		'ok' => $allFieldsOk,
    		'errors' => $errors,
    	];
    }

	// TODO: optionals
	// TODO: validation order
    function validateField($fieldName, $val, $schema) {

        $rules = $schema['rule'];

        if (!is_array($rules)) {
        	$rules = explode('|', $rules);
        }
        
        if (!count($rules)) {
        	Tht::error("No validation rules provided for field: `$fieldName`");
        }

        $allRulesOk = true;
        $error = '';
        $cleanValue = trim($val);

        // TODO: disallow HTML tags

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
        if (strpos($rule, ':')) {
        	list($rule, $arg) = explode($rule, ':', 2);
        	$rule = trim($rule);
        	$arg = trim($arg);
        }

        $fnValidate = 'validate_' . strtolower($rule);

        if (preg_match('/[^a-zA-Z]/', $rule) || !method_exists($this, $fnValidate)) {
            Tht::error("Unknown validation rule: `$rule`");
        }
  
        $result = call_user_func([$this, $fnValidate], $val, $arg);

        $isOk = !is_array($result);
        return [
            'ok' => $isOk,
            'cleanValue' => $isOk ? $result : '',
            'error' => $isOk ? '' : $result[0]
        ];
    }

    function lengthError($num) {
		return ['Must be $num letters or less.'];
    }



    ///////  Validation Rules


    function validate_dangerdangerraw($val) {
        return $val;
    }


    // By Type

    // TODO: negatives
    // TODO: floating point
    // TODO: min/max 
    function validate_number($val) {
        
    	// remove thousand separators
        $val = preg_replace('/[,\']/', '', $val);

        if (preg_match('/[^0-9]/', $val)) {
            return ['Must be all digits.'];
        }
        if (intval($val) > 1000000) {
            return ['Must be less than 1000000'];
        }
        return intval($val);
    }

    function validate_flag($val) {
        if ($val !== 'true' && $val !== 'false' && $val !== '1' && $val !== '0') {
            return ['Invalid flag'];
        }
        return ($val === 'true' || $val === '1');
    }

    // one line of text
    function validate_text($val) {
        $val = preg_replace('/\s+/', ' ', $val);
        if (strlen($val) > 100) {
            return $this->lengthError(100);
        }
        return $val;
    }

    // multiline text
    function validate_textarea($val) {
        $val = preg_replace('/ +/', ' ', $val);
        $val = preg_replace('/\n{2,}/', "\n\n", $val);

        return $val;
    }


    // By Rule

    function validate_id($val) {
        if (preg_match('/[^a-zA-Z0-9_\.\-]/', $val)) {
            return ['Invalid character'];
        }
        if (strlen($val) > 100) {
            return $this->lengthError(100);
        }
        return $val;
    }

    function validate_username($val) {
        
        if (!preg_match('/^[a-zA-Z0-9]+$/', $val)) {
            return ['Only letters and numbers are allowed'];
        }
        if (!preg_match('/^[^a-zA-Z]/', $val)) {
            return ['Must start with a letter'];
        }
        if (strlen($val) > 20) {
            return $this->lengthError(20);
        }
        return $val;
    }

    function validate_url($val) {
        if (!preg_match('/^https?://\S+$/', $val)) {
            return ['Invalid URL'];
        }
        if (strlen($val) > 100) {
            return $this->lengthError(100);
        }
        $val = preg_replace('/[\'\"]/', $val);
        return $val;
    }

    function validate_email($val) {
        if (!preg_match('/^\S+?@[^@\s]+\.\S+$/', $val)) {
            return ['Invalid email.'];
        }
        if (strlen($val) > 100) {
            return $this->lengthError(100);
        }
        $val = preg_replace('/[\'\"]/', '', $val);

        return strtolower($val);
    }

    function validate_password($val) {
        return new OPassword ($val);
    }

    function validate_accepted($val) {
        $val = $this->validate_flag($val);
        if ($val === true) {
        	return true;
        } else {
        	return ['Must be accepted'];
        }
    }

    function validate_phone($val) {
        
        $val = preg_replace('/\s+/', ' ', $val);

        if (preg_match('/[^0-9\(\)\.\-\+ext ]/', $val)) {
            return ['Invalid character'];
        }
        if (strlen($val) > 30) {
            return $this->lengthError(30);
        }
        return $val;
    }

}
