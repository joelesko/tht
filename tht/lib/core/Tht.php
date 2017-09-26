<?php

namespace o;

class Tht {

    static private $VERSION = '0.1.3 - Beta';
    static private $SRC_EXT = 'tht';

    static private $THT_SITE = 'https://tht.help';

    static private $data = [
        'phpGlobals'     => [],
        'settings'       => [],
        'memoryBuffer'   => ''
    ];

    static private $mode = [
        'cli'        => false,
        'testServer' => false,
        'web'        => false
    ];

    static private $paths = [];
    static private $files = [];

    static private $DIR = [

        'root'      => 'tht',

        'pages'     =>   'pages',
        'modules'   =>   'modules',
        'settings'  =>   'settings',
        'scripts'   =>   'scripts',
        // 'phpLib'    =>   'php',

        'data'      => 'data',
        'db'        =>   'db',
        'uploads'   =>   'uploads',
        'logs'      =>   'logs',
        'cache'     =>   'cache',
        'phpCache'  =>     'php',
        'kvCache'   =>     'keyValue',
        'fileCache' =>     'fileCache',
        'files'     =>   'files',
        'counter'   =>   'counter',
        'counterPage' =>    'page',
        'counterDate' =>    'date',
    ];

    static private $FILE = [
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

    static private function includeLibs() {
        Tht::loadLib('ErrorHandler.php');
        Tht::loadLib('Source.php');
        Tht::loadLib('StringReader.php');
        Tht::loadLib('Runtime.php');

        Tht::loadLib('../classes/_index.php');
        Tht::loadLib('../modules/_index.php');
    }

    static private function main () {

        Tht::includeLibs();

        Tht::initMode();

        // Serve static file directly and exit
        if (Tht::isMode('testServer')) {
            // Need to construct path manually.
            // See: https://github.com/joelesko/tht/issues/2
            $path = $_SERVER["DOCUMENT_ROOT"] . $_SERVER['SCRIPT_NAME'];
            if ($_SERVER["REQUEST_URI"] !== "/" && file_exists($path)) {
                return false;
            }
        }

        if (Tht::isMode('cli')) {
            Tht::loadLib('modes/CliMode.php');
            CliMode::main();
        }
        else {
            
            Tht::loadLib('modes/WebMode.php');
            Tht::init();
            WebMode::main();
        }

        Tht::endScript();

        return true;
    }

    static private function endScript () {
        Tht::module('Perf')->printResults();
    }

    static public function handleShutdown() {
        Tht::clearMemoryBuffer();
    }

    static private function init () {
        Tht::initScalarData();
        Tht::initErrorHandler();
        Tht::initPhpGlobals();
        Tht::initPaths();
        Tht::initConfig();
        Tht::initPhpIni();
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
            $msg .= "\n\nGot: "  . Tht::module('Json')->u_format(json_encode($vars));
        }
        return $msg;
    }




    // INITS
    //---------------------------------------------

    static private function initMode() {
        $sapi = php_sapi_name();
        Tht::$mode['testServer'] = $sapi === 'cli-server';
        Tht::$mode['cli'] = $sapi === 'cli';
        Tht::$mode['web'] = !Tht::$mode['cli'];
    }

    static private function initScalarData() {
        // Reserve memory in case of out-of-memory error. Enough to call handleShutdown.
        Tht::$data['memoryBuffer'] = str_repeat('*', 128 * 1024);
    }

    static private function initErrorHandler () {
        set_error_handler("\\o\\ErrorHandler::handlePhpRuntimeError");
        register_shutdown_function('\\o\\handleShutdown');
    }

