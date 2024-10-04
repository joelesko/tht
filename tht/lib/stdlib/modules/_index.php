<?php

namespace o;

class StdLibModules {

    static public $files = [
        'File',
        'Log',
        'Test',
        'Date',
        'String',
        'Test',
        'Php',
        'System',
        'Json',
        'Meta',
        'Math',
        'Result',
        'Perf',
        'Db',
        'Web',
        'Request',
        'Output',
        'Litemark',
        'Jcon',
        'Session',
        'Cookie',
        'Cache',
        'Net',
        'MapDb',
        'Input',
        'AppConfig',
        'Bare',
        'Form',
        'Email',
        'Page',
        'Image',
    ];

    public static function register() {

        // Register modules for autoloading when used
        foreach (self::$files as $lib) {
            ModuleManager::registerStdModule($lib);
        }

        ModuleManager::registerStdModule('Perf',   new u_Perf ());
        ModuleManager::registerStdModule('Regex',  new u_Regex ());
        ModuleManager::registerStdModule('Result', new u_Result ());
        ModuleManager::registerStdModule('*Bare',  new u_Bare ());
    }

    public static function isa($className) {

        return in_array($className, self::$files);
    }
}

ModuleManager::initAutoloading();

StdLibModules::register();

