<?php

namespace o;

class Tht {

    static private $VERSION = '0.2.0 - Beta';
    static private $VERSION_TOKEN = '000200';

    static private $SRC_EXT = 'tht';

    static private $THT_SITE = 'https://tht.help';

    static private $startTime = 0;

    static private $data = [
        'requestData'    => [],
        'config'         => [],
        'requestHeaders' => [],
        'memoryBuffer'   => ''
    ];

    static private $mode = [
        'cli'         => false,
        'testServer'  => false,
        'web'         => false,
        'fileSandbox' => false,
    ];

    static private $paths = [];
    static private $files = [];

    static private $APP_DIR = [

        'app'       => 'app',
        'pages'     =>   'pages',
        'modules'   =>   'modules',
        'config'    =>   'config',

        'misc'      =>   'misc',
        'phpLib'      =>   'php',
        'scripts'     =>   'scripts',

        'localTht'  =>   '.tht',

        'data'      => 'data',
        'db'        =>   'db',
        'sessions'  =>   'sessions',
        'files'     =>   'files',
        'cache'     =>   'cache',
        'phpCache'  =>     'php',
        'kvCache'   =>     'keyValue',
        'fileCache' =>     'fileCache',
        'counter'   =>   'counter',
        'counterPage' =>   'page',
        'counterDate' =>   'date',
        'counterRef'  =>   'referrer',
    ];

    static private $APP_FILE = [
        'configFile'         => 'app.jcon',
        'appCompileTimeFile' => '_appCompileTime',
        'logFile'            => 'app.log',
        'frontFile'          => 'thtApp.php',
        'homeFile'           => 'home.tht',
    ];



    // MAIN FLOW
    //---------------------------------------------

    static function start () {
        try {
            return Tht::main();
        }
        catch (StartupException $e) {
            ErrorHandler::handleStartupException($e);
        }
        catch (\Exception $e) {
            ErrorHandler::handlePhpLeakedException($e);
        }
    }

    // TODO: put all these in one registry function/file?
    static private function includeLibs() {

        Tht::loadLib('utils/Utils.php');
        Tht::loadLib('utils/StringReader.php');
        Tht::loadLib('utils/Minifier.php');  // TODO: lazy load this

        Tht::loadLib('runtime/ErrorHandler.php');
        Tht::loadLib('runtime/Source.php');
        Tht::loadLib('runtime/Runtime.php');
        Tht::loadLib('runtime/ModuleManager.php');
        Tht::loadLib('runtime/Security.php');

        Tht::loadLib('../classes/_index.php');
        Tht::loadLib('../modules/_index.php');
    }

    static private function main () {

        Tht::$startTime = microtime(true);

        Tht::includeLibs();

        Tht::initMode();

        // Serve directly if requested a static file in testServer mode
        if (Tht::isMode('testServer')) {
            // Need to construct path manually.
            // See: https://github.com/joelesko/tht/issues/2
            $path = $_SERVER["DOCUMENT_ROOT"] . $_SERVER['SCRIPT_NAME'];
            if ($_SERVER['SCRIPT_NAME'] !== '/' && file_exists($path)) {
                if (is_dir($path)) {
                    // just a warning
                    throw new StartupException ("Path `$path` can not be a page and also a directory under Document Root.");
                }
                // is a static file
                if (!is_dir($path)) {
                    return false;
                }
            }
        }

        if (Tht::isMode('cli')) {
            Tht::loadLib('modes/CliMode.php');
            Tht::initErrorHandler();

            CliMode::main();
        }
        else {

            Tht::loadLib('modes/WebMode.php');
            Tht::init();

            WebMode::main();
        }

        Tht::printPerf();

        return true;
    }

    static private function printPerf () {
        if (Tht::isMode('web') && !Tht::module('Web')->u_request()['isAjax']) {
            if (Tht::getConfig('showPerfComment') && Tht::isMode('web')) {
                $duration = ceil((microtime(true) - Tht::$startTime) * 1000);
                print("\n<!-- Page Speed: $duration ms -->\n");
            }
            Tht::module('Perf')->printResults();
        }
    }

    static public function exitScript($code) {
        if (!$code) {
            self::printPerf();
        }
        exit($code);
    }

    static public function handleShutdown() {
        Tht::clearMemoryBuffer();
        ErrorHandler::handleShutdown();
        Tht::module('Web')->endGzip();
    }

    static private function init () {

        Tht::initMemoryBuffer();
        Tht::initErrorHandler();
        Tht::initRequestData();
        Tht::initHttpRequestHeaders();
        Tht::initAppPaths();
        Tht::initAppConfig();

        Security::initPhpIni();
    }

