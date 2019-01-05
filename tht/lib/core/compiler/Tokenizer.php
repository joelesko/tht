<?php

namespace o;

abstract class TemplateMode {
    const NONE        = 0;
    const PRE         = 1;
    const BODY        = 2;
    const EXPR        = 3;
    const CODE_LINE   = 4;
}

class TokenStream {
    var $tokens = [];

    function add ($t) {
        $this->tokens []= implode(TOKEN_SEP, $t);
    }
    function done () {
        // add a noop token to prevent error if there are no tokens (e.g. all comments)
        if (count($this->tokens) <= 1) {
            $this->add([TokenType::WORD, '1,1', 0, 'false']);
        }
        $this->tokens = array_reverse($this->tokens);

        return $this;
    }
    function count () {
        return count($this->tokens);
    }
    function next () {
        // note: array_pop is much faster than array_shift
        $t = array_pop($this->tokens);
        return explode(TOKEN_SEP, $t, 4);
    }
}

class Tokenizer extends StringReader {

    private $ADJ_ATOMS = ['NUMBER', 'STRING', 'LSTRING', 'RSTRING', 'WORD'];

    private $ALLOW_NEXT_ADJ_ATOMS = [
        'in', 
        'if',
        'abstract',
        'class',
        'trait',
        'interface',
        'final',
        'public',
        'private',
        'protected',
        'static',
        'extends',
    ];

    private $ALLOW_PREV_ADJ_ATOMS = [
        'let', 
        'function', 
        'template', 
        'in', 
        'return' , 
        'new', 
        'class', 
        'for', 
        'if', 
        'F', 
        'T', 
        'R',
        'class',
        'trait',
        'interface',
        'final',
        'public',
        'private',
        'protected',
        'static',
        'extends',
    ];
    

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
    private $templateType = '';
    private $templateIndent = 0;
    private $templateLineNum = 0;

    function error ($msg, $tokenPos=null) {
        if ($tokenPos !== false) {  $this->updateTokenPos();  }
        if (is_string($tokenPos)) {
            $tokenPos = explode(',', $tokenPos);
        }
        $pos = $tokenPos ?: $this->tokenPos;
        ErrorHandler::handleCompilerError($msg, ['', implode(',', $pos)], Source::getCurrentFile());
    }

    function templateEndError () {
        $this->error("Reached end of file without a closing template brace '}'.");
    }

    // Main: convert string into tokens
    function tokenize () {

        if (!strlen(trim($this->fullText))) {
            return false;
        }

        $this->tokenStream = new TokenStream ();

        $this->processText();

        return $this->tokenStream->done();
    }

    function makeToken ($type, $value, $forceSpaceAfter = false) {

        $spaceMask = 0;

        $nextChar = $this->char();

        if ($forceSpaceAfter) {
            $nextChar = ' ';
        }
        
        if ($this->prevNewline)    { $spaceMask += 2; }
        else if ($this->prevSpace) { $spaceMask += 1; }

        if ($nextChar === ' ' || $nextChar === "\t") { $spaceMask += 4; }
        else if ($nextChar === "\n")                 { $spaceMask += 8; }

        $pos = $this->tokenPos[0] . ',' . $this->tokenPos[1];
        $token = [$type, $pos, $spaceMask, $value];
        $this->prevToken = $token;

        $this->tokenStream->add($token);

        $this->prevSpace = false;
        $this->prevNewline = false;
    }

    function insertToken ($type, $value) {
        $this->updateTokenPos();
        $this->makeToken($type, $value);
    }

