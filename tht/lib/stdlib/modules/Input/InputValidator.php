<?php

namespace o;

require_once('InputValidatorRules.php');
require_once('InputValidatorRuleParser.php');

// TODO: probably convert rulemaps, schemas, etc. to proper classes to it's more clear
// what is being passed around. This also extends into FormBuilder & FormObject.

class u_InputValidator {

    // TODO: probably make these something other than traits
    use InputValidatorRules;
    use InputValidatorRuleParser;

    private $inList = false;

    function error($msg) {

        ErrorHandler::setHelpLink(
            '/reference/input-validation',
            'Input Validation'
        );

        Tht::error($msg);
    }

    function okField($key, $value) {

        return OMap::create([
            'ok'    => true,
            'error' => '',
            'key'   => $key,
            'value' => $value,
        ]);
    }

    function errorField($key, $msg, $errorValue) {

        return OMap::create([
            'ok'    => false,
            'error' => $msg,
            'key'   => $key,
            'value' => $errorValue,
        ]);
    }

    function defaultValueForType($type) {

        switch ($type) {
            case 'int':     return 0;
            case 'float':   return 0.0;
            case 'boolean': return false;
            case 'list':    return OList::create([]);
        }

        return '';
    }

    // Validate multiple fields and return aggregate result
    public function validateFields($rawData, $fieldToRuleset) {

        $allFieldsOk = true;
        $errors = [];
        $validatedFields = [];

        foreach (unv($fieldToRuleset) as $key => $ruleset) {

            $value = $rawData[$key] ?? '';
            $result = $this->validateField($key, $value, $ruleset);

            $validatedFields[$key] = $result['value'];

            if (!$result['ok']) {
                $allFieldsOk = false;
                $errors []= OMap::create([
                    'key' => $result['key'],
                    'error' => $result['error'],
                ]);
            }
        }

        return OMap::create([
            'ok'     => $allFieldsOk,
            'errors' => OList::create($errors),
            'fields' => OMap::create($validatedFields),
        ]);
    }

    public function validateField($key, $rawValue, $ruleMap) {

        // Children of lists will already have a pre-parsed ruleMapString.
        if (is_string($ruleMap)) {
            $ruleMap = $this->getRuleMapForString($key, $ruleMap);
        }

        // Handle lists
        if (isset($ruleMap['list']) || is_array($rawValue) || OList::isa($rawValue)) {
            return $this->validateListField($key, $rawValue, $ruleMap);
        }

        // Handle Uploads
        if ($ruleMap['zUploadType'] == 'file') {

            $uploadResult = Security::validateUploadedFile($key, $ruleMap['ext'], $ruleMap['maxSizeMb'], $ruleMap['dir']);

            if ($uploadResult->u_is_ok()) {
                $tmpName = $uploadResult->u_get();
                return $this->okField($key, $uploadResult->u_get());
            }
            else {
                return $this->errorField($key, $uploadResult->u_get_fail_code(), '');
            }
        }
        // else if ($ruleMap['zUploadType'] == 'image') {
        //     return $this->validateUploadedImage($key, $ruleMap);
        // }


        // No field value
        if (!$rawValue) {
            $defaultValue = $this->defaultValueForType($ruleMap['zValueType']);

            if ($ruleMap['optional']) {
                // Return default if optional field wasn't filled
                // TODO: if number, use 'min' instead of default zero?
                return $this->okField($key, $defaultValue);
            }
            else {
                return $this->errorField($key, 'Please fill this field.', $defaultValue);
            }
        }

        // Sanitize
        $cleanValue = $this->sanitizeValue($rawValue, $ruleMap);

        // Validate
        foreach ($ruleMap as $ruleName => $ruleValue) {

            $result = $this->validateFieldForRule($key, $cleanValue, $ruleName, $ruleValue);

            if (!$result['ok']) {
                $defaultValue = $this->defaultValueForType($ruleMap['zValueType']);
                return $this->errorField($key, $result['error'], $defaultValue);
            }
            else {
                $cleanValue = $result['cleanValue'];
            }
        }

        // Post-processing
        $cleanValue = $this->postProcessValue($ruleMap['zPostProcess'], $cleanValue);

        return $this->okField($key, $cleanValue);
    }