    static function executePhp ($phpPath) {
        try {
            require_once($phpPath);
        } catch (ThtException $e) {
            ErrorHandler::handleThtException($e, $phpPath);
        }
    }


    // OUTPUT
    //---------------------------------------------

    static function devPrint ($msg) {
        if (Tht::getConfig('_devPrint')) {
            echo "### " . $msg . "\n";
        }
    }

    static function errorLog ($msg) {
        $msg = trim($msg);
        if (!$msg) { return; }
        $msg = preg_replace("/\n{3,}/", "\n\n", $msg);
        if (strpos($msg, "\n") !== false) {
            $msg = ltrim(v($msg)->u_indent(4));
        }
        $line = '[' . strftime('%Y-%m-%d %H:%M:%S') . "]  " . $msg . "\n";
        file_put_contents(Tht::path('logFile'), $line, FILE_APPEND);
    }

    static function error ($msg, $vars=null) {
        $msg = Tht::errorVars($msg, $vars);
        throw new ThtException ($msg);
    }

    static function configError ($msg, $vars=null) {
        $msg = Tht::errorVars($msg, $vars);
        ErrorHandler::handleConfigError($msg);
    }

    static private function errorVars($msg, $vars=null) {
        if (!is_null($vars)) {
            $msg .= "\n\nGot: " . Tht::module('Json')->u_format(json_encode($vars));
        }
        return $msg;
    }




    // INITS
    //---------------------------------------------

    static private function initRequestData() {
        Tht::$data['requestData'] = Security::initRequestData();
    }

    static private function initMode() {
        $sapi = php_sapi_name();
        Tht::$mode['testServer'] = $sapi === 'cli-server';
        Tht::$mode['cli'] = $sapi === 'cli';
        Tht::$mode['web'] = !Tht::$mode['cli'];
    }

    static private function initMemoryBuffer() {
        // Reserve memory in case of out-of-memory error. Enough to call handleShutdown.
        Tht::$data['memoryBuffer'] = str_repeat('*', 128 * 1024);
    }

    static private function clearMemoryBuffer() {
        Tht::$data['memoryBuffer'] = '';
    }

    static private function initErrorHandler () {
        set_error_handler("\\o\\ErrorHandler::handlePhpRuntimeError");
        register_shutdown_function('\o\Tht::handleShutdown');
    }

    // TODO: public to allow CliMode access.  Revisit this.
    static public function initAppPaths($isSetup=false) {

        // Find the Document Root
        if (Tht::isMode('cli')) {
            if ($isSetup) {
                // TODO: take as argument
                Tht::$paths['docRoot'] = getcwd();
            } else {
                // TODO: travel up parent
                Tht::$paths['docRoot'] = getcwd();
            }
        } else {
            Tht::$paths['docRoot'] = Tht::getPhpGlobal('server', 'DOCUMENT_ROOT');
        }

        $docRootParent = Tht::makePath(Tht::$paths['docRoot'], '..');

        // TODO: clean this up at v1 when paths are locked.
        // Top Level directories.  There is only one now: 'app'
        foreach (['app'] as $topDir) {

            // Read APP_ROOT & DATA_ROOT
            $globalConstant = strtoupper($topDir . '_root');

            if (defined($globalConstant)) {
                $constantPath = constant($globalConstant);
                if ($constantPath[0] === '/') {
                    // absolute path
                    Tht::$paths[$topDir] = realpath($constantPath);
                }
                else {
                    // relative to Document Root
                    Tht::$paths[$topDir] = realpath(Tht::makePath(Tht::$paths['docRoot'], $constantPath));
                }
            } else {
                // Assume it is adjacent to Document Root
                Tht::$paths[$topDir] = Tht::makePath(realpath($docRootParent), Tht::$APP_DIR[$topDir]);
            }

            if (!Tht::$paths[$topDir]) {
                throw new StartupException ("Can't find app directory `$topDir`.\n\n"
                    . "Run: 'tht " . Tht::$CLI_OPTIONS['new'] . "' in your Document Root directory\nto create a new app.\n\n");
            }
        }

        // Define subdirectories
        $dirs = [
            ['app', 'data'],
            ['app', 'pages'],
            ['app', 'modules'],
            ['app', 'config'],
            ['app', 'localTht'],
            ['app', 'misc'],

            ['data', 'db'],
            ['data', 'cache'],
            ['data', 'sessions'],
            ['data', 'files'],
            ['data', 'counter'],

            ['counter', 'counterPage'],
            ['counter', 'counterDate'],
            ['counter', 'counterRef'],

            ['cache', 'phpCache'],
            ['cache', 'kvCache'],

            ['misc', 'scripts'],
            ['misc', 'phpLib'],
        ];

        foreach ($dirs as $d) {
            $parent = $d[0];
            $key = $d[1];
            Tht::$paths[$key] = Tht::path($parent, Tht::$APP_DIR[$key]);
        }

        // Define file paths
        $files = [
            ['config',   'configFile'],
            ['phpCache', 'appCompileTimeFile'],
            ['files',    'logFile'],
        ];

        foreach ($files as $f) {
            $parent = $f[0];
            $key = $f[1];
            Tht::$paths[$key] = Tht::path($parent, Tht::$APP_FILE[$key]);
        }
    }

