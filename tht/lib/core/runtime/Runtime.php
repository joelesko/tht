<?php

namespace o;

define('NULL_NORETURN', null);
define('NULL_NOTFOUND', null);

class Runtime {

    static $TYPES = [ 'OList', 'OString', 'ONumber', 'OBoolean', 'OFunction' ];

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

    static function cloneArg($a) {
        return is_object($a) && !is_callable($a) ? $a->cloneArg() : $a;
    }

    static function openTemplate($mode) {
        self::$templateLevel += 1;
        if (!isset(Runtime::$MODE_TO_TEMPLATE[$mode])) {
            $mode = '_default';
        }
        $class = 'o\\' . self::$MODE_TO_TEMPLATE[$mode];
        return new $class ();
    }

    static function closeTemplate() {
        self::$templateLevel -= 1;
    }

    static function inTemplate() {
        return self::$templateLevel > 0;
    }

    static function resetTemplateLevel() {
        self::$templateLevel = 0;
    }

    static function andPush($result) {
        array_push(self::$andStack, $result);
        return self::truthy($result);
    }

    static function andPop() {
        return array_pop(self::$andStack);
    }

    // This was added to allow [] and {} to be falsey.
    // Also '0' should not be falsey.
    static function truthy($v) {

        if (is_object($v)) {
            return $v->u_to_boolean();
        }
        else if ($v === '0') {
            return true;
        }
        else {
            return $v;
        }
    }

    static function concat($a, $b) {
        return self::concatVal($a) . self::concatVal($b);
    }

    static function concatVal($v) {

        if (is_string($v)) { return $v; }

        if (is_int($v) || is_float($v)) {
            return '' . $v;
        }

        if ($v === true)  { return 'true'; }
        if ($v === false) { return 'false'; }

        $type = v($v)->u_z_class_name();
        $suggest = '';
        if (OTypeString::isa($v)) {
            ErrorHandler::setHelpLink('/manual/class/type-string/append', 'TypeString.append');
            $suggest = "Try: `.append()`";
        }

        Tht::error("Can't string-append `~` object of type: `" . $type . "`  $suggest");
    }

    static function matchDie($unmatchedVal) {
        Tht::module('*Bare')->u_die("No match found for value: `$unmatchedVal`  Try: Add a `default` case.");
    }

    static function infixEquals($arg1, $arg2) {

        // Use double equals to allow comparison of int with float
        if (vIsNumber($arg1) && vIsNumber($arg2)) {
            return $arg1 == $arg2;
        }

        return v($arg1)->u_equals($arg2);
    }

    static function checkNumArgs($funName, $maxArgs, $passedArgs) {
        if ($passedArgs > $maxArgs) {
            ErrorHandler::skipFunctionDefinitionInStack();
            Tht::error("Too many arguments passed to: `$funName()`  Got: `$passedArgs`  Expected: `$maxArgs`");
        }
    } 
}





    // static function checkNumericArg($side, $op, $value) {

    //     if (!vIsNumber($value)) {
    //         $type = v($value)->u_type();

    //         $tip = '';
    //         if (is_string($value)) {
    //             if ($op == '+')  {  $tip = "Try: `~` (string append)"; }
    //             if ($op == '+=') {  $tip = "Try: `~=` (string append)"; }
    //         }

    //         Tht::error("$side side of `$op` must be a number.  Got: $type $tip");
    //     }
    // }

    // // TODO: These infix methods might someday be replaced with type inference at compile time,
    // // instead of doing them at runtime.
    // static function infixMath($arg1, $aop, $arg2) {

    //     self::checkNumericArg('Left', $aop, $arg1);
    //     self::checkNumericArg('Right', $aop, $arg2);

    //     $op = rtrim($aop, '=');

    //     switch ($op) {
    //         case '+':  return $arg1 +  $arg2;
    //         case '-':  return $arg1 -  $arg2;
    //         case '*':  return $arg1 *  $arg2;
    //         case '**': return $arg1 ** $arg2;

    //         case '/':
    //             if ($arg2 == 0) {
    //                 Tht::error("Right side of division `$aop` can not be zero.");
    //             }
    //             return $arg1 / $arg2;

    //         case '%':
    //             if ($arg2 == 0) {
    //                 Tht::error("Right side of modulo `$aop` can not be zero.");
    //             }
    //             return $arg1 % $arg2;
    //     }
    // }

    // static function checkComparisonArg($side, $op, $value) {
    //     if (!is_numeric($value) && !is_string($value)) {
    //         $type = v($value)->u_type();
    //         Tht::error("$side side of comparison `$op` must be a number or string.  Got: $type");
    //     }
    // }

    // // Relative comparisons must be either strings or numbers
    // static function infixCompare($arg1, $op, $arg2) {

    //     self::checkComparisonArg('Left', $op, $arg1);
    //     self::checkComparisonArg('Right', $op, $arg2);

    //     $t1 = v($arg1)->u_type();
    //     $t2 = v($arg2)->u_type();

    //     if ($t1 !== $t2) {
    //         Tht::error("Left and right side of comparison `$op` must be of the same type.  Got: `$t1` & `$t2`");
    //     }

    //     switch ($op) {
    //         case '>':   return $arg1 >   $arg2;
    //         case '>=':  return $arg1 >=  $arg2;
    //         case '<':   return $arg1 <   $arg2;
    //         case '<=':  return $arg1 <=  $arg2;
    //         case '<=>': return $arg1 <=> $arg2;
    //     }
    // }

