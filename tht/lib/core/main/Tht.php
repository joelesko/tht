<?php

// All THT runtime files are namespace 'o', originally because the language was "OWL".
// But now it kind of works as a non-noisy letter.  It should stay simple because it shows
// up everywhere in transpiled code.
namespace o;

// Set this to true if you are developing THT and need the raw PHP error output or
// need to avoid getting a blank error page.
define('DEV_ERRORS', false);

require_once('Error/ErrorHandler.php');
require_once('Error/ErrorHandlerMinimal.php');

// Traits
require_once('Tht/ThtErrors.php');
require_once('Tht/ThtInit.php');
require_once('Tht/ThtPaths.php');
require_once('Tht/ThtConfig.php');

// Sort of a god object
class Tht {

    // TODO: Right now these are all just interdependent mixins.
    // Probably want to separate these into sub-objects or something.
    use ThtInit;
    use ThtPaths;
    use ThtConfig;
    use ThtErrors;

    static private $files = [];

    static private $data = [
        'requestData' => [],
        'config'      => [],
    ];

    static private $mode = [
        'cli'         => false,
        'web'         => false,
        'testServer'  => false,
    ];


    // MAIN FLOW
    //---------------------------------------------

    static public function main() {

        self::initThtRootPath();
        self::initMode();
        self::loadLibs();
        self::checkRequirements();
        
        return ErrorHandler::catchErrors(
            function () {
                return self::runMode();
            }
        );
    }

    static private function runMode() {

        if (Tht::isMode('cli')) {
            self::initAppPaths();
            self::loadLib('lib/core/main/Modes/CliMode.php');
            return CliMode::main();
        }
        else {
            self::initRequestData();
            self::initAppPaths();
            self::initAppConfig();  
            Security::initPhpIni();

            self::loadLib('lib/core/main/Modes/WebMode.php');
            return WebMode::main();
        }
    }

    static public function exitScript($exitCode) {

        if (self::isMode('web') && !$exitCode) {
            WebMode::onEnd();
        }

        exit($exitCode);
    }


    // INTERNAL DEV ONLY
    //---------------------------------------------

    static public function debug() {
        Tht::module('*Bare')->u_print(...func_get_args());
    }

    static public function dump($v) {
        print("<pre style='font-weight: bold; font-size: 150%;'>");
        print_r($v);
        print("</pre>");
        exit(0);
    }



    // CONSTANT GETTERS
    //---------------------------------------------

    static public function getThtExt() {

        return self::$SRC_EXT;
    }

    static public function getThtSiteUrl($relPath) {

        return self::$THT_SITE . $relPath;
    }

    static public function getThtVersion($digits=false) {

        return $digits ? self::$VERSION_DIGITS : self::$VERSION;
    }

    // Get a token that includes the version of THT and PHP
    static public function getThtPhpVersionToken() {

        if (!self::$VERSION_DIGITS_PHP) {
            self::$VERSION_DIGITS_PHP = self::$VERSION_DIGITS . floor(PHP_VERSION_ID / 100);
        }

        return self::$VERSION_DIGITS_PHP;
    }



    // DATA GETTERS
    //---------------------------------------------


    static public function module($name) {

        return ModuleManager::get($name);
    }

    static public function isMode($m) {

        return self::$mode[$m];
    }

    static public function isWindows() {
        return Tht::module('System')->u_get_os() == 'windows';
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

    static public function getInfoDump() {

        $copy = self::$data;

        $copy['tht'] = [
            'version' => self::getThtVersion(),
            'appPath' => self::path('app'),

            // TODO: tht page
            // TODO: entry function (auto-call in WebMode)
        ];

        return $copy;
    }

    static public function getPhpGlobal($g, $key, $def='') {

        if (!isset(self::$data['requestData'][$g])) {
            return $def;
        }

        $val = self::$data['requestData'][$g];

        if ($key !== '*') {
            if (!isset($val[$key])) {
                return $def;
            }
            $val = $val[$key];
        }

        $val = Security::sanitizeInputString($val);

        return $val;
    }

    static public function isOpcodeCacheEnabled() {

        if (function_exists('opcache_get_status')) {
            $opStatus = opcache_get_status();
            return $opStatus && $opStatus['opcache_enabled'];
        }

        return false;
    }
}

