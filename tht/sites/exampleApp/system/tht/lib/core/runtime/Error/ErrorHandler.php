<?php

namespace o;

include_once('ErrorTelemetry.php');

class ThtError extends \Exception {
    function u_message () {
        return $this->getMessage();
    }
}

class StartupError extends \Exception {}

class ErrorHandler {

    static private $MEMORY_BUFFER_BYTES = 1024;

    static private $trapErrors = false;
    static private $trappedError = null;
    static private $helpLink = null;
    static private $origins = [];
    static private $subOrigins = [];
    static private $topLevelFunction = [];
    static private $memoryBuffer = '';
    static private $file = '';

    static private function initErrorHandler () {

        // Reserve memory in case of out-of-memory error. Enough to call handleShutdown.
        self::$memoryBuffer = str_repeat('*', self::$MEMORY_BUFFER_BYTES);

        set_error_handler('\o\ErrorHandler::handlePhpRuntimeError');
        register_shutdown_function('\o\ErrorHandler::handleShutdown');
    }

    static function catchErrors($fnCallback) {

        self::initErrorHandler();

        try {
            return $fnCallback();
        }
        catch (StartupError $e) {
            self::handleStartupError($e);
        }
        catch (\ThtError $e) {
            // User exceptions
            self::handleThtRuntimeError($e);
        }
        catch (\TypeError $e) {
            // Catch these separately because they have extra caller info
            self::handleThtRuntimeError($e);
        }
        catch (\Error $e) {
            // Internal exceptions
            self::handleThtRuntimeError($e);
        }
        catch (\Exception $e) {
            // User exceptions
            self::handleThtRuntimeError($e);
        }
    }

    static function addOrigin($c) {

        self::$origins []= $c;
    }

    static function addSubOrigin($c) {

        self::$subOrigins []= $c;
    }

    // Register the function that was auto-called (e.g. main())
    static function setMainEntryFunction($file, $fun) {

        $fun = preg_replace('/.*u_/', '', $fun);
        $fun = v($fun)->u_camel_case();

        self::$topLevelFunction = [
            'file' => Tht::getPhpPathForTht($file),
            'function' => $fun,
            'lineNum' => 0,
            'linePos' => -1,
        ];
    }

    static function startTrapErrors() {

        self::$trapErrors = true;
        self::$trappedError = null;
    }

    static function endTrapErrors() {

        $trapped = self::$trappedError;
        self::resetState();

        return $trapped;
    }

    // Primarily used after try/catches
    static function resetState() {

        self::$trapErrors = false;
        self::$helpLink = null;
        self::$origins = [];
        self::$subOrigins = [];
        self::$trappedError = null;
    }

    static function setHelpLink($url, $label) {

        self::$helpLink = [
            'url'   => $url,
            'label' => $label
        ];
    }

    static function setStdLibHelpLink($type, $packageName, $method='') {

        $packageToken = strtolower(v($packageName)->u_slug());
        $url = '/manual/' . $type . '/' . $packageToken;
        $label = $packageName;

        if ($method) {
            $methodToken = strtolower(v($method)->u_slug());
            $url .= '/' . $methodToken;
            $label .= '.' . v(unu_($method))->u_camel_case();
        }

        return self::setHelpLink($url, $label);
    }

    static function setOopHelpLink() {

        self::setHelpLink(
            '/language-tour/classes-and-objects',
            'Classes & Objects'
        );
    }

    static function setFile($filePath) {
        self::$file = $filePath;
    }

    static private function clearMemoryBuffer() {

        self::$memoryBuffer = '';
    }

    static function printError($e) {

        if (ErrorHandler::$trapErrors) {
            ErrorHandler::$trappedError = $e;
            return;
        }

        if (Compiler::isSandboxMode()) {
            throw new \Exception ('[Sandbox] ' . $e['message']);
        }

        $source = $e['source'];
        if (!$source['file']) {
            $source['file'] = self::$file;
        }

        $e['source'] = $source;

        $e = self::handleSpecificCases($e);

        $e['helpLink'] = self::$helpLink;

        $e = self::initOrigin($e);

        $e['entryFrame'] = self::$topLevelFunction;

        // Lazy load output class, since this will rarely be needed
        require_once(__DIR__ . '/ErrorPage.php');

        $page = new ErrorPage ($e);
        $page->print();

        ErrorTelemetry::save($e);
    }

