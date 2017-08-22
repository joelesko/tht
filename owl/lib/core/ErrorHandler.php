<?php

namespace o;

class OwlException extends \Exception {
    function u_message () {
        return $this->getMessage();
    }
}

class StartupException extends \Exception {
}

// Need a bare function as an entry, because it seems the
// static method requires too much memory to call?
function handleShutdown() {
    Owl::handleShutdown();
    ErrorHandler::handleShutdown();
}

class ErrorHandler {

    /// Handlers

    static private $trapErrors = false;
    static private $trappedError = null;

    static function startTrapErrors() {
        ErrorHandler::$trapErrors = true;
        ErrorHandler::$trappedError = null;
    }

    static function endTrapErrors() {
        ErrorHandler::$trapErrors = false;
        return ErrorHandler::$trappedError;
    }

    static function handlePhpRuntimeError ($severity, $message, $phpFile, $phpLine) {

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        if (strpos($message, 'Missing argument') !== false) {
            array_shift($trace);
            $phpFile = $trace[0]['file'];
            $phpLine = $trace[0]['line'];
        }

        $message = str_replace('foreach()', 'for()', $message);
        $message = str_replace('supplied for', 'in', $message);

        ErrorHandler::printError([
            'type'    => 'RuntimeError',
            'message' => $message,
            'phpFile' => $phpFile,
            'phpLine' => $phpLine,
            'trace'   => $trace
        ]);
    }

    static function handleShutdown () {

        $error = error_get_last();

        if ($error) {

            $types = [ E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ];
            if (in_array($error['type'], $types)) {

                $error['function'] = '';
                $trace = [ $error ];

                preg_match('/Allowed memory size of (\d+)/i', $error['message'], $m);
                if ($m) {
                    $max = Owl::getConfig('memoryLimitMb');
                    $error['message'] = "Max memory limit exceeded ($max MB).  Check config 'memoryLimitMb'.";
                    $error['file'] = null;
                }

                preg_match('/Maximum execution time of (\d+)/i', $error['message'], $m);
                if ($m) {
                    $max = Owl::getConfig('maxExecutionTimeSecs');
                    $secs = v('second')->u_plural($max);
                    $error['message'] = "Max execution time exceeded ($max $secs).  Check config 'maxExecutionTimeSecs'.";
                    $error['file'] = null;
                }

                ErrorHandler::printError([
                    'type'    => 'ShutdownError',
                    'message' => $error['message'],
                    'phpFile' => $error['file'],
                    'phpLine' => $error['line'],
                    'trace'   => null
                ]);
            }
        }
    }

    // Errors not related to a source file (e.g. config errors)
    static function handleConfigError ($message) {
        ErrorHandler::printError([
            'type'    => 'OwlConfigError',
            'message' => $message,
            'phpFile' => '',
            'phpLine' => 0,
            'trace'   => null
        ]);
    }

    // Triggered by Owl::error
    static function handleOwlException ($error, $sourceFile) {

        $trace = $error->getTrace();
        $frame = [];

        foreach ($trace as $f) {
            if (!isset($f['file'])) {
                $f['file'] = '(anon)';
            }
            if (strpos($f['file'], '.owl') !== false) {
                $frame = $f;
                break;
            }
        }
        ErrorHandler::printError([
            'type'    => 'OwlException',
            'message' => $error->getMessage(),
            'phpFile' => $frame['file'],
            'phpLine' => $frame['line'],
            'trace'   => $trace
        ]);
    }

    // PHP exception during startup
    static function handleStartupException ($error) {

        $phpFile = $error->getFile();
        $phpLine = $error->getLine();
        $message = $error->getMessage();

        preg_match("/with message '(.*)' in \//i", $message, $match);
        $msg = (isset($match[1]) ? $match[1] : $message);

        ErrorHandler::printError([
            'type'    => 'PhpStartupError',
            'message' => $message,
            'phpFile' => $phpFile,
            'phpLine' => $phpLine,
            'trace'   => $error->getTrace()
        ]);
    }

