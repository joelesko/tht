<?php

namespace o;

// TODO: create a script that generates all errors (from testsite) to audit message copy & formatting

include('templates/ErrorPageHtml.php');
include('templates/ErrorPagePlainText.php');

include('components/ErrorPageTitle.php');
include('components/ErrorPageMessage.php');
include('components/ErrorPageStackTrace.php');
include('components/ErrorPageSourceLine.php');
include('components/ErrorPageFilePath.php');
include('components/ErrorPageHelpLink.php');

class ErrorPage {

    public $error = null;
    private $components = [];

    function __construct($error) {

        $this->error = $error;

        // Need to do this first, because other components depend on it.
        $this->error['message'] = ErrorPageMessage::cleanString(
            $this, $this->error['message']
        );

        $this->components = [
            'title'      => new ErrorPageTitle ($this),
            'message'    => new ErrorPageMessage ($this),
            'helpLink'   => new ErrorPageHelpLink ($this),
            'sourceLine' => new ErrorPageSourceLine ($this),
            'filePath'   => new ErrorPageFilePath ($this),
            'stackTrace' => new ErrorPageStackTrace ($this),
        ];
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

        $html->print($this->components);
    }

    function printToLog() {

        $out = ErrorPagePlainText::format($this->error);

        // Log to file.
        if (Tht::getConfig('logErrors')) {
            Tht::errorLog($out);
        }

        // Log to stderr
        file_put_contents('php://stderr', $out);

        Tht::module('Output')->u_send_error(500);
    }

    function printToConsole () {

        $plain = new ErrorPagePlainText ();

        // $divider = "\n\n" . str_repeat('`', 80) . "\n\n";

        // print $divider . $out;

        print "\n";

        $plain->print($this->components);
    }

    private function doShowWebError () {

        if ($this->error['origin'] == 'tht.config' || $this->error['origin'] == 'php.runtime.filePermissions') {
            // Some essential setup errors should always be shown
            return true;
        }
        else if (Security::isDev()) {
            return true;
        }

        // Show error if recently compiled
        $showErrorThresh = time() - Tht::getConfig('showErrorPageForMins') * 60;

        return Compiler::getAppCompileTime() > $showErrorThresh;
    }

    function cleanVars ($raw) {

        $fnCamel = function ($m) {
            $isUpper = false;
            if (preg_match('/^[A-Z]/', $m[1][0])) {
                $isUpper = true;
            }
            $token = v($m[1])->u_to_token_case();

            return $isUpper ? v($token)->u_to_case('upperFirst') : $token;
        };

        $v = $raw;
        $v = preg_replace('#[a-zA-Z0-9_\\\\]*\\\\#', '', $v);            // namespace
        $v = preg_replace_callback('/\bu_([a-zA-Z_]+)/', $fnCamel, $v);  // to camelCase
        $v = preg_replace('/(?<=\w)(::|->)/', '.', $v);                  // to dot .
        $v = preg_replace('/\bO(?=[A-Z][a-z])/', '', $v);                // internal classes e.g. "OString"
        $v = ltrim($v, '\\');

        return $v;
    }

    function cleanPath ($path, $keepPath = false) {

        if (preg_match('#\.jcon$#', $path)) {
            $path = Tht::stripAppRoot($path);
            return $path;
        }

        $path = Tht::getThtPathForPhp($path);

        $path = Tht::stripAppRoot($path);
        $path = preg_replace('#^code/?#', '', $path);

        if (!$keepPath) {
            $path = preg_replace('#\.tht$#', '', $path);
        }

        return $path;
    }

}