    // TODO: public to allow Cli access.  Revisit this.
    static public function initPaths($isSetup=false) {

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

        // TODO: clarify app root constant name (appRoot vs docRootParent etc)
        $docRootParent = Tht::makePath(Tht::$paths['docRoot'], '..');
        $thtAppRoot = defined('APP_ROOT') ? constant('APP_ROOT') : $docRootParent;
        Tht::$paths['root'] = Tht::makePath(realpath($docRootParent), Tht::$DIR['root']);

        if (!Tht::$paths['root']) {
            Tht::startupError("App has not been setup yet.\n\n"
                . "Run: 'tht " . Tht::$CLI_OPTIONS['new'] . "' in your Document Root directory.");
        }

        // main dirs
        Tht::$paths['appRoot']   = realpath($docRootParent);

        Tht::$paths['data']      = Tht::path('root', Tht::$DIR['data']);
        Tht::$paths['pages']     = Tht::path('root', Tht::$DIR['pages']);
        Tht::$paths['modules']   = Tht::path('root', Tht::$DIR['modules']);
        Tht::$paths['settings']  = Tht::path('root', Tht::$DIR['settings']);
        Tht::$paths['scripts']   = Tht::path('root', Tht::$DIR['scripts']);
        // Tht::$paths['phpLib']    = Tht::path('root', Tht::$DIR['phpLib']);

        // data subdirs
        Tht::$paths['db']          = Tht::path('data', Tht::$DIR['db']);
        Tht::$paths['logs']        = Tht::path('data', Tht::$DIR['logs']);
        Tht::$paths['cache']       = Tht::path('data', Tht::$DIR['cache']);
        Tht::$paths['phpCache']    = Tht::path('cache', Tht::$DIR['phpCache']);
        Tht::$paths['kvCache']     = Tht::path('cache', Tht::$DIR['kvCache']);
        Tht::$paths['files']       = Tht::path('data', Tht::$DIR['files']);
        Tht::$paths['counter']     = Tht::path('data', Tht::$DIR['counter']);
        Tht::$paths['counterPage'] = Tht::path('counter', Tht::$DIR['counterPage']);
        Tht::$paths['counterDate'] = Tht::path('counter', Tht::$DIR['counterDate']);

        // files
        Tht::$paths['configFile']         = Tht::path('settings', Tht::$FILE['configFile']);
        Tht::$paths['appCompileTimeFile'] = Tht::path('phpCache', Tht::$FILE['appCompileTimeFile']);
        Tht::$paths['logFile']            = Tht::path('files',    Tht::$FILE['logFile']);
    }

