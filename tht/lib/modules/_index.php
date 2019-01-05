<?php

namespace o;

class StdModule {
    function __set ($k, $v) {
        Tht::error("Can't set property '$k' on '" . get_class($this) . "' standard module.");
    }
}


class LibModules {
    static public $files = [
        'File',
        'Test',
        'Date',
        'Global',
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
        'Css',
        'Js',
        'Web',
        'Litemark',
        'Jcon',
        'Form',
        'FormValidator',
        'Session',
        'Cache',
        'Net',
    ];

    public static function load () {
        foreach (LibModules::$files as $lib) {
            ModuleManager::registerStdModule($lib);
        }
        ModuleManager::registerStdModule('Perf', new u_Perf ());
        ModuleManager::registerStdModule('Regex', new u_Regex ());
        ModuleManager::registerStdModule('Result', new u_Result ());

        Security::registerInternalFileModule();
    }

    public static function isa ($lib) {
        return in_array($lib, LibModules::$files);
    }
}

ModuleManager::initAutoloading();

LibModules::load();

