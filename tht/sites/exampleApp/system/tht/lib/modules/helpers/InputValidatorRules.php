<?php

namespace o;

/*
    Rule to input type

    OVERRIDE

    type: hidden
      is probably a dynamic var


    AMBIGUOUS

    -    hidden
    id   hidden

    id|in     select
    id|in     radio


    SIMPLE

    ms        textarea
    json      textarea
    comment   textarea

    accepted  checkbox
    b         checkbox
    list|in   checkbox


    TEXT LIKE

    name(s)    text
    s          text
    address    text
    f          num
    i          num
    phone      tel
    url        url
    color      color
    search     search
    email      email
    password   password

*/


trait InputValidatorRules {

    private $defaultRule = [

        // Types (internal)
        'type'      => '',
        'valueType' => 'string',
        'fieldType' => '',

        // Common constraints
        'min' => 0,
        'max' => 100,
        'step' => 0,
        'regex' => '',
        'optional' => false,

        // Sanitizers
        'xDangerAllowHtml'  => false,
        'removeNewlines'    => true,
        'removeQuotes'      => true,
        'crunchSpaces'      => true,
        'crunchNewlines'    => false,
        'civilize'          => false,

        // Constraints
        'in'      => null,
        'notIn'   => null,
        'same'    => null,
        'notSame' => null,

        // Is list of values
        'list' => null,

        // More complex checks (internal)
        'checkUrl'   => null,
        'checkEmail' => null,
        'checkFile'  => null,
        'checkImage' => null,

        // Post-processing (internal)
        'postProcess' => '',

        // Uploads
        'dir' => '',
        'sizeKb' => 0,
        'ext' => '',
        'dim' => '',
        'keepAspectRatio' => false,
    ];

