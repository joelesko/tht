<?php

namespace o;

abstract class TemplateMode {
    const NONE       = 0;
    const DECLARE    = 1;
    const BODY       = 2;
    const EXPRESSION = 3;
    const CODE_LINE  = 4;
}

class TokenStream {

    var $tokens = [];

    function add($t) {
        $this->tokens []= implode(TOKEN_SEP, $t);
    }

    function done() {
        if (count($this->tokens) <= 1) {
            // add a noop token to prevent error if there are no tokens (e.g. all comments)
            $this->add([TokenType::WORD, '1,1', 0, 'false']);
        }
        // array_pop is much faster than array_shift, so reverse it
        $this->tokens = array_reverse($this->tokens);

        return $this;
    }

    function count() {
        return count($this->tokens);
    }

    function next() {
        $t = array_pop($this->tokens);
        return explode(TOKEN_SEP, $t, 4);
    }

    function lookahead() {
        $t = $this->tokens[count($this->tokens) - 1];
        return explode(TOKEN_SEP, $t, 4);
    }
}


class Tokenizer extends StringReader {

    private $ADJ_TOKEN_TYPES = ['V', 'N', 'S', 'TS', 'RS', 'W'];
    private $INDENT_SPACES = 4;
    private $CONTINUE_GLYPHS = '+-*/%.';

    private $prevToken = [];
    private $prevAdjToken = [];
    private $tokens = [];
    private $tokenStream = null;

    private $stringMod = '';
    private $inMultiLineString = false;
    private $inQuickList = false;
    private $inComment = false;

    private $currentBraces = [];
    private $currentBrace = [
        'token' => null,
        'indent' => 0,
        'isMultiline' => false,
        'closeBrace' => '',
    ];

    private $prevSpace = false;
    private $prevNewline = false;

    private $inOneLineBlock = false;
    private $expectingBlock = false;
    private $prevBlockIsOneLine = false;

    private $templateMode = TemplateMode::NONE;
    private $currentTemplateTransformer = null;
    private $templateName = '';
    private $templateNameToken = null;
    private $templateType = '';
    private $templateIndent = 0;
    private $templateLineNum = 0;

    private $stats = [
        'numCommentLines' => 0,
        'numCodeLines' => 0,
    ];

    function error ($msg, $tokenPos=null, $_isTemplate=false) {

        if ($tokenPos !== false) {
            $this->updateTokenPos();
        }

        if (is_string($tokenPos)) {
            $tokenPos = explode(',', $tokenPos);
        }

        $pos = $tokenPos ?: $this->tokenPos;

        ErrorHandler::addOrigin('tokenizer');
        if ($_isTemplate) { ErrorHandler::addOrigin('template'); }

        ErrorHandler::handleThtCompilerError($msg, ['', implode(',', $pos)], Compiler::getCurrentFile());
    }

    function templateError($msg, $token=null) {

        if (!$token) {
            $token = $this->prevAdjToken;
        }

        $pos = !is_null($token) ? $token[TOKEN_POS] : $this->lineNum . ',' . $this->colNum;

        $this->error($msg, $pos, true);
    }

    function templateEndError () {
        $this->error("Reached end of file without a closing template brace '}'.");
    }


    // Main: convert string into tokens
    function tokenize () {

        $this->tokenStream = new TokenStream ();

        $this->processText();

        //Tht::dump($this->tokenStream);

        return $this->tokenStream->done();
    }

    function nextSpacesEndInNewline() {
        while (true) {
            $nc = $this->nextChar(1);
            if ($nc == ' ') {
                // trim
                $this->next();
            }
            else {
                // non-space
                return $nc === "\n";
            }
        }
    }

    function isContinueGlyph($c) {
        return str_contains($this->CONTINUE_GLYPHS, $c[0]);
    }

