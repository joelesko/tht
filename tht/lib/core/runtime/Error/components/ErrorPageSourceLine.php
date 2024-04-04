<?php

namespace o;

require_once('ErrorPageComponent.php');

class ErrorPageSourceLine extends ErrorPageComponent {

    private $MAX_SOURCE_LINE_LENGTH = 60;
    private $POINTER_CHAR = '↑';

    private $isLongLine = false;

    function get () {

        $source = $this->error['source'];

        if ($source['lineSource']) {
            $this->initIsLongLine($source['lineSource']);
            return $source['lineSource'];
        }

        if (!$source['lineNum']) {
            return '';
        }

        if (!isset($source['function'])) {
            $source['function'] = '';
        }

        if ($source['lang'] == 'php') {
            $source = Compiler::sourceLinePhpToTht($source['file'], $source['lineNum'], $source['function']);
        }

        $srcPath     = $source['file'];
        $srcLineNum1 = $source['lineNum'];
        $pos         = $source['linePos'];

        $srcLineNum0 = $srcLineNum1 - 1;  // convert to zero-index

        $lines = $this->readSourceFile($srcPath, $srcLineNum1);

        $line = (count($lines) > $srcLineNum0) ? $lines[$srcLineNum0] : '';

        // Adjust pos for lines that have mb chars
        if ($pos) {
            $asciiSegBeforePos = substr($line, 0, $pos);
            $pos = mb_strlen($asciiSegBeforePos);
        }

        // trim indent
        preg_match('/^(\s*)/', $line, $matches);
        $numSpaces = mb_strlen($matches[1]);
        $line = ltrim($line);
        if (!trim($line)) { return ''; }
        $prefix = $srcLineNum1 . ':  ';

        // Make sure pointer is visible in long lines
        $this->initIsLongLine($line);
        $maxLen = $this->MAX_SOURCE_LINE_LENGTH;
        if (mb_strlen($line) > $maxLen && $pos > $maxLen) {
            $trimNum = abs($maxLen - mb_strlen($line));
            $line = mb_substr($line, $trimNum);
            $pos -= $trimNum;
            $pos -= 2;
            $prefix .= '… ';
        }

        $fmtLine = $prefix . $line;

        // Pointer (↑)
        $pointer = "";
        if ($pos !== null && $pos >= $numSpaces) {
            $pointerPos = $pos - ($numSpaces + 1) + strlen($prefix);
            $pointerPos = max($pointerPos, 0);
            $fmtLine .= "\n";
            $pointer = str_repeat(' ', $pointerPos) . $this->POINTER_CHAR;
        }

        return $fmtLine . $pointer;
    }

    function initIsLongLine($line) {
        if (mb_strlen($line) > $this->MAX_SOURCE_LINE_LENGTH) {
            $this->isLongLine = true;
        }
    }

    // TODO: This is probably redundant with something in the Compiler class.
    function readSourceFile($srcPath) {

        if (Tht::module('File')->u_is_relative($srcPath)) {
            $srcPath = Tht::path('app', $srcPath);
        }

        $source = file_get_contents($srcPath);
        $lines = preg_split('/\n/', $source);

        return $lines;
    }

    function colorCodeJs() {

        $jsPath = Tht::getCoreVendorPath('frontend/colorCode.js');
        $colorCodeJs = file_get_contents($jsPath);

        $colorCodeJs .= "colorCode('dark', '.tht-color-code');";

        return Tht::module('Output')->wrapJs($colorCodeJs);
    }

    function getHtml() {

        $out = $this->get();

        $out = Security::escapeHtml($out);

        if (!$out) { return ''; }

        // Add style to pointer.
        $colorPointer = $this->wrapHtml('span', 'tht-error-line-pointer', $this->POINTER_CHAR);
        $out = preg_replace("/" . $this->POINTER_CHAR . "$/", $colorPointer , $out);

        // Syntax Highlighting
        $out = preg_replace("/^(\d+:\s+)(.*)/", '$1<span class="tht-color-code theme-dark">$2</span>', $out);

        // Use smaller font for long lines.
        if ($this->isLongLine) {
            $out = $this->wrapHtml('div', 'tht-src-small', $out);
        }

        $out .= $this->colorCodeJs();

        return $out;
    }
}