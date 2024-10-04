<?php

namespace o;

// Note: There is no distinct "Format Checker" phase.
// Maybe items that are style-specific (e.g. spacing) could be moved to a separate module.
// Otherwise, for now, all checks happen here or at the location of the issue.

class Validator {

    private $parser;

    private $functionScopes = [];
    private $scopes = [];
    private $scopeDepth = -1;

    private $userFunctions = [];

    //const SKIP_UNUSED_PREFIX = 'x';

    function __construct($parser) {

        $this->userFunctions = [
            'defined' => [],
            'called'  => [],
        ];

        $this->parser = $parser;
    }

    function error($msg, $token) {

        ErrorHandler::addSubOrigin('validator');

        $this->parser->error($msg, $token);
    }

    function dump() {
        $ary = [];
        foreach ($this->scopes as $s) {
            $frame = [];
            $frame['pending'] = array_keys($s['pending']);
            $frame['exact'] = array_keys($s['exact']);
            $ary []= $frame;
        }
        return $ary;
    }

    function postParseValidation() {

        $this->validateFunctionCalls();
    }

    function newFunctionScope() {

        $this->functionScopes []= $this->scopes;
        $this->scopes = [];
        $this->scopeDepth = -1;
    }

    function popFunctionScope() {

        $this->scopes = array_pop($this->functionScopes);
        $this->scopeDepth = count($this->scopes) - 1;
    }

    function newScope() {

        $this->scopeDepth += 1;

        $this->scopes []= [
            'exact'   => [],
            'fuzzy'   => [],
            'pending' => [],
      //      'unused'  => [],
        ];
    }

    function popScope() {

        $pending = $this->scopes[$this->scopeDepth]['pending'];
        if (count($pending)) {

            // Variable that was not initialized immediately after it appeared in the code
            $undefVarName = array_keys($pending)[0];
            $undefVar = $pending[$undefVarName];

            if ($this->skipLambdaVar($undefVarName)) { return; }

            $varsInScope = array_keys($this->scopes[$this->scopeDepth]['exact']);
            $suggest = ErrorHandler::getFuzzySuggest($undefVarName, $varsInScope);

            $this->error("Unknown variable: `". $undefVarName . "`  $suggest", $undefVar->token);
        }

        // TODO: Disabling this for now, as things like foreach vars are often unused. Delete later.
        // $unused = $this->scopes[$this->scopeDepth]['unused'];
        // if (count($unused)) {
        //     $unusedVarName = array_keys($unused)[0];
        //     $unusedVar = $unused[$unusedVarName];

        //     $xName = preg_replace('/\$/', '$' . self::SKIP_UNUSED_PREFIX, $unusedVarName);
        //     $suggest = "Try: Prefix the var with `" . self::SKIP_UNUSED_PREFIX . "` if you want to keep it.  Ex: `$xName`";
        //     $this->error("Variable was declared but never used: `". $unusedVarName . "`  $suggest", $unusedVar->token);
        // }

        $this->scopeDepth -= 1;
        return array_pop($this->scopes);
    }

    // Check if already defined.  Otherwise wait to see if it is defined.
    function registerVar($symbol) {

        $token = $symbol->token;
        $name = '$' . $symbol->getValue();

        if ($this->skipLambdaVar($name)) { return; }

        $exactName = $this->isDefined($name, 'fuzzy');
        if ($exactName && $exactName !== $name) {
            $this->error("Typo in variable name: `$name`  Try: `" . $exactName . "` (exact case)", $token);
        }
        else if (isset($this->scopes[$this->scopeDepth]['pending'][$name])) {
            // e.g. $a = $a + 1
            $this->error("Unknown variable: `$name`", $token);
        }
        else if (!$exactName) {
            $this->scopes[$this->scopeDepth]['pending'][$name] = $symbol;
            // if (!$this->parser->funArgMode && $name[1] !== self::SKIP_UNUSED_PREFIX) {
            //     $this->scopes[$this->scopeDepth]['unused'][$name] = $symbol;
            // }
        }
        // else {
        //     $this->markVarAsUsed($name);
        // }

        $this->validateVarFormat($name, $symbol->token);
    }