    function makeToken ($type, $value, $forceSpace = '') {

        $spaceMask = 0;

        $nextChar = $this->char();

        // Handle trailing spaces before a newline, as a newline
        if ($nextChar == ' ') {
            if ($this->nextSpacesEndInNewline()) {
                $nextChar = "\n";
            }
        }

        if ($forceSpace == 'after') {
            $nextChar = ' ';
        }
        else if ($forceSpace == 'noAfter') {
            $nextChar = '';
        }

        if ($this->prevNewline)    { $spaceMask += NEWLINE_BEFORE_BIT; }
        else if ($this->prevSpace) { $spaceMask += SPACE_BEFORE_BIT; }

        if ($nextChar === ' ')        { $spaceMask += SPACE_AFTER_BIT; }
        else if ($nextChar === "\n")  { $spaceMask += NEWLINE_AFTER_BIT; }

        $pos = $this->tokenPos[0] . ',' . $this->tokenPos[1];
        $token = [$type, $pos, $spaceMask, $value];

        $this->prevAdjToken = $token;
        if ($nextChar === "\n") {
            $this->prevAdjToken = null;
        }
        if ($value != '(nl)') {
            $this->prevToken = $token;
        }

        $this->prevSpace = false;
        $this->prevNewline = false;

        $this->tokenStream->add($token);

        return $token;
    }

    function insertToken ($type, $value, $forceSpace = '') {
        $this->updateTokenPos();
        $this->makeToken($type, $value, $forceSpace);
    }

    function onNewline() {
        if ($this->colNum > CompilerConstants::$MAX_LINE_LENGTH && $this->templateMode !== TemplateMode::BODY
            && !$this->inMultiLineString && !$this->inComment && !$this->inQuickList) {

            $try = '';
            if (preg_match("/'(.*?)'/", $this->line, $m)) {
                if (mb_strlen($m[1]) >= 60) {
                    $try = " Try: A multi-line string `'''` (it can be any length)";
                }
            }

            // align error pointer with last character in line
            $endTokenPos = $this->getTokenPos();
            $endTokenPos[1] = $this->colNum - 1;
            $this->error('Line has ' . $this->colNum . ' characters.  Maximum is ' . CompilerConstants::$MAX_LINE_LENGTH . '.' . $try, $endTokenPos);
        }
    }

    function processText () {

        while (true) {

            $c = $this->char1;

            if ($c === null) {
                break;
            }
            else if ($c <= ' ') {
                $this->handleWhitespace($c);
            }
            else {

                if ($this->isGlyph(Glyph::LINE_COMMENT)) {
                    $this->handleLineComment();
                }
                else {

                    if ($this->isCloseBrace($c)) {
                        $this->handleCloseBrace($c);
                    }

                    if ($this->isFirstCharOfLine($c)) {
                       $this->checkIndent($c);
                    }

                    if ($c == '$') {
                        $this->handleVarName();
                    }
                    else if (isset($this->isAlpha[$c])) {
                        $this->handleWord($c);
                    }
                    else if (isset($this->isDigit[$c])) {
                        $this->handleNumber($c);
                    }
                    else if ($c === Glyph::QUOTE) {
                        $this->handleString($c);
                    }
                    else if ($this->isGlyph(Glyph::BLOCK_COMMENT_START)) {
                        $this->handleBlockComment($c);
                    }
                    else if ($c == '-') {
                        $this->handleFlag($c);
                    }
                    else {
                        $this->handleGlyph($c);
                    }
                }
            }

            if ($this->templateMode === TemplateMode::BODY) {
                $this->templateMode = $this->handleTemplate();
            }
        }

        $this->postProcess();
    }

    function checkIndent($c) {

        if ($this->indent < $this->currentBrace['indent']) {
            $pc = $this->prevToken[TOKEN_VALUE];
            if ($this->isCloseBrace($pc)) {
                ErrorHandler::addSubOrigin('formatChecker.closingBraces');
                $this->error("Please move delimiter to the next line: `$pc`", $this->prevToken[TOKEN_POS]);
            }
            else {
                $this->updateTokenPos();
                $numSpaces = $this->currentBrace['indent'] - $this->indent;
                $spaces = $numSpaces == 1 ? 'space' : 'spaces';
                ErrorHandler::addSubOrigin('formatChecker.indentation');
                $this->error("Please move indentation: ↦ " . $numSpaces . " $spaces right");
            }
        }
        else if ($this->indent > $this->currentBrace['indent']) {
            // Lines can be indented at further steps of 4. Allows commented-out outer blocks.
            if ($this->indent % $this->INDENT_SPACES != 0) {
                $this->updateTokenPos();
                $numSpaces = $this->indent - $this->currentBrace['indent'];
                $spaces = $numSpaces == 1 ? 'space' : 'spaces';
                ErrorHandler::addSubOrigin('formatChecker.indentation');
                $this->error("Please move indentation: ↤ $numSpaces $spaces left");
            }
        }
    }

