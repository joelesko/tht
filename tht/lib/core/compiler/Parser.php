<?php

namespace o;

class Parser {

    var $symbol = null;

    var $inTernary = false;
    var $inClass = false;
    var $inFieldMap = false;
    var $numClasses = 0;
    var $blockDepth = 0;
    var $expressionDepth = 0;
    var $breakableDepth = 0;
    var $functionDepth = 0;
    var $lambdaDepth = 0;
    var $anonFunctionDepth = 0;
    var $sOuterParen = false;

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

    public $ignoreNewlines = false;

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
    ///  Parsing Methods:   Block > Statement(s) > Expression(s)
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
                } else if ($hasFunction && $type !== SymbolType::PRE_KEYWORD) {
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

        if ($this->symbol->isSeparator('(nl)')) {
            // This will get caught in the 'space' call below
            $this->next();
        }

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

                if ($this->inClass && $this->blockDepth == 1) {
                    $allowed = 'CLASS_PLUGIN|CLASS_FIELDS|NEW_FUN|PRE_KEYWORD';
                    if (strpos($allowed, $sStatement->type) === false) {
                        $msg = 'Invalid statement inside `class` block.';
                        if ($sStatement->type == SymbolType::ASSIGN) {
                            $msg = 'Assignment not allowed in class block. Try: `fields {...}`';
                        }
                        ErrorHandler::setOopErrorDoc();
                        $this->error($msg, $sStatement->token);
                    }
                }
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
                        $str = $st->token[TOKEN_VALUE];
                        $this->error("Invalid standalone value: `$str`", $st->token);
                    }
                    else {
                        if ($st->isValue('==')) {
                            $suggest = ' Try: `=` (assignment)';
                        }
                        $this->error('Invalid standalone expression.' . $suggest, $st->token);
                    }
                }

              //  $this->now(';', 'statement.end');
            }
        }

        return $st;
    }

    // An Expression is an operation that consists of Symbols.
    // Expression starts with a 'left' Symbol, followed by 'inner' Symbols.
    // Symbols are collected into the expression if they have a higher Binding Power.
    // This is how associativity/precedence is determined.
    function parseExpression ($baseBindingPower=0) {

        $this->expressionDepth += 1;

        $left = $this->symbol->asLeft($this);
        $s = null;
        while (true) {
            $s = $this->symbol;
            if (!($s->bindingPower > $baseBindingPower)) {
                break;
            }
            $left = $s->asInner($this, $left);
        }

        $this->expressionDepth -= 1;

        $this->checkOuterParens($s, $baseBindingPower);

        return $left;
    }

    // Handle comma/newline as an inner separator for Lists, Maps, etc.
    // TODO: don't allow comma after opening delimiter
    function parseElementSeparator() {

        if ($this->symbol->isSeparator('(nl)')) {
             $this->next();
        }
        else if ($this->symbol->isSeparator(',')) {
            $this->now(',')->space('x, ');
            $this->next();
            if ($this->symbol->isSeparator('(nl)')) {
                $this->next();
                ErrorHandler::addSubOrigin('formatChecker');
                $this->error('Comma `,` is not needed before a line break.', $this->prevToken);
            }
        }
    }


    // function parseCommaSeparator() {

    //     // if ($this->symbol->isSeparator('(nl)')) {
    //     //     $this->next();
    //     // }
    //  //   else if ($this->symbol->isSeparator(',')) {
    //         $this->now(',')->space('x, ');
    //         $this->next();
    //         if ($this->symbol->isSeparator('(nl)')) {
    //             $this->next();
    //             //ErrorHandler::addSubOrigin('formatChecker');
    //             //$this->error('Comma (,) is not needed before a line break.');
    //         }
    //   //  }
    // }

    function skipNewline() {
        if ($this->symbol->isSeparator('(nl)')) {
            $this->next();
            return true;
        }
        return false;
    }

    // TODO: probably need to refactor duplication between parseMain & parseBlock
    function validateOneStatementPerLine($sStatement) {
        if ($sStatement->type !== SymbolType::TEMPLATE_EXPR && $sStatement->type !== SymbolType::TSTRING) {
            $lineNum = explode(',', $sStatement->token[TOKEN_POS])[0];
            if ($this->prevLineWithStatement == $lineNum) {
                $this->error('Only one statement allowed per line.', $sStatement->token, true);
            }
            $this->prevLineWithStatement = $lineNum;
        }
    }

    // Don't allow e.g. `if (true) {...}`
    function noOuterParens() {
        if ($this->symbol->token[TOKEN_VALUE] == '(') {
            $this->sOuterParen = $this->symbol;
        }
        else {
            $this->sOuterParen = null;
        }
        return $this;
    }

    function checkOuterParens($s, $baseBindingPower) {
        if ($this->sOuterParen && $baseBindingPower == 0 && $this->expressionDepth <= 1) {
            if ($s->token[TOKEN_VALUE] == ')') {
                ErrorHandler::addSubOrigin('formatChecker');
                $this->outerParenError($this->sOuterParen);
            }
        }
    }

    function outerParenError($sOuterParen) {
        ErrorHandler::addSubOrigin('formatChecker');
        $this->error('Please remove the outer parens `(...)`.', $sOuterParen->token);
    }



    ///
    /// Symbol-Level Methods
    ///


    // Take next Token from input stream and return it as a Symbol
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

        if ($token[TOKEN_TYPE] === TokenType::NEWLINE) {
            if ($this->ignoreNewlines) {
                return $this->next();
            }
            $nextToken = $this->tokenStream->lookahead();
            if (in_array($nextToken[TOKEN_VALUE], CompilerConstants::$SKIP_NEWLINE_BEFORE)) {
                return $this->next();
            }

        }

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

        if (!$expectValue) { return $this; }

        if (!$this->symbol->isValue($expectValue)) {
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
        else if ($tokenType === TokenType::VAR) {
            $type = SymbolType::USER_VAR;
            $symbol = new S_Var ($token, $this, $type);
            $this->validator->registerVar($symbol);
        }
        else if (isset(CompilerConstants::$SYMBOL_CLASS[strtolower($tokenValue)])) {
            if ($tokenValue !== strtolower($tokenValue)) {
                $this->error("Keyword `$tokenValue` must be all lowercase.", $token);
            }

            $symbolClass = 'o\\' . CompilerConstants::$SYMBOL_CLASS[$tokenValue];
            $symbol = new $symbolClass ($token, $this);
        }
        else if ($tokenType === TokenType::WORD) {

            $type = '';

            // Classes/Modules start with uppercase letter
            if (in_array(strtolower($tokenValue), CompilerConstants::$KEYWORDS)) {
                if ($tokenValue !== strtolower($tokenValue)) {
                    $this->error("Word `$tokenValue` must be all lowercase.", $token);
                }
                $type = SymbolType::KEYWORD;
            }
            else if ($tokenValue[0] >= 'A' && $tokenValue[0] <= 'Z') {
                $type = SymbolType::PACKAGE;
            }
            else if (Tht::module('Bare')::isa($tokenValue)) {
                $type = SymbolType::BARE_FUN;
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
            if ($tokenValue == ';') {
                $this->error("Please remove semicolon `;`", $token);
            } else {
                $this->error("Unknown token: `$tokenValue`", $token);
            }
        }

        return $symbol;
    }

    function makeSymbol ($tokenType, $tokenValue, $symbolType) {
        $pos = is_null($this->prevToken) ? '0,0' : $this->prevToken[TOKEN_POS];
        $token = [
            $tokenType,
            $pos,
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




