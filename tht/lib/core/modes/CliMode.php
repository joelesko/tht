<?php

namespace o;

class CliMode {

    static private $SERVER_PORT = 3333;
    static private $SERVER_HOSTNAME = 'localhost';

    static public $CLI_OPTIONS = [
        'new'    => 'new',
        'server' => 'server',
        'info'   => 'info',
        'fix'    => 'fix',
     // 'run'    => 'run',
    ];

    static private $options = [];

    static function main() {

        self::initOptions();
        $firstOption = self::$options[0];

        if (!$firstOption) {
            self::printUsage();
        }
        else if ($firstOption === self::$CLI_OPTIONS['new']) {

            self::newApp(isset(self::$options[1]) ? self::$options[1] : '');
        }
        else if ($firstOption === self::$CLI_OPTIONS['info']) {
            self::info();
        }
        else {

            if (!self::isAppInstalled()) {
                self::error("Please 'cd' to your app directory.  Then rerun this command.");
            }
            else if ($firstOption === self::$CLI_OPTIONS['server']) {
                $port = isset(self::$options[1]) ? self::$options[1] : 0;
                self::startTestServer($port);
            }
            else if ($firstOption === self::$CLI_OPTIONS['fix']) {
                self::fix();
            }
            // else if ($firstOption === self::$CLI_OPTIONS['run']) {
            //     // Tht::init();
            //     // Compiler::process(self::$options[1]);
            // }
            else {
                self::printUsage();
            }
        }
    }

    static private function printUsage() {

        self::printHeaderBox('THT');

        echo "- Version: " . Tht::getThtVersion() . "\n\n";
        echo "- Commands:\n\n";
        echo "  · new <appName>   create an app in the current dir\n";
        echo "  · fix             clear app cache and update file permissions\n";
        echo "  · info            get detailed config and install info\n";
        echo "  · server          start the local test server (port: 3333)\n";
        echo "  · server <port>   start the local test server on a custom port\n";
     // echo "tht run <filename>   (run script in scripts directory)\n";

        echo "\n> Usage: tht [command]\n";

        echo "\n";
        Tht::exitScript(0);
    }

    static function printHeaderBox($title) {

        $title = trim(strtoupper($title));
        $line = str_repeat('-', strlen($title) + 8);

        echo "\n";
        echo "-$line" . "-\n";
        echo "     $title     \n";
        echo "-$line" . "-\n\n";

        flush();
    }

    static private function initOptions () {
        global $argv;
        if (count($argv) === 1) {
            self::printUsage();
        }
        self::$options = array_slice($argv, 1);
    }

    static function isAppInstalled () {
        $appRoot = Tht::path('app');
        return $appRoot && file_exists($appRoot);
    }

    static private function info() {

        $info = [
            '- THT Version'     => Tht::getThtVersion(),
            '- THT Path'        => $_SERVER['SCRIPT_FILENAME'],
            '- PHP Version'     => Tht::module('Php')->u_get_version(),
            '- php.ini Path'    => php_ini_loaded_file(),
            '- Opcache'         => is_array(opcache_get_status()) ? '✓ ON' : '✕ OFF',
            '- APCu Cache'      => Tht::module('Cache')->u_get_driver() == 'apcu' ? '✓ ON' : '✕ OFF',
        ];

        self::printHeaderBox('THT Info');
        foreach ($info as $k => $v) {
            echo "$k:\t $v\n";
        }
        echo "\n";
        flush();
    }

    static private function fix() {

        self::printHeaderBox('Fix THT App');

        $appDir = Tht::path('app');

        if (Tht::module('System')->u_get_os() != 'windows') {
            $msg = '> Set app file permissions?';
            if (Tht::module('System')->u_confirm($msg)) {
                self::setPerms();
            }
            echo "\n";
        }

        self::clearCache('Transpiler', 'phpCache');
        self::clearCache('App', 'kvCache');

        // TODO: Create missing app directories or config file?
        // TODO: Check local THT version and copy to app if updated

        // TODO: This has a bug, and not sure if we want to do this yet.
        //echo "- Copying local THT runtime to app...\n";
        //self::copyLocalThtRuntimeToApp();

        self::printHeaderBox('Done!');

        flush();
    }

    static private function clearCache($name, $dirKey) {

        echo "- Clearing $name Cache...\n";
        $num = 0;

        $files = glob(Tht::path($dirKey, '*'));

        $path = Tht::path($dirKey);
        if (!is_dir($path)) {
            self::error("Can not find app data directory.\n\n> Please `cd` to your app directory and re-run the command.");
        }

        foreach($files as $file){
            if (is_file($file)) {
                unlink($file);
                $num += 1;
            }
        }
        echo "  Cache files deleted: $num\n\n";
        flush();
    }

    static private function setPerms() {

        $currentUser = get_current_user();

        $devUser = Tht::module('System')->u_input('> Name of developer user (' . $currentUser . ')?', $currentUser);
        $wwwGroup = Tht::module('System')->u_input('> Name of web server group (www-data)?', 'www-data');

        $user    = escapeshellarg($devUser);
        $group   = escapeshellarg($wwwGroup);
        $dir     = escapeshellarg(Tht::path('app'));
        $dataDir = escapeshellarg(Tht::path('data'));

        echo "\n- Setting App File Permissions...\n\n";

        self::setPerm("chown -R $user $dir");
        self::setPerm("chgrp -R $group $dir");

        self::setPerm("chmod -R 770 $dir");

        flush();
    }

