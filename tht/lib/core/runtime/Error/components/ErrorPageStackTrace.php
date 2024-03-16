<?php

namespace o;

require_once('ErrorPageComponent.php');

class ErrorPageStackTrace extends ErrorPageComponent {

    private $MAX_ARG_LENGTH = 10;

    function get() {
        if ($this->error['trace']) {
            return $this->formatTrace();
        }
        return '';
    }

    // Include error frame in the trace, if it is not the same
    // as the pre-existing last frame.
    function insertErrorFrame($frames) {

        $errorFrame = [
            'file' => $this->error['source']['file'],
            'line' => $this->error['source']['lineNum'],
        ];

        $lastFrame = $frames[0];

        $errorPath = $this->errorPage->cleanPath($errorFrame['file']);
        $lastPath = $this->errorPage->cleanPath($lastFrame['file']);

        if ($errorFrame['line'] != $lastFrame['line'] || $errorPath != $lastPath) {
            array_unshift($frames, $errorFrame);
        }

        return $frames;
    }

    function formatTrace () {

        $frames = $this->filterTrace($this->error['trace']);

        if (!count($frames)) {
            return "";
        }

        if ($this->error['entryFrame']) {
            $frames []= $this->error['entryFrame'];
        }

        $frames = $this->insertErrorFrame($frames);

        $topBullet = '↓';
        $midBullet = '↓';
        $endBullet = 'X';
        $out = '';

        foreach (array_reverse($frames) as $num => $phpFrame) {

            $filePath = $this->formatFilePath($phpFrame);
            $funName = $this->formatFunction($phpFrame);
            $lineNum = $this->getLineNum($phpFrame);
            $args = $this->formatArgs($phpFrame);

            $bullet = $midBullet;
            if ($num == 0) {
                $bullet = $topBullet;
            }
            else if ($num == count($frames) - 1) {
                $bullet = $endBullet;
            }

            $bullet = '<div class="tht-error-trace-bullet">' . $bullet . '</div>';

            // Combine everything
            $frameOut = $bullet . $this->out('&nbsp;&nbsp;', '  ');
            if ($filePath) { $frameOut .= $filePath; }
            if ($lineNum) { $frameOut .= ' · ' . $lineNum; }
            if ($funName) { $frameOut .= " · $funName" . $args; }

            $out .= $this->out('<div class="error-trace-line">' . $frameOut . "</div>", $frameOut) . "\n";
        }

        $firstLine = $this->out("<div>Trace:</div>\n", "Trace:\n");

        $out =  $firstLine . $out;

        return trim($out);
    }

    function out($htmlOut, $plainTextOut) {
        return $this->isHtml ? $htmlOut : $plainTextOut;
    }

    function getLineNum($phpFrame) {

        if (!isset($phpFrame['line'])) { return ''; }
        if (!isset($phpFrame['function'])) { $phpFrame['function'] = ''; }

        if (!$phpFrame['file'] || !$phpFrame['line']) {
            return '';
        }

        $src = Compiler::sourceLinePhpToTht($phpFrame['file'], $phpFrame['line'], $phpFrame['function']);

        return $src['lineNum'];
    }

    function formatFunction($phpFrame) {

        $class = isset($phpFrame['class']) ? $phpFrame['class'] : '';
        $fun = isset($phpFrame['function']) ? $phpFrame['function'] : '';

        // e.g. Bare.print -> print
        if (u_Bare::isa($fun)) {
            $class = '';
        }

        // Prepend classname.  e.g. MyClass.doSomething
        if ($class) {
            $fun = $this->errorPage->cleanVars($class) . '.' . $fun;
        }

        return $fun;
    }

    function formatFilePath($phpFrame) {

        if (!isset($phpFrame['file'])) {
            return '';
        }
        $filePath = $this->errorPage->cleanPath($phpFrame['file']);

        if ($this->isHtml) {
            $filePath = preg_replace('#(^.*/)#', '<span class="tht-error-file-dir">$1</span>', $filePath);
        }

        return $filePath;
    }

    function formatArgs($phpFrame) {

        if (!isset($phpFrame['args']) || !count($phpFrame['args'])) {
            return '';
        }

        $numArgs = count($phpFrame['args']);

        // Convert argument data to strings
        $args = [];
        foreach ($phpFrame['args'] as $a) {
            $argLabel = is_null($a) ? 'null' : v($a);
            $args []= Tht::module('Json')->formatOneLineSummary($argLabel, 70);
        }

        // Return indented arguments
        $out = $this->out("<div class=\"tht-error-args-block\">", '') . "\n";
        foreach ($args as $i => $a) {
            if ($i > 5) {
                $numMore = $numArgs - $i;
                $out .= $this->out("<span class='tht-error-args-line'>($numMore more)</span>", "($numMore more)");
                $out .= "\n";
                break;
            }
            $a = str_replace('\"', '"', $a);  // strip extra escaping for json strings
            $num = $i + 1;
            $out .= $this->out("<div class='tht-error-args-line'>$num. " . v($a)->u_to_encoding('html') . "</div>", "    $num. $a");
            $out .= "\n";
        }
        $out .= $this->out("</div>", "");

        return rtrim($out);
    }

    // Calls to methods in user modules have an extra level of indirection, which
    // needs to be collapsed into a single frame to capure the call location, etc.
    function collapseModuleCalls($trace) {

        $filteredFrames = [];

        $callFrame = null;

        foreach ($trace as $f) {

            if ($f['function'] == 'call_user_func_array' && strpos($f['file'], 'OModule') !== false) {
                $callFrame = $f;
            }
            else if ($callFrame && $f['function'] == '__call') {

                $fullFn = $callFrame['args'][0];

                $modNamespace = preg_replace('/\\\\[a-zA-Z0-9_]+$/', '', $fullFn);
                $modName = ModuleManager::namespaceToBaseName($modNamespace);

                $frame = [
                    'class' => $modName,
                    'file' => $f['file'],
                    'line' => $f['line'],
                    'function' => $f['args'][0],
                    'args' => $f['args'][1],
                ];
                $filteredFrames []= $frame;
                $callFrame = null;
            }
            else {
                $filteredFrames []= $f;
            }
        }

        return $filteredFrames;
    }

    // Filter out internal stack frames.
    // Only include those that are useful to the developer.
    function filterTrace($trace, $includePhpFrames = false) {

        $filterTrace = [];

        $trace = $this->collapseModuleCalls($trace);

        foreach ($trace as $phpFrame) {

            if (! isset($phpFrame['file'])) { continue; }

            $phpFrame['class'] = isset($phpFrame['class']) ? $phpFrame['class'] : '';
            $phpFrame['function'] = $this->errorPage->cleanVars($phpFrame['function']);
            $fun = $phpFrame['function'];

            $phpFrame['file'] = Tht::normalizeWinPath($phpFrame['file']);


            if (isset($this->error['_fullTrace']) || Tht::getConfig('_coreDevMode')) {
                // Show everything
            }
            else if (preg_match('#(/tht\.php|/front\.php)#', $phpFrame['file'])) {
                // Skip internal entry point
                continue;
            }
            else if (preg_match('#/lib/(core|classes|modules|vendor)#', $phpFrame['file'])) {
                // Skip internal library
                continue;
            }
            else if (preg_match('#__#', $fun)) {
                // Skip internal function (e.g. Bag.__set)
                continue;
            }
            else if ($fun == 'handlePhpRuntimeError') {
                // Skip error call
                continue;
            }

            $filterTrace []= $phpFrame;
        }

        return $filterTrace;
    }
}