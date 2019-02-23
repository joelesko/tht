<?php

namespace o;

class StdModule implements \JsonSerializable {

    function __set ($k, $v) {
        Tht::error("Can't set field `$k` on a standard module.");
    }

    function __get ($f) {
        $try = '';

        if (method_exists($this, $f)) {
            $try = 'Try: `.' . unu_($f) . '()`';
        }
        Tht::error("Can't get field `$f` on a standard module. $try");
    }

    function __toString() {
        return '<<<' . Tht::cleanPackageName(get_called_class()) . '>>>';
    }

    function jsonSerialize() {
        return $this->__toString();
    }

    // TODO: some overlap with OClass
    function __call ($method, $args) {

        $suggestion = '';
        if (property_exists($this, 'suggestMethod')) {
            $umethod = strtolower(unu_($method));
            $suggestion = isset($this->suggestMethod[$umethod]) ? $this->suggestMethod[$umethod] : '';
        }
        $suggest = $suggestion ? " Try: `"  . $suggestion . "`" : '';

        $c = get_called_class();

        Tht::error("Unknown method `$method` for module `$c`. $suggest");
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
        'MapDb',
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

