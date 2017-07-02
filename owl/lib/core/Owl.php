<?php

namespace o;

class Owl {

    static private $VERSION = '0.1.0 - Beta';
    static private $SRC_EXT = 'owl';

    static private $OWL_SITE = 'https://owl-lang.org';

    static private $CLI_OPTIONS = [
        'new'    => 'new',
        'server' => 'server',
        'run'    => 'run',
    ];

    static private $data = [
        'phpGlobals'     => [],
        'config'         => [],
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

        'root'      => 'owl',

        'pages'     =>   'pages',
        'modules'   =>   'modules',
        'settings'  =>   'settings',
        'scripts'   =>   'scripts',

        'data'      => 'data',
        'db'        =>   'db',
        'uploads'   =>   'uploads',
        'cache'     =>   'cache',
        'phpCache'  =>     'php',
        'kvCache'   =>     'keyValue',
        'fileCache' =>     'fileCache',
        'files'     =>   'files',
        'counter'   =>     'counter',
        'counterPage' =>      'page',
        'counterDate' =>      'date',
    ];

    static private $FILE = [
        'configFile'         => 'app.jcon',
        'appCompileTimeFile' => '_appCompileTime',
        'logFile'            => 'app.log',
        'frontFile'          => 'owlApp.php',
        'homeFile'           => 'home.owl',
    ];

