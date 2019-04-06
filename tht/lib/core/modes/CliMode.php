<?php

namespace o;

class CliMode {

    static private $SERVER_PORT = 8888;
    static private $SERVER_HOSTNAME = 'localhost';

    static private $CLI_OPTIONS = [
        'new'    => 'new',
        'server' => 'server',
        'images' => 'images',
    //  'run'    => 'run',
    ];

    static private $FRONT_PATH_APP     = '../app';
    static private $FRONT_PATH_DATA    = '../data';
    static private $FRONT_PATH_RUNTIME = '../app/.tht/bin/tht.php';


    static private $options = [];

    static function main() {

        CliMode::initOptions();

        $firstOption = CliMode::$options[0];

        Tht::initAppPaths(true);

        if ($firstOption === CliMode::$CLI_OPTIONS['new']) {
            CliMode::installApp();
        }
        else if ($firstOption === CliMode::$CLI_OPTIONS['server']) {
            $port = isset(CliMode::$options[1]) ? CliMode::$options[1] : 0;
            CliMode::startTestServer($port);
        }
        else if ($firstOption === CliMode::$CLI_OPTIONS['images']) {
            $actionOrDir = isset(CliMode::$options[1]) ? CliMode::$options[1] : 0;
            Tht::module('Image')->optimizeImages($actionOrDir);
        }
        // else if ($firstOption === CliMode::$CLI_OPTIONS['run']) {
        //     // Tht::init();
        //     // Compiler::process(CliMode::$options[1]);
        // }
        else {
            CliMode::printUsage();
        }
    }

    static private function printUsage() {

        self::printHeaderBox('THT');

        echo "Version: " . Tht::getThtVersion() . "\n\n";
        echo "Usage: tht [command]\n\n";
        echo "Commands:\n\n";
        echo "  new             create an app in the current dir\n";
        echo "  server          start the local test server (port: 8888)\n";
        echo "  server <port>   start the local test server on a custom port\n";
        echo "  images          compress images in your document root by up to 70%\n";
     // echo "tht run <filename>   (run script in scripts directory)\n";
        echo "\n";
        Tht::exitScript(0);
    }

    static function printHeaderBox($title) {
        $title = trim(strtoupper($title));
        $line = str_repeat('-', strlen($title) + 8);
        echo "\n";
        echo "+$line+\n";
        echo "|    $title    |\n";
        echo "+$line+\n\n";
    }

    static private function initOptions () {
        global $argv;
        if (count($argv) === 1) {
            CliMode::printUsage();
        }
        CliMode::$options = array_slice($argv, 1);
    }

    static function isAppInstalled () {
        $appRoot = Tht::path('app');
        return $appRoot && file_exists($appRoot);
    }

    static private function confirmInstall() {

        self::printHeaderBox('New App');

        if (file_exists(Tht::path('app'))) {
            echo "\nA THT app directory already exists:\n  " .  Tht::path('app') . "\n\n";
            echo "To start over, just delete or move that directory. Then rerun this command.\n\n";
            Tht::exitScript(1);
        }

        if (!Tht::module('System')->u_confirm("\nYour Document Root is:\n  " . Tht::path('docRoot') . "\n\nInstall THT app for this directory?")) {
            echo "\nPlease 'cd' to your public Document Root directory.  Then rerun this command.\n\n";
            Tht::exitScript(0);
        }

        usleep(500000);
    }

