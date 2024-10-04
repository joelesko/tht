<?php

namespace o;

// TODO: create a script that generates all errors (from testsite) to audit message copy & formatting

require_once('ErrorTextUtils.php');

require_once('ErrorPage/ErrorPageHtml.php');
require_once('ErrorPage/ErrorPageText.php');

class ErrorPage {

    public $error = null;

    function __construct($error) {

        $error['title'] = ucfirst($error['category']) . ' Error';

        // TODO: clean up trace handling from different sources
        $error['trace'] = $this->filterTrace($error['trace'] ?? []);
        if ($error['trace'] && $error['entryFrame']) {
            $error['trace'] []= $error['entryFrame'];
        }

        $error['source']['function'] ??= '';
        $error['source']['file'] ??= '';

        if ($error['source']['lang'] == 'php' && $error['source']['file']) {
            $error['source'] = Compiler::sourceLinePhpToTht(
                $error['source']['file'],
                $error['source']['lineNum'],
                $error['source']['function']
            );
        }

        if ($error['trace']) {
            $errorFrame = [
                'file' => $error['source']['file'],
                'line' => $error['source']['lineNum'],
            ];
            $error['trace'] = $this->insertErrorFrame($error['trace'], $errorFrame);
        }

        // Don't show trace if there is only one frame
        if (count($error['trace']) <= 1) {
            $error['trace'] = [];
        }

        // Clean file paths
        $error['source']['fileClean'] = ErrorTextUtils::cleanPath($error['source']['file'], true);
        foreach ($error['trace'] as $i => $frame) {
            $error['trace'][$i]['fileClean'] = ErrorTextUtils::cleanPath($frame['file'], true);
        }

        $error['message'] = ErrorTextUtils::cleanString(
            trim($error['message'])
        );

        $this->error = $error;
    }

    // External Interface
    //-----------------------------------------------------

    // Print error to different outputs: web, cli, or log
    public function print() {

        if (Tht::isMode('cli')) {
            $this->printToConsole();
        }
        else if ($this->doShowWebError()) {
            $this->printToWeb();
        }
        else {
            $this->printToLog();
        }

        Tht::exitScript(1);
    }

    function printToWeb() {

        $html = new ErrorPageHtml();

        $html->print($this->error);
    }

    function printToLog() {

        Tht::module('Log')->u_error(OMap::create([
            'file'    => $this->error['source']['fileClean'],
            'line'    => $this->error['source']['lineNum'],
            'message' => $this->error['message'],
        ]));

        $errorPage = new ErrorPageText ();
        $outText = $errorPage->format($this->error);
        file_put_contents('php://stderr', $outText);

        Tht::module('Output')->u_send_error(500);
    }

    function printToConsole() {

        $errorPage = new ErrorPageText ();

        $errorPage->print($this->error);
    }

    private function doShowWebError() {

        $showErrorForMins = Tht::getThtConfig('showErrorPageForMins');

        if ($showErrorForMins <= 0) {
            return false;
        }
        else if ($this->error['origin'] == 'tht.config' || $this->error['origin'] == 'php.runtime.filePermissions') {
            // Some essential setup errors should always be shown
            return true;
        }
        else if (Security::isDev()) {
            return true;
        }

        // Show error if recently compiled
        $showErrorThresh = time() - $showErrorForMins * 60;

        return Compiler::getAppCompileTime() > $showErrorThresh;
    }

    // Filter out internal stack frames.
    // Only include those that are useful to the developer.
    function filterTrace($trace, $includePhpFrames = false) {

        $filterTrace = [];

        $trace = $this->collapseModuleCalls($trace);

        foreach ($trace as $phpFrame) {

            if (! isset($phpFrame['file'])) { continue; }

            $phpFrame['class'] = isset($phpFrame['class']) ? $phpFrame['class'] : '';
            $phpFrame['function'] = ErrorTextUtils::cleanVars($phpFrame['function']);
            $fun = $phpFrame['function'];

            $phpFrame['file'] = Tht::normalizeWinPath($phpFrame['file']);

            if (isset($this->error['_fullTrace']) || Tht::getThtConfig('_coreDevMode')) {
                // Show everything
            }
            else if (preg_match('#(/tht\.php|/front\.php)#', $phpFrame['file'])) {
                // Skip internal entry point
                continue;
            }
            else if (preg_match('#/lib/(core|stdlib|vendor)#', $phpFrame['file'])) {
                // Skip internal library
                continue;
            }
            else if ($phpFrame['class']  == 'o\\Runtime') {
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

    // Calls to methods in user modules have an extra level of indirection, which
    // needs to be collapsed into a single frame to capure the call location, etc.
    function collapseModuleCalls($trace) {

        $filteredFrames = [];

        $callFrame = null;

        foreach ($trace as $f) {

            if ($f['function'] == 'call_user_func_array' && str_contains($f['file'], 'OModule')) {
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

    // Include error frame in the trace, if it is not the same
    // as the pre-existing last frame.
    function insertErrorFrame($frames, $errorFrame) {

        $doInsertFrame = true;
        if (count($frames) && isset($frames[0]['line'])) {

            $lastFrame = $frames[0];

            $lastFrameTht = Compiler::sourceLinePhpToTht($lastFrame['file'], $lastFrame['line'], $lastFrame['function']);

            $errorPath = ErrorTextUtils::cleanPath($errorFrame['file']);
            $lastPath = ErrorTextUtils::cleanPath($lastFrameTht['file']);

            if ($errorPath === $lastPath && $errorFrame['line'] === $lastFrameTht['lineNum']) {
                $doInsertFrame = false;
            }
        }

        if ($doInsertFrame) {
            array_unshift($frames, $errorFrame);
        }

        return $frames;
    }

}

