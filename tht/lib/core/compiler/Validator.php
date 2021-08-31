<?php

namespace o;

class Validator {

    private $parser;

    private $functionScopes = [];
    private $scopes = [];
    private $scopeDepth = -1;

    private $seenVars = [];
    private $userFunctions = [];

    function __construct ($parser) {

        $this->userFunctions = [
            'defined' => [],
            'called'  => [],
        ];

        $this->seenVars = [];

        $this->parser = $parser;
    }

    function error($msg, $token) {

        ErrorHandler::addSubOrigin('validator');
        $this->parser->error($msg, $token);
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

    function newScope () {

        $this->scopeDepth += 1;

        $this->scopes []= [
            'exact'   => [],
            'fuzzy'   => [],
            'pending' => [],
        ];
    }

    function popScope () {

        $pending = $this->scopes[$this->scopeDepth]['pending'];
        if (count($pending)) {

            // Variable that was not initialized immediately after it appeared in the code
            $undefVarName = array_keys($pending)[0];
            $undefVar = $pending[$undefVarName];

            if ($this->skipLambdaVar($undefVarName)) { return; }

            if ($this->seenVars[$undefVarName]) {
                ErrorHandler::setHelpLink('/language-tour/functions#scope', 'Variable Scope');
            }

            $this->error("Unknown variable: `". $undefVarName . "`", $undefVar->token);
        }

        $this->scopeDepth -= 1;
        array_pop($this->scopes);
    }

    // Check if already defined.  Otherwise wait to see if it is defined.
    function registerVar($symbol) {

        $token = $symbol->token;
        $name = '$' . $symbol->getValue();

        $this->seenVars[$name] = true;

        // implicit vars
        // if ($name == '$this' || $name == '$' || $name == '$$') {
        //     return;
        // }
        if ($this->skipLambdaVar($name)) { return; }

        $this->validateVarFormat($name, $symbol->token);

        $exactName = $this->isDefined($name, 'fuzzy');
        if ($exactName) {
            if ($exactName !== $name) {
                $this->error("Typo in variable name: `$name` Try: `" . $exactName . "` (exact case)", $token);
            }
        }
        else if (isset($this->scopes[$this->scopeDepth]['pending'][$name])) {
            // e.g. $a = $a + 1
            $this->error("Unknown variable: `$name`", $token);
        } else {
            $this->scopes[$this->scopeDepth]['pending'][$name] = $symbol;
        }
    }

    function skipLambdaVar($name) {
        if ($this->parser->lambdaDepth > 0) {
            if ($name == '$a' || $name == '$b' || $name == '$c') {
                return true;
            }
        }
        return false;
    }

    function defineVar($symbol, $isStrict = false) {

        $name = '$' . $symbol->getValue();
        $lowerName = strtolower($name);

        // TODO: fix this workaround -- should not be getting registered
        if ($name == '$[' || $name == '$.') { return; }


        if ($isStrict && $this->isDefined($name, 'fuzzy')) {
            $this->error("Variable already defined in this scope: `$name`", $symbol->token);
        }

        $this->scopes[$this->scopeDepth]['fuzzy'][$lowerName] = $name;
        $this->scopes[$this->scopeDepth]['exact'][$name] = $name;

        unset($this->scopes[$this->scopeDepth]['pending'][$name]);
    }

    // is defined in scope
    function isDefined ($name, $match) {

        if ($name == CompilerConstants::$ANON) {  return false;  }

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
                $vars []= $var;
            }
        }

        return $vars;
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
                    $this->error("Typo in function name: `$calledName` Try: `$exactName` (exact case)", $calledToken);
                }
            }
            else {
                $l = $calledName[0];
                if (!preg_match('/^[A-Z]/', $l)) {
                    $this->error("Unknown function: `$calledName`", $calledToken);
                }
            }
        }
    }

    // mark a function as 'defined' or 'called'
    function registerUserFunction($context, $token) {

        if ($token[TOKEN_VALUE] == CompilerConstants::$ANON) {
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
                }
                $this->error("Function `" . $exact . "` already defined$as.", $token);
            }

            $this->userFunctions['defined'][$lowerName] = $exact;

            if (u_Bare::isa($lowerName)) {
                $this->error("Name `" . $lowerName . "` is already the name of a core function.", $token);
            }
            else if (in_array($lowerName, CompilerConstants::$KEYWORDS)) {
                $this->error("Name `" . $lowerName . "` is a reserved word.", $token);
            }
            else if (preg_match('/^[A-Z]/', $exact)) {
                $this->error("Name `" . $exact . "` must be pure lowerCamelCase.", $token);
            }

        } else {
            $this->userFunctions['called'] []= $token;
        }
    }

    function validateVarFormat($name, $token) {

        if ($name == '$') {
            $this->error("Variable is missing a name.", $token);
        }
        else if (preg_match('/[A-Z]/', $name[1])) {
            $this->error("Variable name `$name` should be lower camelCase.", $token);
        }
        else if (preg_match('/[0-9]/', $name[1])) {
            $this->error("Variable name `$name` must start with a letter.", $token);
        }
        else if (preg_match("/[A-Z][A-Z]/", $name)) {
            $this->error("Variable name `$name` should be pure camelCase.", $token);
        }
        else if (strrpos($name, '_') > -1) {
            $this->error("Variable name `$name` should be camelCase. No underscores.", $token);
        }
        else if (strrpos($name, '$') > 0) {
            $this->error("Variable name `$name` should have only one `$` prefix.", $token);
        }
        else if (strlen($name) > CompilerConstants::$MAX_WORD_LENGTH) {
            $this->error("Variable names must be " . CompilerConstants::$MAX_WORD_LENGTH . " characters or less.", $token);
        }
    }

    function validateWordFormat($name, $token, $type) {

        if (strrpos($name, '_') > -1) {
            $this->error("Word `$name` should be camelCase. No underscores.", $token);
        }
        else if (preg_match("/[A-Z][A-Z]/", $name)) {
            if (preg_match("/^[a-z]/", $name)) {
                $this->error("Word `$name` should be pure lowerCamelCase.", $token);
            }
            else {
                $this->error("Word `$name` should be camelCase or UpperCamelCase.", $token);
            }
        }
        else if (strlen($name) === 1 && $name >= 'A' && $name <= 'Z') {
            $this->error("Word with only 1 character must be lower-case.", $token);
        }
        else if (strrpos($name, '$') > -1) {
            $this->error("Non-variable word can not contain a `$`.", $token);
        }
        else if (strlen($name) > CompilerConstants::$MAX_WORD_LENGTH) {
            $this->error("Words must be " . CompilerConstants::$MAX_WORD_LENGTH . " characters or less.", $token);
        }
    }

    function validateFlagFormat($name, $token) {

        if (preg_match("/[A-Z][A-Z]/", $name) || preg_match("/^-[A-Z]/", $name)) {
            $this->error("Flag `$name` should be lowerCamelCase.", $token);
        }
    }
}

