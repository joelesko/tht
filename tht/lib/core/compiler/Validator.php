<?php

namespace o;

class Validator {

    private $scopes = [];
    private $parser;
    private $undefinedVars = [];
    private $userFunctions = [];
    private $isPaused = false;

    function __construct ($parser) {

        $this->userFunctions = [
            'defined' => [],
            'called'  => [],
        ];

        $this->parser = $parser;
        $this->newScope();
    }

    function validate() {
        $this->validateUndefined();
        $this->validateFunctions();
    }

    function popScope () {
        return array_pop($this->scopes);
    }

    function newScope () {
        $this->scopes []= [ 'exact' => [], 'fuzzy' => [] ];
    }

    function define ($symbol, $allowDupe=false) {

        $symbol->setDefined();
        $name = $symbol->getValue();
        $token = $symbol->token;
        $lowerName = strtolower($name);

        $existingName = $this->isDefined($lowerName);
        if ($existingName && !$allowDupe) {
            $this->parser->error("Name `" . $existingName . "` is already defined in this scope.", $token);
        }
        if (OBare::isa($lowerName)) {
            $this->parser->error("Name `" . $lowerName . "` is the name of a core function.", $token);
        }
        if (in_array($lowerName, ParserData::$RESERVED_NAMES)) {
            $this->parser->error("Name `" . $lowerName . "` is a reserved word.", $token);
        }

        $currentScope = count($this->scopes) - 1;
        $this->scopes[$currentScope]['fuzzy'][$lowerName] = $token;
        $this->scopes[$currentScope]['exact'][$name] = $token;
    }

    function isDefined ($name) {
        if ($name == ParserData::$ANON) {  return false;  }
        foreach ($this->scopes as $s) {
            if (isset($s['fuzzy'][$name])) {
                return $s['fuzzy'][$name][TOKEN_VALUE];
            }
        }
        return false;
    }

    function validateDefined ($symbol) {
        if (!$this->inScope($symbol->token)) {
            $this->undefinedVars []= $symbol;
        }
    }

    function validateUndefined () {
        foreach ($this->undefinedVars as $s) {
            if ($s->type === SymbolType::USER_VAR && !$s->getDefined()) {
                $this->parser->error('Unknown variable: `' . $s->getValue() . '`', $s->token);
            }
        }
    }

    function validateFunctions() {
        // Force exact case for user-defined functions
        $defined = $this->userFunctions['defined'];
        foreach ($this->userFunctions['called'] as $funToken) {
            $funName = $funToken[TOKEN_VALUE];
            $fuzzy = strtolower($funName);
            if (isset($defined[$fuzzy])) {
                $exact = $defined[$fuzzy];
                if ($funName !== $exact) {
                    $this->parser->error("Function name case mismatch.  Use `$exact` instead.", $funToken);
                }
            }
        }
    }

    function registerUserFunction($context, $token) {
        if ($context === 'defined') {
            $exact = $token[TOKEN_VALUE];
            $fuzzy = strtolower($exact);
            $this->userFunctions['defined'][$fuzzy] = $exact;
        } else {
            $this->userFunctions['called'] []= $token;
        }
    }

    function validateNameFormat ($name, $token, $type) {

        if (preg_match("/[A-Z][A-Z]/", $name)) {
            $case = 'camelCase';
            if (preg_match("/^[A-Z]/", $name) && preg_match("/[a-z]/", $name)) {
                $case = 'UpperCamelCase';
            }
            $this->parser->error("Word `$name` should be pure $case.", $token);
        }
        else if (strrpos($name, '_') > -1) {
            $this->parser->error("Word `$name` should be camelCase. No underscores.", $token);
        }
        else if (strlen($name) === 1 && $name >= 'A' && $name <= 'Z') {
            $this->parser->error("UpperCamelCase words must be longer than 1 character.", $token);
        }
        else if (strlen($name) > ParserData::$MAX_WORD_LENGTH) {
            $this->parser->error("Words must be " . ParserData::$MAX_WORD_LENGTH . " characters or less.", $token);
        }
        else if ($name == 'l') {
            $this->parser->error("Can't use `l` as an identifier, for readability.  It looks too much like a `1` (number one).", $token);
        }
    }

    function setPaused ($isPaused) {
        $this->isPaused = $isPaused;
    }

    function inScope ($token) {
        $name = $token[TOKEN_VALUE];
        if (OBare::isa($name)) {
            return true;
        }
        foreach ($this->scopes as $s) {
            if (isset($s['exact'][$name])) {
                return true;
            }
        }
        return false;
    }
}

