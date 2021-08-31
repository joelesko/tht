<?php

namespace o;

// TODO: Factor out redundancies with File module methods
trait ThtPaths {

    static private $paths = [];

    // Mapping of path id to actual dir name.
    static private $APP_DIR = [

        'app'       => 'app',

        'public'    => 'public',

        'code'      => 'code',
        'pages'     =>   'pages',
        'modules'   =>   'modules',
        'phpLib'    =>   'php',
        'scripts'   =>   'scripts',

        'config'    =>  'config',

        'system'    =>  'system',
        'localTht'    =>    'tht',

        'data'      => 'data',
        'db'        =>   'db',
        'files'     =>   'files',
        'logs'     =>    'logs',
        'temp'      =>   'temp',
        'sessions'  =>     'sessions',
        'cache'     =>     'cache',
        'phpCache'  =>       'compiler',
        'kvCache'   =>       'keyValue',
        'counter'   =>   'counter',
        'counterPage' =>   'page',
        'counterDate' =>   'date',
        'counterRef'  =>   'referrer',

    ];

    // Mapping of path id to actual file name.
    static private $APP_FILE = [
        'configFile'          => 'app.jcon',
        'localConfigFile'     => 'app.local.jcon',
        'appCompileTimeFile'  => '_appCompileTime',
        'logFile'             => 'app.log',
        'frontFile'           => 'front.php',
        'homeFile'            => 'home.tht',
    ];

    // TODO: public to allow CliMode access.  Revisit this.
    static public function initAppPaths($appDir = '') {

        if (!$appDir) {
            self::$paths['app'] = self::findTopAppDir();
        }
        else {
            self::$paths['app'] = $appDir;
        }

        // Define subdirectories
        $dirs = [

            ['app', 'code'],
                ['code', 'pages'],
                ['code', 'modules'],
                ['code', 'scripts'],
                ['code', 'phpLib'],

            ['app', 'config'],

            ['app', 'public'],

            ['app', 'system'],
                ['system', 'localTht'],

            ['app', 'data'],
                ['data', 'db'],
                ['data', 'files'],
                ['data', 'logs'],
                ['data', 'counter'],
                    ['counter', 'counterPage'],
                    ['counter', 'counterDate'],
                    ['counter', 'counterRef'],
                ['data', 'temp'],
                    ['temp', 'sessions'],
                    ['temp', 'cache'],
                        ['cache', 'phpCache'],
                        ['cache', 'kvCache'],
        ];

        foreach ($dirs as $d) {
            $parent = $d[0];
            $key = $d[1];
            self::$paths[$key] = self::path($parent, self::$APP_DIR[$key]);
        }

        // Define file paths
        $files = [
            ['config',   'configFile'],
            ['config',   'localConfigFile'],
            ['phpCache', 'appCompileTimeFile'],
            ['logs',     'logFile'],
        ];

        foreach ($files as $f) {
            $parent = $f[0];
            $key = $f[1];
            self::$paths[$key] = self::path($parent, self::$APP_FILE[$key]);
        }
    }

    static public function path() {

        $parts = func_get_args();
        $base = $parts[0];
        if (!isset(self::$paths[$base])) {
            self::error("Unknown base path key: `$base`");
        }
        $parts[0] = self::$paths[$base];

        return self::makePath($parts);
    }

    static public function getAppFileName($key) {

        return self::$APP_FILE[$key];
    }

    static public function getAllPaths() {

        return self::$paths;
    }

    static public function validatePath($path) {

        if ($path && strpos('..', $path) !== false) {
            self::error("Parent shortcut `..` not allowed in path: `$path`");
        }
    }

    static public function makePath () {

        $args = func_get_args();
        if (is_array($args[0])) { $args = $args[0]; }

        $path = implode('/', $args);
        $path = self::normalizeWinPath($path);

        $path = str_replace('//', '/', $path); // prevent double slashes
        $path = rtrim($path, '/');

        self::validatePath($path);

        return $path;
    }

    static public function getRelativePath ($baseKey, $fullPath) {

        $basePath = self::path($baseKey);
        $rel = str_replace(self::realpath($basePath), '', $fullPath);
        self::validatePath($fullPath);

        return ltrim($rel, '/');
    }

    static public function realpath($path) {

        $real = realpath($path);
        return self::normalizeWinPath($real);
    }

    static public function getFullPath ($file) {

        if (substr($file, 0, 1) === '/') {
            return $file;
        }

        return self::makePath(self::realpath(getcwd()), $file);
    }

    static public function getPhpPathForTht ($thtFile) {

        $relPath = self::module('File')->u_strip_root_path($thtFile, self::$paths['code']);
        $cacheFile = preg_replace('/[\\/]/', '_', $relPath);

        return self::path('phpCache', self::getThtPhpVersionToken() . '_' . $cacheFile . '.php');
    }

    static public function getThtPathForPhp ($phpPath) {

        if (preg_match('/\.tht$/', $phpPath)) {
            // Already the THT path.
            return $phpPath;
        }

        // The relative THT path is encoded in the compiled file name.
        $f = basename($phpPath);
        $f = preg_replace('!\d+_!', '', $f);
        $f = preg_replace('/\.php/', '', $f);
        $f = str_replace('_', '/', $f);

        return self::path('code', $f);
    }

    static public function getThtFileName ($fileBaseName) {

        return $fileBaseName . '.' . self::$SRC_EXT;
    }

    static public function stripAppRoot($path) {

        $path = self::normalizeWinPath($path);

        $path = str_replace(self::path('app'), '', $path);
        $path = str_replace(self::path('public'), '', $path);

        if (preg_match('/\.php/', $path)) {
            $path = preg_replace('#.*tht/#', '', $path);
        }

        return ltrim($path, '/');
    }

    // e.g. 'C:\mypath\sdfsdf' to '/mypath/sdfsdf'
    // TODO: Retain drive letter? If so, probably has impact on File module validations.
    static public function normalizeWinPath($raw) {

        $fnWinPath = function ($m) {
            return str_replace('\\', '/', $m[1]);
        };

        return preg_replace_callback('/[a-zA-Z]:(\\\\\S+)/', $fnWinPath, $raw);
    }

    static public function getCoreVendorPath($relPath) {

        $thisDir = dirname(__FILE__);
        $vendorDir = self::makePath($thisDir, '../../vendor');

        return self::makePath($vendorDir, $relPath);
    }
}