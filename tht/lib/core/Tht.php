<?php

namespace o;

class Tht {

    static private $VERSION = '0.1.2 - Beta';
    static private $SRC_EXT = 'tht';

    static private $THT_SITE = 'https://tht-lang.org';

    static private $CLI_OPTIONS = [
        'new'    => 'new',
        'server' => 'server',
        'run'    => 'run',
    ];

    static private $data = [
        'phpGlobals'     => [],
        'settings'         => [],
        'routeParams'    => [],
        'requestHeaders' => [],
        'cliOptions'     => [],
        'urls'           => [],
        'cspNonce'       => '',
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

    static private $CONFIG_KEY = [
        'main'   => 'tht',
        'routes' => 'routes',
        'db'     => 'db'
    ];

    static private $HOME_ROUTE = 'home';
    static private $SERVER_PORT = 8888;

    static private $WEB_PRINT_BUFFER = [];



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
            Tht::mainCli();
        }
        else {
            Tht::mainWeb();
        }

        Tht::endScript();

        return true;
    }

    static private function endScript () {
        Tht::module('Perf')->printResults();
    }

    static public function handleShutdown() {
        Tht::clearMemoryBuffer();
        Tht::flushWebPrintBuffer();
    }

    static private function init () {
        Tht::initScalarData();
        Tht::initErrorHandler();
        Tht::initPhpGlobals();
        Tht::initPaths();
        Tht::initConfig();
        Tht::initPhpIni();
    }

    static private function mainCli() {

        Tht::initCliOptions();

        $firstOption = Tht::$data['cliOptions'][0];

        if ($firstOption === Tht::$CLI_OPTIONS['new']) {
            Tht::initPaths(true);
            Tht::installApp();
        }
        else if ($firstOption === Tht::$CLI_OPTIONS['server']) {
            Tht::initPaths(true);
            Tht::startTestServer();  // TODO: support run from tht parent, port
        }
        else if ($firstOption === Tht::$CLI_OPTIONS['run']) {
            Tht::init();
            Source::process(Tht::$data['cliOptions'][1]);
        }
        else {
            echo "\nUnknown argument.\n";
            Tht::printUsage();
        }
    }

    static private function mainWeb () {
        Tht::init();
        Tht::initResponseHeaders();
        Tht::executeWeb();
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

    static private function printUsage() {
        echo "\n{o,o} THT - v" . Tht::$VERSION . "\n";
        echo "\nUsage:\n\n";
        echo "tht new              (create a new app)\n";
        echo "tht server           (start local test server)\n";
     //   echo "tht run <filename>   (run script in scripts directory)\n";
        echo "\n";
        exit(0);
    }

    static public function queueWebPrint($s) {
        Tht::$WEB_PRINT_BUFFER []= $s;
    }

    // Send the output of all print() statements
    static private function flushWebPrintBuffer() {
        if (!count(Tht::$WEB_PRINT_BUFFER)) { return; }

        $zIndex = 99998;  //  one less than error page

        echo "<style>\n";
        echo ".tht-print { white-space: pre; border: 0; border-left: solid 2px #99f; padding: 4px 20px; margin: 4px 0 0;  font-family: " . u_Css::u_monospace_font() ."; }\n";
        echo ".tht-print-panel { position: fixed; top: 0; left: 0; z-index: $zIndex; width: 100%; padding: 20px 40px 30px; font-size: 18px; background-color: rgba(255,255,255,0.98);  -webkit-font-smoothing: antialiased; color: #222; box-shadow: 0 4px 4px rgba(0,0,0,0.15); max-height: 400px; overflow: auto;  }\n";
        echo "</style>\n";

        echo "<div class='tht-print-panel'>\n";
        foreach (Tht::$WEB_PRINT_BUFFER as $b) {
            echo "<div class='tht-print'>" . $b . "</div>\n";
        }
        echo "</div>";
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

        Tht::$data['cspNonce']  = Tht::module('String')->u_random(40);
        Tht::$data['csrfToken'] = Tht::module('String')->u_random(40);
    }

    static private function initCliOptions () {
        global $argv;
        if (count($argv) === 1) {
            Tht::printUsage();
        }
        Tht::$data['cliOptions'] = array_slice($argv, 1);
    }

    static private function initErrorHandler () {
        set_error_handler("\\o\\ErrorHandler::handlePhpRuntimeError");
        register_shutdown_function('\\o\\handleShutdown');
    }

    static private function initPaths($isSetup=false) {

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
        $mainKey = Tht::$CONFIG_KEY['main'];
        $routeKey = Tht::$CONFIG_KEY['routes'];
        foreach ([$mainKey, $routeKey] as $key) {
            if (!isset($appConfig[$key])) {
                Tht::configError("Missing top-level key `$key` in `" . Tht::$paths['configFile'] . "`.", $appConfig);
            }
        }

        // check for invalid keys in 'tht' section
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

        $default[Tht::$CONFIG_KEY['routes']] = [
            '/' => '/home'
        ];

        $default[Tht::$CONFIG_KEY['main']] = [

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




    // INSTALL
    //---------------------------------------------

    static private function confirmInstall() {

        echo "\n";
        echo "+-------------------+\n";
        echo "|      NEW APP      |\n";
        echo "+-------------------+\n";

        if (file_exists(Tht::$paths['root'])) {
            echo "\nAn THT directory already exists:\n  " .  Tht::$paths['root'] . "\n\n";
            echo "To start over, just delete or move that directory. Then rerun this command.\n\n";
            exit(1);
        }

        if (!Tht::module('System')->u_confirm("\nYour Document Root is:\n  " . Tht::$paths['docRoot'] . "\n\nInstall THT app for this directory?")) {
            echo "\nPlease 'cd' to your public Document Root directory.  Then rerun this command.\n\n";
            exit(0);
        }

        usleep(500000);
    }

    static private function installApp () {

        Tht::confirmInstall();

        try {

            // create directory tree
            foreach (Tht::$paths as $id => $p) {
                if (substr($id, -4, 4) === 'File') {
                    touch($p);
                } else {
                    Tht::module('File')->u_make_dir($p, 0755);
                }
            }

            // Front controller
            // TODO: move paths to constants
            $appRoot = '../tht';
            $thtBinPath = realpath($_SERVER['SCRIPT_NAME']);

            Tht::writeSetupFile(Tht::$FILE['frontFile'], "

            <?php

                 define('APP_ROOT', '$appRoot');
                 define('THT_MAIN', '$thtBinPath');

                 return require_once(THT_MAIN);

            ");


            // .htaccess
            // TODO: don't overwrite previous
            Tht::writeSetupFile('.htaccess', "

                # THT App

                DirectoryIndex index.html index.php thtApp.php
                Options -Indexes

                # Redirect all non-static URLs to THT app
                RewriteEngine On
                RewriteCond %{REQUEST_FILENAME} !-f
                RewriteCond %{REQUEST_FILENAME} !-d
                RewriteRule  ^(.*)$ /thtApp.php [QSA,NC,L]

                # Uncomment to redirect to HTTPS
                # RewriteCond %{HTTPS} off
                # RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI}
            ");


            // Starter App
            $exampleFile = 'home.tht';
            $examplePath = Tht::path('pages', $exampleFile);
            $exampleRelPath =  Tht::getRelativePath('appRoot', $examplePath);
            $publicPath = Tht::getRelativePath('appRoot', Tht::path('pages'));
            $exampleCssPath = Tht::path('pages', 'css.tht');

            Tht::writeSetupFile($examplePath, "
                Web.sendPage({
                    title: 'Hello World',
                    body: bodyHtml(),
                    css: '/css',
                });

                template bodyHtml() {

                    <main>
                        <div style='margin: 2em'>

                            <h1>> Hello World
                            <.subline>> {{ Web.icon('check') }}  Congratulations!  The hard part is over.

                            <p>> Add new pages to:<br /> <code>> tht/pages

                            <p>> For example, when you add this file...<br /> <code>> pages/testPage.tht

                            <p>> ... it will automatically become this URL:<br /> <code>> http://yoursite/test-page

                            <p style=\"margin-top: 4rem\">> For more info, see <a href=\"https://tht-lang.org/tutorials/how-to-create-a-basic-web-app\">How to Create a Basic Web App</a>.
                        </>
                    </>
                }
            ");

            Tht::writeSetupFile($exampleCssPath, "

                Web.sendCss(css());

                template css() {

                    {{ Css.include('base', 700) }}

                    body {
                        font-size: 2rem;
                        color: #29296f;
                    }
                    .subline {
                        font-size: 2.5rem;
                        color: #394;
                        margin-bottom: 4rem;
                        margin-top: -3rem;
                        border-bottom: solid 1px #d6d6e6;
                        padding-bottom: 2rem;
                    }
                    code {
                        font-weight: bold;
                    }

                }
            ");

            // Starting config file
            Tht::writeSetupFile(Tht::path('configFile'), "
                {
                    // Dynamic URL routes
                    routes: {
                        // /post/{postId}:  post.tht
                    }

                    // Custom app settings.  Read via `Global.setting(key)`
                    app: {
                        // myVar: 1234
                    }

                    // Core settings
                    tht: {
                        // Server timezone
                        // See: http://php.net/manual/en/timezones.php
                        // Example: America/Los_Angeles
                        timezone: GMT

                        // Print performance timing info
                        showPerfScore: false
                    }

                    // Database settings
                    // See: https://tht-lang.org/manual/module/db
                    databases: {

                        // Default sqlite file in 'data/db'
                        default: {
                            file: app.db
                        }

                        // Other database accessible via 'Db.use'
                        // example: {
                        //     driver: 'mysql', // or 'pgsql'
                        //     server: 'localhost',
                        //     database: 'example',
                        //     username: 'dbuser',
                        //     password: '12345'
                        // }
                    }
                }
            ");

          //  Tht::installDatabases();

        } catch (\Exception $e) {
            echo "Sorry, something went wrong.\n\n";
            echo "  " . $e->getMessage() . "\n\n";
            if (file_exists(Tht::$paths['root'])) {
                echo "Move or delete your app directory before trying again:\n\n  " . Tht::$paths['root'];
                echo "\n\n";
            }
            exit(1);
        }

        echo "\n\n";
        echo "+-------------------+\n";
        echo "|      SUCCESS!     |\n";
        echo "+-------------------+\n\n";

        echo "Your new THT app directory is here:\n  " . Tht::$paths['root'] . "\n\n";
        echo "*  Load 'http://yoursite.com' to see if it's working.\n";
        echo "*  Or run 'tht server' to start a local web server.";
        echo "\n\n";

        exit(0);
    }

    static private function writeSetupFile($name, $content) {
        file_put_contents($name, v($content)->u_trim_indent() . "\n");
    }

    static private function createDbIndex($dbh, $table, $col) {
        $dbh->u_danger_danger_query("CREATE INDEX i_{$table}_{$col} ON $table ($col)");
    }

    static private function installDatabases () {

        $initDb = function ($dbId) {
            $dbFile = $dbId . '.db';
         //   touch(Tht::getAppDataPath($dbFile));
            $dbh = Tht::module('Db')->u_use($dbId);
            return $dbh;
        };

        // app
        $dbh = $initDb('app');

        // cache
        $dbh = $initDb('cache');
        $dbh->u_danger_danger_query("CREATE TABLE cache (
            key VARCHAR(64),
            value TEXT,
            isJson TINYINT DEFAULT 0,
            expireDate UNSIGNED INT
        )");
        Tht::createDbIndex($dbh, 'cache', 'key');
        Tht::createDbIndex($dbh, 'cache', 'expireDate');

        // session
        // $dbh = $initDb('session');
        // $dbh->u_danger_danger_query("CREATE TABLE session (
        //     cookieId VARCHAR(64) PRIMARY KEY,
        //     ip VARCHAR(64),
        //     createTime UNSIGNED INT,
        //     updateTime UNSIGNED INT,
        //     userId UNSIGNED INT DEFAULT 0,
        //     loggedIn UNSIGNED TINYINT DEFAULT 0,
        //     sessionData TEXT
        // )");


        //Tht::module('Database')->u_use('default');

        echo " [OK]\n";
    }




    // WEB
    //---------------------------------------------

    static private function executeWeb () {
        if (Tht::getConfig('downtime')) {
            Tht::downtimePage(Tht::getConfig('downtime'));
        }

        $controllerFile = Tht::initWebRoute();
        if ($controllerFile) {
            Tht::executeWebController($controllerFile);
        }
    }

    static private function downtimePage($file) {
        http_response_code(503);
        $downPage = Tht::module('File')->u_document_path($file);
        if ($file !== true && file_exists($downPage)) {
            print(file_get_contents($downPage));
        }
        else {
            $font = Tht::module('Css')->u_sans_serif_font();
            echo "<div style='padding: 2rem; text-align: center; font-family: $font'><h1>Temporarily Down for Maintenance</h1><p>Sorry for the inconvenience.  We'll be back soon.</p></div>";
        }
        exit(0);
    }

    static public function runStaticRoute($route) {
        $routes = Tht::getTopConfig(Tht::$CONFIG_KEY['routes']);
        if (!isset($routes[$route])) { return false; }
        $file = Tht::path('pages', $routes[$route]);
        Tht::executeWebController($file);
        exit(0);
    }

    static private function getScriptPath() {

        $path = Tht::module('Web')->u_request()['url']['path'];

        // Validate route name
        // all lowercase, no special characters, hyphen separators, no trailing slash
        $pathSize = strlen($path);
        $isTrailingSlash = $pathSize > 1 && $path[$pathSize-1] === '/';
        if (preg_match('/[^a-z0-9\-\/\.]/', $path) || $isTrailingSlash)  {
            Tht::errorLog("Path `$path` is not valid");
            Tht::module('Web')->u_send_error(404);
        }

        return $path;
    }

    static private function getControllerForPath($path) {

        $routes = Tht::getTopConfig(Tht::$CONFIG_KEY['routes']);

        if (isset($routes[$path])) {
            // static path
            return Tht::path('pages', $routes[$path]);
        }
        else {
            $c = Tht::getDynamicController($routes, $path);
            return $c === false ? Tht::getPublicController($path) : $c;
        }
    }

    static private function getDynamicController($routes, $path) {

        // path with dynamic parts
        $pathParts = explode('/', ltrim($path, '/'));
        $numPathParts = count($pathParts);

        $routeTargets = [];

        foreach (uv($routes) as $match => $controllerPath) {
            if (strpos($match, '{') === false) {
                continue;
            }
            $params = [];
            $matchParts = explode('/', ltrim($match, '/'));
            $numMatchParts = count($matchParts);

            $routeTargets[strtolower('/' . $controllerPath)] = true;

            if ($numMatchParts === $numPathParts) {
                $isMatch = true;
                foreach (range(0, $numMatchParts - 1) as $i) {
                    $mPart = $matchParts[$i];
                    if ($mPart[0] === '{' && $mPart[strlen($mPart)-1] === '}') {
                        $token = substr($mPart, 1, strlen($mPart)-2);
                        if (preg_match('/[^a-zA-Z0-9]/', $token)) {
                            Tht::configError("Route placeholder `{$token}` should only"
                                . " contain letters and numbers (no spaces).");
                        }
                        $val = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $pathParts[$i]); // [security]
                        $params[$token] = $val;
                    } else {
                        if ($mPart !== $pathParts[$i]) {
                            $isMatch = false;
                            break;
                        }
                    }
                }
                if ($isMatch) {
                    Tht::$data['routeParams'] = $params;
                    return Tht::path('pages', $controllerPath);
                }
            }
        }

        $camelPath = strtolower(v($path)->u_to_camel_case());
        if (isset($routeTargets[$camelPath]) || $camelPath == '/' . Tht::$HOME_ROUTE) {
            Tht::errorLog("Direct access to route not allowed: `$path`");
            Tht::module('Web')->u_send_error(404);
        }

        return false;
    }

    static private function getPublicController($path) {

        $apath = '';
        if ($path === '/') {
            $apath = Tht::$HOME_ROUTE;
        }
        else {
            // convert dash-case URL to camelCase file path
            $parts = explode('/', $path);
            $camelParts = [];
            foreach ($parts as $p) {
                $camelParts []= v($p)->u_to_camel_case();
            }
            $camelParts []= array_pop($camelParts);
            $apath = implode('/', $camelParts);
        }

        $thtPath = Tht::path('pages', Tht::getThtFileName($apath));

        if (!file_exists($thtPath)) {
            Tht::errorLog("Entry file not found for path: `$path`");
            Tht::module('Web')->u_send_error(404);
        }

        return $thtPath; // Tht::path('pages', $thtPath)
    }

    static private function initWebRoute () {

        Tht::module('Perf')->u_start('tht.route');

        $path = Tht::getScriptPath();

        $controllerFile = Tht::getControllerForPath($path);

        Tht::module('Perf')->u_stop();

        return $controllerFile;
    }

    static private function executeWebController ($controllerName) {

        Tht::module('Perf')->u_start('tht.executeWebRoute', $controllerName);

        $dotExt = '.' . Tht::$SRC_EXT;
        if (strpos($controllerName, $dotExt) === false) {
            Tht::configError("Route file `$controllerName` requires `$dotExt` extension in `" . Tht::$FILE['configFile'] ."`.");
        }

        $userFunction = '';
        $controllerFile = $controllerName;
        if (strpos($controllerName, '@') !== false) {
            list($controllerFile, $userFunction) = explode('@', $controllerName, 2);
        }

        Source::process($controllerFile, true);

        Tht::callAutoFunction($controllerFile, $userFunction);

        Tht::module('Perf')->u_stop();
    }

    static private function callAutoFunction($controllerFile, $userFunction) {

        $nameSpace = Runtime::getNameSpace(Tht::getFullPath($controllerFile));

        $fullController = $nameSpace . '\\u_' . basename($controllerFile);
        $fullUserFunction = $nameSpace . '\\u_' . $userFunction;

        $mainFunction = 'main';
        $web = Tht::module('Web');
        if ($web->u_request()['isAjax']) {
            $mainFunction = 'ajax';
        } else if ($web->u_request()['method'] === 'POST') {
            $mainFunction = 'post';
        }
        $fullMainFunction = $nameSpace . '\\u_' . $mainFunction;


        $callFunction = '';
        if ($userFunction) {
            if (!function_exists($fullUserFunction)) {
                Tht::configError("Function `$userFunction` not found for route target `$controllerName`");
            }
            $callFunction = $fullUserFunction;
        }
        else if (function_exists($fullMainFunction)) {
            $callFunction = $fullMainFunction;
        }

        if ($callFunction) {
            try {
                $ret = call_user_func($callFunction);
                if (OLockString::isa($ret)) {
                    Tht::module('Web')->sendByType($ret);
                }

            } catch (ThtException $e) {
                ErrorHandler::handleThtException($e, Tht::getPhpPathForTht($controllerFile));
            }
        }
    }

    static function initResponseHeaders () {

        // [security]  Set response headers
        header_remove('Server');
        header_remove("X-Powered-By");
        header('X-Frame-Options: deny');
        header('X-Content-Type-Options: nosniff');
        header("X-UA-Compatible: IE=Edge");

        // [security]  Content Security Policy (CSP)
        $csp = Tht::getConfig('contentSecurityPolicy');
        if (!$csp) {
            $nonce = "'nonce-" . Tht::data('cspNonce') . "'";
            $eval = Tht::getConfig('dangerDangerAllowJsEval') ? 'unsafe-eval' : '';
            $scriptSrc = "script-src $eval $nonce";
            $csp = "default-src 'self' $nonce; style-src 'unsafe-inline' *; img-src data: *; media-src *; font-src *; " . $scriptSrc;
        }
        header("Content-Security-Policy: $csp");

        if (Tht::getConfig('useSession')) {
            Tht::module('Session')->u_init();
        }
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

    static function startTestServer ($hostName='localhost', $port=0, $docRoot='.') {

        if (!$port) {
            $port = Tht::$SERVER_PORT;
        }
        if ($port <= 1024) {
            Tht::error("Server port must be greater than 1024.");
        }

        if (!Tht::isAppInstalled()) {
            echo "\nCan't find app directory.  Please `cd` to your your document root and try again.\n\n";
            exit(1);
        }

        echo "\n";
        echo "+-------------------+\n";
        echo "|    TEST SERVER    |\n";
        echo "+-------------------+\n\n";

        echo "App directory:\n  " . Tht::path('appRoot') . "\n\n";
        echo "Serving app at:\n  http://$hostName:$port\n\n";
        echo "Press [Ctrl-C] to stop.\n\n";

        $controller = realpath('thtApp.php');

        passthru("php -S $hostName:$port $controller");
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

    static function isAppInstalled () {
        $appRoot = Tht::path('root');
        return $appRoot && file_exists($appRoot);
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

    static function makePath () {
        $args = func_get_args();
        if (is_array($args[0])) { $args = $args[0]; }
        $sep = DIRECTORY_SEPARATOR;
        $path = implode($sep, $args);
        $path = str_replace($sep . $sep, $sep, $path); // prevent double slashes
        $path = rtrim($path, '/');
        return $path;
    }

    static function getRelativePath ($baseKey, $fullPath) {
        $basePath = Tht::path($baseKey);
        $rel = str_replace(realpath($basePath), '', $fullPath);
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
        $f = rtrim($f, '.php');
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

    static function getWebRequestHeader ($key) {
        if (!isset(Tht::$data['requestHeaders'][$key])) {
            return '';
        }
        return Tht::$data['requestHeaders'][$key];
    }

    static function getWebRequestHeaders () {
        return Tht::$data['requestHeaders'];
    }

    static function getTopConfig() {
        $args = func_get_args();
        if (is_array($args[0])) { $args = $args[0]; }
        $val = Tht::searchConfig(Tht::$data['settings'], $args);
        if ($val === null) {
            $val = Tht::searchConfig(Tht::getDefaultConfig(), $args);
            if ($val === null) {
                throw new StartupException ('No config for key \'' . implode($args, '.') . '\'');
            }
        }
        return $val;
    }

    static function getConfig () {
        $args = func_get_args();
        array_unshift($args, Tht::$CONFIG_KEY['main']);
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

    static function getWebRouteParam ($key) {
        if (!isset(Tht::$data['routeParams'][$key])) {
            throw new ThtException ("Route param '$key' does not exist.");
        }
        return Tht::$data['routeParams'][$key];
    }

    static function getUrl($key) {
        if (!isset(Tht::$data['urls'][$key])) {
            throw new ThtException ("Url type '$key' does not exist.");
        }
        return Tht::$data['urls'][$key];
    }

    static function getThtVersion() {
        return Tht::$VERSION;
    }

    static function module ($name) {
        return Runtime::getModule('', $name);
    }
}

