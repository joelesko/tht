<?php

namespace o;

class ModuleManager {

    static private $fileToNameSpace = [];
    static private $moduleRegistry = [ '_page' => -1 ];
    static private $moduleCache = [];

    static function init() {

        self::initAutoloading();
        StdLibModules::load();
    }

    static function isStdLib($lib) {

        return StdLibModules::isa($lib);
    }

    static function getNamespace($relPath) {

        $relPath = Tht::getRelativePath('app', $relPath);
        if (!isset(self::$fileToNameSpace[$relPath])) {
            self::$fileToNameSpace[$relPath] = self::pathToNamespace($relPath);
        }

        return self::$fileToNameSpace[$relPath];
    }

    static function pathToNamespace($path) {

        $relPath = Tht::getRelativePath('app', $path);

        $ns = $relPath;
        $ns = str_replace('/', '\\', $ns);
        $ns = str_replace('-', '_', $ns);
        $ns = str_replace('.tht', '', $ns);
        $ns = 'tht\\' . $ns . '_x'; // work around reserved PHP words

        return $ns;
    }

    static function namespaceToBaseName($ns) {

        preg_match('/([a-zA-Z0-9_]+)_x$/', $ns, $m);

        return $m ? $m[1] : '';
    }

    static function namespaceToModulePath($ns) {

        $path = $ns;
        $path = str_replace('_x', '', $path);
        $path = preg_replace('#.*tht\\\\modules\\\\#', '', $path);
        $path = str_replace('\\', '/', $path);
        $parts = explode('/', $path);

        $mod = array_pop($parts);

        // remove redundant filename in namespace
        array_pop($parts);

        $mod = ucfirst(unu_($mod));
        $path = implode('/', $parts);
        $path .= '/' . $mod;

        return $path;
    }

    static function cleanNamespacedFunction($ns) {

        $parts = explode('\\', $ns);

        $fun = array_pop($parts);
        $mod = array_pop($parts) ?? '';

        $mod = str_replace('_x', '', $mod);

        if ($mod) { $mod .= '.'; }

        return $mod . $fun;
    }

    static function getNamespacedPackage($relPath) {

        // todo: handle subdirs
        return '\\' . self::getNamespace('modules/' . $relPath) . '\\' . u_($relPath);
    }

    static function registerUserModule($file, $nameSpace) {

        $relPath = Tht::getRelativePath('app', $file);
        self::$fileToNameSpace[$relPath] = $nameSpace;
        self::registerModule($nameSpace, $relPath);
    }

    static function registerStdModule($name, $obj = -1) {

        self::$moduleRegistry[$name] = $obj;
    }

    static function registerModule($nameSpace, $path) {

        self::$moduleRegistry[$path] = new OModule ($nameSpace, $path);
    }

    // static function getFromNamespace($ns) {
    //     $parts = explode('\\', $ns);
    //     $name = array_pop($parts);
    //     if ($parts[1] == 'pages') {
    //         return self::loadPageModule();
    //     }
    //     return self::get($name);
    // }

    static function getFromLocalPath($fullPhpPath) {

        $thtPath = Tht::getThtPathForPhp($fullPhpPath);
        $relPath = Tht::getRelativePath('app', $thtPath);

        if (substr($relPath, 0, 6) == 'pages/') {
            return self::loadPageModule();
        }

        // This is coming from `@@` (local module), so consider it already loaded.
        return self::$moduleRegistry[$relPath];
    }


    static function get($modName) {

        $cacheKey = $modName;
        if (isset(self::$moduleCache[$cacheKey])) {
            return self::$moduleCache[$cacheKey];
        }

        // Already loaded user module
        $cleanModName = preg_replace('/_x$/', '', $modName);
        $cacheKey = 'u//' . $cleanModName;
        if (isset(self::$moduleRegistry[$cacheKey])) {
            $mod = self::$moduleRegistry[$cacheKey];
        }
        // Built-in module
        else if (isset(self::$moduleRegistry[$cleanModName])) {
            $mod = self::loadBuiltinModule($cleanModName);
        }
        else {
            // User module
            $mod = self::loadUserModule($cleanModName);
        }

        self::$moduleCache[$cacheKey] = $mod;

        return $mod;
    }

    static function loadPageModule() {

        // Create a dummy module so users can use @@ to create "local" globals for the current page.
        $modName = '_page';
        if (self::$moduleRegistry[$modName] === -1) {
            self::$moduleRegistry[$modName] = new OModule ($modName, $modName);
        }

        return self::$moduleRegistry[$modName];
    }