    function postProcess() {

        if ($this->currentBrace['closeBrace']) {
            $this->error("Reached end of file without closing delimiter: `" . $this->currentBrace['closeBrace'] . "`", $this->currentBrace['tokenPos']);
        }

        if ($this->templateMode) {
            $this->templateEndError();
        }

        if ($this->currentTemplateTransformer) {
            $this->currentTemplateTransformer->onEndFile();
        }

        $this->insertToken(TokenType::END, '(end)');
    }

    // Note: Transpiled strings are single-quoted.
    // TODO: Move this to EmitterPHP.  Will need to re-parse strings.
    // This applies to STRING, LSTRING, and RSTRING
    // Template strings (TMSTRING) are already escaped in Emitter because they need to be minified first.
    function escape ($c, $stringType) {

        if ($c === "'") {
            return "\\'";
        }
        else if ($c === "`") {
            return "\\'";
        }
        else if ($c === "\\") {

            $next = $this->nextChar();
            $this->next();

            if ($stringType === TokenType::RSTRING) {
                if ($next === '`') {
                    return '`';
                }
                else if ($next == '\\') {
                    return '\\\\\\\\';
                }
                return "\\" . $next;
            }
            else {
                if ($next === '`') {
                    return '`';
                }
                else if ($next == 'n') {
                    return "\n";
                }
                else if ($next == 't') {
                    return "\t";
                }
                else if ($next == '\\') {
                    return '\\\\';
                }

                return "\\" . $next;
            }
        }

        return $c;
    }

    // Basic escaping for single-quoted transpiler strings.
    // TODO: not good having this separate from the logic in escape()
    static function simpleEscape($string) {

        $string = str_replace('\\', '\\\\', $string);
        $string = str_replace("'", "\\'", $string);

        return $string;
    }

    // Make sure certain tokens are not adjacent
    // (e.g. 'foo bar' is not ok.  'function myFun' is ok.)
    function validateAdjToken ($type, $current) {

        if (!$this->prevAdjToken) {  return;  }
        $prev = $this->prevAdjToken[TOKEN_VALUE];

        if (in_array($this->prevAdjToken[TOKEN_TYPE], $this->ADJ_TOKEN_TYPES)) {
            if (!in_array(strtolower($prev), CompilerConstants::$OK_PREV_ADJ_TOKENS) &&
                !in_array(strtolower($current), CompilerConstants::$OK_NEXT_ADJ_TOKENS)) {

                $prevPos = $this->prevAdjToken[TOKEN_POS];

                if ($this->prevAdjToken[TOKEN_TYPE] == 'W') {
                    $this->error("Unknown keyword: `$prev`", $prevPos);
                }
                else if ($type == 'W') {
                    $currentToken = $this->makeToken(TokenType::WORD, $current);
                    $this->error("Unexpected $type.", $currentToken[TOKEN_POS]);
                }
                else {
                    $currentToken = $this->makeToken(TokenType::WORD, $current);
                    $this->error("Unexpected $type. Try: Look for missing separator. e.g. `,`", $currentToken[TOKEN_POS]);
                }
            }
        }
    }

    // Have to do this before indentation is checked
    function handleCloseBrace($c) {

        if ($this->currentBrace['closeBrace'] && $c != $this->currentBrace['closeBrace']) {
            $this->error("Unmatched closing delimiter: `$c`  Expected: `" . $this->currentBrace['closeBrace'] . "`");
        }
        else if (count($this->currentBraces)) {
            $this->currentBrace = array_pop($this->currentBraces);
        }
        else {
            $this->currentBrace = [
                'token' => null,
                'indent' => 0,
                'isMultiline' => false,
                'closeBrace' => '',
            ];
        }
    }

    function handleOpenBrace($c) {
        if ($this->currentBrace) {
            $this->currentBraces []= $this->currentBrace;
        }

        $addIndent = $this->nextChar() == "\n" ? $this->INDENT_SPACES : 0;

        $this->currentBrace = [
            'tokenPos' => $this->tokenPos[0] . ',' . $this->tokenPos[1],
            'indent' => $this->indent + $addIndent,
            'isMultiline' => $addIndent > 0,
            'closeBrace' => $this->getCloseBrace($c),
        ];
    }