    // Validate each value in a list
    // TODO: Make this error more useful, retain good values?
    private function validateListField($key, $valueList, $ruleMap) {

        if (!isset($ruleMap['list'])) {
            return $this->errorField($key, 'Multiple values are only allowed via the `list` rule.', '');
        }
        else if ($this->inList) {
            return $this->errorField($key, 'Nested lists are not suported.', OList::create([]));
        }

        if ($valueList === '') {
            // If no fields are checked, the client sends nothing
            $valueList = [];
        }
        if (!is_array($valueList) && !OList::isa($valueList)) {
            // Treat single value as list
            $valueList = [$valueList];
        }

        $cleanValues = [];
        $this->inList = true;

        // Remove 'list' rule for child elements
        unset($ruleMap['list']);

        // Interpret `min` and `max` as number of items chosen.
        if (isset($ruleMap['in'])) {

            if (isset($ruleMap['min'])) {
                if (count($valueList) < $ruleMap['min']) {
                    $items = v('item')->u_plural(intval($ruleMap['min']));
                    return $this->errorField(
                        $key . '[]',
                        'Please pick at least ' .  $ruleMap['min'] . " $items.",
                        OList::create([])
                    );
                }
            }
            if (isset($ruleMap['max'])) {
                if (count($valueList) > $ruleMap['max']) {
                    $items = v('item')->u_plural(intval($ruleMap['max']));
                    return $this->errorField(
                        $key . '[]',
                        'Please pick up to ' .  $ruleMap['max'] . " $items.",
                        OList::create([])
                    );
                }
            }

            // Remove for child elements
            unset($ruleMap['min']);
            unset($ruleMap['max']);
        }

        // Validate each element
        foreach ($valueList as $value) {
            $result = $this->validateField($key, $value, $ruleMap);
            if (!$result['ok']) {
                return $this->errorField($key, $result['error'], OList::create([]));
            }
            $cleanValues []= $result['value'];
        }

        $this->inList = false;

        return $this->okField($key, OList::create($cleanValues));
    }

    // TODO: Clean this up and re-implement Image uploads


    // private function validateUploadedFile($key, $rules) {

    //     $this->validateUploadDir('file', $key, $rules);

    //     if (!$rules['ext']) {
    //         $this->error('Input field `$key` with validation type `file` requires an `ext` rule with a comma-delimited list of allowed extensions.');
    //     }

    //     $exts = preg_split('/\s*,\s*/', $rules['ext']);

    //     $relPath = Tht::module('Input')->u_get_uploaded_file(
    //         $key,
    //         $rules['dir'],
    //         OList::create($exts),
    //         intval($rules['maxSizeMb']),
    //     );

    //     return $this->getUploadResult($key, $relPath);
    // }

    // function validateUploadedImage($key, $rules) {

    //     $rules = OMap::create($rules);

    //     $this->validateUploadDir('imge', $key, $rules);

    //     if (!$rules['dim']) {
    //         $this->error('Input field `$key` with validation type  `image` requires a `dim` rule to specify the image dimensions. Ex: dim:300x300');
    //     }
    //     if (!preg_match('/^\d+x\d+$/', $rules['dim'])) {
    //         $this->error('Input field `$key` validation rule `dim` should be in the format of: `width x height`  Ex: dim:300x300');
    //     }

    //     $relPath = Tht::module('Input')->u_get_uploaded_image(
    //         $key,
    //         $rules['dir'],
    //         $rules['dim'],
    //         OMap::create(['exactSize' => $rules['exactSize']])
    //     );

    //     return $this->getUploadResult($key, $relPath);
    // }

    function getUploadResult($key, $relPath) {

        if (!$relPath) {
            return $this->errorField(
                $key,
                Tht::module('Input')->u_get_upload_error(),
                ''
            );
        }

        return $this->okField($key, $relPath);
    }


}

