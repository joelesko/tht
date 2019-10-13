<?php

namespace o;

class ModuleManager {

    static private $fileToNameSpace = [];
    static private $moduleRegistry = [ '_page' => -1 ];
    static private $moduleCache = [];

    static function init() {
        self::initAutoloading();
        LibModules::load();
    }

    static function isStdLib ($lib) {
        return LibModules::isa($lib);
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
        $ns = str_replace('.tht', '', $ns);
        $ns = 'tht\\' . $ns . '_x'; // work around reserved PHP words
        return $ns;
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

    static function getNamespacedPackage ($relPath) {
        // todo: handle subdirs
        return '\\' . self::getNamespace('modules/' . $relPath) . '\\' . u_($relPath);
    }

    static function registerUserModule ($file, $nameSpace) {
        $relPath = Tht::getRelativePath('app', $file);
        self::$fileToNameSpace[$relPath] = $nameSpace;
        self::registerModule($nameSpace, $relPath);
    }

    static function registerStdModule ($name, $obj = -1) {
        self::$moduleRegistry[$name] = $obj;
    }

    static function registerModule ($nameSpace, $path) {
        self::$moduleRegistry[$path] = new OModule ($nameSpace, $path);
    }

    static function getModuleFromNamespace($ns) {
        $parts = explode('\\', $ns);
        $name = array_pop($parts);
        if ($parts[1] == 'pages') {
            return self::loadPageModule();
        }
        return self::getModule($name);
    }

    static function getModule ($modName) {

        if (isset(self::$moduleCache[$modName])) {
            return self::$moduleCache[$modName];
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

        self::$moduleCache[$modName] = $mod;

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

    // Entry point for `import()`
    static function loadUserModule($relPath) {

        self::validateImportPath($relPath);

        $fullPath = Tht::path('modules', $relPath . '.' . Tht::getExt());
        Compiler::process($fullPath);

        $relPath = Tht::getRelativePath('app', $fullPath);
        if (!isset(self::$moduleRegistry[$relPath])) {
            Tht::error("Can't find module for `$relPath`");
        }

        // Create local alias
        $baseName = basename($relPath, '.' . Tht::getExt());
        $cacheKey = 'u//' . $baseName;
        self::$moduleRegistry[$cacheKey] = self::$moduleRegistry[$relPath];

        return self::$moduleRegistry[$relPath];
    }

    static function validateImportPath($relPath) {
        if (preg_match('!\.tht!i', $relPath)) {
            Tht::error("Please remove `.tht` file extension from import path.");
        }
        else if (strpos($relPath, './') !== false || strpos($relPath, '..') !== false) {
            Tht::error("Dot shortcuts (`.` or `..`) are not support in `import`.");
        }
        else if (strpos($relPath, '\\') !== false) {
            Tht::error("Please use forward slashes `/` in file paths.");
        }
        else if (preg_match('![^a-zA-Z0-9\/]!', $relPath)) {
            Tht::error("Invalid character in `import` path: `$relPath`");
        }
    }

    // Entry point for `new Object ()`
    static function newObject($className, $args) {
        $mod = self::getModule($className);
        return $mod->newObject($className, $args);
    }

    static function initAutoloading() {

        // Lazy Load.  Saves ~250kb
        spl_autoload_register(function ($aclass) {

            $class = str_replace('o\\u_', '', $aclass);

            if (LibModules::isa($class)) {

                if ($class !== 'Perf') { Tht::module('Perf')->u_start('tht.loadModule', $class); }

                if ($class == 'System') {
                    $class = 'SystemX';
                }
                Tht::loadLib('../modules/' . $class . '.php');

                if ($class !== 'Perf') { Tht::module('Perf')->u_stop(); }

            } else if (strpos($aclass, 'tht\\') === 0) {

                self::loadUserModule(self::namespaceToModulePath($aclass));

            } else {
                // Tht::error("Can not autoload PHP class: `$aclass`");
                // UPDATE: Allow pass through for PHP intrerop
            }

        });
    }
}

