<?php

namespace o;

require_once('InputValidatorRules.php');

class u_InputValidator {

    use InputValidatorRules;

    private $inList = false;

    function error($msg) {

        ErrorHandler::setHelpLink(
            '/reference/input-validation',
            'Input Validation'
        );

        Tht::error($msg);
    }

    // Validate multiple fields and return aggregate result
    public function validateFields($rawData, $fieldConfigs) {

        $allFieldsOk = true;
        $errors = [];
        $results = [];

        foreach (unv($fieldConfigs) as $fieldName => $fieldConfig) {

            $rawVal = isset($rawData[$fieldName]) ? $rawData[$fieldName] : '';
            $rawRule = isset($fieldConfig['rule']) ? $fieldConfig['rule'] : '';

            $result = $this->validateField($fieldName, $rawVal, $rawRule);

            if (!$result['ok']) {
                $allFieldsOk = false;
                $errors []= OMap::create([
                    'field' => $result['field'],
                    'error' => $result['error'],
                ]);
            }
            $results[$fieldName] = $result['value'];
        }

        return OMap::create([
            'ok' => $allFieldsOk,
            'errors' => OList::create($errors),
            'fields' => OMap::create($results),
        ]);
    }

    public function validateField($fieldName, $rawVal, $rawRules) {

        $rules = $this->initFieldRules($fieldName, $rawRules);

        // Handle lists
        if (isset($rules['list']) || is_array($rawVal) || OList::isa($rawVal)) {
            return $this->validateListField($fieldName, $rawVal, $rules);
        }

        // Handle Uploads
        if ($rules['checkFile']) {
            return $this->validateUploadedFile($fieldName, $rules);
        }
        else if ($rules['checkImage']) {
            return $this->validateUploadedImage($fieldName, $rules);
        }

        // Sanitize
        $cleanVal = $this->sanitizeValue($rawVal, $rules);

        if (!$rawVal) {
            if ($rules['optional']) {
                // TODO: if number, use 'min' instead of default zero?
                // Don't validate if optional field wasn't filled
                $cleanVal = $this->defaultValueForType($rules['valueType']);
                return $this->okField($fieldName, $cleanVal);
            }
            else {
                // BUG: this causes 'list' to fail
                return $this->errorField($fieldName, 'Please fill this field.', $rules['valueType']);
            }
        }

        // Final Validation
        foreach ($rules as $ruleName => $ruleValue) {

            $result = $this->validateFieldForRule($fieldName, $cleanVal, $ruleName, $ruleValue);

            // Failed
            if (!$result['ok']) {
                return $this->errorField($fieldName, $result['error'], $rules['valueType']);
            }

            $cleanVal = $result['cleanValue'];
        }

        // Post-processing
        if ($rules['postProcess'] == 'parseJson') {
            $cleanVal = Tht::module('Json')->u_decode($cleanVal);
        }
        else if ($rules['postProcess'] == 'hashPassword') {
            $cleanVal = new \o\OPassword($cleanVal);
        }
        else if ($rules['postProcess'] == 'dateObject') {
            $cleanVal = Tht::module('Date')->u_create($cleanVal);
        }

        return $this->okField($fieldName, $cleanVal);
    }

    // Validate each value in a list
    // TODO: Make this error more useful, retain good values?
    private function validateListField($fieldName, $valList, $rules) {

        if (!isset($rules['list'])) {
            return $this->errorField($fieldName, 'Multiple values are only allowed via the `list` rule.', 's');
        }
        else if ($this->inList) {
            return $this->errorField($fieldName, 'Nested lists are not suported.', 'list');
        }

        if ($valList === '') {
            // If no fields are checked, the client sends nothing
            $valList = [];
        }
        if (!is_array($valList) && !OList::isa($valList)) {
            // Treat single value as list
            $valList = [$valList];
        }

        $cleanVals = [];
        $this->inList = true;

        // Remove 'list' rule for child elements
        unset($rules['list']);

        // Interpret `min` and `max` as number of items chosen.
        if (isset($rules['in'])) {

            if (isset($rules['min'])) {
                if (count($valList) < $rules['min']) {
                    $items = v('item')->u_plural(intval($rules['min']));
                    return $this->errorField(
                        $fieldName . '[]',
                        'Please pick at least ' .  $rules['min'] . " $items.", 'list'
                    );
                }
            }
            if (isset($rules['max'])) {
                if (count($valList) > $rules['max']) {
                    $items = v('item')->u_plural(intval($rules['max']));
                    return $this->errorField(
                        $fieldName . '[]',
                        'Please pick ' .  $rules['max'] . " $items or less.", 'list'
                    );
                }
            }

            // Remove for child elements
            unset($rules['min']);
            unset($rules['max']);
        }

        // Validate each element
        foreach ($valList as $val) {

            $result = $this->validateField($fieldName, $val, $rules);

            if (!$result['ok']) {
                return $this->errorField($fieldName, $result['error'], 'list');
            }

            $cleanVals []= $result['value'];
        }

        $this->inList = false;

        return $this->okField($fieldName, OList::create($cleanVals));
    }

