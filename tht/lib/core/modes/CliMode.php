<?php

namespace o;

class CliMode {

    static private $SERVER_PORT = 8888;

    static private $CLI_OPTIONS = [
        'new'    => 'new',
        'server' => 'server',
        'run'    => 'run',
    ];

    static private $options = [];

    static function main() {

        CliMode::initOptions();

        $firstOption = CliMode::$options[0];

        if ($firstOption === CliMode::$CLI_OPTIONS['new']) {
            Tht::initPaths(true);
            CliMode::installApp();
        }
        else if ($firstOption === CliMode::$CLI_OPTIONS['server']) {
            Tht::initPaths(true);
            CliMode::startTestServer();  // TODO: support run from tht parent, port
        }
        else if ($firstOption === CliMode::$CLI_OPTIONS['run']) {
            // Tht::init();
            // Source::process(CliMode::$options[1]);
        }
        else {
            echo "\nUnknown argument.\n";
            CliMode::printUsage();
        }
    }

    static private function printUsage() {
        echo "\nTHT - v" . Tht::$VERSION . "\n";
        echo "\nUsage:\n\n";
        echo "tht new              (create a new app)\n";
        echo "tht server           (start local test server)\n";
     //   echo "tht run <filename>   (run script in scripts directory)\n";
        echo "\n";
        exit(0);
    }

    static private function initOptions () {
        global $argv;
        if (count($argv) === 1) {
            CliMode::printUsage();
        }
        CliMode::$options = array_slice($argv, 1);
    }

    static function isAppInstalled () {
        $appRoot = Tht::path('root');
        return $appRoot && file_exists($appRoot);
    }

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

        CliMode::confirmInstall();

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

            CliMode::writeSetupFile(Tht::$FILE['frontFile'], "

            <?php

                 define('APP_ROOT', '$appRoot');
                 define('THT_MAIN', '$thtBinPath');

                 return require_once(THT_MAIN);

            ");


            // .htaccess
            // TODO: don't overwrite previous
            CliMode::writeSetupFile('.htaccess', "

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

            CliMode::writeSetupFile($examplePath, "
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

                            <p style=\"margin-top: 4rem\">> For more info, see <a href=\"https://tht.help/tutorials/how-to-create-a-basic-web-app\">How to Create a Basic Web App</a>.
                        </>
                    </>
                }
            ");

            CliMode::writeSetupFile($exampleCssPath, "

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
                        showPerfScore: false
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


    static function startTestServer ($hostName='localhost', $port=0, $docRoot='.') {

        if (!$port) {
            $port = CliMode::$SERVER_PORT;
        }
        if ($port <= 1024) {
            Tht::error("Server port must be greater than 1024.");
        }

        if (!CliMode::isAppInstalled()) {
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

}

