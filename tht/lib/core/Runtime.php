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

}

