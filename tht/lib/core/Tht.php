<?php

namespace o;

class Tht {

    static private $VERSION = '0.6.1 - Beta';
    static private $VERSION_TOKEN = '00601';
    static private $VERSION_TOKEN_PHP = '';

    static private $SRC_EXT = 'tht';

    static private $THT_SITE = 'https://tht-lang.org';
    static private $ERROR_API_URL = 'https://tht-lang.org/remote/error';

    static private $startTime = 0;
    static private $didSideloadInit = false;

    static private $data = [
        'requestData'    => [],
        'config'         => [],
    ];

    static private $mode = [
        'cli'         => false,
        'testServer'  => false,
        'web'         => false,
        'fileSandbox' => false,
        'sideload'    => false,
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

    static public function sideloadMain() {
        if (self::$didSideloadInit) {
            return;
        }
        self::$mode['sideload'] = true;
        self::$didSideloadInit = true;
        self::main();
    }

    static public function main() {

        self::checkRequirements();
        self::initLibs();
        self::initMode();

        $fn = function() {

            Tht::init();

            if (Tht::isMode('sideload')) {
                // Init only
                return true;
            }
            else if (Tht::isMode('cli')) {
                CliMode::main();
                return true;
            }
            else {
                return WebMode::main();
            }
        };

        return ErrorHandler::catchErrors($fn);
    }

    static private function init () {

        if (self::isMode('cli')) {
            self::loadLib('modes/CliMode.php');
        }
        else {
            self::initRequestData();
            self::initAppPaths();
            self::initAppConfig();

            Security::initPhpIni();

            self::loadLib('modes/WebMode.php');
        }
    }

    static private function checkRequirements() {

        if (PHP_VERSION_ID < 70100) {
            print('THT Startup Error: PHP version 7.1+ is required.  Current: ' . phpversion());
            exit();
        }
        else if (!extension_loaded('mbstring')) {
            print('THT Startup Error: PHP extension `mbstring` is required. (<a href="https://askubuntu.com/questions/491629/how-to-install-php-mbstring-extension-in-ubuntu">help</a>)');
            exit();
        }
    }

    // Includes take 0.25ms
    // TODO: can cut in half or more by consolidating all these to 1 file.
    static private function initLibs() {

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

    static public function exitScript($code) {
        if (self::isMode('web') && !$code) {
            WebMode::printPerf();
        }
        exit($code);
    }

    static public function handleShutdown() {
        ErrorHandler::handleShutdown();
        self::module('Response')->endGzip();
    }

    static public function executePhp ($phpPath) {
        try {
            require_once($phpPath);
        } catch (ThtError $e) {
            ErrorHandler::handleThtRuntimeError($e, $phpPath);
        }
    }


    // SIDELOAD INTERFACE
    //---------------------------------------------

    static public function catchPreThtError() {
        $ob = ob_get_clean();
        if (headers_sent($atFile, $atLine) || $ob) {
            if (!$atFile) { $atFile = ''; }
            if (!$atLine) { $atLine = 0; }
            $e = error_get_last();
            if ($e) {
                ErrorHandler::handlePhpRuntimeError($e['type'], $e['message'], $e['file'], $e['line']);
            } else if ($ob) {
                $ob = substr($ob, 0, 200);
                ErrorHandler::handlePhpRuntimeError(0, "Unexpected output sent before THT page started: `$ob...`", $atFile, $atLine);
            } else {
                ErrorHandler::handlePhpRuntimeError(0, 'Unexpected output sent before THT page started.', $atFile, $atLine);
            }
        }
    }

    static public function sideloadPage($pageUrl) {
        self::sideloadMain();
        self::catchPreThtError();
        $fnRun = function() use ($pageUrl) {
            WebMode::runRoute($pageUrl);
        };
        ErrorHandler::catchErrors($fnRun);
        exit(0);
    }

    static public function sideloadModule($mod) {
        self::sideloadMain();
        return \o\ModuleManager::getModule($mod, true);
    }


    // ERROR / DEBUG
    //---------------------------------------------

    static public function debug () {
        Tht::module('*Bare')->u_print(...func_get_args());
    }

    static public function errorLog ($msg) {
        $msg = trim($msg);
        if (!$msg) { return; }
        $msg = preg_replace("/\n{3,}/", "\n\n", $msg);
        if (strpos($msg, "\n") !== false) {
            $msg = ltrim(v($msg)->u_indent(2));
        }
        $line = '[' . strftime('%Y-%m-%d %H:%M:%S') . "]  " . $msg . "\n";
        file_put_contents(self::path('logFile'), $line, FILE_APPEND);
    }

    static public function error ($msg, $vars=null) {
        $msg = self::errorVars($msg, $vars);
        throw new ThtError ($msg);
    }

    static public function configError ($msg, $vars=null) {
        $msg = self::errorVars($msg, $vars);
        ErrorHandler::handleConfigError($msg);
    }

    static public function startupError ($msg, $vars=null) {
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
        $appConfig = self::module('Jcon')->u_parse_file(self::$APP_FILE['settingsFile']);
        self::validateAppConfig($appConfig);
        self::$data['config'] = $appConfig;

        self::module('Perf')->u_stop();
    }

    static private function validateAppConfig($appConfig) {
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

            'downtime' => '',

            'turboMode' => false,
        ];

        return $default;
    }




    // MISC
    //---------------------------------------------

    static public function loadLib ($file) {
        $libDir = dirname(__FILE__);
        require_once(self::makePath($libDir, $file));
    }

    static public function parseTemplateString ($type, $lRawText) {
        $rawText = OTypeString::getUntyped($lRawText, $type);
        $tsr = new TemplateStringReader ($type, $rawText);
        return $tsr->parse();
    }


    // GETTERS
    //---------------------------------------------

    static public function isMode($m) {
        return self::$mode[$m];
    }

    static public function data($k, $subKey='') {
        $d = self::$data[$k];
        if ($subKey && $subKey !== '*') {
            return isset($d[$subKey]) ? $d[$subKey] : '';
        }
        if (is_array($d) && $subKey !== '*') {
            self::error("Need to specify a subKey for array data: `$k`");
        }
        return $d;
    }

    static public function getExt() {
        return self::$SRC_EXT;
    }

    static public function getInfoDump() {
        $copy = self::$data;
        $copy['tht'] = [
            'version' => self::getThtVersion(),
            'appPath' => self::path('app'),
            'dataPath' => self::path('data'),
            // TODO: tht page
            // TODO: entry function (auto-call in WebMode)
        ];
        return $copy;
    }



    // PATH GETTERS
    //---------------------------------------------
    // TODO: some of these are redundant with File module methods

    static public function path() {
        $parts = func_get_args();
        $base = $parts[0];
        if (!isset(self::$paths[$base])) {
            self::error("Unknown base path key: `$base`");
        }
        $parts[0] = self::$paths[$base];
        return self::makePath($parts);
    }

    static public function getAppFileName($key) {
        return self::$APP_FILE[$key];
    }

    static public function getAllPaths() {
        return self::$paths;
    }

    static public function validatePath($path) {
        if (strpos('..', $path) !== false) {
            self::error("Parent shortcut `..` not allowed in path: `$path`");
        }
        return true;
    }

    static public function makePath () {
        $args = func_get_args();
        if (is_array($args[0])) { $args = $args[0]; }
        $sep = DIRECTORY_SEPARATOR;
        $path = implode($sep, $args);
        $path = str_replace($sep . $sep, $sep, $path); // prevent double slashes
        $path = rtrim($path, '/');

        self::validatePath($path);

        return $path;
    }

    static public function getRelativePath ($baseKey, $fullPath) {
        $basePath = self::path($baseKey);
        $rel = str_replace(realpath($basePath), '', $fullPath);
        self::validatePath($fullPath);
        return ltrim($rel, '/');
    }

    static public function getFullPath ($file) {
        if (substr($file, 0, 1) === DIRECTORY_SEPARATOR) {  return $file;  }
        return self::makePath(realpath(getcwd()), $file);
    }

    static public function getPhpPathForTht ($thtFile) {
        $relPath = self::module('File')->u_relative_path($thtFile, self::$paths['app']);
        $cacheFile = preg_replace('/[\\/]/', '_', $relPath);
        return self::path('phpCache', self::getThtPhpVersionToken() . '_' . $cacheFile . '.php');
    }

    static public function getThtPathForPhp ($phpPath) {
        $f = basename($phpPath);
        $f = preg_replace('!\d+_!', '', $f);
        $f = preg_replace('/\.php/', '', $f);
        $f = str_replace('_', '/', $f);
        return self::path('app', $f);
    }

    static public function getThtFileName ($fileBaseName) {
        return $fileBaseName . '.' . self::$SRC_EXT;
    }

    static public function stripAppRoot($value) {
        $value = str_replace(self::path('app'), '', $value);
        $value = str_replace(self::path('docRoot'), '', $value);
        if (preg_match('/\.php/', $value)) {
            $value = preg_replace('#.*tht/#', '', $value);
        }

        return ltrim($value, '/');
    }



    // MISC GETTERS
    //---------------------------------------------

    static public function getThtSiteUrl($relPath) {
        return self::$THT_SITE . $relPath;
    }

    static public function getTopConfig() {
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

    static public function getConfig () {
        $args = func_get_args();
        array_unshift($args, 'tht');
        return self::getTopConfig($args);
    }

    static public function searchConfig($config, $keys) {
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

    static public function getAllConfig () {
        return self::$data['config'];
    }

    static public function getPhpGlobal ($g, $key, $def='') {

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

    static public function getThtVersion($token=false) {
        return $token ? self::$VERSION_TOKEN : self::$VERSION;
    }

    // Get a token that includes the version of THT and PHP
    static public function getThtPhpVersionToken() {
        if (!self::$VERSION_TOKEN_PHP) {
            self::$VERSION_TOKEN_PHP = self::$VERSION_TOKEN . floor(PHP_VERSION_ID / 100);
        }
        return self::$VERSION_TOKEN_PHP;
    }

    static public function module ($name) {
        return ModuleManager::getModule($name);
    }

    // TODO: This is a bit messy, and user module names have duplication.
    //       Should put in Utils / Consolidate with ErrorHandlerOutput cleanup functions.
    static public function cleanPackageName($p) {
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