    static function printInlineWarning($msg) {

        $msg = htmlspecialchars($msg);
        print '<div style="background-color: #a33; color: white; font-size: 20px; padding: 16px 16px; font-family: sans-serif;">';
        print "THT Warning: " . $msg;
        print '</div>';
    }

    static function initOrigin($e) {

        $subOrigin = '';

        if (count(self::$origins)) {
            $subOrigin = implode('.', self::$origins);
        }

        if (count(self::$subOrigins)) {
            $subOrigin .= '.' . implode('.', self::$subOrigins);
        }

        if ($subOrigin) {
            $e['origin'] .= '.' . $subOrigin;
        }

        return $e;
    }

    static function parseInlineTrace($message) {

        if (!preg_match('/Stack trace:/i', $message)) {
            return null;
        }

        $trace = [];
        // example:
        // #0 /dir/cache/php/00300703_pages_home.tht.php(123): tht\pages\home_x\u_do_something('a')
        preg_match_all('/#\d+\s+(\S+?)\((\d+)\):\s+(\S+?)\n/', $message, $lines, PREG_SET_ORDER);
        foreach($lines as $line) {
            $fun = preg_replace('/\(.*\)/', '', $line[3]);
            $frame = [
                'file' => $line[1],
                'line' => $line[2],
                'function' => $fun,
            ];
            $trace []= $frame;
        }

        return $trace;
    }

    static function handleSpecificCases($error) {

        if (preg_match('/Too few arguments to function \\S+\\\\(.*?\\(\\))/i', $error['message'], $m)) {

            // TODO: show full signature

            $error['origin'] .= '.arguments.less';

            preg_match('/(\d+) expected/i', $error['message'], $num);

            $error['message'] = "Not enough arguments passed to `" . $m[1] . "`";
            if ($num) {
                $error['message'] .= " (Expected: " . $num[1] . ')';
            }

            // Show caller instead of function signature
            $source = $error['source'];
            $source['file'] = $error['trace'][0]['file'];
            $source['lineNum'] = $error['trace'][0]['line'];

            $error['source'] = $source;

            // $hasCallerInfo = preg_match('/(\d+) passed in (.*?) on line (\d+)/', $error['message'], $m);
            // if ($hasCallerInfo) {
            //     $error['phpFile'] = Tht::normalizeWinPath($m[2]);
            //     $error['phpLine'] = $m[3];
            // }
        }
        else if (preg_match('/Argument (\d+) passed to (\S+) must be of the type (\S+), (\S+) given/i', $error['message'], $m)) {

            // TODO: show full signature

            // Type Error for function arguments
            $error['origin'] .= '.arguments.type';
            $error['message'] = "Argument $m[1] passed to `$m[2]` must be of type `$m[3]`. Got `$m[4]` instead.";

            // In PHP, the error frame is the location of the function signature, NOT the caller.
            //
            // $fnSource = [
            //     'file' => $error['source']['file'],
            //     'lineNum' => $error['source']['lineNum'],
            // ];

            // Show caller instead of function signature
            $source = $error['source'];
            $source['file'] = $error['trace'][0]['file'];
            $source['lineNum'] = $error['trace'][0]['line'];

            $error['source'] = $source;
        }
        else if (preg_match('/Using \$this when not in object context/i', $error['message'])) {
            $error['message'] = "Can not use `@` outside of an object.";
        }
        else if (preg_match("/function '(.*?)' not found or invalid function name/i", $error['message'], $m)) {
            $error['message'] = "PHP function does not exist: `" . $m[1] . "`";
        }
        // else if (preg_match("/Timezone ID '(.*?)' is invalid/i", $error['message'], $m)) {
        //     // TODO: link to timezone list. Make this a Config Error with source line.
        //     $error['message'] = "Timezone in `config/app.jcon` is invalid: `" . $m[1] . "`";
        // }
        else if (preg_match('/Syntax error, unexpected \'return\'/i', $error['message'], $m)) {
            $error['message'] = 'Invalid statement at end of function.  Missing `return`?';
        }
        else if (preg_match('/Errors parsing (.*)/i', $error['message'], $m)) {

            // Error during PHP parse phase
            $file = Tht::getThtPathForPhp($m[1]);
            $error['message'] = "Unknown PHP parser error in: `$file`\n\nSorry, there isn't more information.\n\nTry double-checking the last change you made.";
        }
        else if (preg_match('/resource temporarily/i', $error['message'])) {

            // TODO: This find a true fix for this
            $error['message'] .= '.  This is probably a race condition in Windows after re-compiling.  Just try refreshing the page.';

        }
        else if (preg_match('/permission denied/i', $error['message'])) {

            // Catch very common deploy issue when permissions aren't set correctly

            // TODO: Better to just wrap actual calls to file_*_contents|touch with explicit error checking.
            $found = preg_match('/(file_.*?|touch|f.*?)\((.*?)\)/i', $error['message'], $m);
            $func = $found ? $m[1] : '';
            $file = $found ? Tht::normalizeWinPath($m[2]) : '';

            $verb = 'accessing';
            if ($func == 'file_get_contents') {
                $verb = 'reading';
            }
            if ($func == 'file_put_contents' || $func == 'touch') {
                $verb = 'writing';
            }

            $error['origin'] .= '.filePermissions';
            $error['message'] = "Permission denied $verb file: `$file` Try: Run `tht fix` in your app directory to update permissions.";
            $error['phpFile'] = '';
            $error['phpLine'] = 0;
        }

        return $error;
    }






