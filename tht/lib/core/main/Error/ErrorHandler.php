<?php

namespace o;

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('log_errors', '0');
ini_set('display_errors', DEV_ERRORS ? '1' : '0');

if (!DEV_ERRORS) {
    register_shutdown_function('\o\ErrorHandler::handlePhpShutdown');
}

class ErrorHandler {

    static private $trapErrors = false;
    static private $trappedError = null;
    static private $helpLink = null;
    static private $formatCheckerRule = '';
    static private $origins = [];
    static private $subOrigins = [];
    static private $topLevelFunction = [];
    static private $objectDetail = null;
    static private $objectDetailName = '';
    static private $file = '';
    static private $skipFunDefInStack = false;

    // PUBLIC
    //-------------------------------------------------------------

    public static function catchErrors($fnCallback) {

        if (DEV_ERRORS) {
            return $fnCallback();
        }

        set_error_handler('\o\ErrorHandler::handlePhpRuntimeError');

        try {
            return $fnCallback();
        }
        catch (ThtError $e) {
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
            // Exceptions thrown by user
            self::handleThtRuntimeError($e);
        }
    }

    public static function addOrigin($c) {

        self::$origins []= $c;
    }

    public static function addSubOrigin($c) {

        self::$subOrigins []= $c;
    }

    public static function addObjectDetails($o, $name = '') {

        self::$objectDetail = $o;
        self::$objectDetailName = $name;
    }

    public static function skipFunctionDefinitionInStack() {
        self::$skipFunDefInStack = true;
    }

    // Register the function that was auto-called (e.g. main())
    public static function setMainEntryFunction($file, $fun) {

        $fun = preg_replace('/.*u_/', '', $fun);
        $fun = v($fun)->u_to_token_case('camel');

        self::$topLevelFunction = [
            'file' => Tht::getPhpPathForTht($file),
            'function' => $fun,
            'lineNum' => 0,
            'linePos' => -1,
        ];
    }

    public static function startTrapErrors() {

        self::$trapErrors = true;
        self::$trappedError = null;
    }

    public static function endTrapErrors() {

        $trapped = self::$trappedError;
        self::resetState();

        return $trapped;
    }

    // Primarily used after try/catches
    public static function resetState() {

        self::$trapErrors = false;
        self::$helpLink = null;
        self::$origins = [];
        self::$subOrigins = [];
        self::$trappedError = null;
        self::$objectDetail = null;
        self::$objectDetailName = '';
    }

    public static function setHelpLink($url, $label) {

        self::$helpLink = [
            'url'   => $url,
            'label' => $label
        ];
    }

    public static function getFuzzySuggest($needle, $haystack, $isMethod=false, $alias=[]) {

        if (isset($alias[strtolower($needle)])) {
            $match = $alias[strtolower($needle)];
            if ($isMethod) { $match .= '()'; }
            return 'Try: `' . $match . '`';
        }

        $prefixes = OList::create(
            $isMethod ? ['get', 'set', 'to', 'is', 'num', 'z', 'xDanger'] : []
        );
        $matches = v($needle)->u_fuzzy_search(OList::create($haystack), $prefixes);

        $topMatches = self::getTopFuzzyMatches($matches, $isMethod);
        if ($topMatches) {
            return 'Try: ' . $topMatches;
        }

        return '';
    }

    public static function filterUserlandNames($names) {

        $names = array_filter($names, function($n) { return hasu_($n); });
        $names = array_map(function($n) { return unu_($n); }, $names);

        return $names;
    }

    public static function getTopFuzzyMatches($matches, $isMethod = false) {

        if (!$matches->u_length()) {
            return '';
        }

        $append = $isMethod ? '()' : '';

        if ($matches->u_length() == 1) {
            $m = $matches->u_pop();
            $suggest = "`" . $m['word'] . $append . "`";
            if ($m['score'] >= 8) {
                $suggest .= ' (possible typo)';
            }
            return $suggest;
        }

        $topScore = floor($matches[1]['score']);
        $topWords = $matches->u_filter(function($a) use ($topScore) {
            return floor($a['score']) == $topScore;
        });
        $topWords = $topWords->u_map(function($a) use ($append) {
            return '`' . $a['word'] . $append . '`';
        });

        return $topWords->u_join(' ');
    }

