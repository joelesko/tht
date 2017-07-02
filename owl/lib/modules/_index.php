<?php

namespace o;

class StdModule {
    function __set ($k, $v) {
        Owl::error("Can't set property '$k' on '" . get_class($this) . "' standard module.");
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
        'Settings'
    ];

    public static function load () {
        foreach (LibModules::$files as $lib) {
            Runtime::registerStdModule($lib);
        }
        Runtime::registerStdModule('Perf', new u_Perf ());
        Runtime::registerStdModule('Regex', new u_Regex ());
        Runtime::registerStdModule('Result', new u_Result ());
    }

    public static function isa ($lib) {
        return in_array($lib, LibModules::$files);
    }
}



// Lazy Load.  Saves ~250kb
spl_autoload_register(function ($class) {
    $class = str_replace('o\\u_', '', $class);
    if (LibModules::isa($class)) {
        if ($class !== 'Perf') { Owl::module('Perf')->u_start('owl.loadModule', $class); }

        require_once($class . '.php');

        if ($class !== 'Perf') { Owl::module('Perf')->u_stop(); }
    }
});


LibModules::load();

