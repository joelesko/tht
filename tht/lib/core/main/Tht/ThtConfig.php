<?php

namespace o;

trait ThtConfig {

    static private $VERSION = '0.8.1 - Beta';
    static private $VERSION_DIGITS = '00801';
    static private $VERSION_DIGITS_PHP = '';  // filled in later

    static private $REQUIRE_PHP_VERSION_DIGITS = '00803';
    static private $REQUIRE_PHP_VERSION_STRING = '8.3+';

    static private $SRC_EXT = 'tht';

    static private $THT_SITE = 'https://tht.dev';
    static private $ERROR_API_URL = 'https://thtfeedback.dev/api/error';

    static private $isTimezoneError = false;
    static private $cachedTimezone = null;

    static private function getDefaultConfig() {

        $default = [

            'routes' => [
                '/' => '/home'
            ],

            'databases' => [
            ],

            'email' => [
            ],

            "logs" => [
                "logLevel" => 'all',
            ],

            'tht' => [
                // internal
                "_coreDevMode"  => false,
                "_sendErrorsUrl" => OTypeString::create('url', THT::$ERROR_API_URL),
                // TODO: doc site

                // features
                "devIp"                  => '',
                "showPrintPanel"         => true,
                "sessionDurationHours"   => 24,
                'hitCounter'             => false,
                'hitCounterExcludePaths' => [],
                'litemarkCustomTags'     => [],
                'logSlowDbQuerySecs'     => 10,
                'formatChecker'          => 'normal',

                // Perf toggles
                "showPerfPanel"          => false,
                "minifyAssetTemplates"   => true,
                "optimizeAssets"         => 'minify|gzip|images|timestamps',
                "compressOutput"         => true,

                // telemetry
                'sendErrors'             => true,

                // security
                "contentSecurityPolicy"   => '',
                "showErrorPageForMins"    => 10,
                "xDangerAllowJsEval"      => false,
                "passwordAttemptsPerHour" => 30,

                // resource limits
                "maxMemoryMb"       => 32,
                'maxRunTimeSecs'    => 10,

                // misc
                "cacheGarbageCollectRate" => 100,
                "logErrors"               => true,
                'downtime'                => '',
                'timezone'                => 'UTC',
                'scrambleNumSecretKey'    => '',

                // easter egg
                'turboMode'               => false,
            ]
        ];

        return $default;
    }

    static private function initAppConfig() {

        $perfTask = Tht::module('Perf')->u_start('tht.initAppConfig');

        // TODO: only validate if reading an un-cached file

        $appFile = self::$APP_FILE['configFile'];
        $appConfig = self::module('Jcon')->u_parse_file($appFile);
        self::validateAppConfig($appConfig, $appFile, true);

        // Get environment config
        $envName = Tht::module('System')->u_get_env_var(self::$APP_ENV_VAR, 'local');
        $envFileName = "app." . $envName . ".jcon";
        $envFile = self::module('Jcon')->getFilePath($envFileName);
        if (file_exists($envFile)) {
            $envConfig = self::module('Jcon')->u_parse_file($envFileName);
            self::validateAppConfig($envConfig, $envFile, false);
            $appConfig = $appConfig->u_merge($envConfig, OMap::create(['deep' => true]));
        }

        self::$data['config'] = $appConfig;

        $perfTask->u_stop();
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
        // TODO: check for invalid keys in all sections
        $mainKey = 'tht';
        $defConfig = self::getDefaultConfig();
        if (isset($config[$mainKey])) {
            foreach (unv($config[$mainKey]) as $k => $v) {
                if (!isset($defConfig[$mainKey][$k])) {
                    ErrorHandler::setHelpLink('/reference/app-config', 'App Config');
                    ErrorHandler::setFile(Tht::path('config', $file));
                    $try = ErrorHandler::getFuzzySuggest($k, array_keys($defConfig[$mainKey]));
                    self::configError("Unknown config key: `$mainKey.$k`  $try");
                }
            }
        }
    }

    // Get config in a top-level section (e.g. 'tht', 'databases')
    static public function getTopConfig() {

        $keys = func_get_args();
        if (is_array($keys[0])) { $keys = $keys[0]; }

        $val = self::searchConfig(self::$data['config'], $keys);
        if ($val === null) {
            $val = self::searchConfig(self::getDefaultConfig(), $keys);
            if ($val === null) {
                self::configError('Key not found in app.jcon or app.local.jcon: `' . implode('.', $keys) . '`');
            }
        }

        return $val;
    }

    // Get a THT-level config
    static public function getThtConfig() {

        $args = func_get_args();

        array_unshift($args, 'tht');

        return self::getTopConfig($args);
    }

    // Get user 'app'-level config
    static public function getAppConfig($key, $default = null) {

        $keys = explode('.', $key);
        array_unshift($keys, 'app');

        $val = self::searchConfig(self::$data['config'], $keys);

        if ($val === null && $default === null) {
            self::error("No `app` config value for key: `$key`");
        }

        return $val === null ? $default : $val;
    }

    // Recursively go down the chain of keys to find a config value
    static public function searchConfig($config, $keys) {

        $ref = $config;

        while (count($keys)) {
            $key = array_shift($keys);
            if (!isset($ref[$key]) && $key !== '*') {
                return null;
            }
            $ref = $ref[$key];
        }

        return $ref;
    }

    static public function getAllConfig() {
        return self::$data['config'];
    }

    static public function getFuzzyConfigKey($keyList, $findKey) {

        // Only look for the parent
        array_pop($keyList);

        $parent = self::searchConfig(self::$data['config'], $keyList);
        if ($parent) {
            $keysInScope = array_keys(unv($parent));
        }
        else {
            return '';
        }
    }

    // Safe way to get timezone from config.
    static public function getTimezone() {

        if (self::$cachedTimezone !== null) {
            return self::$cachedTimezone;
        }

        // Error handling code also uses Date methods, so prevent catch-22.
        if (self::$isTimezoneError) {
            return \date_default_timezone_get();
        }

        $configTz = Tht::getThtConfig('timezone');

        try {
            $tz = new \DateTimeZone($configTz);
            self::$cachedTimezone = $configTz;
            return $configTz;
        } catch (\Exception $e) {
            self::$isTimezoneError = true;
            Tht::configError("Config value for `timezone` is not valid: `$configTz`");
        }
    }

}