    static private function installApp () {

        CliMode::confirmInstall();

        try {

            // create directory tree
            foreach (Tht::getAllPaths() as $id => $p) {
                if (substr($id, -4, 4) === 'File') {
                    touch($p);
                } else {
                    Tht::module('*File')->u_make_dir($p, '755');
                }
            }

            $appRoot  = self::$FRONT_PATH_APP;
            $dataRoot = self::$FRONT_PATH_DATA;
            $thtMain  = self::$FRONT_PATH_RUNTIME;

            // Make a local copy of the THT runtime to app tree
            $thtBinPath = realpath(dirname($_SERVER['SCRIPT_NAME']) . '/..');
            Tht::module('*File')->u_copy_dir($thtBinPath, Tht::path('localTht'));

            // Front controller
            CliMode::writeSetupFile(Tht::getAppFileName('frontFile'), "

            <?php

            define('APP_ROOT', '$appRoot');
            define('DATA_ROOT', '$dataRoot');
            define('THT_RUNTIME', '$thtMain');

            return require_once(THT_RUNTIME);

            ");


            // .htaccess
            // TODO: don't overwrite previous
            CliMode::writeSetupFile('.htaccess', "

                ### THT APP

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

                ### END THT APP

            ");

            // We are doing compression for THT-processed files
            //
            // # Compression
            // <IfModule mod_deflate.c>
            //     <IfModule mod_filter.c>
            //         AddOutputFilterByType DEFLATE
            //           \"application/javascript\" \
            //           \"application/json\" \
            //           \"text/css\" \
            //           \"text/html\" \
            //           \"text/javascript\" \
            //     </IfModule>
            // </IfModule>

            // Starter App
            $exampleFile = 'home.tht';
            $examplePath = Tht::path('pages', $exampleFile);
            $exampleRelPath =  Tht::getRelativePath('app', $examplePath);
            $publicPath = Tht::getRelativePath('app', Tht::path('pages'));
            $exampleCssPath = Tht::path('pages', 'css.tht');

            CliMode::writeSetupFile($examplePath, "
                Response.sendPage({
                    title: 'Hello World',
                    body: bodyHtml(),
                    css: '/css',
                });

                template bodyHtml() {

                    <main>
                        <div style='margin: 2em'>

                            <h1>> Hello World
                            <.subline>> {{ Web.icon('check') }}  Congratulations!  The hard part is over.

                            <p>> Add new pages to:<br /> <code>> app/pages

                            <p>> For example, when you add this file...<br /> <code>> app/pages/testPage.tht

                            <p>> ... it will automatically become this URL:<br /> <code>> http://yoursite.com/test-page

                            <p style=\"margin-top: 4rem\">> For more info, see <a href=\"https://tht.help/tutorials/how-to-create-a-basic-web-app\">How to Create a Basic Web App</a>.
                        </>
                    </>
                }
            ");

            CliMode::writeSetupFile($exampleCssPath, "

                Response.sendCss(css());

                template css() {

                    {{ Css.include('base', 700) }}

                    body {
                        font-size: 2rem;
                        color: #222;
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
            CliMode::writeSetupFile(Tht::path('configFile'), "
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
                        showPerfPanel: false
                    }

                    // Database settings
                    // See: https://tht.help/manual/module/db
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
            if (file_exists(Tht::path('app'))) {
                echo "Move or delete your app directories before trying again:\n\n  " . Tht::path('app');
                echo "\n\n";
            }
            Tht::exitScript(1);
        }

        self::printHeaderBox('Success!');

        echo "Your new THT app directory is here:\n  " . Tht::path('app') . "\n\n";
        echo "*  Load 'http://yoursite.com' to see if it's working.\n";
        echo "*  Or run 'tht server' to start a local web server.";
        echo "\n\n";

        Tht::exitScript(0);
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
        CliMode::createDbIndex($dbh, 'cache', 'key');
        CliMode::createDbIndex($dbh, 'cache', 'expireDate');

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

        echo " [OK]\n";
    }

    static function startTestServer ($port=0, $docRoot='.') {

        $hostName = CliMode::$SERVER_HOSTNAME;

        if (!$port) {
            $port = CliMode::$SERVER_PORT;
        }
        else if ($port < 8000 || $port >= 9000) {
            echo "\nServer port must be in the range of 8000-8999.\n\n";
            Tht::exitScript(1);
        }

        if (!CliMode::isAppInstalled()) {
            echo "\nCan't find app directory.  Please `cd` to your your document root and try again.\n\n";
            Tht::exitScript(1);
        }

        self::printHeaderBox('Test Server');

        echo "App directory:\n  " . Tht::path('app') . "\n\n";
        echo "Serving app at:\n  http://$hostName:$port\n\n";
        echo "Press [Ctrl-C] to stop.\n\n";

        $controller = realpath('thtApp.php');

        passthru("php -S $hostName:$port $controller");
    }
}

