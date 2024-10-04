<?php

namespace o;

// TODO: Factor out redundancies with File module methods
trait ThtPaths {

    static private $THT_ROOT_PATH = '';
    static private $paths = [];

    // Mapping of path id to actual directory name.
    static private $APP_DIR = [

        'app'       => 'app',

        'config'    => 'config',

        'code'      => 'code',
        'pages'     =>     'pages',
        'modules'   =>     'modules',
        'scripts'   =>     'scripts',
        'phpLib'    =>     'php',
        'system'    =>         'system',
        'localTht'  =>             'tht',
        'public'    =>     'public',
        'images'    =>         'images',

        'data'      => 'data',
        'db'        =>     'db',
        'files'     =>     'files',
        'logs'      =>     'logs',
        'uploads'   =>     'uploads',
        'temp'      =>     'temp',
        'sessions'  =>         'sessions',
        'cache'     =>         'cache',
        'phpCache'  =>             'compiler',
        'kvCache'   =>             'keyValue',
        'counter'   =>     'counter',
        'counterPage' =>       'page',
        'counterDate' =>       'date',
        'counterRef'  =>       'referrer',

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
    // TODO: probably just make these all literal strings? Or lazy evaluated?
    static public function initAppPaths($cliAppDir = '') {

        self::$paths = [];

        if ($cliAppDir) {
            self::$paths['app'] = $cliAppDir;
        }
        else {
            self::$paths['app'] = self::findTopAppDir();
        }

        // Define subdirectories
        $dirs = [

            ['app', 'config'],

            ['app', 'code'],
                ['code', 'pages'],
                ['code', 'modules'],
                ['code', 'scripts'],
                ['code', 'phpLib'],
                    ['phpLib', 'system'],
                        ['system', 'localTht'],
                ['code', 'public'],
                    ['public', 'images'],

            ['app', 'data'],
                ['data', 'db'],
                ['data', 'files'],
                ['data', 'logs'],
                ['data', 'uploads'],
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

    static private function findTopAppDir() {

        if (Tht::isMode('cli')) {
            // CLI Mode: Assume it is the current directory
            $fullPath = self::normalizeWinPath(getcwd());
        }
        else {
            // Web Mode: parent of 'code/public'
            $docRoot = self::normalizeWinPath(
                self::getPhpGlobal('server', 'DOCUMENT_ROOT')
            );
            $tryPath = self::makePath($docRoot, '../..');
            $fullPath = self::realpath($tryPath);
        }

        return $fullPath;
    }

    static private function initThtRootPath() {
        $dir = str_replace('\\', '/', __DIR__);
        $rootDir = preg_replace('#/lib/core/.*#', '', $dir);
        self::$THT_ROOT_PATH = $rootDir;
    }

    // User App path
    static public function path() {

        // Expand base key (e.g. 'modules') to path
        $parts = func_get_args();
        $base = $parts[0];
        if (!isset(self::$paths[$base])) {
            self::error("Unknown base path key: `$base`");
        }
        $parts[0] = self::$paths[$base];

        return self::makePath($parts);
    }

    // THT internal path
    static public function systemPath() {

        return self::makePath(self::$THT_ROOT_PATH, ...func_get_args());
    }

    static public function getAppFileName($key) {

        return self::$APP_FILE[$key];
    }

    // Glue path parts together into a single path
    static public function makePath() {

        $pathParts = func_get_args();
        if (is_array($pathParts[0])) { $pathParts = $pathParts[0]; }

        $path = implode('/', $pathParts);
        $path = self::normalizeWinPath($path);

        $path = str_replace('//', '/', $path); // prevent double slashes
        $path = rtrim($path, '/');

        return $path;
    }

    static public function getRelativePath($baseKey, $fullPath) {

        $basePath = self::path($baseKey);
        $rel = str_replace(self::realpath($basePath), '', $fullPath);

        return ltrim($rel, '/');
    }

    static public function realpath($path) {

        $real = realpath($path);

        if (!$real) {
            // Resolve non-existent path manually
            $resolvedPath = [];
            foreach (explode('/', $path) as $part) {
                if (empty($part) || $part === '.') continue;
                if ($part !== '..') {
                    array_push($resolvedPath, $part);
                }
                else if (count($resolvedPath) > 0) {
                    array_pop($resolvedPath);
                } else {
                    Tht::error("Relative path goes above root directory: `$path`");
                }
            }
            $real = '/' . join('/', $resolvedPath);
        }

        return self::normalizeWinPath($real);
    }

    static public function getFullPath($file) {

        if (substr($file, 0, 1) === '/') {
            return $file;
        }

        return self::makePath(self::realpath(getcwd()), $file);
    }

    static public function getPhpPathForTht($thtFile) {

        $relPath = Tht::stripRootPath($thtFile, self::$paths['code']);
        $cacheFile = preg_replace('/[\\/]/', '_', $relPath);

        return self::path('phpCache', self::getThtPhpVersionToken() . '_' . $cacheFile . '.php');
    }

    static public function getThtPathForPhp($phpPath) {

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

    static public function isThtFile($filePath) {

        return preg_match('/\.' . self::$SRC_EXT . '/', $filePath);
    }

    static public function getThtFileName($fileBaseName) {

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
    // TODO: Retain drive letter? If so, probably has impact on file path validations elsewhere.
    // TODO: Trigger error when getting network drive path: e.g. \\server1\share1
    //       Suggest mapping the drive instead:  https://awesometoast.com/php-and-mapped-network-drives/
    static public function normalizeWinPath($path) {

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#^[a-zA-Z]:#', '', $path);

        return $path;
    }

    static public function getCoreVendorPath($relPath) {

        return self::systemPath('lib/vendor', $relPath);
    }

    // Path Utilities used internally and by File module
    //--------------------------------------------------------------------

    static public function stripRootPath($fullPath, $rootPath) {

        if (!self::hasRootPath($fullPath, $rootPath)) {
            // TODO: include values in output, for comparison (and ensure raw paths are shown)
            Tht::error("Root path not found in full path.  Root: `$rootPath`  Full: `$fullPath`");
        }

        $remainder = substr($fullPath, strlen($rootPath));
        $remainder = ltrim($remainder, '/');

        return $remainder;
    }

    static public function stripEndPath($fullPath, $endPath) {

        if (!self::hasEndPath($fullPath, $endPath)) {
            // TODO: include values in output, for comparison (and ensure raw paths are shown)
            Tht::error('End path not found in full path.');
        }

        $remainder = substr($fullPath, 0, strlen($fullPath) - strlen($endPath));
        $remainder = rtrim($remainder, '/');

        return new FileTypeString($remainder);
    }

    static public function hasRootPath($fullPath, $rootPath) {

        // Make sure match doesn't cross dir boundaries
        // Both paths have to get appended, so that path always has itself as a rootPath
        $rootPath = rtrim($rootPath, '/') . '/';
        $fullPath = rtrim($fullPath, '/') . '/';

        return mb_strpos($fullPath, $rootPath) === 0;
    }

    static public function hasEndPath($fullPath, $endPath) {

        // Make sure match doesn't cross dir boundaries
        if ($endPath[0] != '/') {
            $endPath = '/' . $endPath;
        }

        $offset = strlen($fullPath) - strlen($endPath);

        return mb_strpos($fullPath, $endPath) === $offset;
    }

    static public function isRelative($p) {

        return !self::isAbsolute($p);
    }

    static public function isAbsolute($p) {

        return preg_match('#^([A-Za-z]:)?/#', $p);
    }

}