    // PHP exception - in theory, this should never leak through to end users
    static function handlePhpLeakedException ($error) {

        $phpFile = $error->getFile();
        $phpLine = $error->getLine();
        $message = $error->getMessage();

        preg_match("/with message '(.*)' in \//i", $message, $match);
        $msg = (isset($match[1]) ? $match[1] : $message);

        ErrorHandler::printError([
            'type'    => 'PhpLeakedException',
            'message' => $message,
            'phpFile' => $phpFile,
            'phpLine' => $phpLine,
            'trace'   => $error->getTrace(),
            '_rawTrace' => true
        ]);
    }

    static function handlePhpParseError ($msg) {

        $matches = [];
        $found = preg_match('/(\S*?) on line (\d+)/', $msg, $matches);
        if (!$found) {
            Owl::error($msg);
        }
        $phpFile = $matches[1];
        $phpLine = $matches[2];

        $found = preg_match('/:(.*) in/', $msg, $matches);
        $phpMsg = $found ? trim($matches[1]) : '';

        ErrorHandler::printError([
            'type'    => 'PhpParserError',
            'message' => $phpMsg,
            'phpFile' => $phpFile,
            'phpLine' => $phpLine,
            'trace'   => null
        ]);
    }


    static function handleCompilerError ($msg, $srcToken, $srcFile) {

        $srcPos = explode(',', $srcToken[TOKEN_POS]);
        $src = [
            'file' => $srcFile,
            'line' => $srcPos[0],
            'pos'  => $srcPos[1]
        ];

        ErrorHandler::printError([
            'type'    => 'OwlParserError',
            'message' => $msg,
            'phpFile' => '',
            'phpLine' => '',
            'trace'   => null,
            '_src'    => $src
        ]);
    }



    /////////  PRINT

    static function printError ($error, $logOut='') {

        if (Source::isSandboxMode()) {
            throw new \Exception ('[Sandbox] ' . $error['message']);
        }

        if (ErrorHandler::$trapErrors) {
            ErrorHandler::$trappedError = $error;
            return;
        }

        $eh = new ErrorHandler();

        $prepError = $eh->prepError($error);
        $plainOut = $eh->formatError($prepError);

        if (Owl::isMode('cli')) {
            $eh->printToConsole($plainOut);
        } else {
            if ($eh->doDisplayWebErrors() || $error['type'] == 'OwlConfigError') {
                $eh->printToWeb($prepError);
            } else {
                if (!$logOut) { $logOut = $plainOut; }
                $eh->printToLog($logOut);
                Owl::module('Web')->u_send_error(500);
            }
        }
        exit(1);
    }

    function prepError($error) {

        $error['message'] = $this->cleanMsg($error['message']);

        $error['src'] = null;
        if (isset($error['_src'])) {
            $error['src'] = $error['_src'];
        }
        else if ($error['phpFile']) {
            $error['src'] = $this->phpToSrc($error['phpFile'], $error['phpLine']);
        }

        $error['srcLine'] = '';
        if (isset($error['src'])) {
            $error['srcLine'] = $this->getSourceLine($error['src']['file'], $error['src']['line'], $error['src']['pos']);
            if ($error['src']['file']) {
                $error['src']['file'] = $this->cleanPath($error['src']['file']);
            }
        }

        if ($error['trace']) {
            $forcePhp = isset($error['_rawTrace']) ? $error['_rawTrace'] : false;
            $error['trace'] = $this->cleanTrace($error['trace'], $forcePhp);
        }

        return $error;
    }

