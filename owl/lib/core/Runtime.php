<?php

namespace o;


// PHP to Owl type
function v ($v) {

    $phpType = gettype($v);
    if ($phpType === 'object') {
       if ($v instanceof \Closure) {
           $phpType = 'Closure';
       } else if ($v instanceof \ONothing) {
           $v->error();
       } else {
            return $v;
       }
    }
    else if ($phpType === 'NULL') {
        Owl::error("Leaked 'null' value found in transpiled PHP.");
    }

    $o = Runtime::$SINGLE[$phpType];
    $o->val = $v;

    return $o;
}

// Owl to PHP type
function uv ($v) {
    return OMap::isa($v) || OList::isa($v) ? $v->val : $v;
}

// numeric value (assertion)
function vn ($v, $isAdd) {
    if (!is_numeric($v)) {
        $tag = $isAdd ? "Did you mean '~'?" : '';
        Owl::error("Can't use math on non-number value.  $tag");
    }
    return $v;
}

// Convert camelCase to user_underscore_case  (with u_ prefix)
function u_ ($s) {
    $out = preg_replace('/([^_])([A-Z])/', '$1_$2', $s);
    return 'u_' . strtolower($out);
}

// user_underscore_case back to camelCase (without u_ prefix)
function un_ ($s) {
    $s = preg_replace('/^u_/', '', $s);
    return v($s)->u_to_camel_case();
}

// NOOP for now
function sig($sig, $arguments) {}

// TODO: function argument checking
// function sig($sig, $arguments) {
//     $err = '';
//     // TODO: fewer args (handle optionals)
//     if (count($arguments) > count($sig)) {
//         $err = 'expects ' . count($sig) . ' arguments.  Got ' . count($arguments) . ' instead.';
//     } else {
//         $i = 0;
//         foreach ($arguments as $arg) {
//             $t = gettype($arg);
//             $s = $sig[$i];
//
//             if ($s === 'n') { $s = 'number'; }
//             else if ($s === 's') { $s = 'string'; }
//             else if ($s === 'b') { $s = 'boolean'; }
//
//             if ($s === 'number') {
//                 if ($t === 'integer' || $t === 'double' || $t === 'float') { $t = 'number'; }
//             }
//
//             if ($t !== $s && $s !== 'any') {
//                 $name = gettype($arg);
//                 // if ($name == 'object') {
//                 //     $name = get_class($arg);
//                 // }
//                 $err = "expects argument $i to be type '" . $s . "'.  Got '" . $name . "' instead.";
//                 break;
//             }
//             $i += 1;
//         }
//     }
//
//     if ($err) {
//         $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[1]['function'];
//         Owl::error($caller . '() ' . $err);
//     }
// }
//

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

    static $SINGLE = [];

    static private $templateLevel = 0;
    static $andStack = [];
    static $fileToNameSpace = [];
    static $moduleRegistry = [];

    static function _initSingletons () {
        foreach (Runtime::$PHP_TO_TYPE as $php => $owl) {
            $c = '\\o\\' . $owl;
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
        $relPath = Owl::getRelativePath('root', $file);
        Runtime::$fileToNameSpace[$relPath] = $nameSpace;
        Runtime::registerModule($nameSpace, $relPath);
    }

    static function getNameSpace ($file) {
        $relPath = Owl::getRelativePath('root', $file);
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
                Owl::error("Can't combine (~) a LockString with a non-LockString.");
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
            Owl::error("Can't combine (~) an array or object.");
        }
    }

    static function registerStdModule ($name, $obj=-1) {
        Runtime::$moduleRegistry[$name] = $obj;
    }

    static function registerModule ($ns, $path) {
        Runtime::$moduleRegistry[$path] = new OModule ($ns, $path);
    }

    static function loadModule ($localNs, $path) {
        $relPath = Owl::getRelativePath('root', $path);
        if (!isset(Runtime::$moduleRegistry[$relPath])) {
            Owl::error("Can't find module for '$relPath'", [ 'knownModules' => Runtime::$moduleRegistry ]);
        }
        $derivedAlias = basename($relPath, '.' . Owl::getExt());
        Runtime::$moduleRegistry[$localNs . '::' . $derivedAlias] = Runtime::$moduleRegistry[$relPath];
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
            // TODO: duplicate of Bare.import
            $fullPath = Owl::path('modules', $alias . '.' . Owl::getExt());
            Source::process($fullPath);
            Runtime::loadModule($localNs, $fullPath);
            return Runtime::$moduleRegistry[$key];
        }
    }

    static function void ($fnName) {
        return new ONothing ($fnName);
    }
}

