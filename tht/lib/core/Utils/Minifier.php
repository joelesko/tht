<?php

namespace o;

class Minifier extends StringReader {

    private $out = '';
    private $buffer = '';
    private $inString = false;
    private $stringDelim = '';
    private $crunchRegex = '';

    function minifyBuffer() {
        $b = $this->buffer;
        $b = preg_replace("/\s+/", ' ', $b);
        $b = preg_replace($this->crunchRegex, '$1', $b);

        $this->out .= $b;
        $this->buffer = '';
    }

    function minify($crunchRegex) {

        $this->crunchRegex = $crunchRegex;

        Tht::module('Perf')->u_start('js.minify', $this->fullText);

        while (true) {
            $c = $this->char();
            if ($c === null) {
                $this->minifyBuffer();
                break;
            }
            else if (!$this->inString && $c === '/' && $this->nextChar() === '/') {
                // line comment
                $this->slurpLine();
            }
            else if (!$this->inString && $c === '/' && $this->nextChar() === '*') {
                // block comment
                $this->slurpUntil('*/');
            }
            else if ($c === "'" || $c === '"' || $c === '`') {
                // beginning or end of string
                if (!$this->inString) {
                    $this->buffer .= $c;
                    $this->minifyBuffer();
                    $this->inString = true;
                    $this->stringDelim = $c;
                } else if ($c === $this->stringDelim){
                    $this->out .= $c;
                    $this->inString = false;
                    $this->stringDelim = '';
                } else {
                    $this->out .= $c;
                }
            }
            else {
                if ($this->inString) {
                    if ($c === "\n") { $c = "\\n"; }
                    $this->out .= $c;
                }
                else {
                    $this->buffer .= $c;
                }
            }
            $this->next();
        }

        Tht::module('Perf')->u_stop();

        return trim($this->out);
    }
}