    // Handlers
    // --------------------------------


    static function handlePhpRuntimeError ($severity, $message, $phpFile, $phpLine) {

        $trace = debug_backtrace(0);

        $source = [
            'lang'       => 'php',
            'file'       => $phpFile,
            'lineNum'    => $phpLine,
            'linePos'    => 0,
            'lineSource' => '',
        ];

        self::printError([
            'category' => 'runtime',
            'origin'   => 'php.runtime',
            'message'  => $message,
            'trace'    => $trace,
            'source'   => $source,
        ]);
    }

    static function handleShutdown () {

        //Tht::module('Output')->endGzip();

        $error = error_get_last();

        if (!$error) {
            return;
        }

        $errorTypes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array($error['type'], $errorTypes)) {
            return;
        }

        self::handleResourceErrors($error);

        $trace = self::parseInlineTrace($error['message']);

        $source = [
            'lang'       => 'php',
            'file'       => $error['file'],
            'lineNum'    => $error['line'],
            'linePos'    => 0,
            'lineSource' => '',
        ];

        self::printError([
            'category' => 'runtime',
            'origin'   => 'php.shutdown',
            'message'  => $error['message'],
            'trace'    => $trace,
            'source'   => $source,
        ]);
    }

    static function handleResourceErrors($error) {

        // Show minimal error message for memory and execution errors.
        preg_match('/Allowed memory size of (\d+)/i', $error['message'], $m);
        if ($m) {
            $max = Tht::getConfig('memoryLimitMb');
            print "<b>Page Error: Max memory limit exceeded ($max MB).  See `memoryLimitMb` in `app.jcon`.</b>";
            Tht::exitScript(1);
        }

        preg_match('/Maximum execution time of (\d+)/i', $error['message'], $m);
        if ($m) {
            $max = Tht::getConfig('maxExecutionTimeSecs');
            print "<b>Page Error: Max execution time exceeded ($max seconds).  See `maxExecutionTimeSecs` in `app.jcon`.</b>";
            Tht::exitScript(1);
        }
    }

    // Errors not related to a source file (e.g. config errors)
    static function handleConfigError ($message) {

        $source = [
            'lang'       => 'config',
            'file'       => '',
            'lineNum'    => 0,
            'linePos'    => 0,
            'lineSource' => '',
        ];

        self::printError([
            'category' => 'startup',
            'origin'   => 'tht.config',
            'message'  => $message,
            'trace'    => null,
            'source'   => $source,
        ]);
    }

    // Triggered by Tht::error
    static function handleThtRuntimeError ($exception) {

        $trace = $exception->getTrace();
        $eFile = $exception->getFile();
        $eLine = $exception->getLine();

        // Put tht frame in front
        $hasTempFrame = false;
        if ($eFile && $eLine) {
            array_unshift($trace, ['file' => $eFile, 'line' => $eLine]);
            $hasTempFrame = true;
        }

        // Find the first frame within THT space
        // Otherwise line is always "throw new ThtError"
        $frame = [];
        foreach ($trace as $f) {
            if (!isset($f['file'])) {
                $f['file'] = '(anon)';
            }
            if (strpos($f['file'], '.tht') !== false) {
                $frame = $f;
                break;
            }
        }

        $file = isset($frame['file']) ? $frame['file'] : '';
        $line = isset($frame['line']) ? $frame['line'] : '';
        $fn   = isset($frame['function']) ? $frame['function'] : '';

        if ($hasTempFrame) {
            array_shift($trace);
        }

        $source = [
            'lang'       => 'php',
            'file'       => $file,
            'lineNum'    => $line,
            'linePos'    => 0,
            'lineSource' => '',
            'function'   => $fn,
        ];

        self::printError([
            'category' => 'runtime',
            'origin'   => 'tht.runtime',
            'message'  => $exception->getMessage(),
            'trace'    => $trace,
            'source'   => $source,
        ]);
    }

    // PHP exception during startup
    static function handleStartupError ($error) {

        $phpFile = $error->getFile();
        $phpLineNum = $error->getLine();
        $message = $error->getMessage();

        preg_match("/with message '(.*)' in \//i", $message, $match);
        $msg = (isset($match[1]) ? $match[1] : $message);

        print '<h2>Startup Error</h2>' . $message;
        Tht::exitScript(1);
    }

    // In theory, this should never leak through to end users
    static function handleLeakedPhpRuntimeError ($error) {

        $phpFile = $error->getFile();
        $phpLineNum = $error->getLine();
        $message = $error->getMessage();

        preg_match("/with message '(.*)' in \//i", $message, $match);
        $msg = (isset($match[1]) ? $match[1] : $message);

        $source = [
            'lang'       => 'php',
            'file'       => $phpFile,
            'lineNum'    => $phpLineNum,
            'linePos'    => 0,
            'lineSource' => '',
        ];

        self::printError([
            'category'   => 'runtime',
            'origin'     => 'php.runtime.leaked',
            'message'    => $message,
            'trace'      => $error->getTrace(),
            'source'     => $source,
            '_fullTrace'  => true
        ]);
    }

    static function handlePhpParseError ($msg) {

        $matches = [];

        // Note there can be multiple 'in' words, so the leading .* is needed
        $found = preg_match('/.* in (.*?) on line (\d+)/i', $msg, $matches);

        print($msg); exit();

        if ($found) {
            $phpFile = $matches[1];
            $phpLineNum = $matches[2];
            $found2 = preg_match('/:(.*) in/', $msg, $matches);
            $phpMsg = $found2 ? trim($matches[1]) : '';
        }
        else {
            $phpMsg = $msg;
            $phpFile = '';
            $phpLineNum = '';
        }

        $source = [
            'lang'       => 'php',
            'file'       => $phpFile,
            'lineNum'    => $phpLineNum,
            'linePos'    => 0,
            'lineSource' => '',
        ];

        self::printError([
            'category' => 'compiler',
            'origin'   => 'php.parser',
            'message'  => $phpMsg,
            'trace'    => null,
            'source'   => $source,
        ]);
    }

    static function handleThtCompilerError ($msg, $thtSrcToken, $thtFile, $isLineError=false) {

        $thtPos = explode(',', $thtSrcToken[TOKEN_POS]);

        $source = [
            'lang'       => 'tht',
            'file'       => $thtFile,
            'lineNum'    => $thtPos[0],
            'linePos'    => $isLineError ? -1 : $thtPos[1],
            'lineSource' => '',
        ];

        self::printError([
            'category' => 'compiler',
            'origin'   => 'tht.compiler',
            'message'  => $msg,
            'trace'    => Tht::getConfig('_coreDevMode') ? debug_backtrace() : null,
            'source'   => $source,
        ]);
    }

    static function handleJconError ($msg, $srcFile, $lineNum, $line) {

        $source = [
            'lang'       => 'jcon',
            'file'       => $srcFile,
            'lineNum'    => $lineNum,
            'linePos'    => null,
            'lineSource' => $lineNum . ':  ' . trim($line),
        ];

        self::printError([
            'category' => 'runtime',
            'origin'   => 'jcon.parser',
            'message'  => $msg,
            'trace'    => null,
            'source'   => $source,
        ]);
    }
}