    private $typeRules = [

        // Base Types
        //-------------------------------------------

        'b' => [
            'min' => 0,
            'max' => 5,
            'valueType' => 'boolean',
            'fieldType' => 'checkbox',
        ],

        'i' => [
            'min' => 0,
            'max' => 'none',
            'regex' => '[0-9\-]+',
            'valueType' => 'int',
            'fieldType' => 'number',
        ],

        'f' => [
            'min' => 0,
            'max' => 'none',
            'regex' => '[0-9\-\.]+',
            'valueType' => 'float',
            'fieldType' => 'number',
        ],

        's' => [
            'min' => 1,
            'max' => 50,
            'removeQuotes' => false,
            'valueType' => 'string',
            'fieldType' => 'text',
        ],

        'ms' => [
            'min' => 1,
            'max' => 2000,
            'crunchSpaces'   => false,
            'removeNewlines' => false,
            'crunchNewlines' => true,
            'removeQuotes'   => false,
            'valueType' => 'string',
            'fieldType' => 'textarea',
        ],

        // Semantic Types
        //-------------------------------------------

        'id' => [
            'min' => 1,
            'max' => 100,
            'regex' => '[a-zA-Z0-9\-\._]+',
        ],

        'accepted' => [
            'min' => 1,
            'max' => 5,
            'valueType' => 'boolean',
            'fieldType' => 'checkbox',
        ],

        'url' => [
            'min' => 8,
            'max' => 200,
            'regex' => 'http(s?)://\S+\.\S+',
            'checkUrl' => true,
            'fieldType' => 'url',
        ],

        'color' => [
            'min' => 7,
            'max' => 9,
            'regex' => '#[a-fA-F0-9]{6,8}',
            'fieldType' => 'color',
        ],

        'search' => [
            'min' => 1,
            'max' => 100,
            'fieldType' => 'search',
        ],


        // Registration Types
        //------------------------------------------------

        // Similar to reddit, but no hyphens
        // https://www.reddit.com/r/help/comments/1ttv80/what_are_the_valid_usernamecharacters/
        'username' => [
            'min' => 3,
            'max' => 20,
            'regex' => '[a-zA-Z0-9_]+',
            'fieldType' => 'text',
        ],

        'password' => [
            'fieldType' => 'password',
            'min' => 8,
            'max' => 100,
            'removeQuotes' => false,
            'xDangerAllowHtml' => true,
            'postProcess' => 'hashPassword',
        ],

        // Same as 'password' but has strength check in client
        'newPassword' => [
            'fieldType' => 'password',
            'min' => 8,
            'max' => 100,
            'removeQuotes' => false,
            'xDangerAllowHtml' => true,
            'postProcess' => 'hashPassword',
        ],

        'email' => [
            'min' => 4,
            'max' => 60,
            'regex' => '\S+?@[^@\s]+\.\S+',
            'fieldType' => 'email',
            'checkEmail' => true,
        ],

        'phone' => [
            'min' => 6,
            'max' => 30,
            'regex' => '[0-9\(\)\.\-\+ext ]+',
            'fieldType' => 'tel',
        ],

        'name' => [
            'min' => 1,
            'max' => 50,
            'fieldType' => 'text',
        ],

        'firstName' => [
            'min' => 1,
            'max' => 20,
            'fieldType' => 'text',
        ],

        'lastName' => [
            'min' => 1,
            'max' => 30,
            'fieldType' => 'text',
        ],


        // Content Types
        //------------------------------------------------

        'title' => [
            'fieldType' => 'text',
            'min' => 1,
            'max' => 80,
            'regex' => '',
            'removeQuotes' => false,
            'civilize'     => true,
        ],

        'comment' => [
            'fieldType' => 'textarea',
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
            'fieldType' => 'textarea',
            'min' => 1,
            'max' => 'none',
            'regex' => '',
            'removeQuotes'      => false,
            'crunchSpaces'      => false,
            'removeNewlines'    => false,
            'xDangerAllowHtml'  => true,
            'postProcess'       => 'parseJson',
        ],

        'file' => [
            'fieldType' => 'file',
            'min' => 1,
            'max' => 'none',
            'checkFile' => true,
        ],

        'image' => [
            'fieldType' => 'file',
            'min' => 1,
            'max' => 'none',
            'checkImage' => true,
        ],


        // Date / Time
        // ------------------------------------------------

        // TODO: interpret min/max as date ranges

        // yyyy-mm-dd
        'date' => [
            'min' => 10,
            'max' => 10,
            'regex' => '[12]\d{3}-[01]\d-[0123]\d',
            'fieldType' => 'date',
            'postProcess' => 'dateObject',
        ],

        // yyyy-MM-ddThh:mm
        'dateTime' => [
            'min' => 16,
            'max' => 16,
            'regex' => '[12]\d{3}-[01]\d-[0123]\dT[012]\d:[0-5]\d',
            'fieldType' => 'datetime-local',
            'postProcess' => 'dateObject',
        ],

        // 2017-W52
        'dateWeek' => [
            'min' => 8,
            'max' => 8,
            'regex' => '[12]\d{3}\-W[0-5]\d',
            'fieldType' => 'week',
            'postProcess' => 'dateObject',
        ],

        // yyyy-mm
        'dateMonth' => [
            'min' => 7,
            'max' => 7,
            'regex' => '[12]\d{3}-[01]\d',
            'fieldType' => 'month',
            'postProcess' => 'dateObject',
        ],

        // hh:mm
        'time' => [
            'min' => 5,
            'max' => 5,
            'regex' => '[012]\d:[0-5]\d',
            'fieldType' => 'time',
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

        'file'     => '',  // require a rule

        'checkbox'         => 'b',
        'checkbox+options' => 'id',

        'date'           => 'date',
        'datetime'       => 'dateTime',
        'datetime-local' => 'dateTime',
        'week'           => 'dateWeek',
        'month'          => 'dateMonth',
        'time'           => 'time',
    ];

    public function getRuleForInputTag($type) {

        if (isset(self::$inputTagToRule[$type])) {
            return self::$inputTagToRule[$type];
        }

        return '';
    }

    function defaultValueForType($type) {

        switch ($type) {
            case 'int':     return 0;
            case 'float':   return 0.0;
            case 'boolean': return false;
            case 'string':  return '';
            case 'list':    return OList::create([]);
        }

        return '';
    }

    function sanitizeValue($val, $rules) {

        $val = trim($val);

        if ($val === '') { return ''; }

        // Normalize windows newlines
        $val = str_replace("\r", '', $val);

        if (!$rules['xDangerAllowHtml']) {
            $val = Security::removeHtmlTags($val);
        }
        if ($rules['removeQuotes']) {
            $val = preg_replace('/[\'"]/', '', $val);
        }
        if ($rules['removeNewlines']) {
            $val = preg_replace('/\n+/', ' ', $val);
        }
        if ($rules['crunchNewlines']) {
            $val = preg_replace('/ +\n/', "\n", $val);
            $val = preg_replace('/\n{3,}/', "\n\n\n", $val);
        }
        if ($rules['crunchSpaces']) {
            $val = preg_replace('/[\t ]+/', ' ', $val);
        }
        if ($rules['civilize']) {
            $val = v($val)->u_civilize();
        }

        // Cast to Type
        if ($rules['valueType'] == 'int') {
            $val = intval($val);
        }
        else if ($rules['valueType'] == 'float') {
            $val = floatval($val);
        }
        else if ($rules['valueType'] == 'boolean') {
            $val = $val === 'true' || $val === '1';
        }

        return $val;
    }




    // validate_* Methods
    //
    //  Return the sanitized (ok) value, or a List with an error message
    // -------------------------------------------------------------------


    function validate_optional($val, $isOptional) {

        if ($val === '' && !$isOptional) {
            return ['Please fill this field.'];
        }

        return $val;
    }

    function validate_min($val, $limit) {

        if ($limit === 'none') {
            return $val;
        }

        $limit = intval($limit);

        if (is_int($val) || is_float($val)) {
            if ($val < $limit) {
                return ["Please make this $limit or more."];
            }
        } else {
            if (mb_strlen($val) < $limit) {
                $letters = $limit == 1 ? 'letter' : 'letters';
                return ["Please make this $limit $letters or longer."];
            }
        }

        return $val;
    }

    function validate_max($val, $limit) {

        if ($limit === 'none') {
            return $val;
        }

        $limit = intval($limit);

        if (is_int($val) || is_float($val)) {
            if ($val > $limit) {
                return ["Please make this $limit or less."];
            }
        } else {
            if (mb_strlen($val) > $limit) {
                return ["Please make this $limit letters or less."];
            }
        }

        return $val;
    }

    function validate_step($val, $step) {

        if ($step === 0) {
            return $val;
        }

        if ($val % $step !== 0) {
            return ["Must be an increment of $step."];
        }

        return $val;
    }

    function validate_regex($val, $pattern) {

        if ($pattern === '') {
            return $val;
        }

        $pattern = str_replace('/', '\\/', $pattern);
        $rx = '/^' . $pattern . '$/';

        if (!preg_match($rx, $val)) {
            return ["Please fix this field."];
        }

        return $val;
    }

    function validate_accepted($val) {

        $val = $this->validate_b($val);

        if ($val === true) {
            return true;
        }

        return ['Please accept this field.'];
    }

    function validate_checkurl($val) {

        if (!Security::validateUserUrl($val)) {
            return ['Please provide a valid URL.'];
        }

        return $val;
    }

    // Fix common typos for the most popular domains
    function validate_checkemail($email) {

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

    function validate_list($val) {

        return $val;
    }

    // Will be processed in InputValidator
    function validate_postprocess($val) {

        return $val;
    }



    // Variable Rules
    //-------------------------------------------------

    function validate_same($val, $arg) {

        if (!isset($this->data[$arg])) {
            return ["RULE ERROR: 'same:$arg'."];
        }

        if (trim($val) !== trim($this->data[$arg])) {
            $arg = ucfirst($arg);
            return ["Please make this the same as '$arg'."];
        }

        return $val;
    }

    function validate_notsame($val, $arg) {

        if (!isset($this->data[$arg])) {
            return ["RULE ERROR: 'notsame:$arg'."];
        }

        if (trim($val) === trim($this->data[$arg])) {
            $arg = ucfirst($arg);
            return ["Please make this different than '$arg'."];
        }

        return $val;
    }

    function validate_in($val, $arg) {

        if (in_array($val, unv($arg))) {
            return $val;
        }

        return ["Must be one of these: $arg"];
    }

    function validate_notin($val, $arg) {

        $result = $this->validate_in($val, $arg);

        if (!is_array($result)) {
            return ["Must not be one of these: $arg"];
        }

        return $val;
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


