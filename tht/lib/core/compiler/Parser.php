<?php

namespace o;

class Parser {

    var $symbol = null;
    var $inTernary = false;
    var $inClass = false;
    var $blockDepth = 0;
    var $expressionDepth = 0;
    var $foreverDepth = 0;
    var $symbolTable = null;
    var $prevToken = null;
    var $validator = null;
    var $foreverBreaks = [];

    var $prevLineWithStatement = -1;
    var $prevLineStatement = null;

    private $tokenNum = 0;
    private $tokenStream = null;
    private $numTokens = 0;
    private $undefinedSymbols = [];

    // Main entry function
    function parse ($tokenStream) {

        $this->tokenStream = $tokenStream;
        $this->numTokens = $tokenStream->count();
        $this->symbolTable = new SymbolTable ($this->numTokens, $this);
        $this->validator = new Validator ($this);

        $this->parseMain();
        $this->validator->validate();

        return $this->symbolTable;
    }

    function error ($msg, $token = null, $isLineError = false) {
        if (!$token) { $token = $this->symbol->token; }
        return ErrorHandler::handleCompilerError($msg, $token, Source::getCurrentFile(), $isLineError);
    }



    ///
    ///  Parsing Methods  (Block > Statement(s) > Expression(s))
    ///


    // Main top-level scope (block without braces)
    function parseMain () {
        $sStatements = [];
        $sMain = $this->makeSequence(SequenceType::BLOCK, []);
        $this->next();
        while (true) {
            $s = $this->symbol;
            if ($s->type === SymbolType::END) {
                break;
            }
            $sStatement = $this->parseStatement();
            if ($sStatement) {
                $this->validateOneStatementPerLine($sStatement);
                $sStatements []= $sStatement;
            }
        }
        $sMain->setKids($sStatements);
    }

    // A Block is a list of Statements (inside braces)
    function parseBlock () {

        $sStatements = [];

        $this->validator->newScope();

        $this->now('{')->space(' { ', true)->next();

        $this->blockDepth += 1;

        while (true) {
            $s = $this->symbol;
            if ($s->isValue('}')) {
                $this->space(' }*', true);
                $this->next();
                break;
            }
            if ($s->type === SymbolType::END) {
                $this->error("Reached end of file without a closing brace `}`.");
            }
            $sStatement = $this->parseStatement();
            if ($sStatement) {
                $this->validateOneStatementPerLine($sStatement);
                $sStatements []= $sStatement;
            }
        }

        $this->validator->popScope();

        $this->blockDepth -= 1;

        $this->prevLineWithStatement = -1;

        return $this->makeSequence(SequenceType::BLOCK, $sStatements);
    }

    // A Statement is a tree of Expressions.
    function parseStatement () {

        Tht::devPrint("START STATEMENT:\n");

        $this->expressionDepth = 0;

        $s = $this->symbol;
        if ($s instanceof S_Statement) {
            $st = $s->asStatement($this);
        }
        else {
            $st = $this->parseExpression(0);

            // Expressions-as-statements must end in a semicolon
            if ($this->symbol->token[TOKEN_VALUE] === ';') {
                $this->next();
            }
            else if ($this->prevToken[TOKEN_VALUE] !== ';') {
                $this->error('Missing semicolon `;` after statement', $this->prevToken);
            }

        }

        return $st;
    }

    // An Expression is an operation that consists of Symbols.
    // Expression starts with a 'left' Symbol, followed by 'inner' Symbols.
    // Symbols are collected into the expression if they have a higher Binding Power.
    // This is how associativity, or precedence, is determined.
    function parseExpression ($baseBindingPower=0) {

        $this->expressionDepth += 1;

        Tht::devPrint("START EXPRESSION bp=$baseBindingPower d=" . $this->expressionDepth . " :\n");

        $s = $this->symbol;
        $left = $s->asLeft($this);
        while (true) {
            if (!($this->symbol->bindingPower > $baseBindingPower)) { break; }
            $left = $this->symbol->asInner($this, $left);
        }

        $this->expressionDepth -= 1;

        return $left;
    }

    // TODO: probably need to refactor duplication between parseMain & parseBlock
    function validateOneStatementPerLine($sStatement) {
        if ($sStatement->type !== SymbolType::TEMPLATE_EXPR && $sStatement->type !== SymbolType::TSTRING) {
            $lineNum = explode(',', $sStatement->token[TOKEN_POS])[0];
            if ($this->prevLineWithStatement == $lineNum) {
                $this->error('Only one semicolon statement allowed per line.', $sStatement->token, true);
            }
            $this->prevLineWithStatement = $lineNum;
        }
    }




