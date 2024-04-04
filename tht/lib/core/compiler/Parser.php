<?php

namespace o;

class Parser {

    private $tokenNum = 0;
    private $tokenStream = null;
    private $numTokens = 0;
    private $undefinedSymbols = [];

    private $sOuterParen = false;
    private $prevLineWithStatement = -1;
    private $prevLineStatement = null;

    public $symbol = null;

    public $inTernary = false;
    public $inClass = false;
    public $inFieldMap = false;
    public $allowAssignmentExpression = false;
    public $ignoreNewlines = false;
    public $numClasses = 0;

    public $blockDepth = 0;
    public $expressionDepth = 0;
    public $breakableDepth = 0;
    public $functionDepth = 0;
    public $lambdaDepth = 0;
    public $anonFunctionDepth = 0;
    public $outerParenExpressionDepth = -10;
    public $inTemplate = false;
    public $assignmentLeftSide = null;

    public $symbolTable = null;
    public $prevToken = null;
    public $validator = null;
    public $loopBreaks = [];


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



    //  Parsing Methods:   Block > Statement(s) > Expression(s)
    //-------------------------------------------------------------------------


    // Main top-level scope (block without braces)
    function parseMain () {

        $sStatements = [];
        $this->validator->newScope();
        $sMain = $this->makeAstList(AstList::BLOCK, []);
        $this->next();
        $fileHasFunction = false;

        while (true) {

            $s = $this->symbol;
            if ($s->type === SymbolType::END) {
                break;
            }

            // Skip newlines
            if ($s->isNewline()) {
                $this->next();
                continue;
            }

            $sStatement = $this->parseStatement();

            if ($sStatement) {

                $this->validateOneStatementPerLine($sStatement);
                $sStatements []= $sStatement;

                $type = $sStatement->type;

                if ($type === SymbolType::NEW_FUN || $type === SymbolType::NEW_TEMPLATE || $type === SymbolType::NEW_CLASS) {
                    $fileHasFunction = true;
                }
                else if ($fileHasFunction && $type !== SymbolType::PRE_KEYWORD) {
                    $this->error("Top-level statements must be declared before functions.", $sStatement->token, true);
                }
            }
        }
        $sMain->setKids($sStatements);

        $this->validator->popScope();
    }

