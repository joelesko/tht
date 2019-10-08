<?php

namespace o;


class Runtime {

    static $TYPES = [ 'OList', 'OString', 'ONumber', 'OBoolean', 'OFunction', 'ONothing' ];

    static $PHP_TO_TYPE = [
        'string'  => 'OString',
        'array'   => 'OList',
        'map'     => 'OMap',
        'boolean' => 'OBoolean',
        'null'    => 'ONothing',
        'double'  => 'ONumber',
        'integer' => 'ONumber',
        'Closure' => 'OFunction',
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
        'f' => 'boolean',
        'l' => 'list',
        'm' => 'map',
        'c' => 'callable',
    ];

    static $SINGLE = [];

    static private $templateLevel = 0;
    static $andStack = [];

    static function _initSingletons () {
        foreach (self::$PHP_TO_TYPE as $php => $tht) {
            $c = '\\o\\' . $tht;
            self::$SINGLE[$php] = new $c ();
        }
    }

    static function openTemplate ($mode) {
        self::$templateLevel += 1;
        $mode = strtolower($mode);
        if (!isset(Runtime::$MODE_TO_TEMPLATE[$mode])) {
            $mode = '_default';
        }
        $class = 'o\\' . self::$MODE_TO_TEMPLATE[$mode];
        return new $class ();
    }

    static function closeTemplate () {
        self::$templateLevel -= 1;
    }

    static function inTemplate () {
        return self::$templateLevel > 0;
    }

    static function resetTemplateLevel() {
        self::$templateLevel = 0;
    }

    static function andPush ($result) {
        array_push(self::$andStack, $result);
        return $result;
    }

    static function andPop () {
        return array_pop(self::$andStack);
    }

    static function concat ($a, $b) {
        $sa = OTypeString::isa($a);
        $sb = OTypeString::isa($b);
        if ($sa || $sb) {
            if (!($sa && $sb)) {
                Tht::error("Can't combine (~) a TypeString with a non-TypeString.");
            }
            return OTypeString::concat($a, $b);
        } else {
            return self::concatVal($a) . self::concatVal($b);
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

    static function spaceship($a, $b) {
        if ($a === $b) { return 0; }
        return $a > $b ? 1 : -1;
    }

    static function match($v, $pattern) {

        if ($v === $pattern) {
            return true;
        }
        else if (OList::isa($pattern)) {
            return $pattern->u_contains($v);
        }
        else if (ORegex::isa($pattern)) {
            return v($v)->u_match($pattern);
        }
        else if ($pattern === true || $pattern === false) {
            // EmitterPHP already optimizes this
            return $pattern;
        }
        else {
            return false;
        }
    }

}

