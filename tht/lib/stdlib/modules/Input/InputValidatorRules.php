<?php

namespace o;

trait InputValidatorRules {

    private $defaultRuleMap = [

        // Types (internal)
        'zValueType'   => 'string',
        'zTagType'     => '',

        // Common constraints
        'min' => 0,
        'max' => 100,
        'step' => 0,
        'regex' => '',
        'optional' => false,

        // Constraints
        'in'       => null,
        'notIn'    => null,
        'same'     => null,
        'notSame'  => null,
        'accepted' => null,
        'list'     => null,

        // String Sanitizers
        'removeNewlines'    => true,
        'removeQuotes'      => true,
        'crunchSpaces'      => true,
        'crunchNewlines'    => false,
        'civilize'          => false,
        'xDangerAllowHtml'  => false,
        'litemark'          => '',

        // Tag autocomplete field
        'autocomplete' => '',

        // More complex checks (internal)
        'zCheckUrl'   => null,
        'zCheckEmail' => null,

        // Post-processing (internal)
        'zPostProcess' => '',

        // Uploads
        'zUploadType' => '',
        'dir' => '',
        'maxSizeMb' => 0,
        'ext' => '',
        'dim' => '',
        'exactSize' => false,
    ];

    private $typeToRuleMap = [

        // Base Types
        //-------------------------------------------

        'b' => [
            'min' => 0,
            'max' => 5,
            'zValueType' => 'boolean',
            'zTagType' => 'checkbox',
        ],

        'i' => [
            'min' => 0,
            'max' => 'none',
            'regex' => '[0-9\-]+',
            'zValueType' => 'int',
            'zTagType' => 'number',
        ],

        'f' => [
            'min' => 0,
            'max' => 'none',
            'regex' => '[0-9\-\.]+',
            'zValueType' => 'float',
            'zTagType' => 'number',
        ],

        's' => [
            'min' => 1,
            'max' => 50,
            'removeQuotes' => false,
            'zValueType' => 'string',
            'zTagType' => 'text',
        ],

        'ms' => [
            'min' => 1,
            'max' => 2000,
            'crunchSpaces'   => false,
            'removeNewlines' => false,
            'crunchNewlines' => true,
            'removeQuotes'   => false,
            'zValueType' => 'string',
            'zTagType' => 'textarea',
        ],

        // Semantic Types
        //-------------------------------------------

        'id' => [
            'min' => 1,
            'max' => 100,
            'regex' => '[a-zA-Z0-9\-\._]+',
            'zTagType' => 'hidden',
        ],

        'unscramble' => [
            'min' => 1,
            'max' => 20,
            'regex' => '[a-z0-9]+',
            'zPostProcess' => 'unscrambleId',
            'zTagType' => 'hidden',
        ],

        'accepted' => [
            'min' => 1,
            'max' => 5,
            'zValueType' => 'boolean',
            'zTagType' => 'checkbox',
            'accepted' => true,
        ],

        'url' => [
            'min' => 8,
            'max' => 200,
            'regex' => 'http(s?)://\S+\.\S+',
            'zCheckUrl' => true,
            'zTagType' => 'url',
            'autocomplete' => 'url',
        ],

        'color' => [
            'min' => 7,
            'max' => 9,
            'regex' => '#[a-fA-F0-9]{6,8}',
            'zTagType' => 'color',
        ],

        'search' => [
            'min' => 1,
            'max' => 100,
            'zTagType' => 'search',
            'autocomplete' => 'off',
        ],


        // Registration Types
        //------------------------------------------------

        // Similar to reddit, but no hyphens
        // https://www.reddit.com/r/help/comments/1ttv80/what_are_the_valid_usernamecharacters/
        'username' => [
            'zTagType' => 'text',
            'min' => 3,
            'max' => 20,
            'regex' => '[a-zA-Z0-9_]+',
            'autocomplete' => 'username',
        ],

        'password' => [
            'zTagType' => 'password',
            'min' => 8,
            'max' => 100,
            'removeQuotes' => false,
            'xDangerAllowHtml' => true,
            'zPostProcess' => 'hashPassword',
            'autocomplete' => 'current-password',
        ],

        // Same as 'password' but has strength check in client
        'newPassword' => [
            'zTagType' => 'password',
            'min' => 8,
            'max' => 100,
            'removeQuotes' => false,
            'xDangerAllowHtml' => true,
            'zPostProcess' => 'hashPassword',
            'autocomplete' => 'new-password',
        ],

        'email' => [
            'min' => 4,
            'max' => 60,
            'regex' => '\S+?@[^@\s]+\.\S+',
            'zTagType' => 'email',
            'zCheckEmail' => true,
            'autocomplete' => 'email',
        ],

        'phone' => [
            'min' => 6,
            'max' => 30,
            'regex' => '[0-9\(\)\.\-\+ext ]+',
            'zTagType' => 'tel',
            'autocomplete' => 'tel',
        ],

        'name' => [
            'min' => 1,
            'max' => 50,
            'zTagType' => 'text',
            'autocomplete' => 'name',
        ],

        'firstName' => [
            'min' => 1,
            'max' => 20,
            'zTagType' => 'text',
            'autocomplete' => 'given-name',
        ],

        'lastName' => [
            'min' => 1,
            'max' => 30,
            'zTagType' => 'text',
            'autocomplete' => 'family-name',
        ],


        // Content Types
        //------------------------------------------------

        'title' => [
            'zTagType' => 'text',
            'min' => 1,
            'max' => 80,
            'regex' => '',
            'removeQuotes' => false,
            'civilize'     => true,
        ],

        'comment' => [
            'zTagType' => 'textarea',
            'min' => 1,
            'max' => 2000,
            'regex' => '',
            'crunchSpaces'   => false,
            'removeNewlines' => false,
            'crunchNewlines' => true,
            'removeQuotes'   => false,
            'civilize'       => true,
        ],

        'json' => [
            'zTagType' => 'textarea',
            'min' => 1,
            'max' => 'none',
            'regex' => '',
            'removeQuotes'      => false,
            'crunchSpaces'      => false,
            'removeNewlines'    => false,
            'xDangerAllowHtml'  => true,
            'zPostProcess'      => 'parseJson',
        ],

        'file' => [
            'zTagType' => 'file',
            'min' => 1,
            'max' => 'none',
            'maxSizeMb' => 1,
            'zUploadType' => 'file',
        ],

        'image' => [
            'zTagType' => 'file',
            'min' => 1,
            'max' => 'none',
            'zUploadType' => 'image',
        ],


        // Date / Time
        // ------------------------------------------------

        // TODO: interpret min/max as date ranges

        // yyyy-mm-dd
        'date' => [
            'min' => 10,
            'max' => 10,
            'regex' => '[12]\d{3}-[01]\d-[0123]\d',
            'zTagType' => 'date',
            'zPostProcess' => 'dateObject',
        ],

        // yyyy-MM-ddThh:mm
        'dateTime' => [
            'min' => 16,
            'max' => 16,
            'regex' => '[12]\d{3}-[01]\d-[0123]\dT[012]\d:[0-5]\d',
            'zTagType' => 'datetime-local',
            'zPostProcess' => 'dateObject',
        ],

        // 2017-W52
        'dateWeek' => [
            'min' => 8,
            'max' => 8,
            'regex' => '[12]\d{3}\-W[0-5]\d',
            'zTagType' => 'week',
            'zPostProcess' => 'dateObject',
        ],

        // yyyy-mm
        'dateMonth' => [
            'min' => 7,
            'max' => 7,
            'regex' => '[12]\d{3}-[01]\d',
            'zTagType' => 'month',
            'zPostProcess' => 'dateObject',
        ],

        // hh:mm
        'time' => [
            'min' => 5,
            'max' => 5,
            'regex' => '[012]\d:[0-5]\d',
            'zTagType' => 'time',
        ],


        // Escape Rules
        //---------------------------------

        'xDangerRaw' => [
            'min' => 1,
            'max' => 'none',
            'removeQuotes'   => false,
            'crunchSpaces'   => false,
            'removeNewlines' => false,
            'xDangerAllowHtml' => true,
        ],
    ];