    static private function setPerm($cmd) {

        echo $cmd . "\n";
        $ok = exec($cmd, $out, $retval);
        if ($retval) {
            self::error("Error setting permissions.  Run `sudo tht fix`?");
        }
    }

    static private function confirmNewApp($appDir) {

        $msg = "- App name: $appDir\n\n> Create new THT app in current directory?";

        if (!Tht::module('System')->u_confirm($msg)) {
            echo "\n- Skipping new app\n\n";
            Tht::exitScript(0);
        }

        usleep(500000);
    }

    static private function error($msg) {
        echo "\n<!> $msg\n\n";
        Tht::exitScript(1);
    }

    static private function newApp ($appDir) {

        self::printHeaderBox('New App');

        if (!$appDir || preg_match('/[^a-zA-Z0-9_]/', $appDir)) {
            self::error("Please provide an alpha-numeric app name.\n\n  Ex: tht new myApp");
        }

        $fullAppDir = Tht::makePath(getcwd(), $appDir);
        if (file_exists($fullAppDir)) {
            $msg = "The app directory already exists.\n\nTo start over, just delete or move that directory. Then rerun this command.";
            self::error($msg);
        }

        self::confirmNewApp($appDir);

        try {
            $appRoot = self::initNewAppBaseDirs($appDir);
            Tht::initAppPaths($appRoot);

            // Make a local copy of the THT runtime to app tree
            $srcPath = realpath(__DIR__ . '/../../../sites/starter');
            Tht::module('*File')->u_copy_dir($srcPath, $appDir);

            self::copyLocalThtRuntimeToApp();
            self::createAppFavicon(basename($appDir));
            self::writeAppName(basename($appDir));
            self::writeScrambleKey();
        }
        catch (\Exception $e) {
            echo "\n<!> Sorry, something went wrong.\n\n";
            echo "  " . $e->getMessage() . "\n\n";
            if (file_exists(Tht::path('app'))) {
                echo "> Move or delete your app directories before trying again:\n\n  " . Tht::path('app');
                echo "\n\n";
            }
            Tht::exitScript(1);
        }

        self::printHeaderBox('Success!');

        echo "> To run your app with the local server, run these commands:\n\n";
        echo "  1.  cd $appDir \n";
        echo "  2.  tht server";
        echo "\n\n";

        Tht::exitScript(0);
    }

    static private function copyLocalThtRuntimeToApp() {

        $thtBinPath = Tht::realpath(dirname($_SERVER['SCRIPT_NAME']) . '/..');

        Tht::module('*File')->u_copy_dir($thtBinPath . '/run', Tht::path('localTht', 'run'));
        Tht::module('*File')->u_copy_dir($thtBinPath . '/lib', Tht::path('localTht', 'lib'));
    }

    static private function initNewAppBaseDirs($appDir) {

        $appRoot = Tht::makePath(getcwd(), $appDir);

        if (file_exists($appRoot)) {
            self::error("App directory '$appDir' already exists.  Please remove it and try again.");
        }

        Tht::module('*File')->u_make_dir($appRoot, '750');

        return $appRoot;
    }

    static function startTestServer ($port=0) {

        $hostName = self::$SERVER_HOSTNAME;

        if (!$port) {
            $port = self::$SERVER_PORT;
        }
        else if ($port < 1024 || $port >= 10000) {
            echo "\nServer port must be in the range of 1024-9999.\n\n";
            Tht::exitScript(1);
        }

        self::printHeaderBox('Test Server');

        $controller = Tht::path('public', 'front.php');

        if (!file_exists($controller)) {
             self::error("Can not find front controller file:\n  $controller\n\n  Please 'cd' to your app directory and re-run the command.");
        }

        $name = basename(Tht::path('app'));

        echo "- App: $name\n";
        echo "- URL: http://$hostName:$port\n\n";
        echo "> Press [Ctrl-C] to stop.\n\n";

        $controllerArg = escapeshellarg(realpath($controller));
        $docRootArg = '-t ' . escapeshellarg(Tht::path('public'));

        flush();

        passthru("php -S $hostName:$port $docRootArg $controllerArg");
    }

    static function createAppFavicon($appName) {

        if (!extension_loaded('gd')) { return false; }

        $text = strtoupper(substr($appName, 0, 1));

        $icon = Tht::module('Image')->u_create(OMap::create([ 'sizeX' => 128, 'sizeY' => 128 ]));

        $icon->u_fill(OMap::create([ 'x' => 1, 'y' => 1 ]), OMap::create([ 'color' => 'white' ]));
        $icon->u_draw_text($text, OMap::create([ 'x' => 1, 'y' => 20, 'alignX' => 'center', 'fontSize' => 110, 'sizeX' => 128, 'color' => 'black' ]));

        $icon->u_save('public/images/favicon_128.png');
    }

    static function writeAppName($appName) {
        $filePath = Tht::path('modules', 'App.tht');
        self::replaceText($filePath, 'MyApp', v($appName)->u_to_token_case('label'));
    }

    static function writeScrambleKey() {
        $filePath = Tht::path('config', 'app.local.jcon');
        $key = Security::getRandomScrambleKey();
        self::replaceText($filePath, '__scrambleNumSecretKey__', $key);
    }

    static function replaceText($filePath, $search, $replace) {
        $content = Tht::module('*File')->u_read($filePath, OMap::create([ 'join' => true ]));
        $content = str_replace($search, $replace, $content);
        Tht::module('*File')->u_write($filePath, $content);
    }
}