    static private function initConfig () {

        Tht::module('Perf')->start('tht.initAppConfig');

        $defaultConfig = Tht::getDefaultConfig();

        $iniPath = Tht::path('configFile');

        // TODO: cache as JSON
        $appConfig = Tht::module('Jcon')->u_parse_file(Tht::$FILE['configFile']);

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
                Tht::configError("Unknown settings key `$mainKey.$k` in `" . Tht::$paths['configFile'] . "`.");
            }
        }

        Tht::$data['settings'] = $appConfig;

        Tht::module('Perf')->u_stop();
    }

    // [security] set ini
    static private function initPhpIni () {

        // locale
        date_default_timezone_set(Tht::getConfig('timezone'));
        ini_set('default_charset', 'utf-8');
        mb_internal_encoding('utf-8');

        // logging
        error_reporting(E_ALL);
        ini_set('display_errors', Tht::isMode('cli') ? '1' : (Tht::getConfig('_phpErrors') ? '1' : '0'));
        ini_set('display_startup_errors', '1');
        ini_set('log_errors', '0');  // assume we are logging all errors manually

        // file security
        ini_set('allow_url_fopen', '0');
        ini_set('allow_url_include', '0');

        // limits
        ini_set('max_execution_time', Tht::isMode('cli') ? 0 : intval(Tht::getConfig('maxExecutionTimeSecs')));
        ini_set('max_input_time', intval(Tht::getConfig('maxInputTimeSecs')));
        ini_set('memory_limit', intval(Tht::getConfig('memoryLimitMb')) . "M");


        // Configs that are only set in .ini or .htaccess
        // Trigger an error if PHP is more strict than Tht.
        $thtMaxPostSize = intval(Tht::getConfig('maxPostSizeMb'));
        $phpMaxFileSize = intval(ini_get('upload_max_filesize'));
        $phpMaxPostSize = intval(ini_get('post_max_size'));
        $thtFileUploads = Tht::getConfig('allowFileUploads');
        $phpFileUploads = ini_get('file_uploads');

        if ($thtMaxPostSize > $phpMaxFileSize) {
            Tht::configError("Config `maxPostSizeMb` ($thtMaxPostSize) is larger than php.ini `upload_max_filesize` ($phpMaxFileSize).\n"
                . "You will want to edit php.ini so they match.");
        }
        if ($thtMaxPostSize > $phpMaxPostSize) {
            Tht::configError("Config `maxPostSizeMb` ($thtMaxPostSize) is larger than php.ini `post_max_size` ($phpMaxPostSize).\n"
                . "You will want to edit php.ini so they match.");
        }
        if ($thtFileUploads && !$phpFileUploads) {
            Tht::configError("Config `allowFileUploads` is true, but php.ini `file_uploads` is false.\n"
                . "You will want to edit php.ini so they match.");
        }
    }

    // [security] clear out super globals
    static private function initPhpGlobals () {

        Tht::$data['phpGlobals'] = [
            'get'    => $_GET,
            'post'   => $_POST,
            'cookie' => $_COOKIE,
            'files'  => $_FILES,
            'server' => $_SERVER
        ];

        // Only keep ENV for CLI mode.  In Web mode, config should happen in app.odf.
        if (Tht::isMode('cli')) {
            Tht::$data['phpGlobals']['env'] = $_ENV;
        }

        if (isset($HTTP_RAW_POST_DATA)) {
            Tht::$data['phpGlobals']['post']['_raw'] = $HTTP_RAW_POST_DATA;
            unset($HTTP_RAW_POST_DATA);
        }

        Tht::initHttpRequestHeaders();

        // [security]  remove all php globals
        unset($_ENV);
        unset($_REQUEST);
        unset($_GET);
        unset($_POST);
        unset($_COOKIE);
        unset($_FILES);
        unset($_SERVER);

        $GLOBALS = null;
    }

    static private function initHttpRequestHeaders () {
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (substr($k, 0, 5) === 'HTTP_') {
                $base = substr($k, 5);
                $base = str_replace('_', '-', strtolower($base));
                $headers[$base] = $v;
            }
        }
        if (isset($headers['referer'])) {
            $headers['referrer'] = $headers['referer'];
            unset($headers['referer']);
        }
        Tht::$data['requestHeaders'] = $headers;
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
            "useSession"           => false,
            "disableFormatChecker" => false,
            "minifyCss"            => true,
            "minifyJs"             => true,

            // [security]
            "allowFileAccess"         => false,
            "allowFileUploads"        => false,
            "contentSecurityPolicy"   => '',
            "showErrorPageForMins"    => 30,
            "dangerDangerAllowJsEval" => false,

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






    // MISC
    //---------------------------------------------

    static function loadLib ($file) {
        $libDir = dirname(__FILE__);
        require_once(Tht::makePath($libDir, $file));
    }

    static function sanitizeString ($str) { // [security]
        if (is_array($str)) {
            foreach ($str as $k => $v) {
                $str[$k] = Tht::sanitizeString($v);
            }
        } else if (is_string($str)) {
            $str = str_replace(chr(0), '', $str);  // remove null bytes
            $str = trim($str);
        }
        return $str;
    }

    static function parseTemplateString ($type, $lRawText) {
        $rawText = OLockString::getUnlocked($lRawText);
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
        if ($subKey) {
            return $d[$subKey];
        }
        if (is_array($d)) {
            Tht::error("Need to specify a subKey for array data: `$k`");
        }
        return $d;
    }

    static function clearMemoryBuffer() {
        Tht::$data['memoryBuffer'] = '';
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
        $relPath = Tht::module('File')->u_relative_path($thtFile, Tht::$paths['root']);
        $cacheFile = preg_replace('/[\\/]/', '_', $relPath);
        return Tht::path('phpCache', $cacheFile . '.php');
    }

    static function getThtPathForPhp ($phpPath) {
        $f = basename($phpPath);
        $f = preg_replace('/\.php/', '', $f);
        $f = str_replace('_', '/', $f);
        return Tht::path('root', $f);
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
        $val = Tht::searchConfig(Tht::$data['settings'], $args);
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
        return Tht::$data['settings'];
    }

    static function getPhpGlobal ($g, $key, $def='') {

        if (!isset(Tht::$data['phpGlobals'][$g]) || !isset(Tht::$data['phpGlobals'][$g][$key])) {
            return $def;
        }
        $val = Tht::$data['phpGlobals'][$g][$key];
        $val = Tht::sanitizeString($val);

        return $val;
    }

    static function getThtVersion() {
        return Tht::$VERSION;
    }

    static function module ($name) {
        return Runtime::getModule('', $name);
    }
}

