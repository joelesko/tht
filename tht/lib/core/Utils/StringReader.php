<?php

namespace o;


StringReader::initChars();

class StringReader {

    public $fullText = '';
    public $len = 0;
    public $i = 0;

    protected $lineNum = 1;
    protected $colNum = 0;
    protected $tokenPos = [1,1];
    protected $indent = 0;

    public $startOfLine = true;

    public $char1 = '';
    public $char2 = '';

    static public $SisDigit = [];
    static public $SisAlpha = [];
    static public $SisBinaryDigit = [];
    static public $SisHexDigit = [];

    public $isDigit = [];
    public $isAlpha = [];

    // OVERRIDE
    function onNewline() {}

    function __construct($fullText) {

        $fullText = rtrim($fullText) . "\n";

        $fullText = str_replace("\t", "    ", $fullText);

        $this->fullText = $fullText;
        $this->len = strlen($fullText);

        $this->char1 = $this->fullText[0];
        $this->char2 = $this->fullText[1];

        $this->isDigit       = static::$SisDigit;
        $this->isAlpha       = static::$SisAlpha;
        $this->isHexDigit    = static::$SisHexDigit;
        $this->isBinaryDigit = static::$SisBinaryDigit;
    }

    // precompile list of character ranges
    static function initChars() {
        foreach (range(0,9) as $n) {
            StringReader::$SisDigit['' . $n] = 1;
        }
        foreach (range(0,1) as $n) {
            StringReader::$SisBinaryDigit['' . $n] = 1;
        }
        $hexDigits = str_split('0123456789abcdef');
        foreach ($hexDigits as $n) {
            StringReader::$SisHexDigit['' . $n] = 1;
        }
        foreach (range('a', 'z') as $n) {
            StringReader::$SisAlpha[$n] = 1;
        }
        foreach (range('A', 'Z') as $n) {
            StringReader::$SisAlpha[$n] = 1;
        }
    }

    function error($msg) {
        Tht::error($msg . '  Line: ' . $this->lineNum . '  Pos: ' . $this->colNum);
    }

    function updateTokenPos () {
        $this->tokenPos = [$this->lineNum, $this->colNum];
    }

    function getTokenPos() {
        return $this->tokenPos;
    }

    function char ($size=1, $offset=0) {
        $i = $this->i + $offset;
        if ($i < 0 || $i + ($size-1) >= $this->len) {
            return null;
        }
        if ($size === 1) {
            return $this->fullText[$i];
        }
        return substr($this->fullText, $i, $size);
    }

    function prevChar ($numChars=1) {
        return $this->char($numChars, -1 * $numChars);
    }

    function nextChar ($numChars=1) {
        return $this->char($numChars, 1);
    }

    function nextFor ($str) {
        $this->next(strlen($str));
    }

    function indent() {
        return $this->indent;
    }


    // Advance N characters
    // WARNING: Super hot path!  Each change should be measured.
    function next ($num=1) {

        if ($this->i >= $this->len) { return; }

        $c = $this->fullText[$this->i];

        if ($this->startOfLine) {
            if ($c === ' ') {
                $this->indent += 1;
            } else {
                $this->startOfLine = false;
            }    
        }
        
        if ($c === "\n") {

            $this->onNewline();

            $this->startOfLine = true;
            $this->lineNum += 1;
            $this->indent = 0;
            $this->colNum = 0;
        }

        $this->i += $num;
        $this->colNum += $num;

        $this->char1 = $this->i < $this->len ? $this->fullText[$this->i] : null;
    }

    function slurpLine () {
        $line = '';
        $isIndent = true;
        $numIndent = 0;
        while (true) {
            $c = $this->char1;
            
            if ($c === ' ' && $isIndent) {
                $numIndent += 1;
                $this->next();
            }
            else if ($c === "\n") {
                $this->next();
                break;
            }
            else if ($c === null) {
                return null;
            } else {
                $line .= $this->fullText[$this->i];
                $isIndent = false;
                $this->next();
            }
        }
        $full = str_repeat(' ', $numIndent) . rtrim($line);

        return [ 'indent' => $numIndent, 'text' => rtrim($line), 'fullText' => $full ];
    }

    function getLine () {
        $l = $this->slurpLine();
        return $l['fullText'];
    }

    function atEndOfFile () {
        return $this->char1 === null;
    }

    function atStartOfLine () {
        return $this->startOfLine;
    }

    function atNewLine () {
        return $this->char1 === "\n";
    }

    // TODO: rename - isNow()?
    function isGlyph ($str) {
        return substr($this->fullText, $this->i, strlen($str)) === $str;
    }

    function isDigit ($c) {
        return isset($this->isDigit[$c]);
    }

    function isAlpha ($c) {
       return isset($this->isAlpha[$c]);
    }

    function isWhitespace($c) {
        return $c === " " || $c === "\n";
    }

    function slurpNumber() {

        $str = $this->slurpDigits();

        if ($this->char1 === '.') {
            $this->next();
            $str .= '.';
            $str .= $this->slurpDigits($this->isDigit);
        }
        else if ($this->char1 === 'b') {
            $this->next();
            $str .= 'b';
            $str .= $this->slurpDigits($this->isBinaryDigit);
        }
        else if ($this->char1 === 'x') {
            $this->next();
            $str .= 'x';
            $str .= $this->slurpDigits($this->isHexDigit);
        }

        return $str;
    }

    function slurpDigits($allowedDigits=null) {
        if (!$allowedDigits) {
            $allowedDigits = $this->isDigit;
        }
        $str = '';
        while (true) {
            $c = $this->char1;
            if ($c === '_') { $this->next();  continue; }  // _ separator
            if (!isset($allowedDigits[$c])) {
                break;
            }
            $str .= $c;
            $this->next();
        }
        return $str;
    }

    function slurpWord() {
        $str = '';
        while (true) {
            $c = $this->fullText[$this->i];
            if (isset($this->isAlpha[$c]) || isset($this->isDigit[$c]) || $c === '_') {
                $str .= $c;
            }
            else {
                break;
            }
            $this->next();
        }
        return $str;
    }

    function slurpChar($getChar) {
        $num = 0;
        while (true) {
            $c = $this->char1;
            if ($c !== $getChar) {
                break;
            }
            $num += 1;
            $this->next();
        }
        return $num;
    }

    function slurpUntil($endChar) {
        $s = '';
        while (true) {
            if ($this->isGlyph($endChar)) {
                $this->nextFor($endChar);
                break;
            }
            $c = $this->char1;
            $s .= $c;
            $this->next();
        }
        return $s;
    }
}


// Read a template from a dynamic string.  (no support for embedded THT)
class TemplateStringReader extends StringReader {

    private $templateTransfomer;

    function __construct ($type, $fullText) {
        $templateClass = "o\\" . ucfirst($type) . 'TemplateTransformer';
        $this->templateTransfomer = new $templateClass ($this);

        parent::__construct($fullText);
    }

    function parse() {
        $str = '';
        while (true) {

            if ($this->atEndOfFile()) {
                break;
            }

            $transformed = $this->templateTransfomer->transformNext();
            if ($transformed !== false) {
                $str .= $transformed;
            }
            else {
                // plaintext
                $c = $this->char();
                $str .= $c;
                $this->next();
            }
        }

        $str = $this->templateTransfomer->onEndString($str);
        $this->templateTransfomer->onEndFile();

        return new HtmlLockString ($str);
    }
}