    function handleWhitespace ($c) {

        if ($c === "\n") {

            // Template. End of single-line code block '--'
            if ($this->templateMode === TemplateMode::CODE_LINE) {
                $this->templateMode = TemplateMode::BODY;
            }
            else if ($this->templateMode === TemplateMode::EXPRESSION) {
                //$this->updateTokenPos();
                $this->templateError("Unexpected newline inside of template expression: `{{ ... }}`");
            }

            if ($this->currentBrace['closeBrace'] && !$this->currentBrace['isMultiline']) {
                $this->error("Expected closing delimiter on same line: `" . $this->currentBrace['closeBrace'] . "`", $this->currentBrace['tokenPos']);
            }

            // Crunch consecutive newlines
            if (!$this->prevNewline) {
                $this->makeToken(TokenType::NEWLINE, "(nl)");
            }

            $this->prevNewline = true;
        }
        else if ($c === ' ') {
            $this->prevSpace = true;
        }
        else {
            // Noop: skip all other whitespace chars
        }

        $this->next();
    }

    // e.g. $fooBar
    function handleVarName () {
        $this->updateTokenPos();
        $this->next();
        $str = $this->slurpWord();
        $this->validateAdjToken('variable', $str);
        $this->makeToken(TokenType::VAR, $str);
    }

    // e.g. fooBar
    function handleWord ($c) {

        $this->updateTokenPos();

        // modifiers
        $nextChar = $this->nextChar();
        if ($nextChar === Glyph::QUOTE) {
            if (strpos(Glyph::STRING_PREFIXES, $c) === false) {
                $this->error("Unknown string modifier: `$c`");
            }
            $this->stringMod = $c;
            $this->next();
            return;
        }
        else if ($nextChar === '[') {

            // quoted list `q[ word1 word2 etc ]`
            if ($c === Glyph::QUOTED_LIST_PREFIX) {

                $this->inQuickList = true;
                $this->next();
                $this->next();
                $list = $this->slurpUntil(']');
                $this->inQuickList = false;

                // Remove line comments
                $list = preg_replace('#^\s*//.*$#m', '', $list);
                $list = trim($list);

                $words = [];

                if (preg_match('/\n/', $list)) {
                    $words = preg_split('/\s*\n+\s*/', $list);
                }
                else {
                    $words = preg_split('/\s+/', $list);
                }

                $this->makeToken(TokenType::GLYPH, '[', 'noAfter');
                $i = 0;

                foreach ($words as $w) {
                    $this->makeToken(TokenType::STRING, self::simpleEscape($w));
                    if ($i < count($words) - 1) {
                        // insert comma
                        $this->makeToken(TokenType::GLYPH, ',', 'after');
                    }
                    $i += 1;
                }

                $this->makeToken(TokenType::GLYPH, ']');

                return;
            }
        }
        else if ($nextChar === '{') {
            // lambda `x{ ... }`
            if ($c === 'x') {
                $this->makeToken(TokenType::WORD, 'lambda');
                $this->next();
                return;
            }
        }

        // complete word
        $str = $this->slurpWord();


        if ($this->char() === "'") {
            $this->stringMod = $str;
            $this->handleString("'");
        }
        else {
            $this->validateAdjToken('word', $str);
            $token = $this->makeToken(TokenType::WORD, $str);

            // Track function name in case we are going into a template.
            if ($this->templateName === '(pending)') {
                $this->templateName = $str;
                $this->templateNameToken = $token;
            }
            else if ($str === CompilerConstants::$TEMPLATE_TOKEN) {
                // Allow 'tem' as a map key
                if ($this->char() !== ':') {
                    $this->templateMode = TemplateMode::DECLARE;
                    $this->templateName = '(pending)';
                    $this->templateIndent = $this->indent;
                    $this->templateLineNum = $this->lineNum;
                }
            }
        }
    }

    // e.g. 1234
    function handleNumber ($c) {

        $this->updateTokenPos();

        $str = $this->slurpNumber();

        // Convert the string value to a number
        if (strlen($str) > 1 && ($str[1] == 'b' || $str[1] == 'x')) {
            // hex & binary: 0x1234 | 0b011010
            $this->makeToken(TokenType::NUMBER, $str);
        }
        else if (is_numeric($str)) {

            // Ignore leading zeros -- no implicit octal
            $str = ltrim($str, '0');
            if (!$str) { $str = 0; }

            $this->validateAdjToken('number', $str);
            $this->makeToken(TokenType::NUMBER, $str);
        } else {
            $this->error("Unable to convert to number: `$str` ");
        }
    }

