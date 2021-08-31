<?php

namespace o;

define('EMPTY_RETURN', false);

class Runtime {

    static $TYPES = [ 'OList', 'OString', 'ONumber', 'OBoolean', 'OFunction' ];

    static $PHP_TO_THT_CLASS = [
        'string'   => '\o\OString',
        'resource' => '\o\OString',  // temp placeholder for stack traces
        'array'    => '\o\OList',
        'boolean'  => '\o\OBoolean',
        'null'     => '\o\OBoolean',
        'double'   => '\o\ONumber',
        'integer'  => '\o\ONumber',
        'Closure'  => '\o\OFunction',
    ];

    static $MODE_TO_TEMPLATE = [
        '_default' => 'OTemplate',
        'html'     => 'TemplateHtml',
        'js'       => 'TemplateJs',
        'css'      => 'TemplateCss',
        'lm'       => 'TemplatLm',
        'jcon'     => 'TemplateJcon',
        'text'     => 'TemplateText'
    ];


    static private $templateLevel = 0;
    static $andStack = [];

    // See perf note below.
    static $AUTOBOX_OBJECT = [];
    // static function _initAutoboxObjects () {
    //     foreach (self::$PHP_TO_THT_CLASS as $php => $thtClass) {
    //         self::$AUTOBOX_OBJECT[$php] = new $thtClass ();
    //     }
    // }

    // Perf: Originally I used pre-made singletons of every type,
    // but it resulted in bugs around nested autoboxing.
    // This is around 3x slower, but the absolute impact is almost zero.
    // With 10,000 calls, it's about 0.5ms.
    // TODO: Look into some kind of pooling. Really only impacts Strings
    // since Lists and Maps are usually objects from the beginning.

    static function autoBoxObject($phpType) {

        $thtClass = self::$PHP_TO_THT_CLASS[$phpType];

        return new $thtClass ();
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
        return self::truthy($result);
    }

    static function andPop () {
        return array_pop(self::$andStack);
    }

    // This was added to allow [] and {} to be falsey.
    static function truthy ($v) {
        if (is_object($v)) {
            return $v->u_is_truthy();
        }
        else {
            return $v;
        }
    }

    static function concat ($a, $b) {

        $sa = OTypeString::isa($a);
        $sb = OTypeString::isa($b);

        if ($sa || $sb) {
            if (!($sa && $sb)) {
                Tht::error("Can not combine (~) a TypeString with a non-TypeString.");
            }
            return OTypeString::concat($a, $b);
        }
        else {
            return self::concatVal($a) . self::concatVal($b);
        }
    }

    static function concatVal ($v) {

        $t = gettype($v);

        if ($t === 'boolean') {
            return $v ? 'true' : 'false';
        }
        else if ($t === 'integer' || $t === 'double' || $t === 'string'){
            return '' . $v;
        }
        else if ($t === 'null') {
            return '';
        }
        else {
            Tht::error("Can not combine (~) an array or object.");
        }
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

    static function matchDie($matchVal) {
        Tht::error('`match` value did not match any of the conditions, and there was no `default` condition.');
    }

    static function listFilter($list, $fn) {

        // if (is_callable($list)) {
        //     Tht::debug($list, $fn);
        //     $leftFn = $list;
        //     return function ($el, $i) use ($leftFn)  {

        //     };
        // }

        $out = [];
        foreach ($list as $i => $el) {
            $ret = $fn($el, $i);
            if ($ret === true) {
                $out []= $el;
            }
            else if ($ret === false) {
                continue;
            }
            else {
                $out []= $ret;
            }
        }

        return OList::create($out);
    }

    static function checkNumericArg($side, $op, $value) {

        if (!vIsNumber($value)) {
            $type = v($value)->u_type();

            $tip = '';
            if (is_string($value)) {
                if ($op == '+')  {  $tip = "Did you mean `~`?"; }
                if ($op == '+=') {  $tip = "Did you mean `~=`?"; }
            }

            Tht::error("$side side of `$op` must be a number. Got: $type $tip");
        }
    }

    // TODO: These infix methods can probably be replaced with type inference at compile time,
    // instead of doing them at runtime.
    static function infixMath($arg1, $op, $arg2) {

        self::checkNumericArg('Left', $op, $arg1);
        self::checkNumericArg('Right', $op, $arg2);

        $op = rtrim($op, '=');

        switch ($op) {
            case '+':  return $arg1 +  $arg2;
            case '-':  return $arg1 -  $arg2;
            case '*':  return $arg1 *  $arg2;
            case '**': return $arg1 ** $arg2;

            case '/':
                if ($arg2 == 0) {
                    Tht::error("Right side of division `$op` can not be zero.");
                }
                return $arg1 / $arg2;

            case '%':
                if ($arg2 == 0) {
                    Tht::error("Right side of modulo `$op` can not be zero.");
                }
                return $arg1 % $arg2;
        }
    }

    static function infixMathAssign($arg1, $op, $arg2) {

        return self::infixMath($arg1, $op, $arg2);
    }

    static function checkComparisonArg($side, $op, $value) {
        if (!is_numeric($value) && !is_string($value)) {
            $type = v($value)->u_type();
            Tht::error("$side side of comparison `$op` must be a number or string. Got: $type");
        }
    }

    // Relative comparisons must be either strings or numbers
    static function infixCompare($arg1, $op, $arg2) {

        self::checkComparisonArg('Left', $op, $arg1);
        self::checkComparisonArg('Right', $op, $arg2);

        $t1 = v($arg1)->u_type();
        $t2 = v($arg2)->u_type();

        if ($t1 !== $t2) {
            Tht::error("Left and right side of comparison `$op` must be of the same type. Got: $t1 & $t2");
        }

        switch ($op) {
            case '>':   return $arg1 >   $arg2;
            case '>=':  return $arg1 >=  $arg2;
            case '<':   return $arg1 <   $arg2;
            case '<=':  return $arg1 <=  $arg2;
            case '<=>': return $arg1 <=> $arg2;
        }
    }

    // Equals comparisons can be between any types
    static function infixEquals($arg1, $arg2) {

        // Use double equals to allow comparison of int with float
        if (vIsNumber($arg1) && vIsNumber($arg2)) {
            return $arg1 == $arg2;
        }

        return $arg1 === $arg2;
    }

    static function infixNotEquals($arg1, $arg2) {

        return !self::infixEquals($arg1, $arg2);
    }
}

