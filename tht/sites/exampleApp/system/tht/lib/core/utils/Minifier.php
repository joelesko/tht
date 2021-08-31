<?php

namespace o;

class Minifier extends StringReader {

    private $out = '';
    private $buffer = '';
    private $inString = false;
    private $stringDelim = '';
    private $crunchRegex = '';
    private $keepNewlines = false;

    function minifyBuffer() {

        $b = $this->buffer;
        $b = preg_replace("/ +/", ' ', $b);
        $b = preg_replace("/\\n /", "\n", $b);
        $b = preg_replace($this->crunchRegex, '$1', $b);

        $this->out .= $b;
        $this->buffer = '';
    }

    function minify($perfId, $crunchRegex, $keepNewlines = false) {

        $this->crunchRegex = $crunchRegex;
        $this->keepNewlines = $keepNewlines;

        Tht::module('Perf')->u_start('minify.' . $perfId, $this->fullText);

        // TODO: This is a little ugly when switching between string and code
        while (true) {

            $c = $this->char();

            if ($c === null) {
                $this->minifyBuffer();
                break;
            }
            else if ($keepNewlines && $c === "\n") {
                if ($this->inString) {
                    $this->out .= $c;
                }
                else {
                    $this->buffer .= $c;
                }
            }
            else if ($c === "'" || $c === '"' || $c === '`') {
                // beginning or end of string
                if (!$this->inString) {
                    $this->buffer .= $c;
                    $this->minifyBuffer();
                    $this->inString = true;
                    $this->stringDelim = $c;
                }
                else if ($c === $this->stringDelim){
                    $this->out .= $c;
                    $this->inString = false;
                }
                else {
                    $this->out .= $c;
                }
            }
            else if ($this->inString) {
                if ($c === "\n") {
                    $this->out .= "\\n";
                }
                else if ($c === '\\') {
                    // Retain character after backslash
                    $this->out .= '\\';
                    $this->next();
                    $c = $this->char();
                    $this->out .= $c;
                }
                else {
                    $this->out .= $c;
                }
            }
            else if ($c === '/' && $this->nextChar() === '/') {
                // line comment
                $this->slurpLine();
                continue;
            }
            else if ($c === '/' && $this->nextChar() === '*') {
                // block comment
                $this->slurpUntil('*/');
            }
            else {
                $this->buffer .= $c;
            }

            $this->next();
        }

        Tht::module('Perf')->u_stop();

        return $keepNewlines ? $this->out : trim($this->out);
    }
}

