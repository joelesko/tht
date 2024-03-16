<?php

namespace o;

trait ThtConfig {

    static private $VERSION = '0.7.2 - Beta';
    static private $VERSION_DIGITS = '00702';
    static private $VERSION_DIGITS_PHP = '';  // filled in later

    static private $REQUIRE_PHP_VERSION_DIGITS = '00803';
    static private $REQUIRE_PHP_VERSION_STRING = '8.3+';

    static private $SRC_EXT = 'tht';

    static private $THT_SITE = 'https://tht.dev';
    static private $ERROR_API_URL = 'https://thtfeedback.dev/api/error';

    static private $isTimezoneError = false;

    static private function getDefaultConfig () {

        $default = [];

        $default['routes'] = [
            '/' => '/home'
        ];

        $default['tht'] = [

            // internal
            "_coreDevMode"  => false,
            "_sendErrorsUrl" => OTypeString::create('url', THT::$ERROR_API_URL),

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

            // misc
            "cacheGarbageCollectRate" => 100,
            "logErrors"               => true,

            // resource limits
            "memoryLimitMb"        => 16,
            'maxExecutionTimeSecs' => 20, // starts at request start, lasts until execution ends
            'maxInputTimeSecs'     => 10, // starts at request start, ends when request received, execution starts

            'downtime' => '',

            'timezone' => 'UTC',

            'turboMode' => false,

            'scrambleNumSecretKey' => '',
        ];

        return $default;
    }

    // Get config in a top-level section (e.g. 'tht', 'databases')
    static public function getTopConfig() {

        $args = func_get_args();
        if (is_array($args[0])) { $args = $args[0]; }

        $val = self::searchConfig(self::$data['config'], $args);
        if ($val === null) {
            $val = self::searchConfig(self::getDefaultConfig(), $args);
            if ($val === null) {
                self::startupError('No config value for key: `' . implode('.', $args) . '`');
            }
        }

        return $val;
    }

    // Get a THT-level config
    static public function getConfig () {

        $args = func_get_args();

        array_unshift($args, 'tht');

        return self::getTopConfig($args);
    }

    // Get user 'app'-level config
    static public function getAppConfig ($key, $default = null) {

        $keys = explode(' > ', $key);
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


    // Safe way to get timezone if timezone ID in config is invalid.
    // Error handling code also uses Date methods, so it's a catch 22.
    static public function getTimezone() {

        if (self::$isTimezoneError) {
            return \date_default_timezone_get();
        }

        $config = Tht::getConfig('timezone');
        try {
            $tz = new \DateTimeZone($config);
            return $config;
        } catch (\Exception $e) {
            self::$isTimezoneError = true;
            Tht::configError("Timezone ID `$config` is not valid.");
        }
    }

}