    public static function setFormatCheckerRule($rule) {
        self::$formatCheckerRule = $rule;
    }

    public static function setStdLibHelpLink($type, $packageName, $method='') {

        $packageToken = strtolower(v($packageName)->u_to_token_case('-'));
        $url = '/manual/' . $type . '/' . $packageToken;
        $label = $packageName;

        if ($method) {
            $methodToken = strtolower(v($method)->u_to_token_case('-'));
            $url .= '/' . $methodToken;
            $label .= '.' . $method;
        }

        return self::setHelpLink($url, $label);
    }

    public static function setOopHelpLink() {

        self::setHelpLink(
            '/language-tour/oop/classes-and-objects',
            'Classes & Objects'
        );
    }

    public static function setFile($filePath) {
        self::$file = $filePath;
    }

    public static function setLine($line) {
        self::$line = $line;
    }


    // PRIVATE
    //-------------------------------------------------------------

    private static function printError($e) {

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
            if (!$source['file'] && Tht::isMode('web') && \class_exists('o\WebMode')) {
                $source['file'] = WebMode::$entryFile;
            }
        }

        $e['source'] = $source;

        $e = self::filterSpecificPhpErrors($e);

        $e['helpLink'] = self::$helpLink;


        $e = self::initOrigin($e);

        $e['entryFrame'] = self::$topLevelFunction;

        $e['objectDetail'] = self::$objectDetail;
        $e['objectDetailName'] = self::$objectDetailName;


        require_once(__DIR__ . '/ErrorPage.php');
        $page = new ErrorPage ($e);
        $page->print();

