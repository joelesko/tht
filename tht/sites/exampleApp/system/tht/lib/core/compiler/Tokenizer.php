<?php

namespace o;

abstract class TemplateMode {
    const NONE      = 0;
    const PRE       = 1;
    const BODY      = 2;
    const EXPR      = 3;
    const CODE_LINE = 4;
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

    private $prevToken = [];
    private $tokens = [];

    private $stringMod = '';
    private $inMultiLineString = false;
    private $inComment = false;

    private $indentBlocks = [];
    private $currIndentBlock = [ 'indent' => 0, 'glyph' => '' ];

    private $prevSpace = false;
    private $prevNewline = false;

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

    function error ($msg, $tokenPos=null, $isTemplate=false) {

        if ($tokenPos !== false) {
            $this->updateTokenPos();
        }

        if (is_string($tokenPos)) {
            $tokenPos = explode(',', $tokenPos);
        }

        $pos = $tokenPos ?: $this->tokenPos;

        ErrorHandler::addOrigin('tokenizer');
        if ($isTemplate) { ErrorHandler::addOrigin('template'); }

        ErrorHandler::handleThtCompilerError($msg, ['', implode(',', $pos)], Compiler::getCurrentFile());
    }

    function templateError($msg, $token=null) {

        if (!$token) {
            $token = $this->prevToken;
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

        $this->prevToken = $token;
        if ($nextChar === "\n") {
            $this->prevToken = null;
        }

        $this->prevSpace = false;
        $this->prevNewline = false;

        $this->tokenStream->add($token);

        return $token;
    }

    function insertToken ($type, $value) {
        $this->updateTokenPos();
        $this->makeToken($type, $value);
    }

    function onNewline() {
        if ($this->colNum > CompilerConstants::$MAX_LINE_LENGTH && $this->templateMode !== TemplateMode::BODY
            && !$this->inMultiLineString && !$this->inComment) {

            $try = '';
            if (preg_match("/'(.*?)'/", $this->line, $m)) {
                if (mb_strlen($m[1]) >= 60) {
                    $try = " Try: A multi-line string `''' ... '''` (can be any length)";
                }
            }

            $this->error('Line has ' . $this->colNum . ' characters.  Maximum is ' . CompilerConstants::$MAX_LINE_LENGTH . '.' . $try);
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
            else if ($c == '$') {
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
            else if ($this->isGlyph(Glyph::LINE_COMMENT)) {
                $this->handleLineComment();
            }
            else if ($this->isGlyph(Glyph::BLOCK_COMMENT_START)) {
                $this->handleBlockComment($c);
            }
            else {
                $this->handleGlyph($c);
            }


            if ($this->templateMode === TemplateMode::BODY) {
                $this->templateMode = $this->handleTemplate();
            }
        }

        $this->postProcess();
    }

    function postProcess() {
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

    // Make sure certain tokens are not adjacent (e.g. 'foo bar' is not ok.  'function myFun' is ok.)
    function validateAdjToken ($type, $current) {

        if (!$this->prevToken) {  return;  }
        $prev = $this->prevToken[TOKEN_VALUE];

        if (in_array($this->prevToken[TOKEN_TYPE], $this->ADJ_TOKEN_TYPES)) {
            if (!in_array(strtolower($prev), CompilerConstants::$OK_PREV_ADJ_TOKENS) &&
                !in_array(strtolower($current), CompilerConstants::$OK_NEXT_ADJ_TOKENS)) {

                $prevPos = $this->prevToken[TOKEN_POS];
                // if (in_array($prev, CompilerConstants::$UNSUPPORTED_PREV_TOKENS)) {
                //     $this->error("Unknown keyword `$prev`.", $prevPos);
                // }
                if ($this->prevToken[TOKEN_TYPE] == 'W') {
                    $this->error("Unknown keyword: `$prev`", $prevPos);
                }
                else if ($type == 'W') {
                    $this->error("Unexpected $type.", false);
                }
                else {
                    $this->error("Unexpected $type. Try: Look for missing separator. e.g. `,`", false);
                }
            }
        }
    }

    function handleWhitespace ($c) {

        if ($c === "\n") {

            // Template. End of single-line code block '--'
            if ($this->templateMode === TemplateMode::CODE_LINE) {
                $this->templateMode = TemplateMode::BODY;
            }
            else if ($this->templateMode === TemplateMode::EXPR) {
                //$this->updateTokenPos();
                $this->templateError("Unexpected newline inside of template expression `{{ ... }}`.");
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

                $this->next();
                $this->next();
                $list = $this->slurpUntil(']');
                $words = preg_split('/\s+/', trim($list));
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
                $this->templateMode = TemplateMode::PRE;
                $this->templateName = '(pending)';
                $this->templateIndent = $this->indent;
                $this->templateLineNum = $this->lineNum;
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
            $this->error("Bad number `$str`. It can not be converted to a number.");
        }
    }

    // e.g. 'hello' or '''multiline\nstring'''
    function handleString ($c) {

        $str = '';
        $closeQuote = $c;
        $this->updateTokenPos();
        $startPos = $this->getTokenPos();

        $this->inMultiLineString = false;
        if ($this->nextChar(2) == "''") {
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

            $str .= $this->escape($c, $type);
        }

        if ($this->inMultiLineString) {
            $str = v($str)->u_trim_indent(true);
        }

        $this->next();

        if ($type == TokenType::RSTRING) {
            $regexMods = $this->slurpWord();
            $str = $regexMods . '::' . $str;
        }

        $this->validateAdjToken('string', $str);
        $this->makeToken($type, $str);
    }

    function handleBlockComment($c) {

        $this->updateTokenPos();
        $commentDepth = 0;

        $this->inComment = true;
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

                if ($this->char() !== "\n") {
                    $this->error("Missing newline after block comment `" . Glyph::BLOCK_COMMENT_END . "`.");
                }
                $commentDepth -= 1;
                if (!$commentDepth) {
                    break;
                }

            } else {
                if ($this->atEndOfFile()) {
                    $this->error("Unclosed comment block.  You missed a `" . Glyph::BLOCK_COMMENT_END . "` somewhere.");
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

        // Catch mistake of using 'fn' in place of 'tm'
        if (($this->prevToken && $this->prevToken[TOKEN_VALUE] === '{') || $this->prevToken === null) {
            if (strpos('<#', $c) !== false) {
                $this->error("Unexpected `$c`.  Did you mean to use a `tm` template instead?");
            }
            if ($this->isGlyph('{{') || $this->isGlyph('===')) {
                $this->error("Unexpected `$c`.  Did you mean to use a `tm` template instead?");
            }
        }

        if ($this->isGlyph('{') && $this->templateMode == TemplateMode::PRE) {

            // start of template body
            $foundType = preg_match('/(' . CompilerConstants::$TEMPLATE_TYPES . ')$/i', $this->templateName, $m);
            if (!$foundType) {
                $rec = lcfirst($this->templateName) . "Html";
                $this->templateError("Missing type at end of template function name.  e.g. `tm $rec`", $this->templateNameToken);
            }
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
        else if ($this->templateMode === TemplateMode::EXPR && $this->isGlyph(Glyph::TEMPLATE_EXPR_END)) {
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
            $this->error("Missing `.` after `$str`.", $t[TOKEN_POS]);
        }
    }

    // Dynamically insert expressions into a template
    function insertTmExpression($rawSource) {

        $this->addTemplateString();

        $innerTok = new Tokenizer($rawSource);
        $tokens = $innerTok->tokenize();

        $this->insertToken(TokenType::GLYPH, Glyph::TEMPLATE_EXPR_START);
        $this->insertToken(TokenType::STRING,
            $this->currentTemplateTransformer->currentContext() . ':' . $this->indent);

        $this->insertToken(TokenType::GLYPH, '+'); // just to satistfy the tokenizer rules

        foreach ($tokens as $t) {
            $this->insertToken($t[TOKEN_TYPE], $t[TOKEN_VALUE]);
        }

        $this->makeToken(TokenType::GLYPH, Glyph::TEMPLATE_EXPR_END);
    }

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
                $this->insertToken(TokenType::STRING,
                    $this->currentTemplateTransformer->currentContext() . ':' . $this->indent);
                $this->insertToken(TokenType::GLYPH, '+'); // just to satistfy the tokenizer rules

                return TemplateMode::EXPR;
            }
            else if ($this->atStartOfLine() && !$this->isWhitespace($c)
                && $this->indent <= $this->templateIndent) {

                // End of template body '}'

                if (!$this->isGlyph('}')) {
                    $this->templateError("Line should be indented inside template `"
                        . $this->templateName . "` starting at Line " . $this->templateLineNum . ".");
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
            // else if ($this->atStartOfLine() && $this->isGlyph(Glyph::TEMPLATE_LINE_COMMENT)) {
            //     $this->handleLineComment();
            // }
            else if ($this->atStartOfLine() && $this->isGlyph(Glyph::TEMPLATE_CODE_LINE)) {

                // One line of THT code e.g. '--- $a = 1'
                $this->addTemplateString();
                $this->nextFor(Glyph::TEMPLATE_CODE_LINE);
                if ($this->char() !== " ") {
                    $this->templateError("Missing space after `" . Glyph::TEMPLATE_CODE_LINE . "`.");
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