    // e.g. 'hello' or '''multiline\nstring'''
    function handleString ($c) {

        $str = '';
        $closeQuote = $c;
        $this->updateTokenPos();
        $startPos = $this->getTokenPos();

        $multilineIndent = -1;
        $this->inMultiLineString = false;
        if ($this->nextChar(2) == "''") {
            $multilineIndent = $this->indent;
            $this->next(2);
            $this->inMultiLineString = true;
            if ($this->nextChar() !== "\n") {
                $this->error("Triple-quotes `'''` must be followed by a newline.\n\n"
                    . "Indentation will be trimmed to the left-most character.");
            }
        }

        $type = TokenType::STRING;
        if ($this->stringMod === Glyph::REGEX_PREFIX) {
            $type = TokenType::RSTRING;
        }
        else if ($this->stringMod) {
            $type = TokenType::TSTRING;
            $str = $this->stringMod . '::';
        }
        $this->stringMod = '';

        while (true) {
            $this->next();
            $c = $this->char();

            if ($c === "\n" && !$this->inMultiLineString) {
                $this->updateTokenPos();
                $this->error("Unexpected newline. Missing quote? Try: triple-quotes `'''` for a multi-line string.");
            }
            if ($this->atEndOfFile()) {
                $this->error("Unclosed string. Maybe you missed a closing quote ($c).", $startPos);
            }
            if ($c === $closeQuote) {
                if ($this->inMultiLineString) {
                    if ($this->nextChar(2) == "''") {
                        if (!$this->atStartOfLine()) {
                            $this->error("Closing triple-quotes `'''` must belong on a separate line.");
                        }
                        $this->next(2);
                        break;
                    }
                }
                else {
                    break;
                }
            }
            if ($this->isFirstCharOfLine($c) && $this->indent < $multilineIndent + $this->INDENT_SPACES) {
                if (!$this->isGlyph("'")) {
                    $this->updateTokenPos();
                    $numSpaces = ($multilineIndent + $this->INDENT_SPACES) - $this->indent;
                    $spaces = $numSpaces == 1 ? 'space' : 'spaces';
                    ErrorHandler::addSubOrigin('formatChecker.indentation');
                    $this->error("Please indent line in multi-line string: ↦ " . $numSpaces . " $spaces");
                }
            }
            $str .= $this->escape($c, $type);
        }

        if ($this->inMultiLineString) {
            $str = v($str)->u_trim_indent(OMap::create(['keepRelative' => true]));
        }

        $this->next();

        if ($type == TokenType::RSTRING) {
            $regexMods = $this->slurpWord();
            $str = $regexMods . '::' . $str;
        }

        $this->validateAdjToken('string', $str);
        $this->makeToken($type, $str);

        if ($this->char() == Glyph::QUOTE) {
            $this->error("Extra quote `'` character.");
        }
    }

    // // e.g. -myFlag
    function handleFlag ($c) {

        $nc = $this->nextChar();
        if (!isset($this->isAlpha[$nc])) {
            return $this->handleGlyph('-');
        }

        $this->updateTokenPos();

        $str = '-';
        while (true) {
            $this->next();
            $c = $this->char();
            if (isset($this->isAlpha[$c]) || isset($this->isDigit[$c])) {
                $str .= $c;
            }
            else {
                break;
            }
        }

        $type = TokenType::FLAG;

        $this->validateAdjToken('string', $str);
        $this->makeToken($type, $str);
    }

