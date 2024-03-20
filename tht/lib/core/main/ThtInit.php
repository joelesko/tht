<?php

namespace o;

define('ONE_INDEX', 1);

trait ThtInit {

    private static $APP_ENV_VAR = 'APP_ENV';

    static private function checkRequirements() {

        if (PHP_VERSION_ID < self::$REQUIRE_PHP_VERSION_DIGITS) {
            self::reqError("THT Startup Error: PHP version " . self::$REQUIRE_PHP_VERSION_STRING. " is required.\n\nCurrent Version: " . phpversion());
        }

        self::checkRequiredPhpLib('mbstring', 'startup');
        self::checkRequiredPhpLib('fileinfo', 'startup');
    }

    static private function checkRequiredPhpLib($lib, $stage) {

        if (!extension_loaded($lib)) {
            $msg = self::getLibIniError($lib);
            if ($stage == 'startup') {
                self::startupReqError($msg);
            }
            else {
                Tht::error($msg);
            }
        }
    }

    static public function getLibIniError($lib) {

        $msg = "PHP extension `$lib` must be installed and enabled. Then restart server.";

        return self::getIniError($msg);
    }

    static public function getIniError($msg) {

        $iniPath = php_ini_loaded_file();
        $msg = str_replace("__INI__", $iniPath, $msg);

        return $msg;
    }

    static private function startupReqError($msg) {

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
            $appConfig = $appConfig->u_merge($envConfig, OMap::create(['deep' => true]));
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
                    self::configError("Missing top-level key `$key` in file: `" . $file . "`");
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
                    ErrorHandler::setFile($file);
                    self::configError("Unknown config key: `$mainKey.$k`");
                }
            }
        }
    }

}