    // function markVarAsUsed($name) {
    //     foreach ($this->scopes as $i => $scope) {
    //         unset($this->scopes[$i]['unused'][$name]);
    //     }
    // }

    function skipLambdaVar($name) {
        if ($this->parser->lambdaDepth > 0) {
            if ($name == '$a' || $name == '$b' || $name == '$c') {
                return true;
            }
        }
        return false;
    }

    function defineVar($symbol, $checkIfDefined) {

        $name = '$' . $symbol->getValue();
        $lowerName = strtolower($name);

        // TODO: fix this workaround -- should not be getting registered
        if ($name == '$[' || $name == '$.') { return; }

        if ($checkIfDefined && $this->isDefined($name, 'fuzzy')) {
            $this->error("Variable already defined in this scope: `$name`", $symbol->token);
        }

        $this->scopes[$this->scopeDepth]['fuzzy'][$lowerName] = $name;
        $this->scopes[$this->scopeDepth]['exact'][$name] = $name;

        unset($this->scopes[$this->scopeDepth]['pending'][$name]);
    }

    // is defined in scope
    function isDefined($name, $match) {

        if ($name == CompilerConstants::ANON) {  return false;  }

        if ($match == 'fuzzy') {
            $name = strtolower($name);
        }

        foreach ($this->scopes as $s) {
            if (isset($s[$match][$name])) {
                return $s[$match][$name];
            }
        }

        return false;
    }

    function getAllInScope() {

        $vars = [];

        foreach ($this->scopes as $s) {
            foreach ($s['exact'] as $var) {
                $vars[$var] = true;
            }
        }

        return array_keys($vars);
    }

    function validateFunctionCalls() {
        // Make sure called user functions have been defined (exact match)
        $defined = $this->userFunctions['defined'];
        foreach ($this->userFunctions['called'] as $calledToken) {
            $calledName = $calledToken[TOKEN_VALUE];
            $calledNameFuzzy = strtolower($calledName);

            if (isset($defined[$calledNameFuzzy])) {
                $exactName = $defined[$calledNameFuzzy];
                if ($calledName !== $exactName) {
                    $this->error("Typo in function name: `$calledName`  Try: `$exactName` (exact case)", $calledToken);
                }
            }
            else {
                $l = $calledName[0];
                if (!preg_match('/^[A-Z]/', $l)) {

                    $definedFuns = array_values($defined);
                    $suggest = ErrorHandler::getFuzzySuggest($calledName, $definedFuns, 'isMethod');

                    $this->error("Unknown function: `$calledName`  $suggest", $calledToken);
                }
            }
        }
    }

    // mark a function as 'defined' or 'called'
    function registerUserFunction($context, $token) {

        if ($token[TOKEN_VALUE] == CompilerConstants::ANON) {
            return;
        }

        if ($context === 'defined') {
            $exact = $token[TOKEN_VALUE];
            $lowerName = strtolower($exact);

            if (isset($this->userFunctions['defined'][$lowerName])) {
                $correct = $this->userFunctions['defined'][$lowerName];
                $as = '';
                if ($exact !== $correct) {
                    $as = " as `$correct`";
                    $this->error("Function `" . $exact . "` is already defined as: `$correct`", $token);
                }
                else {
                    $this->error("Function already defined: `" . $exact . "`", $token);
                }
            }

            $this->userFunctions['defined'][$lowerName] = $exact;

            if (u_Bare::isa($lowerName)) {
                $this->error("Name is already a core function: `" . $lowerName . "`", $token);
            }
            else if (in_array($lowerName, CompilerConstants::KEYWORDS)) {
                $this->error("Name is a reserved word: `" . $lowerName . "`", $token);
            }
            else if (preg_match('/^[A-Z]/', $exact)) {
                $this->error("Name must be pure lowerCamelCase: `" . $exact . "`", $token);
            }

        } else {
            $this->userFunctions['called'] []= $token;
        }
    }

