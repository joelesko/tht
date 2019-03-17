<?php

namespace o;

class SourceMap {
    private $map = [];
    private $lineNum = 1;

    function __construct ($sourceFile) {
        $this->map = [ 'file' => $sourceFile ];
    }

    function set ($targetSrcLine) {
        $this->map[$this->lineNum] = $targetSrcLine;
    }

    function next () {
        $this->lineNum += 1;
    }

    function out () {
        return '/* SOURCE=' . json_encode($this->map) . ' */';
    }
}
