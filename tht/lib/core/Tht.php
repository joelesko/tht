<?php

namespace o;

include_once('main/ThtInit.php');
include_once('main/ThtPaths.php');
include_once('main/ThtSideload.php');
include_once('main/ThtConfig.php');
include_once('main/ThtErrors.php');

class Tht {

    // TODO: Right now these are all just interdependent mixins.
    // Probably want to separate these into sub-objects or something.
    use ThtInit;
    use ThtPaths;
    use ThtSideload;
    use ThtConfig;
    use ThtErrors;

    static private $VERSION = '0.7.0 - Beta';
    static private $VERSION_DIGITS = '00700';
    static private $VERSION_DIGITS_PHP = '';  // filled in later

    static private $SRC_EXT = 'tht';

    static private $THT_SITE = 'https://tht.dev';
    static private $ERROR_API_URL = 'https://thtfeedback.dev/api/error';

    static private $files = [];

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



    // MAIN FLOW
    //---------------------------------------------

    static private function premain() {

        self::catchPhpCompileErrors();
        self::initMode();
        self::checkRequirements();
        self::initAppPaths();
        self::loadLibs();

    }

    static public function main() {

        self::premain();

        $mainFn = function() {

            if (Tht::isMode('cli')) {

                self::loadLib('modes/CliMode.php');
                CliMode::main();

                return true;
            }
            else if (Tht::isMode('sideload')) {

                Tht::initWebMode();

                return true;
            }
            else {

                Tht::initWebMode();

                return WebMode::main();
            }
        };

        return ErrorHandler::catchErrors($mainFn);
    }

    static private function initWebMode () {

        self::initRequestData();
        self::initAppPaths();
        self::initAppConfig();

        Security::initPhpIni();

        self::loadLib('modes/WebMode.php');
    }

    // Includes take ~10ms
    static private function loadLibs() {

        self::loadLib('utils/GlobalFunctions.php');
        self::loadLib('utils/StringReader.php');

        self::loadLib('compiler/Compiler.php');

        self::loadLib('runtime/Error/ErrorHandler.php');

        self::loadLib('runtime/HitCounter.php');  // TODO: lazy load this
        self::loadLib('runtime/PrintBuffer.php');
        self::loadLib('runtime/Runtime.php');
        self::loadLib('runtime/ModuleManager.php');
        self::loadLib('runtime/Security.php');

        self::loadLib('../classes/_index.php');
        self::loadLib('../modules/_index.php');
    }

    static public function loadLib ($file) {

        $libDir = dirname(__FILE__);
        require_once(self::makePath($libDir, $file));
    }

    static public function exitScript($code) {

        if (self::isMode('web') && !$code) {
            WebMode::onEnd();
        }

        exit($code);
    }


    // INTERNAL DEV ONLY
    //---------------------------------------------

    static public function debug () {

        Tht::module('*Bare')->u_print(...func_get_args());
    }

    static public function dump ($v) {

        print("<pre style='font-weight: bold'>");
        print(Tht::module('Json')->u_format($v));
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


    static public function module ($name) {

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

    static public function getPhpGlobal ($g, $key, $def='') {

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

