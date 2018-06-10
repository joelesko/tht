<?php

namespace o;


class Runtime {

    static $TYPES = [ 'OList', 'OString', 'ONumber', 'OFlag', 'OFunction', 'ONothing' ];

    static $PHP_TO_TYPE = [
        'string'  => 'OString',
        'array'   => 'OList',
        'boolean' => 'OFlag',
        'null'    => 'ONothing',
        'double'  => 'ONumber',
        'integer' => 'ONumber',
        'Closure' => 'OFunction'
    ];

    static $MODE_TO_TEMPLATE = [
        '_default' => 'OTemplate',
        'html'     => 'TemplateHtml',
        'js'       => 'TemplateJs',
        'css'      => 'TemplateCss',
        'lite'     => 'TemplateLite',
        'jcon'     => 'TemplateJcon',
        'text'     => 'TemplateText'
    ];

    static $SIG_TYPE_KEY_TO_LABEL = [
        'n' => 'number',
        's' => 'string',
        'f' => 'flag',
        'l' => 'list',
        'm' => 'map'
    ];

    static $SINGLE = [];

    static private $templateLevel = 0;
    static $andStack = [];
    static $fileToNameSpace = [];
    static $moduleRegistry = [];

    static function _initSingletons () {
        foreach (Runtime::$PHP_TO_TYPE as $php => $tht) {
            $c = '\\o\\' . $tht;
            Runtime::$SINGLE[$php] = new $c ();
        }
    }

    static function openTemplate ($mode) {
        Runtime::$templateLevel += 1;
        $mode = strtolower($mode);
        if (!isset(Runtime::$MODE_TO_TEMPLATE[$mode])) {
            $mode = '_default';
        }
        $class = 'o\\' . Runtime::$MODE_TO_TEMPLATE[$mode];
        return new $class ();
    }

    static function closeTemplate () {
        Runtime::$templateLevel -= 1;
    }

    static function inTemplate () {
        return Runtime::$templateLevel > 0;
    }

    static function andPush ($result) {
        array_push(Runtime::$andStack, $result);
        return $result;
    }

    static function andPop () {
        return array_pop(Runtime::$andStack);
    }

    static function setNameSpace ($file, $nameSpace) {
        $relPath = Tht::getRelativePath('app', $file);
        Runtime::$fileToNameSpace[$relPath] = $nameSpace;
        Runtime::registerModule($nameSpace, $relPath);
    }

    static function getNameSpace ($file) {
        $relPath = Tht::getRelativePath('app', $file);
        return Runtime::$fileToNameSpace[$relPath];
    }

    static function isStdLib ($lib) {
        return LibModules::isa($lib);
    }

    static function concat ($a, $b) {
        $sa = OLockString::isa($a);
        $sb = OLockString::isa($b);
        if ($sa || $sb) {
            if (!($sa && $sb)) {
                Tht::error("Can't combine (~) a LockString with a non-LockString.");
            }
            $combined = OLockString::getUnlocked($a) . OLockString::getUnlocked($b);
            return OLockString::create(get_class($a), $combined);
        } else {
            return Runtime::concatVal($a) . Runtime::concatVal($b);
        }
    }

    static function concatVal ($v) {
        $t = gettype($v);
        if ($t === 'boolean') {
            return $v ? 'true' : 'false';
        } else if ($t === 'integer' || $t === 'double' || $t === 'string'){
            return '' . $v;
        } else if ($t === 'null') {
            return '';
        } else if ($v instanceof ONothing) {
            $v->error();
        } else {
            Tht::error("Can't combine (~) an array or object.");
        }
    }

    static function registerStdModule ($name, $obj=-1) {
        Runtime::$moduleRegistry[$name] = $obj;
    }

    static function registerModule ($ns, $path) {
        Runtime::$moduleRegistry[$path] = new OModule ($ns, $path);
    }

    static function loadModule ($localNs, $fullPath) {
        $relPath = Tht::getRelativePath('app', $fullPath);
        if (!isset(Runtime::$moduleRegistry[$relPath])) {
            Tht::error("Can't find module for `$relPath`", [ 'knownModules' => Runtime::$moduleRegistry ]);
        }
        $derivedAlias = basename($relPath, '.' . Tht::getExt());
        Runtime::$moduleRegistry[$localNs . '::' . $derivedAlias] = Runtime::$moduleRegistry[$relPath];

        return Runtime::$moduleRegistry[$relPath];
    }

    static function getModule ($localNs, $alias) {

        $key = $localNs . '::' . $alias;
        if (isset(Runtime::$moduleRegistry[$key])) {
            return Runtime::$moduleRegistry[$key];
        }
        else if (isset(Runtime::$moduleRegistry[$alias])) {
            if (Runtime::$moduleRegistry[$alias] === -1) {
                // lazy init
                $c = '\\o\\u_' . $alias;
                Runtime::$moduleRegistry[$alias] = new $c ();
            }
            return Runtime::$moduleRegistry[$alias];
        } else {
            return Runtime::loadUserModule($localNs, $alias);
        }
    }

    static function loadUserModule($localNs, $relPath) {
        $fullPath = Tht::path('modules', $relPath . '.' . Tht::getExt());
        Source::process($fullPath);
        return Runtime::loadModule($localNs, $fullPath);
    }

    static function newObject($localNs, $className, $args) {

        $mod = self::getModule($localNs, $className);
        $o = $mod->newObject($className, $args);
        return $o;    
    }

    static function void ($fnName) {
        return new ONothing ($fnName);
    }
}