    function handleBlockComment($c) {

        $this->updateTokenPos();
        $commentDepth = 0;

        $this->inComment = true;
        $blockCommentStartPos = $this->tokenPos;

        while (true) {
            if ($this->isGlyph(Glyph::BLOCK_COMMENT_START)) {
                if (!$this->atStartOfLine()) {
                    $this->error("Block comment should be on a separate line.");
                }
                $this->nextFor(Glyph::BLOCK_COMMENT_START);
                $this->updateTokenPos();
                $commentDepth += 1;
            }
            else if ($this->isGlyph(Glyph::BLOCK_COMMENT_END)) {
                $this->nextFor(Glyph::BLOCK_COMMENT_END);
                $this->updateTokenPos();
                $blockCommentLineNum = 0;

                if ($this->char() !== "\n") {
                    $this->error("Missing newline after block comment: `" . Glyph::BLOCK_COMMENT_END . "`");
                }
                $commentDepth -= 1;
                if (!$commentDepth) {
                    break;
                }

            } else {
                if ($this->atEndOfFile()) {
                    $this->error("Block comment is missing a closing token: `" . Glyph::BLOCK_COMMENT_END . "`", $blockCommentStartPos);
                }
            }
            $this->next();
            if ($this->char() === "\n") {
                $this->stats['numCommentLines'] += 1;
            }
        }
        $this->inComment = false;
    }

    function handleLineComment () {

        $this->updateTokenPos();

        $this->inComment = true;
        $this->slurpLine();

        // Keep newline for code line
        if ($this->templateMode === TemplateMode::CODE_LINE) { $this->rewind(1); }

        $this->inComment = false;

        $this->stats['numCommentLines'] += 1;
    }

    function handleGlyph ($c) {

        $this->updateTokenPos();

        if ($this->templateMode == TemplateMode::NONE) {

            if ($this->isOpenBrace($c)) {
                $this->handleOpenBrace($c);
            }
            else if ($this->atStartOfLine() && $this->isContinueGlyph($c) && $this->currentBrace['closeBrace'] !== ')') {
                $numSpaces = ($this->currentBrace['indent'] + $this->INDENT_SPACES) - $this->indent;
                if ($numSpaces > 0) {
                    $this->updateTokenPos();
                    $spaces = $numSpaces == 1 ? 'space' : 'spaces';
                    ErrorHandler::addSubOrigin('formatChecker.indentation');
                    $this->error("Please indent line in continued statement: ↦ " . $numSpaces . " $spaces right");
                }
            }

            // Catch mistake of using 'fun' in place of 'tem'
            if (($this->prevAdjToken && $this->prevAdjToken[TOKEN_VALUE] === '{') || $this->prevAdjToken === null) {
                if (strpos('<#', $c) !== false) {
                    $this->error("Unexpected token: `$c`  Did you mean to use a `tem` template instead?");
                }
                if ($this->isGlyph('{{') || $this->isGlyph('===')) {
                    $this->error("Unexpected token: `$c`  Did you mean to use a `tem` template instead?");
                }
            }
        }
        else if ($this->templateMode == TemplateMode::DECLARE && $this->isGlyph('{')) {

            // start of template body

            if ($this->templateName == '(pending)') {
                $this->templateError("Template must have a name.");
            }

            $foundType = preg_match('/(' . CompilerConstants::$TEMPLATE_TYPES . ')$/i', $this->templateName, $m);
            if (!$foundType) {
                $rec = lcfirst($this->templateName) . "Html";
                $this->templateError("Missing type at end of template name.  e.g. `tem $rec`", $this->templateNameToken);
            }

            // TODO: require newline after ':'

            $this->templateMode = TemplateMode::BODY;
            $this->templateType = strtolower($m[1]);
            $handlerClass = "o\\" . ucfirst($this->templateType) . 'TemplateTransformer';
            $this->currentTemplateTransformer = new $handlerClass ($this);

            $this->nextFor('{');
            $this->makeToken(TokenType::GLYPH, '{');
            if ($this->char() !== "\n") {
                $this->templateError("Opening template brace `{` must be followed by a newline.");
            }

            return;
        }
        else if ($this->templateMode === TemplateMode::EXPRESSION && $this->isGlyph(Glyph::TEMPLATE_EXPR_END)) {
            // end of template var '}}'
            $this->makeToken(TokenType::GLYPH, Glyph::TEMPLATE_EXPR_END);
            $this->templateMode = TemplateMode::BODY;
            $this->nextFor(Glyph::TEMPLATE_EXPR_END);
            return;
        }

        // collect final characters
        $str = '';

        if ($c == '@') {
            while (true) {
                $str .= $c;
                $this->next();
                $c = $this->char();
                if ($c != '@') {
                    break;
                }
            }
        }
        else if (strpos(Glyph::MULTI_GLYPH_PREFIX, $c) !== false) {

            // multi-glyph (e.g. >=)
            while (true) {
                $str .= $c;
                $this->next();
                $c = $this->char();
                if ($this->atEndOfFile()) {
                    $this->error('Unexpected end of file.');
                }
                if (strpos(Glyph::MULTI_GLYPH_SUFFIX, $c) === false) {
                    break;
                }
            }
        }
        else {

            // single glyph
            $str = $c;
            $this->next();
        }

        $t = $this->makeToken(TokenType::GLYPH, $str);

        if ($str[0] == '@' && $c !== '.') {
            $this->error("Missing `.` after: `$str`", $t[TOKEN_POS]);
        }
    }

