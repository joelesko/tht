<?php

namespace o;

define('ONE_INDEX', 1);

trait ThtInit {

    private static $APP_ENV_VAR = 'APP_ENV';
    private static $REQUIRED_PHP_LIBS = ['mbstring', 'fileinfo', 'gd'];

    static private function checkRequirements() {

        if (PHP_VERSION_ID < self::$REQUIRE_PHP_VERSION_DIGITS) {
            Tht::startupError("PHP version " . self::$REQUIRE_PHP_VERSION_STRING . " or higher is required.\n\nCurrent Version: " . phpversion());
        }

        foreach (self::$REQUIRED_PHP_LIBS as $lib) {
            self::checkRequiredPhpLib($lib);
        }
    }

    static public function checkRequiredPhpLib($lib, $altHelpMsg = '') {

        if (!extension_loaded($lib)) {
            Tht::phpLibError($lib, $altHelpMsg);
        }
    }

    static private function repairPhpIni() {

        $iniFile = php_ini_loaded_file() ?: '';
        if (!$iniFile) {
            $prodIni = PHP_CONFIG_FILE_PATH . '/php.ini-production';
            if (file_exists($prodIni)) {
                $iniFile = PHP_CONFIG_FILE_PATH . '/php.ini';
                copy($prodIni, $iniFile);
            }
            else {
                // ???
            }
        }

        $iniContent = file_get_contents($iniFile);
        $iniContent = preg_replace('/;(extension=(mbstring|fileinfo))/', '$1');

        file_put_contents($iniFile, $iniContent);

    }

    static private function initRequestData() {

        Tht::$data['requestData'] = Security::initRequestData();
    }

    static private function initMode() {

        $sapi = php_sapi_name();

        Tht::$mode['testServer'] = $sapi === 'cli-server';
        Tht::$mode['cli'] = $sapi === 'cli';
        Tht::$mode['web'] = !Tht::$mode['cli'];
    }

    static private function loadLibs() {

        self::loadLib('lib/core/compiler/Compiler.php');

        self::loadLib('lib/core/runtime/PrintPanel.php');
        self::loadLib('lib/core/runtime/Runtime.php');
        self::loadLib('lib/core/runtime/ModuleManager.php');
        self::loadLib('lib/core/runtime/Security.php');

        self::loadLib('lib/core/utils/GlobalFunctions.php');
        self::loadLib('lib/core/utils/StringReader.php');

        self::loadLib('lib/stdlib/classes/_index.php');
        self::loadLib('lib/stdlib/modules/_index.php');
    }

    static public function loadLib($file) {

        require_once(Tht::systemPath($file));
    }

}