    static private function initAppConfig () {

        Tht::module('Perf')->start('tht.initAppConfig');

        $defaultConfig = Tht::getDefaultConfig();

        $iniPath = Tht::path('configFile');

        // TODO: cache as JSON?
        $appConfig = Tht::module('Jcon')->u_parse_file(Tht::$APP_FILE['configFile']);

        // make sure the required top-level keys exist
        foreach (['tht', 'routes'] as $key) {
            if (!isset($appConfig[$key])) {
                Tht::configError("Missing top-level key `$key` in `" . Tht::$paths['configFile'] . "`.", $appConfig);
            }
        }

        // check for invalid keys in 'tht' section
        $mainKey = 'tht';
        $def = Tht::getDefaultConfig();
        foreach (uv($appConfig[$mainKey]) as $k => $v) {
            if (!isset($def[$mainKey][$k])) {
                Tht::configError("Unknown config key `$mainKey.$k` in `" . Tht::$paths['configFile'] . "`.");
            }
        }

        Tht::$data['config'] = $appConfig;

        Tht::module('Perf')->u_stop();
    }

    static private function getDefaultConfig () {

        $default = [];

        $default['routes'] = [
            '/' => '/home'
        ];

        $default['tht'] = [

            // internal
            "_devPrint"        => false,
            "_disablePhpCache" => false,
            "_showPhpInTrace"  => false,
            "_lintPhp"         => true,
            "_phpErrors"       => false,

            // temporary - still working on it
            "tempParseCss"     => false,

            // features
            "showPerfScore"        => false,
            "showPerfComment"      => true,
            "disableFormatChecker" => false,
            "minifyCssTemplates"   => true,
            "minifyJsTemplates"    => true,
            "compressOutput"       => true,
            "sessionDurationMins"  => 120,
            'hitCounter'           => true,

            // security
            "allowFileAccess"         => false,
            "allowFileUploads"        => false,
            "contentSecurityPolicy"   => '',
            "showErrorPageForMins"    => 30,
            "dangerDangerAllowJsEval" => false,

            // misc
            "cacheGarbageCollectRate" => 100,

            // resource limits
            "maxPostSizeMb"        => 2,
            "memoryLimitMb"        => 16,
            'maxExecutionTimeSecs' => 20, // starts at request start, lasts until execution ends
            'maxInputTimeSecs'     => 10, // starts at request start, ends when request received, execution starts

            // server
            "timezone" => 'GMT',

            'downtime' => ''
        ];

        return $default;
    }

    static private function initHttpRequestHeaders() {

        $headers = [];

        // Convert http headers to standard kebab-case
        foreach (Tht::getPhpGlobal('server', '*') as $k => $v) {
            if (substr($k, 0, 5) === 'HTTP_') {
                $base = substr($k, 5);
                $base = str_replace('_', '-', strtolower($base));
                $headers[$base] = $v;
            }
        }

        // Correct spelling of "referrer"
        if (isset($headers['referer'])) {
            $headers['referrer'] = $headers['referer'];
            unset($headers['referer']);
        }

        Tht::$data['requestHeaders'] = Security::filterRequestHeaders($headers);
    }






    // MISC
    //---------------------------------------------

    static function loadLib ($file) {
        $libDir = dirname(__FILE__);
        require_once(Tht::makePath($libDir, $file));
    }

    static function parseTemplateString ($type, $lRawText) {
        $rawText = OLockString::getUnlocked($lRawText, $type);
        $tsr = new TemplateStringReader ($type, $rawText);
        return $tsr->parse();
    }


    // GETTERS
    //---------------------------------------------

    static function isMode($m) {
        return Tht::$mode[$m];
    }

    static function data($k, $subKey='') {
        $d = Tht::$data[$k];
        if ($subKey && $subKey !== '*') {
            return isset($d[$subKey]) ? $d[$subKey] : '';
        }
        if (is_array($d) && $subKey !== '*') {
            Tht::error("Need to specify a subKey for array data: `$k`");
        }
        return $d;
    }