    // Dynamically insert expressions into a template -- NOT USED?
    // function insertTmExpression($rawSource) {

    //     $this->addTemplateString();

    //     $innerTok = new Tokenizer($rawSource);
    //     $tokens = $innerTok->tokenize();

    //     $this->insertToken(TokenType::GLYPH, Glyph::TEMPLATE_EXPR_START);
    //     $this->insertToken(TokenType::STRING,
    //         $this->currentTemplateTransformer->currentContext() . ':' . $this->indent);

    //     $this->insertToken(TokenType::GLYPH, '+'); // just to satistfy the tokenizer rules

    //     foreach ($tokens as $t) {
    //         $this->insertToken($t[TOKEN_TYPE], $t[TOKEN_VALUE]);
    //     }

    //     $this->makeToken(TokenType::GLYPH, Glyph::TEMPLATE_EXPR_END);
    // }

    // Handle template function body as a special string, dipping back out into THT parsing
    // mode when it encounters expressions and code lines.

    private $tmString = '';

    function handleTemplate () {

        $this->tmString = '';

        while (true) {

            $c = $this->char();

            if ($this->atEndOfFile()) {
                $this->templateEndError();
            }
            else if ($this->isGlyph(Glyph::TEMPLATE_EXPR_START)) {

                // THT expression {{ ... }}

                $this->nextFor(Glyph::TEMPLATE_EXPR_START);

                $this->addTemplateString();

                $this->insertToken(TokenType::GLYPH, Glyph::TEMPLATE_EXPR_START);
                $this->insertToken(TokenType::STRING, $this->currentTemplateTransformer->currentContext() . ':' . $this->indent);
                $this->insertToken(TokenType::GLYPH, '+'); // just to satistfy the tokenizer rules

                return TemplateMode::EXPRESSION;
            }
            else if ($this->isFirstCharOfLine($c) && $this->indent < $this->templateIndent + $this->INDENT_SPACES) {

                // End of template body
                if (!$this->isGlyph('}')) {
                    ErrorHandler::addSubOrigin('formatChecker.indentation');
                    $numSpaces = ($this->templateIndent + $this->INDENT_SPACES) - $this->indent;
                    $spaces = $numSpaces == 1 ? 'space' : 'spaces';
                    $this->templateError("Please indent line inside template: ↦ " . $numSpaces . " $spaces");
                }

                $this->currentTemplateTransformer->onEndBody();
                $this->addTemplateString();
                $this->nextFor('}');

                if ($this->char() !== "\n") {
                    $this->templateError("Missing newline after closing brace `}`.");
                }

                $this->prevSpace = true;  // satisfy format checker (space before '}')
                $this->insertToken(TokenType::GLYPH, '}');

                $this->templateName = '';

                return TemplateMode::NONE;
            }
            else if ($this->atStartOfLine() && $this->isGlyph(Glyph::TEMPLATE_CODE_LINE)) {

                // One line of THT code e.g. '--- $a = 1'
                $this->addTemplateString();
                $this->nextFor(Glyph::TEMPLATE_CODE_LINE);
                if ($this->char() !== " ") {
                    $this->templateError("Missing space after: `" . Glyph::TEMPLATE_CODE_LINE . "`");
                }

                return TemplateMode::CODE_LINE;
            }
            else {

                // Do transformations based on template type (e.g. handle HTML tags)
                $transformed = $this->currentTemplateTransformer->transformNext();

                if ($transformed !== false) {
                    $this->tmString .= $transformed;
                }
                else {
                    // plaintext
                    $this->tmString .= $c;
                    $this->next();
                }
            }
        }
    }

    function addTemplateString () {

        $str = $this->currentTemplateTransformer->onEndChunk($this->tmString);
        $this->insertToken(TokenType::TMSTRING, $str);

        $this->tmString = '';
    }
}

