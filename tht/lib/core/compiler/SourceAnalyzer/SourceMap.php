<?php

namespace o;

class SourceMap {
    private $map = [];
    private $lineNum = 1;

    function __construct($sourceFile) {
        $relPath = Tht::getRelativePath('app', $sourceFile);
        $this->map = [ 'file' => $relPath ];
    }

    function set($targetSrcLine) {
        $this->map[$this->lineNum] = $targetSrcLine;
    }

    function next() {
        $this->lineNum += 1;
    }

    function out() {
        $out = "/* SOURCE=" . json_encode($this->map) . " */\n";
        return $out;
    }
}