    function validateVarFormat($name, $token) {

        if ($name == '$') {
            $this->error("Variable is missing a name.", $token);
        }
        else if (preg_match('/[0-9]/', $name[1])) {
            $this->error("Variable name must start with a letter: `$name`", $token);
        }
        else if (preg_match("/[A-Z][A-Z]/", $name)) {
            $try = $this->suggestCamelCase($name);
            $this->error("Variable name should be pure camelCase: `$name`  $try", $token);
        }
        else if (str_contains($name, '_')) {
            $try = $this->suggestCamelCase($name);
            $this->error("Variable name should be camelCase (no underscores): `$name`  $try", $token);
        }
        else if (strrpos($name, '$') > 0) {
            $this->error("Variable name should have only one `$` prefix: `$name`", $token);
        }
        else if (preg_match('/[A-Z]/', $name[1])) {
            $try = $this->suggestCamelCase(lcfirst($name));
            $this->error("Variable name should be lower camelCase: `$name`  $try", $token);
        }
        else if (strlen($name) > CompilerConstants::MAX_WORD_LENGTH) {
            $this->error("Variable names must be " . CompilerConstants::MAX_WORD_LENGTH . " characters or less.", $token);
        }
    }

    private function suggestCamelCase($s) {
        return 'Try: `$' . v(v($s)->u_to_token_case('-'))->u_to_token_case('camel') . '`';
    }

    function validateWordFormat($name, $token, $type) {

        if (str_contains($name, '_')) {
            $try = v($name)->u_to_token_case('camel');
            $this->error("Word should be camelCase (no underscores): `$name`  Try: `$try`", $token);
        }
        else if (preg_match("/[A-Z][A-Z]/", $name)) {
            if (preg_match("/^[a-z]/", $name)) {
                $this->error("Word should be pure lowerCamelCase: `$name`", $token);
            }
            else {
                $this->error("Word should be camelCase or UpperCamelCase: `$name`", $token);
            }
        }
        else if (strlen($name) === 1 && $name >= 'A' && $name <= 'Z') {
            // TODO: allow these as map keys
            $this->error("Word with only 1 character must be lower-case.", $token);
        }
        else if (str_contains($name, '$')) {
            $this->error("Non-variable word can not contain sigil: `$`", $token);
        }
        else if (strlen($name) > CompilerConstants::MAX_WORD_LENGTH) {
            $this->error("Words must be " . CompilerConstants::MAX_WORD_LENGTH . " characters or less.", $token);
        }
    }

    function validateFlagFormat($name, $token) {

        if (preg_match("/[A-Z][A-Z]/", $name) || preg_match("/^-[A-Z]/", $name)) {
            $this->error("Flag should be lowerCamelCase: `$name`", $token);
        }
    }

    function catchMissingDollarVar($symbol) {

        if (!$symbol) { return; }

        // Bareword symbol wasn't updated to a function name or map key
        if ($symbol->type == SymbolType::BARE_WORD) {
            $word = $symbol->token[TOKEN_VALUE];
            $varName = '$' . $word;
            $this->error("Unexpected word: `$word`  Try: `$varName` (variable)", $symbol->token);
        }
    }

    // Validate whitespace rules for this symbol (before and after).
    // The middle separator character is arbitrary.  Only the left and right have meaning.
    // Examples:
    // ' | ' = whitespace required before & after
    // 'x| ' = space not allowed before, whitespace required after
    // '*| ' = anything before, whitespace required after
    // '+| ' = anything but newline before, whitespace required after
    // '*|N' = anything before, newline or non-space after
    // '*|B' = anything before, newline required (hard break) after
    // '*|S' = anything before, space (not newline) required after
    function validateSymbolSpacing($symbol, $pattern, $formatCheckerRule='') {

        $lPattern = $pattern[0];
        $rPattern = $pattern[strlen($pattern) - 1];

        $this->validateSymbolSpacingPos($symbol, 'L', $lPattern, $formatCheckerRule);
        $this->validateSymbolSpacingPos($symbol, 'R', $rPattern, $formatCheckerRule);
    }