    static function loadBuiltinModule($modName) {

        if (self::$moduleRegistry[$modName] === -1) {
            // triggers auto-load
            $modClass = '\\o\\u_' . $modName;
            self::$moduleRegistry[$modName] = new $modClass ();
        }

        return self::$moduleRegistry[$modName];
    }

    // Entry point for `load()`
    static function loadUserModule($relPath) {

        $modName = basename($relPath);
        if (self::isStdLib($modName)) {
            Tht::error('Module already exists as a standard module: `' . $modName . '`  Try: Rename your module.');
        }

        // relative to calling file
        if (preg_match('!^\.!', $relPath)) {

            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            $caller = Tht::getUserlandCaller();

            $callerPath = $caller['file']; // e.g. 'my_file.tht.php'
            if (!preg_match('/_modules_/', $callerPath)) {
                Tht::error('Path for `load` can only be relative when calling from a module (not a page).');
            }

            $thtFile = Tht::getThtPathForPhp($callerPath); // MyFile.tht
            $relCallingModule = Tht::getRelativePath('modules', $thtFile);
            $relCallingDir = preg_replace('!/.*$!', '', $relCallingModule);

            $dottedRelPath = $relCallingDir . '/' . $relPath;
            $dottedFullPath = Tht::makePath(Tht::path('modules'), $dottedRelPath) . '.tht';

            $path = Tht::realpath($dottedFullPath);

            $path = Tht::getRelativePath('modules', $path);

            $path = str_replace('.tht', '', $path);

            $relPath = $path;
        }

        $relPath = OTypeString::isa($relPath) ? $relPath->u_render_string() : $relPath;
        if (v($relPath)->u_ends_with('*')) {
            $relPath = rtrim($relPath, '*');
            $fullPath = Tht::path('modules', $relPath);
            $subFiles = PathTypeString::create($fullPath)->u_read_dir(OMap::create([ 'filter' => 'files' ]));
            $mods = OMap::create([]);
            foreach ($subFiles as $f) {
                $f = $f->u_render_string();
                if (!v($f)->u_ends_with('.tht')) { continue; }
                $f = str_replace('.tht', '', $f);
                $f = Tht::getRelativePath('modules', $f);
                $mods[basename($f)] = self::loadUserModule($f);
            }
            return $mods;
        }

        self::validateImportPath($relPath);

        $fullPath = Tht::path('modules', $relPath . '.' . Tht::getThtExt());

        Compiler::process($fullPath);

        // Note: can't just rely on Compiler.process to catch mismatch
        $relPath = Tht::getRelativePath('app', $fullPath);
        if (!isset(self::$moduleRegistry[$relPath])) {
            Tht::error("Module file name mismatch: `$relPath`  Try: Check exact spelling/case");
        }

        // Create local alias
        $baseName = basename($relPath, '.' . Tht::getThtExt());
        $cacheKey = 'u//' . $baseName;
        self::$moduleRegistry[$cacheKey] = self::$moduleRegistry[$relPath];

        return self::$moduleRegistry[$relPath];
    }

    static function validateImportPath($relPath) {

        if (preg_match('!\.tht!i', $relPath)) {
            Tht::error("Please remove `.tht` file extension from load path.");
        }
        // else if (str_contains($relPath, './') || str_contains($relPath, '..')) {
        //     Tht::error("Dot shortcuts (`.` or `..`) are not supported in command: `import`");
        // }
        else if (str_contains($relPath, '\\')) {
            Tht::error("Please use forward slashes `/` in file paths.");
        }
        else if (preg_match('![^a-zA-Z0-9\/\.]!', $relPath)) {
            Tht::error("Invalid character in `load` path: `$relPath`  Try: `test/TestModule.tht` (example)");
        }
    }

    // Entry point for `new Object ()`
    static function newObject($className, $args) {

        $mod = self::get($className);

        return $mod->newObject($className, $args);
    }

    static function initAutoloading() {

        // Lazy Load.  Saves ~250kb
        spl_autoload_register(function ($aclass) {

            $class = str_replace('o\\u_', '', $aclass);

            //if ($class !== 'Perf') { $perfTask = Tht::module('Perf')->u_start('tht.loadModule', $class); }

            if (StdLibModules::isa($class)) {
                Tht::loadLib('lib/stdlib/modules/' . $class . '.php');
            } else if (strpos($aclass, 'tht\\') === 0) {
                self::loadUserModule(self::namespaceToModulePath($aclass));
            } else {
                // Tht::error("Unable to autoload PHP class: `$aclass`");
                // UPDATE: Allow pass-through for PHP interop
            }

           // if ($class !== 'Perf') { $perfTask->u_stop(); }

        });
    }
}