        // require_once(__DIR__ . 'ErrorTelemetry.php');
        // ErrorTelemetry::send($e);
    }

    static public function printInlineWarning($msg) {

        $msg = htmlspecialchars($msg);
        print '<div style="background-color: #a33; color: white; font-size: 20px; padding: 16px 16px; font-family: sans-serif;">';
        print "THT Warning: " . $msg;
        print '</div>';
    }

    static private function initOrigin($e) {

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

    static private function filterSpecificPhpErrors($error) {

        $rxVarWithNamepace = '([\w\\\\]+)';
        if (preg_match('/(\w+)\(.*?Argument #(\d+).*? must be of type ' . $rxVarWithNamepace . ', ' . $rxVarWithNamepace . '/i', $error['message'], $match)) {

            // As of PHP v8.3
            $error['origin'] .= '.arguments.type';
            $error['message'] = 'Wrong type for argument #' . $match[2] . ' of function: `' . $match[1] . '()`  Got: `' . $match[4] . '`  Expected: `' . $match[3] . '`';
            $error['source'] = self::findRealSourceLineForFunctionCall($error);
        }
        else if (preg_match('/Too few arguments to function \\S+\\\\(.*?\\(\\))/i', $error['message'], $m)) {

            // As of PHP v8.3
            $error['origin'] .= '.arguments.less';
            preg_match('/(\d+) expected/i', $error['message'], $num);
            $error['message'] = "Not enough arguments passed to: `" . $m[1] . "`";
            if ($num) {
                $error['message'] .= "  Expected arguments: " . $num[1];
            }
            $error['source'] = self::findRealSourceLineForFunctionCall($error);
        }
        // else if (preg_match('/Argument (\d+) passed to (\S+) must be of the type (\S+), (\S+) given/i', $error['message'], $m)) {

        //     // As of PHP v?? - possibly deprecated

        //     // Type Error for function arguments
        //     $error['origin'] .= '.arguments.type';
        //     $error['message'] = "Argument $m[1] passed to `$m[2]` must be of type: `$m[3]`  Got: `$m[4]`";

        //     // Show caller instead of function signature
        //     $source = $error['source'];
        //     $source['file'] = $error['trace'][0]['file'];
        //     $source['lineNum'] = $error['trace'][0]['line'];

        //     $error['source'] = $source;
        // }
        else if (preg_match('/Using \$this when not in object context/i', $error['message'])) {
            $error['message'] = "Can't use `@` outside of an object.";
        }
        else if (preg_match("/function '(.*?)' not found or invalid function name/i", $error['message'], $m)) {
            $error['message'] = "PHP function does not exist: `" . $m[1] . "`";
        }
        // else if (preg_match("/Timezone ID '(.*?)' is invalid/i", $error['message'], $m)) {
        //     // TODO: link to timezone list. Make this a Config Error with source line.
        //     $error['message'] = "Timezone in `config/app.jcon` is invalid: `" . $m[1] . "`";
        // }
        else if (preg_match('/Syntax error, unexpected \'return\'/i', $error['message'], $m)) {
            $error['message'] = 'Invalid statement at end of function.  Try: Missing `return`?';
        }
        else if (preg_match('/Errors parsing (.*)/i', $error['message'], $m)) {

            // Error during PHP parse phase
            $file = Tht::getThtPathForPhp($m[1]);
            $error['message'] = "Unknown PHP parser error in: `$file`\n\nSorry, there isn't more information.\n\nTry double-checking the last change you made.";
        }
        else if (preg_match('/resource temporarily/i', $error['message'])) {

            // TODO: Find a true fix for this
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
            $error['message'] = "Permission denied $verb file: `$file`  Try: Run `tht fix` in your app directory to update permissions.";
            $error['phpFile'] = '';
            $error['phpLine'] = 0;
        }
        else if (preg_match("/must be of type.*null given/i", $error['message'])) {
            ErrorHandler::setHelpLink('https://tht.dev/language-tour/intermediate/null', 'Null');
        }

        // Emitter already should catch this with implicit default.
        // else if (preg_match('/Unhandled match case(.*)/i', $error['message'], $m)) {
        //     $error['message'] = 'No match found for value: `' . trim($m[1]) . '`  Try: Add a `default` case.';
        // }

        return $error;
    }

    static function filterResourceLimitErrors($error) {

        if (preg_match('/Allowed memory size/i', $error['message'])) {
            $max = intval(ini_get('memory_limit'));
            self::setStdLibHelpLink('module', 'System', 'setMaxMemoryMb');
            $error['message'] = "Max memory limit exceeded: $max MB";
            $error['file'] = '';
            $error['line'] = 0;
        }

        if (preg_match('/Maximum execution time/i', $error['message'])) {
            $max = ini_get('max_execution_time');
            self::setStdLibHelpLink('module', 'System', 'setMaxRunTimeSecs');
            $error['message'] = "Max execution time exceeded: $max seconds";
            $error['file'] = '';
            $error['line'] = 0;
        }

        return $error;
    }

    // Argument errors in PHP are reported where the function is DEFINED.
    // We need to show where the function was CALLED.
    static private function findRealSourceLineForFunctionCall($error) {

        $source = $error['source'];

        $tracePos = 0;
        foreach ($error['trace'] as $i => $frame) {
            if (isset($frame['file']) && Tht::isThtFile($frame['file'])) {
                $tracePos = $i;
                break;
            }
        }

        $source['file'] = $error['trace'][$tracePos]['file'];
        $source['lineNum'] = $error['trace'][$tracePos]['line'];

        return $source;
    }




    // Handlers
    // --------------------------------


    public static function handlePhpRuntimeError($severity, $message, $phpFile, $phpLine) {

        $trace = debug_backtrace(0);

        $source = [
            'lang'       => 'php',
            'file'       => $phpFile,
            'lineNum'    => $phpLine,
            'linePos'    => -1,
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

    // Shutdown occurs when script ends.
    static public function handlePhpShutdown() {

        $error = error_get_last();

        if (!$error) {
            return;
        }

        // $errorTypes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        // if (!in_array($error['type'], $errorTypes)) {
        //     return;
        // }

        $error = self::filterResourceLimitErrors($error);

        $trace = self::parsePhpInlineTrace($error['message']);

        $source = [
            'lang'       => 'php',
            'file'       => $error['file'],
            'lineNum'    => $error['line'],
            'linePos'    => -1,
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

    private static function parsePhpInlineTrace($message) {

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

    // Errors not related to a source file (e.g. config errors)
    public static function handleConfigError($message) {

        $source = [
            'lang'       => 'config',
            'file'       => 'config/app.jcon',
            'lineNum'    => 0,
            'linePos'    => -1,
            'lineSource' => '',
        ];

        self::printError([
            'category' => 'config',
            'origin'   => 'tht.config',
            'message'  => $message,
            'trace'    => null,
            'source'   => $source,
        ]);
    }

    // Triggered by Tht::error
    public static function handleThtRuntimeError($exception) {

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
        // TODO: this should probably be factored out to be re-usable?
        $frame = [];
        foreach ($trace as $f) {
            $f['file'] ??= '(anon)';
            if (isset($f['function']) && $f['function'] == 'checkNumArgs') {
                // Adding this, otherwise the error source points to the function definition, instead of the caller.
                continue;
            }
            if (str_contains($f['file'], '.tht')) {
                $frame = $f;
                break;
            }
        }

        $file    = $frame['file']     ?? '';
        $lineNum = $frame['line']     ?? '';
        $fn      = $frame['function'] ?? '';

        if ($hasTempFrame) {
            array_shift($trace);
        }

        if (!$frame) {
            $trace = null;
        }

        $source = [
            'lang'       => 'php',
            'file'       => $file,
            'lineNum'    => $lineNum,
            'linePos'    => -1,
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

    // In theory, this should never leak through to end users
    // public static function handleLeakedPhpRuntimeError($error) {

    //     $phpFile = $error->getFile();
    //     $phpLineNum = $error->getLine();
    //     $message = $error->getMessage();

    //     preg_match("/with message '(.*)' in \//i", $message, $match);
    //     $msg = (isset($match[1]) ? $match[1] : $message);

    //     $source = [
    //         'lang'       => 'php',
    //         'file'       => $phpFile,
    //         'lineNum'    => $phpLineNum,
    //         'linePos'    => -1,
    //         'lineSource' => '',
    //     ];

    //     self::printError([
    //         'category'   => 'runtime',
    //         'origin'     => 'php.runtime.leaked',
    //         'message'    => $message,
    //         'trace'      => $error->getTrace(),
    //         'source'     => $source,
    //         '_fullTrace'  => true
    //     ]);
    // }

    public static function handlePhpLintError($msg) {

        $matches = [];

        // Note there can be multiple 'in' words, so the leading .* is needed
        $found = preg_match('/.* in (.*?) on line (\d+)/i', $msg, $matches);

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
            'linePos'    => -1,
            'lineSource' => '',
        ];

        self::printError([
            'category' => 'compiler',
            'origin'   => 'php.linter',
            'message'  => $phpMsg,
            'trace'    => null,
            'source'   => $source,
        ]);
    }

    public static function handleThtCompilerError($msg, $thtSrcToken, $thtFile, $isLineError=false) {

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
            'trace'    => Tht::getThtConfig('_coreDevMode') ? debug_backtrace() : null,
            'source'   => $source,
        ]);
    }

    public static function handleJconError($msg, $srcFile='', $lineNum=0, $line=0) {

        self::setHelpLink('/reference/jcon-configuration-format', 'JCON Format');

        $source = null;
        if ($srcFile) {
            $source = [
                'lang'       => 'jcon',
                'file'       => $srcFile,
                'lineNum'    => $lineNum,
                'linePos'    => -1,
                'lineSource' => $lineNum . ':  ' . trim($line),
            ];
        }

        self::printError([
            'category' => 'runtime',
            'origin'   => 'jcon.parser',
            'message'  => $msg,
            'trace'    => debug_backtrace(),
            'source'   => $source,
        ]);
    }
}