    function validateSymbolSpacingPos($symbol, $pos, $pattern, $formatCheckerRule) {

        if ($pattern == '*') { return; }

        $s = $symbol;

        $p = $this->parser;
        $t = $s->token;

        $isRequired = ($pattern === ' ' || $pattern === 'S');
        $allowNewline = ($pattern === 'N' || $pattern === 'B');

        $cSpace = $t[TOKEN_SPACE];

        $bitHasSpace = $pos === 'L' ? SPACE_BEFORE_BIT : SPACE_AFTER_BIT;
        $hasSpace = ($cSpace & $bitHasSpace);

        $bitHasNewline = $pos === 'L' ? NEWLINE_BEFORE_BIT : NEWLINE_AFTER_BIT;
        $hasNewline = ($cSpace & $bitHasNewline);

        if ($hasNewline && $pattern !== 'S') {
            $hasSpace = true;
        }

        if ($hasNewline && $allowNewline) {
            return;
        }

        $verb = '';
        $what = 'space';
        if (($pattern === 'S' || $pattern == '+') && $hasNewline) {
            $verb = 'remove the';
            $what = 'newline';
        }
        else if ($pattern === 'B' && !$hasNewline) {
            $verb = 'add a';
            $what = 'newline';
        }
        else if ($hasSpace && !$isRequired) {
            $verb = 'remove the';
            $what = $hasNewline ? 'newline' : 'space';
        }
        else if (!$hasSpace && $isRequired) {
            $verb = 'add a';
            if ($pos === 'R') {
                $nextToken = $p->next()->token;
                if ($nextToken[TOKEN_VALUE] === ';') {
                    $p->error('Unexpected semicolon: `;`', $nextToken);
                }
                else if ($nextToken[TOKEN_VALUE] === ',') {
                    $p->error('Unexpected comma: `,`', $nextToken);
                }
            }
        }

        if ($verb) {

            $sPos = $pos === 'L' ? 'before' : 'after';
            $aPos = explode(',', $t[TOKEN_POS]);

            $posDelta = 0;
            if ($verb == 'remove the') {
                $posDelta = $pos === 'L' ? -1 : strlen($t[TOKEN_VALUE]);
            }

            // e.g. "Please remove the space before: `==`"
            $fullMsg = 'Please ' . $verb . ' ' . $what . ' ' . $sPos . ": `" . $t[TOKEN_VALUE] . "`";

            $t[TOKEN_POS] = $aPos[0] . ',' . ($aPos[1] + $posDelta);

            $subOrigin = 'formatChecker';
            if ($formatCheckerRule) { $subOrigin .= '.' . $formatCheckerRule; }
            ErrorHandler::addSubOrigin($subOrigin);

            $p->error($fullMsg, $t);
        }

        return;
    }

    static function validateUnsupportedKeyword($token, $isUnknown=false) {

        $fuzzyWord = strtolower($token[TOKEN_VALUE]);
        $try = '';
        if (isset(CompilerConstants::UNSUPPORTED_KEYWORD[$fuzzyWord])) {
            $help = CompilerConstants::UNSUPPORTED_KEYWORD[$fuzzyWord];
            if ($help) {
                $isUnknown = true;
                if (is_array($help)) {
                    ErrorHandler::setHelpLink($help[2], $help[1]);
                    $try = "Try: " . $help[0];
                }
                else {
                    $try = "Try: `$help`";
                }
            }
        }
        else {
            $try = ErrorHandler::getFuzzySuggest($fuzzyWord, CompilerConstants::KEYWORDS, false);
        }

        if ($isUnknown) {
            if ($try) {
                $msg = "Unsupported keyword: `" . $token[TOKEN_VALUE] . "`  $try";
            }
            else {
                $msg = "Unexpected keyword: `" . $token[TOKEN_VALUE] . "`";
            }

            ErrorHandler::handleThtCompilerError($msg, $token, Compiler::getCurrentFile());
        }
    }

}

