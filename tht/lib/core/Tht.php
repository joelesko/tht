<?php

namespace o;

class Tht {

    static private $VERSION = '0.5.1 - Beta';
    static private $VERSION_TOKEN = '00501';
    static private $VERSION_TOKEN_PHP = '';

    static private $SRC_EXT = 'tht';

    static private $THT_SITE = 'https://tht-lang.org';
    static private $ERROR_API_URL = 'https://tht-lang.org/remote/error';

    static private $MEMORY_BUFFER_KB = 1;

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
        'settings'  =>   'settings',

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
        'settingsFile'        => 'app.jcon',
        'appCompileTimeFile'  => '_appCompileTime',
        'logFile'             => 'app.log',
        'frontFile'           => 'thtApp.php',
        'homeFile'            => 'home.tht',
    ];




    // MAIN FLOW
    //---------------------------------------------

    static function start () {
        try {
            return self::main();
        }
        catch (StartupError $e) {
            ErrorHandler::handleStartupError($e);
        }
        catch (\Exception $e) {
            ErrorHandler::handleLeakedPhpRuntimeError($e);
        }
    }

    static private function main () {

        self::checkRequirements();

        self::includeLibs();
        self::initMode();

        if (self::isMode('cli')) {
            self::loadLib('modes/CliMode.php');
            self::initErrorHandler();

            CliMode::main();
        }
        else {

            if (self::serveStaticFile()) {
                return false;
            }

            self::loadLib('modes/WebMode.php');
            self::init();

            WebMode::main();
        }

        self::printPerf();

        return true;
    }

    static private function checkRequirements() {

        if (PHP_VERSION_ID < 70100) {
            print('THT Startup Error: PHP version 7.1+ is required.');
            exit();
        }
        else if (!extension_loaded('mbstring')) {
            print('THT Startup Error: PHP extension `mbstring` is required.');
            exit();
        }
    }

    // Includes take 0.25ms
    // TODO: can cut in half or more by consolidating all these to 1 file.
    static private function includeLibs() {

        self::loadLib('utils/Utils.php');
        self::loadLib('utils/StringReader.php');
        self::loadLib('utils/Minifier.php');  // TODO: lazy load this

        self::loadLib('runtime/HitCounter.php');  // TODO: lazy load this
        self::loadLib('runtime/PrintBuffer.php');
        self::loadLib('runtime/ErrorHandler.php');
        self::loadLib('runtime/Compiler.php');
        self::loadLib('runtime/Runtime.php');
        self::loadLib('runtime/ModuleManager.php');
        self::loadLib('runtime/Security.php');

        self::loadLib('../classes/_index.php');
        self::loadLib('../modules/_index.php');
    }

    // Serve directly if requested a static file in testServer mode
    static private function serveStaticFile() {

        if (self::isMode('testServer')) {

            // Dotted filename
            if (preg_match('/\.[a-z0-9]{2,}$/', $_SERVER['SCRIPT_NAME'])) {
                return true;
            }

            // Need to construct path manually.
            // See: https://github.com/joelesko/tht/issues/2
            $path = $_SERVER["DOCUMENT_ROOT"] . $_SERVER['SCRIPT_NAME'];
            if ($_SERVER['SCRIPT_NAME'] !== '/' && file_exists($path)) {
                if (is_dir($path)) {
                    // just a warning
                    self::startupError("Path `$path` can not be a page and also a directory under Document Root.");
                }
                // is a static file
                if (!is_dir($path)) {
                    return true;
                }
            }
        }
        return false;
    }

    static public function exitScript($code) {
        if (!$code) {
            self::printPerf();
        }
        exit($code);
    }

    static public function handleShutdown() {
        self::clearMemoryBuffer();
        ErrorHandler::handleShutdown();
        self::module('Response')->endGzip();
    }

    static private function init () {

        self::initMemoryBuffer();
        self::initErrorHandler();
        self::initRequestData();
        self::initAppPaths();
        self::initAppConfig();

        Security::initPhpIni();
    }

    static function executePhp ($phpPath) {
        try {
            require_once($phpPath);
        } catch (ThtError $e) {
            ErrorHandler::handleThtRuntimeError($e, $phpPath);
        }
    }


    // ERROR / DEBUG
    //---------------------------------------------

    static private function printPerf () {
        if (self::isMode('web') && !self::module('Request')->u_is_ajax() && self::module('Response')->sentResponseType == 'html') {
            self::module('Perf')->printResults();
        }
    }

    static function debug () {
        OBare::u_print(...func_get_args());
    }

    static function errorLog ($msg) {
        $msg = trim($msg);
        if (!$msg) { return; }
        $msg = preg_replace("/\n{3,}/", "\n\n", $msg);
        if (strpos($msg, "\n") !== false) {
            $msg = ltrim(v($msg)->u_indent(2));
        }
        $line = '[' . strftime('%Y-%m-%d %H:%M:%S') . "]  " . $msg . "\n";
        file_put_contents(self::path('logFile'), $line, FILE_APPEND);
    }

    static function error ($msg, $vars=null) {
        $msg = self::errorVars($msg, $vars);
        throw new ThtError ($msg);
    }

    static function configError ($msg, $vars=null) {
        $msg = self::errorVars($msg, $vars);
        ErrorHandler::handleConfigError($msg);
    }

    static function startupError ($msg, $vars=null) {
        throw new StartupError ($msg);
    }

    static private function errorVars($msg, $vars) {
        if (!is_null($vars)) {
            $msg .= "\n\nGot: " . self::module('Json')->u_format(json_encode($vars));
        }
        return $msg;
    }





    // INITS
    //---------------------------------------------

    static private function initRequestData() {
        self::$data['requestData'] = Security::initRequestData();
    }

    static private function initMode() {
        $sapi = php_sapi_name();
        self::$mode['testServer'] = $sapi === 'cli-server';
        self::$mode['cli'] = $sapi === 'cli';
        self::$mode['web'] = !self::$mode['cli'];
    }

    static private function initMemoryBuffer() {
        // Reserve memory in case of out-of-memory error. Enough to call handleShutdown.
        self::$data['memoryBuffer'] = str_repeat('*', self::$MEMORY_BUFFER_KB * 1024);
    }

    static private function clearMemoryBuffer() {
        self::$data['memoryBuffer'] = '';
    }

    static private function initErrorHandler () {
        set_error_handler("\\o\\ErrorHandler::handlePhpRuntimeError");
        register_shutdown_function('\o\Tht::handleShutdown');
    }

    // TODO: public to allow CliMode access.  Revisit this.
    static public function initAppPaths($isSetup=false) {

        // Find the Document Root
        if (self::isMode('cli')) {
            if ($isSetup) {
                // TODO: take as argument
                self::$paths['docRoot'] = getcwd();
            } else {
                // TODO: travel up parent
                self::$paths['docRoot'] = getcwd();
            }
        } else {
            self::$paths['docRoot'] = self::getPhpGlobal('server', 'DOCUMENT_ROOT');
        }

        $docRootParent = self::makePath(self::$paths['docRoot'], '..');

        // TODO: clean this up at v1 when paths are set.
        // Top Level directories.  There is only one now: 'app'
        foreach (['app'] as $topDir) {

            // Read APP_ROOT & DATA_ROOT
            $globalConstant = strtoupper($topDir . '_root');

            if (defined($globalConstant)) {
                $constantPath = constant($globalConstant);
                if ($constantPath[0] === '/') {
                    // absolute path
                    self::$paths[$topDir] = realpath($constantPath);
                }
                else {
                    // relative to Document Root
                    self::$paths[$topDir] = realpath(self::makePath(self::$paths['docRoot'], $constantPath));
                }
            } else {
                // Assume it is adjacent to Document Root
                self::$paths[$topDir] = self::makePath(realpath($docRootParent), self::$APP_DIR[$topDir]);
            }

            if (!self::$paths[$topDir]) {
                self::startupError("Can't find app directory `$topDir`.\n\n"
                    . "Run: 'tht " . self::$CLI_OPTIONS['new'] . "' in your Document Root directory\nto create a new app.\n\n");
            }
        }

        // Define subdirectories
        $dirs = [
            ['app', 'data'],
            ['app', 'pages'],
            ['app', 'modules'],
            ['app', 'settings'],
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
            self::$paths[$key] = self::path($parent, self::$APP_DIR[$key]);
        }

        // Define file paths
        $files = [
            ['settings', 'settingsFile'],
            ['phpCache', 'appCompileTimeFile'],
            ['files',    'logFile'],
        ];

        foreach ($files as $f) {
            $parent = $f[0];
            $key = $f[1];
            self::$paths[$key] = self::path($parent, self::$APP_FILE[$key]);
        }
    }

    static private function initAppConfig () {

        self::module('Perf')->start('tht.initAppConfig');

        $defaultConfig = self::getDefaultConfig();

        $iniPath = self::path('settingsFile');

        // TODO: cache as JSON?
        $appConfig = self::module('Jcon')->u_parse_file(self::$APP_FILE['settingsFile']);

        // make sure the required top-level keys exist
        foreach (['tht', 'routes'] as $key) {
            if (!isset($appConfig[$key])) {
                self::configError("Missing top-level key `$key` in `" . self::$paths['settingsFile'] . "`.", $appConfig);
            }
        }

        // check for invalid keys in 'tht' section
        $mainKey = 'tht';
        $def = self::getDefaultConfig();
        foreach (uv($appConfig[$mainKey]) as $k => $v) {
            if (!isset($def[$mainKey][$k])) {
                if ($mainKey == 'tht') {
                    ErrorHandler::setErrorDoc('/reference/app-settings', 'App Settings');
                }
                self::configError("Unknown settings key `$mainKey.$k` in `" . self::$paths['settingsFile'] . "`.");
            }
        }

        self::$data['config'] = $appConfig;

        self::module('Perf')->u_stop();
    }

    static private function getDefaultConfig () {

        $default = [];

        $default['routes'] = [
            '/' => '/home'
        ];

        $default['tht'] = [

            // internal
            "_devPrint"     => false,
            "_coreDevMode"  => false,
            "_sendErrorUrl" => THT::$ERROR_API_URL,

            // WIP - still working on it
            "tempParseCss"     => false,

            // features
            "adminIp"                => '',
            "showPerfPanel"          => false,
            "disableFormatChecker"   => false,
            "minifyCssTemplates"     => true,
            "minifyJsTemplates"      => true,
            "compressOutput"         => true,
            "sessionDurationMins"    => 120,
            'hitCounter'             => true,
            'hitCounterExcludePaths' => [],

            // telemetry
            'sendErrors'           => true,

            // security
            "contentSecurityPolicy"   => '',
            "showErrorPageForMins"    => 10,
            "dangerDangerAllowJsEval" => false,

            // misc
            "cacheGarbageCollectRate" => 100,
            "logErrors"               => true,

            // resource limits
            "memoryLimitMb"        => 16,
            'maxExecutionTimeSecs' => 20, // starts at request start, lasts until execution ends
            'maxInputTimeSecs'     => 10, // starts at request start, ends when request received, execution starts

            // server
            "timezone" => 'UTC',

            'downtime' => ''
        ];

        return $default;
    }




    // MISC
    //---------------------------------------------

    static function loadLib ($file) {
        $libDir = dirname(__FILE__);
        require_once(self::makePath($libDir, $file));
    }

    static function parseTemplateString ($type, $lRawText) {
        $rawText = OTypeString::getUntyped($lRawText, $type);
        $tsr = new TemplateStringReader ($type, $rawText);
        return $tsr->parse();
    }


    // GETTERS
    //---------------------------------------------

    static function isMode($m) {
        return self::$mode[$m];
    }

    static function data($k, $subKey='') {
        $d = self::$data[$k];
        if ($subKey && $subKey !== '*') {
            return isset($d[$subKey]) ? $d[$subKey] : '';
        }
        if (is_array($d) && $subKey !== '*') {
            self::error("Need to specify a subKey for array data: `$k`");
        }
        return $d;
    }

    static function getExt() {
        return self::$SRC_EXT;
    }



    // PATH GETTERS
    //---------------------------------------------
    // TODO: some of these are redundant with File module methods

    static function path() {
        $parts = func_get_args();
        $base = $parts[0];
        if (!isset(self::$paths[$base])) {
            self::error("Unknown base path key: `$base`");
        }
        $parts[0] = self::$paths[$base];
        return self::makePath($parts);
    }

    static function getAppFileName($key) {
        return self::$APP_FILE[$key];
    }

    static function getAllPaths() {
        return self::$paths;
    }

    static function validatePath($path) {
        if (strpos('..', $path) !== false) {
            self::error("Parent shortcut `..` not allowed in path: `$path`");
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

        self::validatePath($path);

        return $path;
    }

    static function getRelativePath ($baseKey, $fullPath) {
        $basePath = self::path($baseKey);
        $rel = str_replace(realpath($basePath), '', $fullPath);
        self::validatePath($fullPath);
        return ltrim($rel, '/');
    }

    static function getFullPath ($file) {
        if (substr($file, 0, 1) === DIRECTORY_SEPARATOR) {  return $file;  }
        return self::makePath(realpath(getcwd()), $file);
    }

    static function getPhpPathForTht ($thtFile) {
        $relPath = self::module('File')->u_relative_path($thtFile, self::$paths['app']);
        $cacheFile = preg_replace('/[\\/]/', '_', $relPath);
        return self::path('phpCache', self::getThtPhpVersionToken() . '_' . $cacheFile . '.php');
    }

    static function getThtPathForPhp ($phpPath) {
        $f = basename($phpPath);
        $f = preg_replace('!\d+_!', '', $f);
        $f = preg_replace('/\.php/', '', $f);
        $f = str_replace('_', '/', $f);
        return self::path('app', $f);
    }

    static function getThtFileName ($fileBaseName) {
        return $fileBaseName . '.' . self::$SRC_EXT;
    }

    static function stripAppRoot($value) {
        $value = str_replace(self::path('app'), '', $value);
        $value = str_replace(self::path('docRoot'), '', $value);
        if (preg_match('/\.php/', $value)) {
            $value = preg_replace('#.*tht/#', '', $value);
        }

        return ltrim($value, '/');
    }



    // MISC GETTERS
    //---------------------------------------------

    static function getThtSiteUrl($relPath) {
        return self::$THT_SITE . $relPath;
    }

    static function getTopConfig() {
        $args = func_get_args();
        if (is_array($args[0])) { $args = $args[0]; }
        $val = self::searchConfig(self::$data['config'], $args);
        if ($val === null) {
            $val = self::searchConfig(self::getDefaultConfig(), $args);
            if ($val === null) {
                self::startupError('No config for key: `' . implode($args, '.') . '`');
            }
        }
        return $val;
    }

    static function getConfig () {
        $args = func_get_args();
        array_unshift($args, 'tht');
        return self::getTopConfig($args);
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
        return self::$data['config'];
    }

    static function getPhpGlobal ($g, $key) {

        if (!isset(self::$data['requestData'][$g])) {
            return $def;
        }
        $val = self::$data['requestData'][$g];

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
        return self::data('requestHeaders', $key);
    }

    static function getThtVersion($token=false) {
        return $token ? self::$VERSION_TOKEN : self::$VERSION;
    }

    // Get a token that includes the version of THT and PHP
    static function getThtPhpVersionToken() {
        if (!self::$VERSION_TOKEN_PHP) {
            self::$VERSION_TOKEN_PHP = self::$VERSION_TOKEN . floor(PHP_VERSION_ID / 100);
        }
        return self::$VERSION_TOKEN_PHP;
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