    ///
    /// Symbol-Level Methods
    ///


    // Take next Token from input stream and return a Symbol
    function next () {

        // end of stream -- handle off-by-one by returning last symbol (end) again
        if ($this->tokenNum >= $this->numTokens) {
             return $this->symbol;
        }

        if ($this->symbol) {
            $this->prevToken = $this->symbol->token;
        }

        $token = $this->tokenStream->next();
        $this->tokenNum += 1;
        $this->symbol = $this->tokenToSymbol($token);

        if ($token[TOKEN_TYPE] === TokenType::GLYPH) {
            if ($token[TOKEN_VALUE] === ',' || $token[TOKEN_VALUE] === ';') {
                $this->space('x, ');
            }
        }
        return $this->symbol;
    }

    // Assert the current Symbol value
    function now ($expectValue, $context = '') {
        if ($this->symbol->isValue('(end)') && $expectValue === '(newline)') {
            return $this;
        }
        if (!$expectValue) {  return $this;  }
        if (!$this->symbol->isValue($expectValue)) {
            if ($expectValue === ';') {
                $msg = "Missing semicolon `;` at end of statement.";
                $this->error($msg, $this->prevToken);
            } else {
                $msg = "Expected `$expectValue` here instead.";
                if ($context) { $msg .= "  ($context)"; }
                $this->error($msg);
            }

        }
        return $this;
    }

    function space ($mask, $isHard=false) {
        $this->symbol->space($mask, $isHard);
        return $this;
    }

    function checkAltToken ($altValue, $token) {
        $tokenValue = $token[TOKEN_VALUE];
        if (isset(ParserData::$ALT_TOKENS[$tokenValue])) {
            $correct = ParserData::$ALT_TOKENS[$tokenValue];
            $this->error("Unknown token: `$tokenValue`  Try: `$correct`", $token);
        }
    }

    function tokenToSymbol ($token) {

        $symbol = null;
        $tokenType = $token[TOKEN_TYPE];
        $tokenValue = $token[TOKEN_VALUE];

        if (isset(ParserData::$LITERAL_TYPES[$tokenType])) {
            $symbol = new S_Literal ($token, $this, $tokenType);
        }
        else if ($tokenType === TokenType::TSTRING) {
            $symbol = new S_TemplateString ($token, $this);
        }
        else if (isset(ParserData::$SYMBOL_CLASS[$tokenValue])) {
            $symbolClass = 'o\\' . ParserData::$SYMBOL_CLASS[$tokenValue];
            $symbol = new $symbolClass ($token, $this);
        }
        else if ($tokenType === TokenType::WORD) {

            $type = '';
            $allowDigits = true;

            // Classes/Modules start with uppercase letter
            if ($tokenValue[0] >= 'A' && $tokenValue[0] <= 'Z') {
                $type = SymbolType::PACKAGE;
            }
            else if (OBare::isa($tokenValue)) {
                $type = SymbolType::BARE_FUN;
            }
            else if (in_array(strtolower($tokenValue), ParserData::$RESERVED_NAMES)) {
                $type = SymbolType::KEYWORD;
            }
            else {
                // Bare word
                // This might get overrided later as user_fun or map key.
                // TODO: figure out a cleaner way to handle this
                $type = SymbolType::USER_VAR;
            }

            $symbol = new S_Name ($token, $this, $type);

            $this->validator->validateNameFormat($tokenValue, $token, $type);
            $this->validator->validateDefined($symbol);
        }
        else {
            $this->checkAltToken($tokenValue, $token);
            $this->error("Unknown token: `$tokenValue`", $token);
        }

        Tht::devPrint($tokenValue . "  ==>  " . get_class($symbol));

        return $symbol;
    }

    function makeSymbol ($tokenType, $tokenValue, $symbolType) {
        $token = [
            $tokenType,
            $this->prevToken[TOKEN_POS],
            0,
            $tokenValue
        ];
        return new Symbol ($token, $this, $symbolType);
    }

    // A Sequence is a list of Symbols
    function makeSequence ($type, $els) {
        $sList = $this->makeSymbol('(SEQ)', $type, SymbolType::SEQUENCE);
        $sList->setKids($els);
        return $sList;
    }

    function registerUserFunction ($context, $token) {
        $this->validator->registerUserFunction($context, $token);
    }
}




