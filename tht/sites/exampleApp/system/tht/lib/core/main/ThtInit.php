<?php

namespace o;

define('ONE_INDEX', 1);

trait ThtInit {

    private static $APP_ENV_VAR = 'APP_ENV';

    static private function checkRequirements() {

        if (PHP_VERSION_ID < 70100) {
            self::reqError("THT Startup Error: PHP version 7.1+ is required.\n\nCurrent Version: " . phpversion());
        }

        self::checkRequiredPhpLib('mbstring');
        self::checkRequiredPhpLib('fileinfo');
        self::checkRequiredPhpLib('openssl');

        // TODO:  Probably replace this with cUrl, but that means including (or dynamically fetching)
        // the large-ish mozilla ca cert ca bundle.
        if (!ini_get('allow_url_fopen')) {
            self::reqError("php.ini setting `allow_url_fopen` must be set to `On`.  This is used by the Net module.\n\nTry: Edit `__INI__`. Then restart server.");
        }
    }

    static private function checkRequiredPhpLib($lib) {

        if (!extension_loaded($lib)) {
            self::reqError(self::getLibIniError($lib));
        }
    }

    static public function getLibIniError($lib) {

        $msg = "PHP extension `$lib` must be loaded.\n\nTry: Uncomment the line `extension=$lib` in `__INI__`. Then restart server.";

        $iniPath = php_ini_loaded_file();
        $msg = str_replace("__INI__", $iniPath, $msg);

        return $msg;
    }

    static private function reqError($msg) {

        $msg = "\n--- THT Startup Error ---\n\n" . $msg;

        if (self::isMode('web')) {

            $msg = str_replace("\n", "<br>\n", $msg);
        }

        print($msg . "\n\n");

        exit(1);
    }

    static private function initRequestData() {

        self::$data['requestData'] = Security::initRequestData();
    }

    static private function initMode() {

        $sapi = php_sapi_name();

        self::$mode['testServer'] = $sapi === 'cli-server';
        self::$mode['cli'] = $sapi === 'cli';
        self::$mode['web'] = !self::$mode['cli'];
    }

    static private function findTopAppDir() {

        if (Tht::isMode('cli')) {
            // CLI Mode: Assume it is the current directory
            $fullPath = self::normalizeWinPath(getcwd());
        }
        else {
            // Web Mode: parent of 'public'
            $docRoot = self::normalizeWinPath(
                self::getPhpGlobal('server', 'DOCUMENT_ROOT')
            );
            $tryPath = self::makePath($docRoot, '..');
            $fullPath = self::realpath($tryPath);
        }

        return $fullPath;
    }

    static private function initAppConfig () {

        self::module('Perf')->start('tht.initAppConfig');

        $appFile = self::$APP_FILE['configFile'];
        $appConfig = self::module('Jcon')->u_parse_file($appFile);

        // Get environment config
        $envName = Tht::module('System')->u_get_env_var(self::$APP_ENV_VAR, 'local');
        $envFileName = "app." . $envName . ".jcon";
        $envFile = self::module('Jcon')->getFilePath($envFileName);
        if (file_exists($envFile)) {
            $envConfig = self::module('Jcon')->u_parse_file($envFileName);
            self::validateAppConfig($envConfig, $envFile, false);

            // TODO: can probably optimize this merge
            $appConfig = $appConfig->u_merge($envConfig, false, true);
        }

        self::validateAppConfig($appConfig, $appFile, true);

        self::$data['config'] = $appConfig;

        self::module('Perf')->u_stop();
    }

    static private function validateAppConfig($config, $file, $isTop) {

        // Make sure the required top-level keys exist.
        if ($isTop) {
            foreach (['tht', 'routes'] as $key) {
                if (!isset($config[$key])) {
                    self::configError("Missing top-level key `$key` in `" . $file . "`.");
                }
            }
        }

        // Check for invalid keys in 'tht' section.
        $mainKey = 'tht';
        $defConfig = self::getDefaultConfig();
        if (isset($config[$mainKey])) {
            foreach (unv($config[$mainKey]) as $k => $v) {
                if (!isset($defConfig[$mainKey][$k])) {
                    ErrorHandler::setHelpLink('/reference/app-config', 'App Config');
                    self::configError("Unknown config key `$mainKey.$k` in `" . $file . "`.");
                }
            }
        }
    }

}