<?php

namespace o;

require_once('helpers/InputValidator.php');
//require_once('Forms.php');


class LibModules {

    static public $files = [
        'File',
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
//        'Image',
        'Input',
        'Config',
        'Bare',
        'Form',
        'Email',
        'Page',
    ];

    public static function load () {

        foreach (LibModules::$files as $lib) {
            ModuleManager::registerStdModule($lib);
        }

        ModuleManager::registerStdModule('Perf', new u_Perf ());
        ModuleManager::registerStdModule('Regex', new u_Regex ());
        ModuleManager::registerStdModule('Result', new u_Result ());

        ModuleManager::registerStdModule('*Bare', new u_Bare ());

        Security::registerInternalFileModule();
    }

    public static function isa ($lib) {
        return in_array($lib, LibModules::$files);
    }
}

ModuleManager::initAutoloading();

LibModules::load();