    function formatError ($error) {

        $out = "--- " . $error['type'] . " ---\n\n";
        $out .= $error['message'];
        if (isset($error['srcLine'])) {
            $out .= $error['srcLine'];
        }

        $src = isset($error['src']) ? $error['src'] : null;
        if ($error['trace']) {
            $out .= $error['trace'];
        } else if ($src['file']) {
            $out .= "File: " . $src['file'] . "  Line: " . $src['line'];
            if (isset($src['pos'])) {
                $out .= "  Pos: " . $src['pos'];
            }
        }

        return $out;
    }

    function printToLog ($msg) {

        Owl::errorLog($msg);
    }

    function printToCss ($aOut) {

        $req = Owl::module('Web')->u_request();
        $file = $req['relativeUrl'];

        $out = $aOut;

          $out = "Error in CSS action '" . $file . "'\n\n$out";

          $out = str_replace("'", "\\'", $out);
          $out = str_replace("\n", "\\A ", $out);

          $msg = "body:before { content: '$out'; white-space: pre; background-color: #242; color: #fff; position: absolute; top: 0; left: 0; width: 100%; padding: 40px; font-family: monaco }";

          print "$msg\n\n\n$aOut";
    }

    function printToWeb ($error) {

        // TODO: handle errors differently for non-HTML output
        // if (Owl::module('Web')->u_request()['isAjax']) {
        //     print $out;
        //     return;
        // }
        //
        // if (strpos(Owl::module('Web')->u_request()['url']['relative'], '.css') !== false) {
        //     $this->printToCss($out);
        //     return;
        // }

        $logPath = Owl::getRelativePath('root', Owl::path('logFile') );

        $heading = "I found a bug...";

        $error['message'] = htmlspecialchars($error['message']);
        $error['srcLine'] = htmlspecialchars($error['srcLine']);

        // Formatting for "Got: ..." detail.
        // TODO: fix this formatting
        $error['message'] = preg_replace("/\n+Got:(.*)/s", "<br /><br />    : '$1'", $error['message']);

        if (preg_match('/Format Checker/', $error['message'])) {
            $error['message'] = preg_replace('/\(Format Checker\)/i', '', $error['message']);
            $heading = "Format Checker";
        }

        // convert quoted substrings to code
        $error['message'] = preg_replace("/`(.*?)`/", '<span class="owl-error-code">$1</span>', $error['message']);
        $error['message'] = preg_replace("/'([a-zA-Z0-9\.]+)'/", '<span class="owl-error-code">$1</span>', $error['message']);
        $error['message'] = preg_replace("/\((.+?)\)/", '<span class="owl-error-code">$1</span>', $error['message']);

        $error['message'] = preg_replace("/Try:(.*?)/", '<br /><br />Suggestion: $1', $error['message']);

        $isLongSrc = strlen(rtrim($error['srcLine'], "^ \n")) > 50;
        $error['srcLine'] = preg_replace("/\^$/", '<span class="owl-caret">&uarr;</span>', $error['srcLine']);

        // TODO: Clean this up.
        ?>

        <div style='position: fixed; overflow: auto; z-index: 99999; background-color: #225; color: #eee; margin: 0; top: 0; left: 0; right: 0; bottom: 0; color: #fff; padding: 40px 80px;  -webkit-font-smoothing: antialiased;'>
            <style scoped>
                .owl-error-logo { display: inline-block; position: relative; top: -2px; margin-right: 20px; }
                .owl-error-header { opacity: 0.6; font-weight: bold; margin-bottom: 40px; font-size: 100%; border-bottom: solid 1px #fff; padding-bottom: 12px;  }
                .owl-error-message { margin-bottom: 40px; }
                .owl-error-content { font: 22px <?= u_Css::u_sans_serif_font() ?>; line-height: 1.3; z-index: 1; position: relative; margin: 0 auto; max-width: 700px; }
                .owl-error-hint {   margin-top: 80px; line-height: 2; opacity: 0.5; font-size: 80%; }
                .owl-error-srcline { font-size: 90%; border-radius: 4px; margin-bottom: 20px; padding: 30px 30px 30px; background-color: rgba(0,0,0,0.25); white-space: pre; font-family: <?= u_Css::u_monospace_font() ?>; overflow: auto; }
                .src-small { font-size: 65%; }
                .owl-error-trace { font-size: 70%; border-radius: 4px; margin-bottom: 20px; padding: 20px 30px; background-color: rgba(0,0,0,0.25); white-space: pre; font-family: <?= u_Css::u_monospace_font() ?>; }
                .owl-caret { color: #eac222; font-size: 30px; position: relative; left: -3px; top: 2px; line-height: 0; }
                .src-small .owl-caret { font-size: 24px; }
                .owl-error-file { font-size: 90%; margin-bottom: 40px;  }
                .owl-error-file b { margin-right: 40px; margin-left: 5px; font-size: 105%; }
                .owl-error-code {  display: inline-block; margin: 4px 0; border-radius: 4px; font-size: 90%; font-weight: bold; font-family: <?= u_Css::u_monospace_font() ?>; background-color: rgba(255,255,255,0.1); padding: 2 8px; }
            </style>

            <div class='owl-error-content'>

                <div class='owl-error-header'><span class='owl-error-logo'>{o,o}</span><?= $heading ?></div>
                <div class='owl-error-message'><?= $error['message'] ?></div>

                <?php if ($error['src']) { ?>
                <div class='owl-error-file'>
                    File: <b><?= $error['src']['file'] ?></b>
                    Line: <b><?= $error['src']['line'] ?></b>
                </div>
                <?php } ?>

                <?php if ($error['srcLine']) { ?>
                <div class='owl-error-srcline <?= $isLongSrc ? 'src-small' : '' ?>'><?= $error['srcLine'] ?></div>
                <?php } ?>

                <?php if ($error['trace']) { ?>
                <div class='owl-error-trace'><?= $error['trace'] ?></div>
                <?php } ?>

            </div>
        </div>

        <?php
    }

    function printToConsole ($out) {
        $out = "\n\n" . str_repeat('`', 80) . "\n\n" . $out;
        print $out;
    }




    /////////  UTILS

    static function phpToSrc ($phpFile, $phpLine) {

        $phpCode = file_get_contents($phpFile);
        $phpLines = explode("\n", $phpCode);
        $phpLines = array_reverse($phpLines);
        foreach ($phpLines as $l) {
            if (substr($l, 0, 2) === '/*') {
                $match = [];
                $found = preg_match('/SOURCE=(\{.*})/', $l, $match);
                if ($found) {
                    $json = $match[1];
                    $map = json_decode($json, true);
                    if (isset($map[$phpLine])) {
                        $src = [ 'file' => $map['file'], 'line' => $map[$phpLine], 'pos' => null ];
                        return $src;
                    }
                    break;
                }
            }
        }
        return [ 'line' => $phpLine, 'file' => $phpFile, 'pos' => null ];
    }

    function getSourceLine ($srcPath, $srcLineNum1, $pos=null) {

        $srcLineNum = $srcLineNum1 - 1;  // convert to zero-index

   //     print $srcPath; exit();

        if (Owl::module('File')->u_is_relative_path($srcPath)) {
            $srcPath = Owl::path('root', $srcPath);
        }
      //  print $srcPath; exit();

        $source = file_get_contents($srcPath);
        $lines = preg_split('/\n/', $source);
        $line = (count($lines) > $srcLineNum) ? $lines[$srcLineNum] : '';

        // trim indent
        preg_match('/^(\s*)/', $line, $matches);
        $numSpaces = strlen($matches[1]);
        $line = preg_replace('/^(\s*)/', '', $line);
        if (!trim($line)) { return ''; }
        $prefix = '' . $srcLineNum1 . ':  ';

        // make sure pointer is visible in long lines
        if (strlen($line) > 50 && $pos > 50) {
            $trimNum = abs(50 - strlen($line));
            $line = substr($line, $trimNum);
            $pos -= $trimNum;
            $prefix .= '... ';
        }

        $fmtLine = $prefix . $line;

        // pos marker
        $marker = "\n";
        if ($pos !== null && preg_match('/\S/', $line)) {
            $pointerPos = max($pos - ($numSpaces + 1) + strlen($prefix), 0);
            $fmtLine .= "\n";
            $marker = str_repeat(' ', $pointerPos) . '^';
        }

        return $fmtLine . $marker;
    }


    function cleanMsg ($raw) {

        $clean = $raw;
        $clean = $this->cleanVars($clean);
        $clean = preg_replace('/Call to undefined function (.*)\(\)/', 'Unknown function: \'$1\'', $clean);
        $clean = preg_replace('/Call to undefined method (.*)\(\)/', 'Unknown method: \'$1\'', $clean);
        $clean = preg_replace('/, called.*/', '', $clean);
        $clean = preg_replace('/preg_\w+\(\)/', 'ORegex Pattern', $clean);
        $clean = preg_replace('/\(T_.*?\)/', '', $clean);


        if (preg_match('/Syntax error, unexpected \'return\'/i', $clean)) {
            $clean = 'Invalid statement at end of function.';
        }

        $clean = str_replace(Owl::path('root') . '/', '', $clean);

        $clean = ucfirst($clean);

        return $clean;
    }

    function cleanVars ($raw) {

        $fn = function ($m) {
            return v($m[1])->u_to_camel_case();
        };

        $clean = $raw;
        $clean = preg_replace('/o\\\\/', '',    $clean);
        $clean = preg_replace('/owl.*?\\\\/', '', $clean);
        $clean = preg_replace_callback('/u_([a-z_]+)/', $fn, $clean);
        $clean = preg_replace('/(?<=\w)::/', '.',       $clean);
        $clean = preg_replace('/\bO(?=[A-Z])/', '',       $clean);
        $clean = preg_replace('/\bu_/', '', $clean);
        return $clean;
    }

    function cleanPath ($path) {

        $path = Owl::getOwlPathForPhp($path);
        $path = Owl::getRelativePath('root', $path);

        return $path;
    }

    function cleanTrace ($trace, $showPhp=false) {

        $out = '';
        $frameNum = 0;
        foreach ($trace as $phpFrame) {
            if (! isset($phpFrame['file'])) { continue; }
            $cl = isset($phpFrame['class']) ? $phpFrame['class'] : '';

            $file = $this->cleanPath($phpFrame['file']);
            $fun = $this->cleanVars($phpFrame['function']);

            if (!Owl::getConfig('_showPhpInTrace') && !$showPhp) {
                if ($cl === 'o\\Owl' || strpos($phpFrame['file'], '.owl') === false || substr($fun, 0, 2) === '__') {
                    continue;
                }
            }

            if (OBare::isa($fun)) {
                $cl = '';
            }
            else if ($fun === 'handlePhpRuntimeError') {
                $fun = '';
            }
            else if ($cl) {
                $fun = $this->cleanVars($cl) . '.' . $fun;
            }

            $src = ErrorHandler::phpToSrc($phpFrame['file'], $phpFrame['line']);

            $lineMsg = abs($src['line']) . ($src['line'] > 0 ? '' : '(?)');
            $pre = $frameNum . ' |';
            if (count($trace) >= 10 && $frameNum < 10) { $pre = ' ' . $pre; }
            $fun = !$fun ? '' : "- $fun()";

            $out .= "$pre  $file, line $lineMsg $fun\n";

            $frameNum += 1;
        }

        if ($frameNum <= 1) {
            return "";
        }

        return  trim("--- Trace ---\n\n" . $out);
    }

    function doDisplayWebErrors () {

        return Source::getAppCompileTime() > time() - Owl::getConfig('showErrorPageForMins') * 60;
    }


}