    static function getExt() {
        return Tht::$SRC_EXT;
    }



    // PATH GETTERS
    //---------------------------------------------
    // TODO: some of these are redundant with File module methods

    static function path() {
        $parts = func_get_args();
        $base = $parts[0];
        if (!isset(Tht::$paths[$base])) {
            Tht::error("Unknown base path key: `$base`");
        }
        $parts[0] = Tht::$paths[$base];
        return Tht::makePath($parts);
    }

    static function getAppFileName($key) {
        return Tht::$APP_FILE[$key];
    }

    static function getAllPaths() {
        return Tht::$paths;
    }

    static function validatePath($path) {
        if (strpos('..', $path) !== false) {
            Tht::error("Parent shortcut `..` not allowed in path: `$path`");
        }
        return true;
    }

    static function makePath () {
        $args = func_get_args();
        if (is_array($args[0])) { $args = $args[0]; }
        $sep = DIRECTORY_SEPARATOR;
        $path = implode($sep, $args);
        $path = str_replace($sep . $sep, $sep, $path); // prevent double slashes
        $path = rtrim($path, '/');

        Tht::validatePath($path);

        return $path;
    }

    static function getRelativePath ($baseKey, $fullPath) {
        $basePath = Tht::path($baseKey);
        $rel = str_replace(realpath($basePath), '', $fullPath);
        Tht::validatePath($fullPath);
        return ltrim($rel, '/');
    }

    static function getFullPath ($file) {
        if (substr($file, 0, 1) === DIRECTORY_SEPARATOR) {  return $file;  }
        return Tht::makePath(realpath(getcwd()), $file);
    }

    static function getPhpPathForTht ($thtFile) {
        $relPath = Tht::module('File')->u_relative_path($thtFile, Tht::$paths['app']);
        $cacheFile = preg_replace('/[\\/]/', '_', $relPath);
        return Tht::path('phpCache', Tht::getThtVersion(true) . '_' . $cacheFile . '.php');
    }

    static function getThtPathForPhp ($phpPath) {
        $phpPath = preg_replace('!\d{6}_!', '', $phpPath);
        $f = basename($phpPath);
        $f = preg_replace('/\.php/', '', $f);
        $f = str_replace('_', '/', $f);
        return Tht::path('app', $f);
    }

    static function getThtFileName ($fileBaseName) {
        return $fileBaseName . '.' . Tht::$SRC_EXT;
    }



    // MISC GETTERS
    //---------------------------------------------

    static function getThtSiteUrl($relPath) {
        return Tht::$THT_SITE . $relPath;
    }

    static function getTopConfig() {
        $args = func_get_args();
        if (is_array($args[0])) { $args = $args[0]; }
        $val = Tht::searchConfig(Tht::$data['config'], $args);
        if ($val === null) {
            $val = Tht::searchConfig(Tht::getDefaultConfig(), $args);
            if ($val === null) {
                throw new StartupException ('No config for key: `' . implode($args, '.') . '`');
            }
        }
        return $val;
    }

    static function getConfig () {
        $args = func_get_args();
        array_unshift($args, 'tht');
        return Tht::getTopConfig($args);
    }

    static function searchConfig($config, $keys) {
        $ref = $config;
        while (count($keys)) {
            $key = array_shift($keys);
            if (! isset($ref[$key])) {
                return null;
            }
            $ref = $ref[$key];
        }
        return $ref;
    }

    static function getAllConfig () {
        return Tht::$data['config'];
    }

    static function getPhpGlobal ($g, $key) {

        if (!isset(Tht::$data['requestData'][$g])) {
            return $def;
        }
        $val = Tht::$data['requestData'][$g];

        if ($key !== '*') {
            if (!isset($val[$key])) {
                return '';
            }
            $val = $val[$key];
        }

        $val = Security::sanitizeInputString($val);

        return $val;
    }

    static function getWebRequestHeader ($key) {
        return Tht::data('requestHeaders', $key);
    }

    static function getThtVersion($token=false) {
        return $token ? Tht::$VERSION_TOKEN : Tht::$VERSION;
    }

    static function module ($name) {
        return ModuleManager::getModule($name);
    }

    // TODO: this is a bit messy, and user module names have duplication
    static function cleanPackageName($p) {
        $p = preg_replace('/\\\\+/', '/', $p);

        $parts = explode('/', $p);
        $name = array_pop($parts);
        $name = unu_($name);
        $parts []= ucfirst($name);
        $p = implode('/', $parts);
        $p = str_replace('o/', 'std/', $p);
        $p = str_replace('tht/', '', $p);
        return $p;
    }
}