    function onNewline() {
        if ($this->colNum > MAX_LINE_LENGTH && $this->templateMode !== TemplateMode::BODY
            && !$this->inMultiLineString && !$this->inComment) {
            $this->error('Line has ' . $this->colNum . ' characters.  Maximum is ' . MAX_LINE_LENGTH . '.');
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

                if (isset($this->isAlpha[$c])) {
                    $this->handleWord($c);
                }
                else if (isset($this->isDigit[$c])) {
                    $this->handleNumber($c);
                }
                else if ($c === Glyph::QUOTE || $c === Glyph::BACKTICK) {
                    $this->handleString($c);
                }
                else if ($this->isGlyph(Glyph::LINE_COMMENT)) {
                    $this->handleLineComment($c);
                }
                else if ($this->isGlyph(Glyph::BLOCK_COMMENT_START)) {
                    $this->handleBlockComment($c);
                }
                else {
                    $this->handleGlyph($c);
                }
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

    function escape ($c, $stringType) {

        if ($c === "\\") {

            $next = $this->nextChar();
            $this->next();

            // preserve backslash for special characters
            if (strpos("rnt\\", $next) !== false || $stringType === TokenType::RSTRING) {
                return "\\" . $next;
            }
            return $next;

        } else if ($c === "`" && $stringType !== TokenType::TSTRING) {
            // convert backticks ` to single quotes
            return "'";
        }

        return $c;
    }

    // Make sure atoms are not adjacent (e.g. 'foo bar' is not ok.  'function myFun' is ok.)
    function checkAdjacentAtom ($type, $current) {
        if ($this->prevToken && in_array($this->prevToken[TOKEN_TYPE], $this->ADJ_ATOMS)) {
            $prev = $this->prevToken[TOKEN_VALUE];
            if (!in_array($prev, $this->ALLOW_PREV_ADJ_ATOMS) &&
                !in_array($current, $this->ALLOW_NEXT_ADJ_ATOMS)) {

                    $prevPos = $this->prevToken[TOKEN_POS];
                    if ($prev === 'var') {
                        $this->error("Unknown keyword `var`. Try: `let`", $prevPos);
                    }
                    if ($current === 'as') {
                        $this->error("Unknown keyword `as`. Try: `for (item in list) { ... }`", false);
                    }

                    $this->error("Unexpected $type.", false);
            }
        }
    }

    function handleWhitespace ($c) {

        // Template. End of single-line code block '::'
        if ($this->templateMode === TemplateMode::CODE_LINE && $c === "\n") {
            $prev = $this->prevChar();
            if ($prev !== '{' && $prev !== ';' && $prev !== '}') {
                $this->error("Inline code `" . Glyph::TEMPLATE_CODE_LINE ."` must end in brace `{}` or semicolon `;`");
            }
            $this->templateMode = TemplateMode::BODY;
        }

        if ($this->templateMode === TemplateMode::EXPR && $c === "\n") {
            $this->updateTokenPos();
            $this->error("Unexpected newline inside of template expression `{{ ... }}`.");
        }

        if ($c === "\n") {  
            $this->prevNewline = true;
        }
        else if ($c === ' ' || $c === "\t") {  // $c === "\n" 
            $this->prevSpace = true;
        }

        $this->next();
    }

    // e.g. fooBar
    function handleWord ($c) {

        $this->updateTokenPos();

        // modifiers
        $nextChar = $this->nextChar();
        if ($nextChar === Glyph::QUOTE) {
            if (strpos(Glyph::STRING_MODS, $c) === false) {
                if ($c >= 'a' && $c <= 'z') {
                    $up = strtoupper($c);
                    if (strpos(Glyph::STRING_MODS, $up) !== false) {
                        $this->error("String modifier `$c` should be uppercase.");
                    }
                }
                $this->error("Unknown string modifier: `$c`");
            }
            $this->stringMod = $c;
            $this->next();
            return;
        }
        else if ($nextChar === '[') {
            if ($c === Glyph::LIST_MOD) {
                $this->next();
                $this->next();
                $list = $this->slurpUntil(']');
                $words = preg_split('/\s+/', trim($list));
                $this->makeToken(TokenType::GLYPH, '[');
                $i = 0;
                foreach ($words as $w) {
                    $this->makeToken(TokenType::STRING, $w);
                    if ($i < count($words)) { $this->makeToken(TokenType::GLYPH, ',', true); }
                    $i += 1;
                }
                $this->makeToken(TokenType::GLYPH, ']');
                return;
            }
        }

        // complete word
        $str = $this->slurpWord();

        if ($str === 'foreach') {
            $this->error("Unknown keyword `foreach`. Try: `for`", false);
        }

        // Track function name in case we are going into a template.
        if ($this->templateName === '?') {
            $this->templateName = $str;
        }
        else if ($str === 'template' || $str === 'T') {
            $this->templateMode = TemplateMode::PRE;
            $this->templateName = '?';
            $this->templateIndent = $this->indent;
            $this->templateLineNum = $this->lineNum;
        }

        $this->checkAdjacentAtom('word', $str);
        $this->makeToken(TokenType::WORD, $str);
    }

    // e.g. 1234
    function handleNumber ($c) {

        $this->updateTokenPos();

        $str = $this->slurpNumber();

        $c = $this->char();
        if ($this->isAlpha($c)) {
            $str .= $c;
            $this->error("Bad number `$str`. Numbers can not contain letters.");
        }

        // Convert the string value to a number
        if (is_numeric($str)) {
            $this->checkAdjacentAtom('number', $str);
            $this->makeToken(TokenType::NUMBER, (float)$str);
        } else {
            $this->error("Bad number `$str`. It can not be converted to a number.");
        }
    }

    // e.g. 'hello' or `multiline\nstring`
    function handleString ($c) {

        $str = '';
        $closeQuote = $c;
        $this->updateTokenPos();
        $startPos = $this->getTokenPos();

        $this->inMultiLineString = false;
        if ($c === "`") {
            $this->inMultiLineString = true;
            if ($this->nextChar() !== "\n") {
                $this->error("Backtick `$c` must be followed by a newline.\n\n"
                    . "Indentation will be trimmed to the left-most character.");
            }
        }

        $type = TokenType::STRING;
        if ($this->stringMod === Glyph::LOCK_MOD) {
            $type = TokenType::LSTRING;
        }
        else if ($this->stringMod === Glyph::REGEX_MOD) {
            $type = TokenType::RSTRING;
        }

        while (true) {
            $this->next();
            $c = $this->char();

            if ($c === "\n" && !$this->inMultiLineString) {
                $this->updateTokenPos();
                $this->error("Unexpected newline. Try: backtick ($c) for a multi-line string.");
            }

            if ($this->atEndOfFile()) {
                $this->error("Unclosed string. Maybe you missed a closing quote ($c).", $startPos);
            }

            if ($c === $closeQuote) {
                if ($this->inMultiLineString && !$this->atStartOfLine()) {
                        $this->error("Closing backtick ($c) must belong on a separate line.");
                } else {
                    break;
                }
            }

            $str .= $this->escape($c, $type);
        }

        if ($this->inMultiLineString) {
            $str = v($str)->u_trim_indent();
        }

        $this->stringMod = '';

        $this->checkAdjacentAtom('string', $str);
        $this->makeToken($type, $str);

        $this->next();

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
        }
        $this->inComment = false;
    }

    function handleLineComment ($c) {

        $this->updateTokenPos();

        $this->inComment = true;
        $this->slurpLine();
        $this->inComment = false;
    }

    function handleGlyph ($c) {

		$this->updateTokenPos();

        if ($this->prevToken && $this->prevToken[TOKEN_VALUE] === '{') {
            if (strpos('<#.', $c) !== false) {
                $this->error("Unexpected `$c`.  Did you mean to use a `template` instead?");
            }
            if ($this->isGlyph('{{') || $this->isGlyph('::')) {
                $this->error("Unexpected `$c`.  Did you mean to use a `template` instead?");
            }
        }

          if ($this->isGlyph('{') && $this->templateMode == TemplateMode::PRE) {

            // start of template body
            $foundType = preg_match('/(' . ParserData::$TEMPLATE_TYPES . ')$/i', $this->templateName, $m);
            if (!$foundType) {
                $rec = lcfirst($this->templateName) . "Html";
                $this->error("Missing type in template function name.  e.g. `template $rec`");
            }
            $this->templateMode = TemplateMode::BODY;
            $this->templateType = strtolower($m[1]);
            $handlerClass = "o\\" . ucfirst($this->templateType) . 'TemplateTransformer';
            $this->currentTemplateTransformer = new $handlerClass ($this);

            $this->nextFor('{');
            $this->makeToken(TokenType::GLYPH, '{');
            if ($this->char() !== "\n") {
                $this->error("Opening template brace `{` must be followed by a newline.");
            }
            return;
        }
        if ($this->templateMode === TemplateMode::EXPR && $this->isGlyph(Glyph::TEMPLATE_EXPR_END)) {

            // end of template var '}}'
            $this->makeToken(TokenType::GLYPH, Glyph::TEMPLATE_EXPR_END);
            $this->templateMode = TemplateMode::BODY;
            $this->nextFor(Glyph::TEMPLATE_EXPR_END);
            return;
        }

        $str = '';
        if (strpos(Glyph::MULTI_GLYPH_PREFIX, $c) !== false) {

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
        } else {

            // single glyph
            $str = $c;
            $this->next();
        }

        $this->makeToken(TokenType::GLYPH, $str);
    }

    function addTemplateString ($str) {
        $str = $this->currentTemplateTransformer->onEndString($str);
        $this->insertToken(TokenType::TSTRING, $str);
    }

    // Handle template function body as a special string, dipping back out into THT
    // mode when it encounters expressions and code lines.
    function handleTemplate () {

        $str = '';
        while (true) {

            $c = $this->char();

            if ($this->atEndOfFile()) {

                $this->templateEndError();

            } else if ($this->isGlyph(Glyph::TEMPLATE_EXPR_START)) {

                // THT expression {{ ... }}
                $this->addTemplateString($str);
                $this->nextFor(Glyph::TEMPLATE_EXPR_START);
                $this->insertToken(TokenType::GLYPH, Glyph::TEMPLATE_EXPR_START);
                return TemplateMode::EXPR;

            } else if ($this->atStartOfLine() && !$this->isWhitespace($c) && $this->indent <= $this->templateIndent) {

                if (!$this->isGlyph('}')) {
                    $this->error("Line should be indented inside template `" . $this->templateName . "` starting at Line " .$this->templateLineNum . ".");
                }
                // end of template body '}'
                $this->currentTemplateTransformer->onEndTemplateBody();
                $this->addTemplateString($str);
                $this->nextFor('}');
                $this->makeToken(TokenType::GLYPH, ';');  // satisfy "semicolon at end of function" rule

                $this->prevSpace = true;  // satisfy format checker (space before '}')
                $this->insertToken(TokenType::GLYPH, '}');

                $this->templateName = '';

                if ($this->char() !== "\n") {
                    $this->error("Missing newline after closing brace `}`.");
                }

                return TemplateMode::NONE;

            } else if ($this->atStartOfLine() && $this->isGlyph(Glyph::TEMPLATE_CODE_LINE)) {

                // One line of THT code e.g. ':: let a = 1;'
                $this->addTemplateString($str);
                $this->nextFor(Glyph::TEMPLATE_CODE_LINE);
                if ($this->char() !== " ") {
                    $this->error("Missing space after `" . Glyph::TEMPLATE_CODE_LINE . "`.");
                }
                return TemplateMode::CODE_LINE;

            } else {

                // Do transformations based on type (e.g. handle HTML tags)
                $transformed = $this->currentTemplateTransformer->transformNext();
                if ($transformed !== false) {
                    $str .= $transformed;
                }
                else {
                    // plaintext
                    $str .= $this->escape($c, TokenType::TSTRING);
                    $this->next();
                }
            }
        }
    }
}