    static private $CONFIG_KEY = [
        'main'   => 'owl',
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
            return Owl::main();
        }
        catch (StartupException $e) {
           ErrorHandler::handleStartupException($e);
        }
        catch (\Exception $e) {
            ErrorHandler::handlePhpLeakedException($e);
        }
    }

    static private function includeLibs() {
        Owl::loadLib('ErrorHandler.php');
        Owl::loadLib('Source.php');
        Owl::loadLib('StringReader.php');
        Owl::loadLib('Runtime.php');

        Owl::loadLib('../classes/_index.php');
        Owl::loadLib('../modules/_index.php');
    }

    static private function main () {

        Owl::includeLibs();

        Owl::initMode();

        if (Owl::isMode('testServer')) {
            // return static assets
            if (file_exists($_SERVER["SCRIPT_FILENAME"])) {
                return false;
            }
        }

        if (Owl::isMode('cli')) {
            Owl::mainCli();
        }
        else {
            Owl::mainWeb();
        }

        Owl::endScript();

        return true;
    }

    static private function endScript () {
        Owl::module('Perf')->printResults();
    }

    static public function handleShutdown() {
        Owl::clearMemoryBuffer();
        Owl::flushWebPrintBuffer();
    }

    static private function init () {
        Owl::initScalarData();
        Owl::initErrorHandler();
        Owl::initPhpGlobals();
        Owl::initPaths();
        Owl::initConfig();
        Owl::initPhpIni();
    }

    static private function mainCli() {

        Owl::initCliOptions();

        $firstOption = Owl::$data['cliOptions'][0];

        if ($firstOption === Owl::$CLI_OPTIONS['new']) {
            Owl::initPaths(true);
            Owl::installApp();
        }
        else if ($firstOption === Owl::$CLI_OPTIONS['server']) {
            Owl::initPaths(true);
            Owl::startTestServer();  // TODO: support run from owl parent, port
        }
        else if ($firstOption === Owl::$CLI_OPTIONS['run']) {
            Owl::init();
            Source::process(Owl::$data['cliOptions'][1]);
        }
        else {
            echo "\nUnknown argument.\n";
            Owl::printUsage();
        }
    }

    static private function mainWeb () {
        Owl::init();
        Owl::initResponseHeaders();
        Owl::executeWeb();
    }

    static function executePhp ($phpPath) {
        try {
            require_once($phpPath);
        } catch (OwlException $e) {
            ErrorHandler::handleOwlException($e, $phpPath);
        }
    }


    // OUTPUT
    //---------------------------------------------

    static function devPrint ($msg) {
        if (Owl::getConfig('_devPrint')) {
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
        file_put_contents(Owl::path('logFile'), $line, FILE_APPEND);
    }

    static function error ($msg, $vars=null) {
        $msg = Owl::errorVars($msg, $vars);
        throw new OwlException ($msg);
    }

    static function configError ($msg, $vars=null) {
        $msg = Owl::errorVars($msg, $vars);
        ErrorHandler::handleConfigError($msg);
    }

    static private function errorVars($msg, $vars=null) {
        if (!is_null($vars)) {
            $msg .= "\n\nGot: "  . Owl::module('Json')->u_format(json_encode($vars));
        }
        return $msg;
    }

    static private function printUsage() {
        echo "\n{o,o} OWL - v" . Owl::$VERSION . "\n";
        echo "\nUsage:\n\n";
        echo "owl new              (create a new app)\n";
        echo "owl server           (start local test server)\n";
     //   echo "owl run <filename>   (run script in scripts directory)\n";
        echo "\n";
        exit(0);
    }

    static public function queueWebPrint($s) {
        Owl::$WEB_PRINT_BUFFER []= $s;
    }

    // Send the output of all print() statements
    static private function flushWebPrintBuffer() {
        if (!count(Owl::$WEB_PRINT_BUFFER)) { return; }

        $zIndex = 99998;  //  one less than error page

        echo "<style>\n";
        echo ".owl-print { white-space: pre; border: 0; border-left: solid 2px #99f; padding: 4px 20px; margin: 4px 0 0;  font-family: " . u_Css::u_monospace_font() ."; }\n";
        echo ".owl-print-panel { position: fixed; top: 0; left: 0; z-index: $zIndex; width: 100%; padding: 20px 40px 30px; font-size: 18px; background-color: rgba(255,255,255,0.98);  -webkit-font-smoothing: antialiased; color: #222; box-shadow: 0 4px 4px rgba(0,0,0,0.15); max-height: 400px; overflow: auto;  }\n";
        echo "</style>\n";

        echo "<div class='owl-print-panel'>\n";
        foreach (Owl::$WEB_PRINT_BUFFER as $b) {
            echo "<div class='owl-print'>" . $b . "</div>\n";
        }
        echo "</div>";
    }



    // INITS
    //---------------------------------------------

    static private function initMode() {
        $sapi = php_sapi_name();
        Owl::$mode['testServer'] = $sapi === 'cli-server';
        Owl::$mode['cli'] = $sapi === 'cli';
        Owl::$mode['web'] = !Owl::$mode['cli'];
    }

    static private function initScalarData() {
        // Reserve memory in case of out-of-memory error. Enough to call handleShutdown.
        Owl::$data['memoryBuffer'] = str_repeat('*', 128 * 1024);

        Owl::$data['cspNonce']  = Owl::module('String')->u_random(40);
        Owl::$data['csrfToken'] = Owl::module('String')->u_random(40);
    }

    static private function initCliOptions () {
        global $argv;
        if (count($argv) === 1) {
            Owl::printUsage();
        }
        Owl::$data['cliOptions'] = array_slice($argv, 1);
    }

    static private function initErrorHandler () {
        set_error_handler("\\o\\ErrorHandler::handlePhpRuntimeError");
        register_shutdown_function('\\o\\handleShutdown');
    }

    static private function initPaths($isSetup=false) {

        if (Owl::isMode('cli')) {
            if ($isSetup) {
                // TODO: take as argument
                Owl::$paths['docRoot'] = getcwd();
            } else {
                // TODO: travel up parent
                Owl::$paths['docRoot'] = getcwd();
            }
        } else {
            Owl::$paths['docRoot'] = Owl::getPhpGlobal('server', 'DOCUMENT_ROOT');
        }

        // App root is a sibling of doc root
        $docRootParent = Owl::makePath(Owl::$paths['docRoot'], '..');
        Owl::$paths['root'] = Owl::makePath(realpath($docRootParent), Owl::$DIR['root']);

        if (!Owl::$paths['root']) {
            Owl::startupError("App has not been setup yet.\n\n"
                . "Run: 'owl " . Owl::$CLI_OPTIONS['new'] . "' in your Document Root directory.");
        }

        // main dirs
        Owl::$paths['appRoot']   = realpath($docRootParent);

        Owl::$paths['data']      = Owl::path('root', Owl::$DIR['data']);
        Owl::$paths['pages']     = Owl::path('root', Owl::$DIR['pages']);
        Owl::$paths['modules']   = Owl::path('root', Owl::$DIR['modules']);
        Owl::$paths['settings']  = Owl::path('root', Owl::$DIR['settings']);
        Owl::$paths['scripts']   = Owl::path('root', Owl::$DIR['scripts']);

        // data subdirs
        Owl::$paths['db']          = Owl::path('data', Owl::$DIR['db']);
        Owl::$paths['cache']       = Owl::path('data', Owl::$DIR['cache']);
        Owl::$paths['phpCache']    = Owl::path('cache', Owl::$DIR['phpCache']);
        Owl::$paths['kvCache']     = Owl::path('cache', Owl::$DIR['kvCache']);
        Owl::$paths['files']       = Owl::path('data', Owl::$DIR['files']);
        Owl::$paths['counter']     = Owl::path('files', Owl::$DIR['counter']);
        Owl::$paths['counterPage'] = Owl::path('counter', Owl::$DIR['counterPage']);
        Owl::$paths['counterDate'] = Owl::path('counter', Owl::$DIR['counterDate']);

        // files
        Owl::$paths['configFile']         = Owl::path('settings', Owl::$FILE['configFile']);
        Owl::$paths['appCompileTimeFile'] = Owl::path('phpCache', Owl::$FILE['appCompileTimeFile']);
        Owl::$paths['logFile']            = Owl::path('files',    Owl::$FILE['logFile']);
    }

    static private function initConfig () {

        Owl::module('Perf')->start('owl.initAppConfig');

        $defaultConfig = Owl::getDefaultConfig();

        $iniPath = Owl::path('configFile');

        // TODO: cache as JSON
        $setBody = file_get_contents(Owl::path('configFile'));
        $appConfig = Owl::module('Jcon')->u_parse($setBody);

        $mainKey = Owl::$CONFIG_KEY['main'];
        $routeKey = Owl::$CONFIG_KEY['routes'];

        foreach ([$mainKey, $routeKey] as $key) {
            if (!isset($appConfig[$key])) {
                Owl::error("Missing top-level '$key' key in '" . Owl::$FILE['configFile'] . "'.", $appConfig);
            }
        }

        $def = Owl::getDefaultConfig();
        foreach ($appConfig[$mainKey] as $k => $v) {
            if (!isset($def[$mainKey][$k])) {
                Owl::error("Unknown config key '$k'.");
            }
        }

        Owl::$data['config'] = $appConfig;

        Owl::module('Perf')->u_stop();
    }

    // [security] set ini
    static private function initPhpIni () {

        // locale
        date_default_timezone_set(Owl::getConfig('timezone'));
        ini_set('default_charset', 'utf-8');
        mb_internal_encoding('utf-8');

        // logging
        error_reporting(E_ALL);
        ini_set('display_errors', Owl::isMode('cli') ? '1' : (Owl::getConfig('_phpErrors') ? '1' : '0'));
        ini_set('display_startup_errors', '1');
        ini_set('log_errors', '0');  // assume we are logging all errors manually

        // file security
        ini_set('allow_url_fopen', '0');
        ini_set('allow_url_include', '0');

        // limits
        ini_set('max_execution_time', Owl::isMode('cli') ? 0 : intval(Owl::getConfig('maxExecutionTimeSecs')));
        ini_set('max_input_time', intval(Owl::getConfig('maxInputTimeSecs')));
        ini_set('memory_limit', intval(Owl::getConfig('memoryLimitMb')) . "M");


        // Configs that are only set in .ini or .htaccess
        // Trigger an error if PHP is more strict than Owl.
        $owlMaxPostSize = intval(Owl::getConfig('maxPostSizeMb'));
        $phpMaxFileSize = intval(ini_get('upload_max_filesize'));
        $phpMaxPostSize = intval(ini_get('post_max_size'));
        $owlFileUploads = Owl::getConfig('allowFileUploads');
        $phpFileUploads = ini_get('file_uploads');

        if ($owlMaxPostSize > $phpMaxFileSize) {
            Owl::configError("Config 'maxPostSizeMb' ($owlMaxPostSize) is larger than php.ini 'upload_max_filesize' ($phpMaxFileSize).\n"
                . "You will want to edit php.ini so they match.");
        }
        if ($owlMaxPostSize > $phpMaxPostSize) {
            Owl::configError("Config 'maxPostSizeMb' ($owlMaxPostSize) is larger than php.ini 'post_max_size' ($phpMaxPostSize).\n"
                . "You will want to edit php.ini so they match.");
        }
        if ($owlFileUploads && !$phpFileUploads) {
            Owl::configError("Config 'allowFileUploads' is true, but php.ini 'file_uploads' is false.\n"
                . "You will want to edit php.ini so they match.");
        }
    }

    // [security] clear out super globals
    static private function initPhpGlobals () {

        Owl::$data['phpGlobals'] = [
            'get'    => $_GET,
            'post'   => $_POST,
            'cookie' => $_COOKIE,
            'files'  => $_FILES,
            'server' => $_SERVER
        ];

        // Only keep ENV for CLI mode.  In Web mode, config should happen in app.odf.
        if (Owl::isMode('cli')) {
            Owl::$data['phpGlobals']['env'] = $_ENV;
        }

        if (isset($HTTP_RAW_POST_DATA)) {
            Owl::$data['phpGlobals']['post']['_raw'] = $HTTP_RAW_POST_DATA;
            unset($HTTP_RAW_POST_DATA);
        }

        Owl::initHttpRequestHeaders();


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
        Owl::$data['requestHeaders'] = $headers;
    }

    static private function getDefaultConfig () {

        $default = [];

        $default[Owl::$CONFIG_KEY['routes']] = [
            '/' => '/home'
        ];

        $default[Owl::$CONFIG_KEY['main']] = [

            // internal
            "_devPrint"        => false,
            "_disablePhpCache" => false,
            "_showPhpInTrace"  => false,
            "_lintPhp"         => true,
            "_phpErrors"       => false,

            // temporary - still working on it
            "tempParseCss"     => false,

            // features
            "showPerfScore"    => false,
            "useSession"       => false,
            "disableReadabilityChecker" => false,

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

        if (file_exists(Owl::$paths['root'])) {
            echo "\nAn OWL directory already exists:\n  " .  Owl::$paths['root'] . "\n\n";
            echo "To start over, just delete or move that directory. Then rerun this command.\n\n";
            exit(1);
        }

        if (!Owl::module('System')->u_confirm("\nYour Document Root is:\n  " . Owl::$paths['docRoot'] . "\n\nInstall OWL app for this directory?")) {
            echo "\nPlease 'cd' to your public Document Root directory.  Then rerun this command.\n\n";
            exit(0);
        }

        usleep(500000);
    }

    static private function installApp () {

        Owl::confirmInstall();

        try {

            // create directory tree
            foreach (Owl::$paths as $id => $p) {
                if (substr($id, -4, 4) === 'File') {
                    touch($p);
                } else {
                    Owl::module('File')->u_make_dir($p,  0755);
                }
            }

            // Front controller
            // TODO: move paths to constants
            $appRoot = '../owl';
            $owlBinPath = realpath($_SERVER['SCRIPT_NAME']);

            Owl::writeSetupFile(Owl::$FILE['frontFile'], "

            <?php

                 // Static Cache
                 //  0: Off
                 // -1: Never Expire
                 // 1+: Cache output of HTML when { staticCache: true } in Web.printHtml().
                 define('STATIC_CACHE_SECONDS', 0);

                 define('APP_ROOT', '$appRoot');
                 define('OWL_MAIN', '$owlBinPath');

                 return require_once(OWL_MAIN);

            ");


            // .htaccess
            // TODO: don't overwrite previous
            Owl::writeSetupFile('.htaccess', "

                # OWL App

                DirectoryIndex index.html index.php owlApp.php
                Options -Indexes

                # Redirect all non-static URLs to OWL app
                RewriteEngine On
                RewriteCond %{REQUEST_FILENAME} !-f
                RewriteCond %{REQUEST_FILENAME} !-d
                RewriteRule  ^(.*)$ /owlApp.php [QSA,NC,L]

                # Redirect to HTTPS
                RewriteCond %{HTTPS} off
                RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI}
            ");


            // Starter App
            $exampleFile = 'home.owl';
            $examplePath = Owl::path('pages', $exampleFile);
            $exampleRelPath =  Owl::getRelativePath('appRoot', $examplePath);
            $publicPath = Owl::getRelativePath('appRoot', Owl::path('pages'));
            $exampleCssPath = Owl::path('pages', 'css.owl');

            Owl::writeSetupFile($examplePath, "
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
                            <p>> Add new pages to:<br /><code>$publicPath</>
                            <p>> For example, this file...<br /><code>pages/testPage.owl</>
                            <p>> ... will automatically become this URL:<br /><code>https://yoursite/test-page</>
                            <p style=\"margin-top: 4rem\">> For more info, see <a href=\"https://owl-lang.org/tutorials/how-to-create-a-basic-web-app\">How to Create a Basic Web App</a>.
                        </>
                    </>
                }
            ");

            Owl::writeSetupFile($exampleCssPath, "

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
            Owl::writeSetupFile(Owl::path('configFile'), "
                {
                    // Dynamic URL routes
                    routes: {
                        // /post/{postId}:  post.owl
                    }

                    // Custom app settings
                    app: {
                        // myVar: 1234
                    }

                    // Core settings
                    owl: {
                        // Server timezone
                        // See: http://php.net/manual/en/timezones.php
                        // Example: America/Los_Angeles
                        timezone: GMT

                        // Print performance timing info
                        showPerfScore: false

                        // Send session cookies
                        useSession: false

                        // Enable user file uploads (security)
                        // Recommended: false
                        allowFileUploads: false
                    }

                    // Database settings
                    // See: https://owl-lang.org/manual/module/db
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

          //  Owl::installDatabases();

        } catch (\Exception $e) {
            echo "Sorry, something went wrong.\n\n";
            echo "  " . $e->getMessage() . "\n\n";
            if (file_exists(Owl::$paths['root'])) {
                echo "Move or delete your app directory before trying again:\n\n  " . Owl::$paths['root'];
                echo "\n\n";
            }
            exit(1);
        }

        echo "\n\n";
        echo "+-------------------+\n";
        echo "|      SUCCESS!     |\n";
        echo "+-------------------+\n\n";

        echo "Your new OWL app directory is here:\n  " . Owl::$paths['root'] . "\n\n";
        echo "*  Load 'https://yoursite.com' to see if it's working.\n";
        echo "*  Or run 'owl server' to start a local web server.";
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
         //   touch(Owl::getAppDataPath($dbFile));
            $dbh = Owl::module('Db')->u_use($dbId);
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
        Owl::createDbIndex($dbh, 'cache', 'key');
        Owl::createDbIndex($dbh, 'cache', 'expireDate');

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


        //Owl::module('Database')->u_use('default');

        echo " [OK]\n";
    }




    // WEB
    //---------------------------------------------

    static private function executeWeb () {
        if (Owl::getConfig('downtime')) {
            Owl::downtimePage(Owl::getConfig('downtime'));
        }

        if (!Owl::module('Web')->u_request()['isHttps'] && !Owl::isMode('testServer')) {
            Owl::configError("Server must be run under HTTPS.  Here are some options:\n\n\n- Run 'owl server' to develop locally.\n\n- Check your admin panel to turn on HTTPS.\n\n- Visit letsencrypt.org for a free SSL cert.");
        }

        $controllerFile = Owl::initWebRoute();
        if ($controllerFile) {
            Owl::executeWebController($controllerFile);
        }
    }

    static private function downtimePage($file) {
        http_response_code(503);
        $downPage = Owl::module('File')->u_document_path($file);
        if ($file !== true && file_exists($downPage)) {
            print(file_get_contents($downPage));
        }
        else {
            $font = Owl::module('Css')->u_sans_serif_font();
            echo "<div style='padding: 2rem; text-align: center; font-family: $font'><h1>Temporarily Down for Maintenance</h1><p>Sorry for the inconvenience.  We'll be back soon.</p></div>";
        }
        exit(0);
    }

    static public function runStaticRoute($route) {
        $routes = Owl::getTopConfig(Owl::$CONFIG_KEY['routes']);
        if (!isset($routes[$route])) { return false; }
        $file = Owl::path('pages', $routes[$route]);
        Owl::executeWebController($file);
        exit(0);
    }

    static private function getScriptPath() {

        $path = Owl::module('Web')->u_request()['url']['path'];

        // Validate route name
        // all lowercase, no special characters, hyphen separators, no trailing slash
        $pathSize = strlen($path);
        $isTrailingSlash = $pathSize > 1 && $path[$pathSize-1] === '/';
        if (preg_match('/[^a-z0-9\-\/\.]/', $path) || $isTrailingSlash)  {
            Owl::errorLog("path '$path' is not valid");
            Owl::module('Web')->u_send_error(404);
        }

        return $path;
    }

    static private function getControllerForPath($path) {

        $routes = Owl::getTopConfig(Owl::$CONFIG_KEY['routes']);

        if (isset($routes[$path])) {
            // static path
            return Owl::path('pages', $routes[$path]);
        }
        else {
            $c = Owl::getDynamicController($routes, $path);
            return $c === false ? Owl::getPublicController($path) : $c;
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
                            Owl::configError("Route placeholder '{" . $token . "}' should only"
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
                    Owl::$data['routeParams'] = $params;
                    return Owl::path('pages', $controllerPath);
                }
            }
        }

        $camelPath = strtolower(v($path)->u_to_camel_case());
        if (isset($routeTargets[$camelPath]) || $camelPath == '/' . Owl::$HOME_ROUTE) {
            Owl::errorLog('Direct access to route not allowed: ' . $path);
            Owl::module('Web')->u_send_error(404);
        }

        return false;
    }

    static private function getPublicController($path) {

        $apath = '';
        if ($path === '/') {
            $apath = Owl::$HOME_ROUTE;
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

        $owlPath = Owl::path('pages', Owl::getOwlFileName($apath));

        if (!file_exists($owlPath)) {
            Owl::errorLog('Entry file not found for path: ' . $path);
            Owl::module('Web')->u_send_error(404);
        }

        return $owlPath; // Owl::path('pages', $owlPath)
    }

    static private function initWebRoute () {

        Owl::module('Perf')->u_start('owl.route');

        $path = Owl::getScriptPath();

        $controllerFile = Owl::getControllerForPath($path);

        Owl::module('Perf')->u_stop();

        return $controllerFile;
    }

    static private function executeWebController ($controllerName) {

        Owl::module('Perf')->u_start('owl.executeWebRoute', $controllerName);

        $dotExt = '.' . Owl::$SRC_EXT;
        if (strpos($controllerName, $dotExt) === false) {
            Owl::configError("Route file '$controllerName' requires '$dotExt' extension in '" . Owl::$FILE['configFile'] ."'.");
        }

        $userFunction = '';
        $controllerFile = $controllerName;
        if (strpos($controllerName, '@') !== false) {
            list($controllerFile, $userFunction) = explode('@', $controllerName, 2);
        }

        Source::process($controllerFile, true);

        Owl::callAutoFunction($controllerFile, $userFunction);

        Owl::module('Perf')->u_stop();
    }

    static private function callAutoFunction($controllerFile, $userFunction) {

        $nameSpace = Runtime::getNameSpace(Owl::getFullPath($controllerFile));

        $fullController = $nameSpace . '\\u_' . basename($controllerFile);
        $fullUserFunction = $nameSpace . '\\u_' . $userFunction;

        $mainFunction = 'main';
        $web = Owl::module('Web');
        if ($web->u_request()['isAjax']) {
            $mainFunction = 'ajax';
        } else if ($web->u_request()['method'] === 'POST') {
            $mainFunction = 'post';
        }
        $fullMainFunction = $nameSpace . '\\u_' . $mainFunction;


        $callFunction = '';
        if ($userFunction) {
            if (!function_exists($fullUserFunction)) {
                Owl::configError("Function '$userFunction' not found for route target '$controllerName'");
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
                    Owl::module('Web')->sendByType($ret);
                }

            } catch (OwlException $e) {
                ErrorHandler::handleOwlException($e, Owl::getPhpPathForOwl($controllerFile));
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
        $csp = Owl::getConfig('contentSecurityPolicy');
        if (!$csp) {
            $nonce = "'nonce-" . Owl::data('cspNonce') . "'";
            $eval = Owl::getConfig('dangerDangerAllowJsEval') ? 'unsafe-eval' : '';
            $scriptSrc = "script-src $eval $nonce";
            $csp = "default-src 'self' $nonce; style-src 'unsafe-inline' *; img-src *; media-src *; font-src *; " . $scriptSrc;
        }
        header("Content-Security-Policy: $csp");

        if (Owl::getConfig('useSession')) {
            Owl::module('Session')->u_init();
        }
    }



    // MISC
    //---------------------------------------------

    static function loadLib ($file) {
        $libDir = dirname(__FILE__);
        require_once(Owl::makePath($libDir, $file));
    }

    static function sanitizeString ($str) { // [security]
        if (is_array($str)) {
            foreach ($str as $k => $v) {
                $str[$k] = Owl::sanitizeString($v);
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
            $port = Owl::$SERVER_PORT;
        }
        if ($port <= 1024) {
            Owl::error("Server port must be greater than 1024.");
        }

        if (!Owl::isAppInstalled()) {
            echo "\nCan't find app directory.  Please `cd` to your your document root and try again.\n\n";
            exit(1);
        }

        echo "\n";
        echo "+-------------------+\n";
        echo "|    TEST SERVER    |\n";
        echo "+-------------------+\n\n";

        echo "App directory:\n  " . Owl::path('appRoot') . "\n\n";
        echo "Serving app at:\n  http://$hostName:$port\n\n";
        echo "Press [Ctrl-C] to stop.\n\n";

        $controller = realpath('owlApp.php');

        passthru("php -S $hostName:$port $controller");
    }




    // GETTERS
    //---------------------------------------------

    static function isMode($m) {
        return Owl::$mode[$m];
    }

    static function data($k, $subKey='') {
        $d = Owl::$data[$k];
        if ($subKey) {
            return $d[$subKey];
        }
        if (is_array($d)) {
            Owl::error('Need to specify a subKey for array data: ' . $k);
        }
        return $d;
    }

    static function clearMemoryBuffer() {
        Owl::$data['memoryBuffer'] = '';
    }

    static function isAppInstalled () {
        $appRoot = Owl::path('root');
        return $appRoot && file_exists($appRoot);
    }

    static function getExt() {
        return Owl::$SRC_EXT;
    }



    // PATH GETTERS
    //---------------------------------------------
    // TODO: some of these are redundant with File module methods

    static function path() {
        $parts = func_get_args();
        $base = $parts[0];
        if (!isset(Owl::$paths[$base])) {
            Owl::error('Unknown base path key: ' . $base);
        }
        $parts[0] = Owl::$paths[$base];
        return Owl::makePath($parts);
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
        $basePath = Owl::path($baseKey);
        $rel = str_replace(realpath($basePath), '', $fullPath);
        return ltrim($rel, '/');
    }

    static function getFullPath ($file) {
        if (substr($file, 0, 1) === DIRECTORY_SEPARATOR) {  return $file;  }
        return Owl::makePath(realpath(getcwd()), $file);
    }

    static function getPhpPathForOwl ($owlFile) {
        $relPath = Owl::module('File')->u_relative_path($owlFile, Owl::$paths['root']);
        $cacheFile = preg_replace('/[\\/]/', '_', $relPath);
        return Owl::path('phpCache', $cacheFile . '.php');
    }

    static function getOwlPathForPhp ($phpPath) {
        $f = basename($phpPath);
        $f = rtrim($f, '.php');
        $f = str_replace('_', '/', $f);
        return Owl::path('root', $f);
    }

    static function getOwlFileName ($fileBaseName) {
        return $fileBaseName . '.' . Owl::$SRC_EXT;
    }



    // MISC GETTERS
    //---------------------------------------------

    static function getOwlSiteUrl($relPath) {
        return Owl::$OWL_SITE . $relPath;
    }

    static function getWebRequestHeader ($key) {
        if (!isset(Owl::$data['requestHeaders'][$key])) {
            return '';
        }
        return Owl::$data['requestHeaders'][$key];
    }

    static function getWebRequestHeaders () {
        return Owl::$data['requestHeaders'];
    }

    static function getTopConfig() {
        $args = func_get_args();
        if (is_array($args[0])) { $args = $args[0]; }
        $val = Owl::searchConfig(Owl::$data['config'], $args);
        if ($val === null) {
            $val = Owl::searchConfig(Owl::getDefaultConfig(), $args);
            if ($val === null) {
                throw new StartupException ('No config for key \'' . implode($args, '.') . '\'');
            }
        }
        return $val;
    }

    static function getConfig () {
        $args = func_get_args();
        array_unshift($args, Owl::$CONFIG_KEY['main']);
        return Owl::getTopConfig($args);
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
        return Owl::$data['config'];
    }

    static function getPhpGlobal ($g, $key, $def='') {

        if (!isset(Owl::$data['phpGlobals'][$g]) || !isset(Owl::$data['phpGlobals'][$g][$key])) {
            return $def;
        }
        $val = Owl::$data['phpGlobals'][$g][$key];
        $val = Owl::sanitizeString($val);

        return $val;
    }

    static function getWebRouteParam ($key) {
        if (!isset(Owl::$data['routeParams'][$key])) {
            throw new OwlException ("Route param '$key' does not exist.");
        }
        return Owl::$data['routeParams'][$key];
    }

    static function getUrl($key) {
        if (!isset(Owl::$data['urls'][$key])) {
            throw new OwlException ("Url type '$key' does not exist.");
        }
        return Owl::$data['urls'][$key];
    }

    static function getOwlVersion() {
        return Owl::$VERSION;
    }

    static function module ($name) {
        return Runtime::getModule('', $name);
    }
}