    private function validateUploadedFile($fieldName, $rules) {

        if (!$rules['dir']) {
            $this->error('Rule `file` requires a `dir` field to specify the upload directory.');
        }
        if (!$rules['ext']) {
            $this->error('Rule `file` requires an `ext` field with a comma-delimited list of allowed extensions.');
        }

        $exts = preg_split('/\s*,\s*/', $rules['ext']);

        $relPath = Tht::module('Input')->u_get_uploaded_file(
            $fieldName,
            $rules['dir'],
            OList::create($exts),
            intval($rules['sizeKb']),
        );

        return $this->getUploadResult($fieldName, $relPath);
    }

    function validateUploadedImage($fieldName, $rules) {

        if (!$rules['dir']) {
            $this->error('Rule `image` requires a `dir` field to specify the upload directory.');
        }
        if (!$rules['dim']) {
            $this->error('Rule `image` requires a `dim` field to specify the image dimensions. Ex: dim:300,300');
        }
        if (!preg_match('/^\d+,\d+$/', $rules['dim'])) {
            $this->error('Field `dim` should be in the format of `width,height`. Ex: dim:300,300');
        }

        $dimParts = explode(',', $rules['dim']);

        $relPath = Tht::module('Input')->u_get_uploaded_image(
            $fieldName,
            $rules['dir'],
            intval($dimParts[0]),
            intval($dimParts[1]),
            $rules['keepAspectRatio']
        );

        return $this->getUploadResult($fieldName, $relPath);
    }

    function getUploadResult($fieldName, $relPath) {

        if (!$relPath) {
            return $this->errorField(
                $fieldName,
                Tht::module('Input')->u_get_upload_error(),
                's'
            );
        }

        return $this->okField($fieldName, $relPath);
    }

    public function validateFieldForRule($fieldName, $fieldValue, $ruleName, $ruleValue) {

        $result = $fieldValue;

        // Call validate_* function
        $fnValidate = 'validate_' . strtolower($ruleName);
        if (method_exists($this, $fnValidate) && !is_null($ruleValue)) {
            $result = call_user_func([$this, $fnValidate], $fieldValue, $ruleValue, $fieldName);
        }

        if (is_array($result)) {
            return OMap::create([
                'ok' => false,
                'error' => $result[0],
            ]);
        }
        else {
            return OMap::create([
                'ok' => true,
                'cleanValue' => $result,
            ]);
        }
    }

    function okField($fieldName, $value) {

        return OMap::create([
            'ok'    => true,
            'error' => '',
            'field' => $fieldName,
            'value' => $value,
        ]);
    }

    function errorField($fieldName, $msg, $valueType) {

        return OMap::create([
            'ok'    => false,
            'error' => $msg,
            'field' => $fieldName,
            'value' => $this->defaultValueForType($valueType),
        ]);
    }

    public function initFieldRules($fieldName, $rule) {

        if ($rule == '') {
            return $this->defaultRule;
        }

        if (is_string($rule)) {
            $rule = $this->parseRuleString($rule);
        }

        foreach ($rule as $ruleName => $ruleArg) {
            // Convert regex object to raw string
            // TODO: warn if flags
            if (ORegex::isa($ruleArg)) {
                $rule[$ruleName] = $ruleArg->getRawPattern();
            }

            $this->assertRuleNameIsValid($ruleName, $fieldName);
        }

        $expandedRules = $this->expandRules($fieldName, $rule);

        return $expandedRules;
    }

    function assertRuleNameIsValid($ruleName, $fieldName) {

        if (!$ruleName) {
            $this->error("Got an empty validation rule for input field `$fieldName`");
        }

        if (array_key_exists($ruleName, $this->defaultRule) ||
            array_key_exists($ruleName, $this->typeRules)) {
                return true;
        }

        $this->error("Unknown validation rule `$ruleName` for input field `$fieldName`");
    }

    // Merge higher-level rules into the default rule (overriding the default)
    function expandRules($fieldName, $rules) {

        $expandedRules = $this->defaultRule;

        $typeRule = $rules['type'];
        if (!isset($this->typeRules[$typeRule])) {
            $this->error("Missing `type` validation rule for input field `$fieldName`");
        }
        $expandedRules = array_merge($expandedRules, $this->typeRules[$typeRule]);
        unset($expandedRules[$typeRule]);

        foreach ($rules as $ruleName => $ruleValue) {
            $expandedRules[$ruleName] = $ruleValue;
        }

        return $expandedRules;
    }

    // Convert 'foo|bar:123' ---> ['foo' => true, 'bar' => 123]
    function parseRuleString($rawRule) {

        $rules = explode('|', $rawRule);

        $type = array_shift($rules);
        $map = [
            'type' => $type
        ];

        foreach ($rules as $r) {
            $parts = explode(':', $r, 2);
            if (count($parts) == 1) {
                $map[$parts[0]] = true;
            }
            else {
                $map[$parts[0]] = $parts[1];
            }
        }

        return $map;
    }
}

