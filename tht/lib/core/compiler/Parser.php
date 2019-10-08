<?php

namespace o;

class Parser {

    var $symbol = null;

    var $inTernary = false;
    var $inClass = false;
    var $blockDepth = 0;
    var $expressionDepth = 0;
    var $breakableDepth = 0;
    var $functionDepth = 0;
    var $lambdaDepth = 0;

    var $allowAssignmentExpression = false;
    var $symbolTable = null;
    var $prevToken = null;
    var $validator = null;
    var $loopBreaks = [];

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
        $this->validator->postParseValidation();

        return $this->symbolTable;
    }

    function error ($msg, $token = null, $isLineError = false) {
        if (!$token) { $token = $this->symbol->token; }
        ErrorHandler::addOrigin('parser');
        return ErrorHandler::handleThtCompilerError($msg, $token, Compiler::getCurrentFile(), $isLineError);
    }



    ///
    ///  Parsing Methods  (Block > Statement(s) > Expression(s))
    ///


    // Main top-level scope (block without braces)
    function parseMain () {
        $sStatements = [];
        $this->validator->newScope();
        $sMain = $this->makeAstList(AstList::BLOCK, []);
        $this->next();
        $hasFunction = false;

        while (true) {
            $s = $this->symbol;
            if ($s->type === SymbolType::END) {
                break;
            }
            $sStatement = $this->parseStatement();
            if ($sStatement) {
                $this->validateOneStatementPerLine($sStatement);
                $sStatements []= $sStatement;

                $type = $sStatement->type;
                if ($type === SymbolType::NEW_FUN || $type === SymbolType::NEW_TEMPLATE || $type === SymbolType::NEW_CLASS) {
                    $hasFunction = true;
                } else if ($hasFunction) {
                    $this->error("Top-level statements can only be declared before functions.", $sStatement->token);
                }
            }
        }
        $sMain->setKids($sStatements);

        $this->validator->popScope();
    }

    // A Block is a list of Statements (inside braces)
    function parseBlock ($deferClosingScope = false) {

        $sStatements = [];

        $this->validator->newScope();
        $this->blockDepth += 1;

        // one-liner syntax
        if ($this->symbol->isValue(':')) {
            $this->space('x:S', true)->next();
            $s = $this->parseOneLineBlock($deferClosingScope);
            return $this->makeAstList(AstList::BLOCK, [$s]);
        }

        $sOpenBrace = $this->symbol;

        $this->now('{', 'block.open')->space(' { ', true)->next();

        while (true) {
            $s = $this->symbol;
            if ($s->isValue('}')) {
                break;
            }
            if ($s->type === SymbolType::END) {
                $this->error("Reached end of file without a closing block brace `}`.", $sOpenBrace->token, true);
            }
            $sStatement = $this->parseStatement();
            if ($sStatement) {
                $this->validateOneStatementPerLine($sStatement);
                $sStatements []= $sStatement;
            }
        }

        $this->space(' }*', true);

        $this->blockDepth -= 1;
        $this->prevLineWithStatement = -1;

        if (!$deferClosingScope) {
            $this->validator->popScope();
            $this->now('}', 'block.close')->next();
        }

        return $this->makeAstList(AstList::BLOCK, $sStatements);
    }

    function parseOneLineBlock($deferClosingScope) {

        $s = $this->parseStatement();

        if (!$deferClosingScope) {
            $this->validator->popScope();
            $this->next();
        }

        $this->blockDepth -= 1;
        $this->prevLineWithStatement = -1;

        return $s;
    }

    // A Statement is a tree of Expressions.
    function parseStatement() {

        $this->expressionDepth = 0;

        $s = $this->symbol;

        if ($s instanceof S_Statement) {
            $st = $s->asStatement($this);
        }
        else {

            // Standalone expression as statement e.g. `foo();`
            $st = $this->parseExpression(0);

            if ($st) {

                if ($st->type !== SymbolType::ASSIGN && !($st instanceof S_OpenParen)) {
                    $suggest = '';
                    if ($st instanceof S_Literal) {
                        $this->error('Invalid standalone value.', $st->token);
                    }
                    else {
                        if ($st->isValue('==')) {
                            $suggest = ' Try: `=` (assignment)';
                        }
                        $this->error('Invalid standalone expression.' . $suggest, $st->token);
                    }
                }

                $this->now(';', 'statement.end');
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
        if (!$expectValue) { return $this; }

        if (!$this->symbol->isValue($expectValue)) {

            if ($expectValue === ';') {
                $msg = "Missing semicolon `;` at end of statement.";
                $this->error($msg, $this->prevToken);
            } else {
                $token = $this->symbol->token;
                $msg = "Expected `$expectValue` here instead.";
                if ($this->symbol->isValue('(end)')) {
                    $msg = "Expected `$expectValue` as next token.";
                    $token = $this->prevToken;
                }

                ErrorHandler::addSubOrigin('expect');
                if ($context) { ErrorHandler::addSubOrigin($context); }

                $this->error($msg, $token);
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
        if (isset(CompilerConstants::$SUGGEST_TOKEN[$tokenValue])) {
            $correct = CompilerConstants::$SUGGEST_TOKEN[$tokenValue];
            $this->error("Unknown token: `$tokenValue`  Try: `$correct`", $token);
        }
    }

    function tokenToSymbol ($token) {

        $symbol = null;
        $tokenType = $token[TOKEN_TYPE];
        $tokenValue = $token[TOKEN_VALUE];

        if (isset(CompilerConstants::$LITERAL_TYPES[$tokenType])) {
            $symType = CompilerConstants::$LITERAL_TYPES[$tokenType];
            $symbol = new S_Literal ($token, $this, $symType);
        }
        else if ($tokenType === TokenType::TSTRING) {
            $symbol = new S_TemplateString ($token, $this);
        }
        else if (isset(CompilerConstants::$SYMBOL_CLASS[$tokenValue])) {
            $symbolClass = 'o\\' . CompilerConstants::$SYMBOL_CLASS[$tokenValue];
            $symbol = new $symbolClass ($token, $this);
        }
        else if ($tokenType === TokenType::VAR) {
            $type = SymbolType::USER_VAR;
            $symbol = new S_Name ($token, $this, $type);
            $this->validator->registerVar($symbol);
        }
        else if ($tokenType === TokenType::WORD) {

            $type = '';

            // Classes/Modules start with uppercase letter
            if ($tokenValue[0] >= 'A' && $tokenValue[0] <= 'Z') {
                $type = SymbolType::PACKAGE;
            }
            else if (Tht::module('Bare')::isa($tokenValue)) {
                $type = SymbolType::BARE_FUN;
            }
            else if (in_array(strtolower($tokenValue), CompilerConstants::$RESERVED_NAMES)) {
                $type = SymbolType::KEYWORD;
            }
            else {
                // This will be overrided later as user_fun or map key.
                $type = SymbolType::BARE_WORD;
            }

            $this->validator->validateWordFormat($tokenValue, $token, $type);

            $symbol = new S_Name ($token, $this, $type);
        }
        else {
            $this->checkAltToken($tokenValue, $token);
            $this->error("Unknown token: `$tokenValue`", $token);
        }

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

    // A AstList is a list of Symbols (e.g. block, list)
    function makeAstList ($type, $els) {
        $sList = $this->makeSymbol('(SEQ)', $type, SymbolType::AST_LIST);
        $sList->setKids($els);
        return $sList;
    }

    function registerUserFunction ($context, $token) {
        $this->validator->registerUserFunction($context, $token);
    }
}




