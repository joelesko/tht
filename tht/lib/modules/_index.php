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
        'Session'
    ];

    public static function load () {
        foreach (LibModules::$files as $lib) {
            Runtime::registerStdModule($lib);
        }
        Runtime::registerStdModule('Perf', new u_Perf ());
        Runtime::registerStdModule('Regex', new u_Regex ());
        Runtime::registerStdModule('Result', new u_Result ());

        // [security]
        // for internal use
        Runtime::registerStdModule('*File', new u_File (true));  
    }

    public static function isa ($lib) {
        return in_array($lib, LibModules::$files);
    }
}



// Lazy Load.  Saves ~250kb
spl_autoload_register(function ($aclass) {

    $class = str_replace('o\\u_', '', $aclass);
    if (LibModules::isa($class)) {

        if ($class !== 'Perf') { Tht::module('Perf')->u_start('tht.loadModule', $class); }

        if ($class == 'System') {
            $class = 'SystemX';
        }
        require_once($class . '.php');

        if ($class !== 'Perf') { Tht::module('Perf')->u_stop(); }

    } else if (hasu_($aclass)) {

        Tht::error("Can not autoload THT class: `$class`", LibModules::$files);

    } else {

        Tht::error("Can not autoload PHP class: `$aclass`");
    }

});


LibModules::load();