    static private $inputTagToRule = [

        'hidden'   => 'id',
        'text'     => 's',
        'textarea' => 'comment',

        'tel'      => 'phone',
        'password' => 'password',
        'search'   => 'search',
        'email'    => 'email',
        'color'    => 'color',
        'url'      => 'url',
        'number'   => 'i',

        'radio'    => 'id',
        'select'   => 'id',
        'range'    => 'i',

        'file'     => 'file',

        'checkbox'         => 'b',
        'checkbox+options' => 'id',

        'date'           => 'date',
        'datetime'       => 'dateTime',
        'datetime-local' => 'dateTime',
        'week'           => 'dateWeek',
        'month'          => 'dateMonth',
        'time'           => 'time',
    ];

    public function getRuleForInputTag($tagType) {

        if (isset(self::$inputTagToRule[$tagType])) {
            return self::$inputTagToRule[$tagType];
        }

        return '';
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

    function sanitizeValue($value, $ruleMap) {

        $value = trim($value);

        if ($value === '') { return ''; }

        // Normalize windows newlines
        $value = str_replace("\r", '', $value);

        if (!$ruleMap['xDangerAllowHtml']) {
            $value = Security::removeHtmlTags($value);
        }
        if ($ruleMap['removeQuotes']) {
            $value = preg_replace('/[\'"]/', '', $value);
        }
        if ($ruleMap['removeNewlines']) {
            $value = preg_replace('/\n+/', ' ', $value);
        }
        if ($ruleMap['crunchNewlines']) {
            $value = preg_replace('/ +\n/', "\n", $value);
            $value = preg_replace('/\n{3,}/', "\n\n\n", $value);
        }
        if ($ruleMap['crunchSpaces']) {
            $value = preg_replace('/[\t ]+/', ' ', $value);
        }
        if ($ruleMap['civilize']) {
            $value = v($value)->u_civilize();
        }

        // Cast to Type
        if ($ruleMap['zValueType'] == 'int') {
            $value = intval($value);
        }
        else if ($ruleMap['zValueType'] == 'float') {
            $value = floatval($value);
        }
        else if ($ruleMap['zValueType'] == 'boolean') {
            $value = $value === 'true' || $value === '1';
        }

        return $value;
    }

    function postProcessValue($strategy, $value) {

        if ($strategy == 'parseJson') {
            $json = new JsonTypeString($value);
            $value = Tht::module('Json')->u_decode($json);
        }
        else if ($strategy == 'hashPassword') {
            $value = new \o\OPassword($value);
        }
        else if ($strategy == 'unscrambleId') {
            $value = Tht::module('String')->u_unscramble_id($value);
        }
        else if ($strategy == 'dateObject') {
            $value = Tht::module('Date')->u_create($value);
        }

        return $value;
    }




    // validate_* Methods
    //
    //  Return the sanitized (ok) value, or a List with an error message
    // -------------------------------------------------------------------


    function validate_optional($value, $isOptional) {

        if ($value === '' && !$isOptional) {
            return ['Please fill this field.'];
        }

        return $value;
    }

    function validate_min($value, $limit) {

        if ($limit === 'none') {
            return $value;
        }

        $limit = intval($limit);

        if (is_int($value) || is_float($value)) {
            if ($value < $limit) {
                return ["Please make this $limit or more."];
            }
        } else {
            if (mb_strlen($value) < $limit) {
                $letters = $limit == 1 ? 'letter' : 'letters';
                return ["Please make this $limit $letters or longer."];
            }
        }

        return $value;
    }

    function validate_max($value, $limit) {

        if ($limit === 'none') {
            return $value;
        }

        $limit = intval($limit);

        if (is_int($value) || is_float($value)) {
            if ($value > $limit) {
                return ["Please make this $limit or less."];
            }
        } else {
            if (mb_strlen($value) > $limit) {
                return ["Please make this $limit letters or less."];
            }
        }

        return $value;
    }

    function validate_step($value, $step) {

        if ($step === 0) {
            return $value;
        }

        if ($value % $step !== 0) {
            return ["Must be an increment of $step."];
        }

        return $value;
    }

    function validate_regex($value, $pattern) {

        if ($pattern === '') {
            return $value;
        }

        $pattern = str_replace('/', '\\/', $pattern);
        $rx = '/^' . $pattern . '$/';

        if (!preg_match($rx, $value)) {
            return ["Please fix this field."];
        }

        return $value;
    }

    function validate_accepted($value) {

        if ($value === true || $value === "1") {
            return true;
        }

        return ['Please accept this field.'];
    }

    function validate_zcheckurl($value) {

        if (!Security::validateUserUrl($value)) {
            return ['Please provide a valid URL.'];
        }

        return $value;
    }

    // Fix common typos for the most popular domains
    function validate_zcheckemail($email) {

        $email = strtolower($email);

        // trailing .
        $email = preg_replace('/\.+$/', '', $email);

        // double dots
        $email = preg_replace('/\.{2,}/', '.', $email);

        // .cm -> .com
        $email = preg_replace('/\.cm$/', '.com', $email);

        // "mail" typos (gmail|hotmail)
        $email = preg_replace('/@(g|hot)(mai|mal|mil|ma+i+l+|ail)\./', '@$1mail.', $email);

        // hot mail typos
        $email = preg_replace('/@htmail\./', '@hotmail.', $email);

        // yahoo typos
        $email = preg_replace('/@yah.{2,3}\.co/', '@yahoo.co', $email);
        $email = preg_replace('/@ya.oo\.co/', '@yahoo.co', $email);
        $email = preg_replace('/@(yhoo|yaho|yaoo).co/', '@yahoo.co', $email);

        return $email;
    }

    function validate_list($value) {

        return $value;
    }

    // Will be processed in InputValidator
    function validate_zpostprocess($value) {

        return $value;
    }



    // Variable Rules
    //-------------------------------------------------

    function validate_same($value, $arg) {

        if (!isset($this->data[$arg])) {
            return ["RULE ERROR: 'same:$arg'."];
        }

        if (trim($value) !== trim($this->data[$arg])) {
            $arg = ucfirst($arg);
            return ["Please make this the same as '$arg'."];
        }

        return $value;
    }

    function validate_notsame($value, $arg) {

        if (!isset($this->data[$arg])) {
            return ["RULE ERROR: 'notsame:$arg'."];
        }

        if (trim($value) === trim($this->data[$arg])) {
            $arg = ucfirst($arg);
            return ["Please make this different than '$arg'."];
        }

        return $value;
    }

    function validate_in($value, $list) {

        if (is_string($list)) {
            $list = preg_split('/\s*,\s*/', $list);
        }

        if (in_array($value, unv($list))) {
            return $value;
        }

        $list = implode(', ', unv($list));

        return ["Must be one of these: $list"];
    }

    function validate_notin($value, $list) {

        $result = $this->validate_in($value, $list);

        if (is_array($result)) {
            return $value;
        }

        if (!is_string($list)) {
            $list = implode(', ', unv($list));
        }
        return ["Must not be one of these: $list"];
    }
}

/*
    TODO: Support common fields  (taken from browser autocomplete)

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