    // A Block is a list of Statements (inside braces)
    // TODO: don't allow statements on same line as mutli-line braces
    function parseBlock ($deferClosingScope = false) {

        $sStatements = [];

        $this->validator->newScope();
        $this->blockDepth += 1;

        // one-liner syntax
        if ($this->symbol->isValue(':')) {

            $this->space('x:S')->next();
            $s = $this->parseOneLineBlock($deferClosingScope);

            // Make a single-statement block
            return $this->makeAstList(AstList::BLOCK, [$s]);
        }

        if ($this->symbol->isNewline()) {
            // This will get caught in the 'space' call below
            $this->next();
        }

        $sOpenBrace = $this->symbol;

        // Catch missing { in the line itself, instead of at next token position
        if ($this->inTemplate && !$sOpenBrace->isValue('{')) {
            $this->error('Expected `{` at end of template code line.', $this->prevToken, true);
        }

        $this->now('{', 'block.open');

        // Brace goes on same line
        if ($sOpenBrace->hasNewlineBefore()) {
            ErrorHandler::addSubOrigin('formatChecker.openBraces');
            $this->error('Please move open brace `{` to the end of the previous line.');
        }

        $this->space('S{ ')->next();

        while (true) {

            $s = $this->symbol;
            if ($s->isValue('}')) {
                break;
            }
            if ($s->type === SymbolType::END) {
                $this->error("Reached end of file without a closing block brace: `}`", $sOpenBrace->token, true);
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

                        ErrorHandler::setOopHelpLink();
                        $this->error($msg, $sStatement->token);
                    }
                }
            }
        }

        $this->space(' }*');

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

            // Standalone expression as statement e.g. `foo()`
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
            }
            else {
                if ($this->prevToken && $this->prevToken[TOKEN_VALUE] !== '(nl)') {
                    $tokenVal = $this->prevToken[TOKEN_VALUE];
                    $desc = 'Unexpected';
                    if (strpos(CompilerConstants::$CLOSING_SEPERATORS, $tokenVal) !== false) {
                        // Very common typo: Extra `)` or `]` or `}`
                        $desc = 'Extra';
                    }
                    $this->error("$desc separator token: `$tokenVal`", $this->prevToken);
                }
            }
        }

        return $st;
    }

    // An Expression is an operation that consists of Symbols.
    // Expression starts with a 'left' Symbol, followed by 'inner' Symbols.
    // Symbols are collected into the expression if they have a higher Binding Power.
    // This is how associativity/precedence is determined.
    function parseExpression ($baseBindingPower=0, $checkOuterParen = false) {

        $this->expressionDepth += 1;

        if ($checkOuterParen) {
            $this->startCheckOuterParens();
        }

        $left = $this->symbol->asLeft($this);

        while (true) {
            $s = $this->symbol;
            if (!($s->bindingPower > $baseBindingPower)) {
                break;
            }
            $left = $s->asInner($this, $left);
        }

        if ($checkOuterParen) {
            $this->endCheckOuterParens();
        }

        $this->expressionDepth -= 1;

        return $left;
    }

    // Handle comma/newline as an inner separator for Lists, Maps, etc.
    // Returns true if newline.
    function parseElementSeparator($pos, $isMultiline, $openBrace) {

        if ($this->symbol->isNewline()) {

            $this->next();
            return true;
        }
        else if ($this->symbol->isSeparator(',')) {

            $this->next();

            if ($pos == 0) {
                $this->error('Unexpected comma: `,`', $this->prevToken);
            }
            else if ($this->symbol->isNewline()) {
                ErrorHandler::addSubOrigin('formatChecker.trailingCommas');
                $this->error('Comma `,` is not needed at end of line.', $this->prevToken);
            }
            else if ($this->symbol->isValue('}') || $this->symbol->isValue(']')) {
                ErrorHandler::addSubOrigin('formatChecker.trailingCommas');
                $this->error('Please remove the trailing comma: `,`', $this->prevToken);
            }
            else if ($this->symbol->isValue(',')) {
                ErrorHandler::addSubOrigin('formatChecker.trailingCommas');
                $this->error('Please remove the extra comma: `,`', $this->prevToken);
            }

            return false;
        }
    }

    function skipNewline() {

        if ($this->symbol->isNewline()) {
            $this->next();
            return true;
        }

        return false;
    }

    // TODO: probably need to refactor duplication between parseMain & parseBlock
    function validateOneStatementPerLine($sStatement) {

        if ($sStatement->type !== SymbolType::TEMPLATE_EXPR && $sStatement->type !== SymbolType::TMSTRING) {
            $lineNum = explode(',', $sStatement->token[TOKEN_POS])[0];

            if ($this->prevLineWithStatement == $lineNum) {
                $this->error('Only one statement allowed per line.', $sStatement->token, true);
            }

            $this->prevLineWithStatement = $lineNum;
        }
    }

    // Don't allow e.g. `if (true) {...}`
    function startCheckOuterParens() {

        if ($this->symbol->token[TOKEN_VALUE] == '(') {
            $this->sOuterParen = $this->symbol;
        }
        else {
            $this->sOuterParen = null;
        }
        return $this;
    }

    function endCheckOuterParens() {

        if ($this->sOuterParen) {
            if ($this->prevToken[TOKEN_VALUE] == ')') {
                $this->outerParenError($this->sOuterParen);
            }
        }
    }

    function outerParenError($sOuterParen) {

        ErrorHandler::addSubOrigin('formatChecker.outerParens');
        $this->error('Please remove the outer parens: `(...)`', $sOuterParen->token);
    }



    //  Symbol-Level Methods
    //------------------------------------------------------------------


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
            if ($token[TOKEN_VALUE] === ',') {
                $this->space('x, ');
            }
        }
        return $this->symbol;
    }

    // Assert the current Symbol value
    function now ($expectValue, $context = '', $try = '') {

        if (!$expectValue) { return $this; }

        if (!$this->symbol->isValue($expectValue)) {

            $token = $this->symbol->token;
            $msg = "Expected `$expectValue` here instead.";

            if ($this->symbol->isValue('(end)')) {
                $msg = "Expected `$expectValue` as next token.";
                $token = $this->prevToken;
            }
            if ($try) {
                $msg .= 'Try: ' . $try;
            }

            ErrorHandler::addSubOrigin('expect');
            if ($context) { ErrorHandler::addSubOrigin($context); }

            $this->error($msg, $token);
        }
        return $this;
    }

    function peekNextToken() {
        return $this->tokenStream->lookahead();
    }

    function space ($mask) {

        $this->symbol->space($mask);

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
        else if ($tokenType === TokenType::TMSTRING) {
            $symbol = new S_TemplateString ($token, $this);
        }
        else if ($tokenType === TokenType::FLAG) {
            $symbol = new S_Flag ($token, $this);
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
            // Not Found
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

    // Catch missing $ for variables
    function catchMissingDollarVar($symbol) {

        if (!$symbol) { return; }

        if ($symbol->type == SymbolType::BARE_WORD) {
            $varName = '$' . $symbol->token[TOKEN_VALUE];
            $this->error("Unexpected word. Did you mean to use a variable `$varName`?", $symbol->token);
        }
    }
}




