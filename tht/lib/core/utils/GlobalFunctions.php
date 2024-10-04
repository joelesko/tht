<?php

namespace o;

//// Global internal utility functions
//
// These have very short names because they are very commonly used.

// Lookups for functions in this file.
class UtilConfig {

    static $TYPE_TO_LABEL = [
        'I' => 'positive integer',
        'N' => 'positive number',
        'i' => 'integer',
        'n' => 'number',  // float or int
        's' => 'string',
        'S' => 'string', // non-empty okay
        'b' => 'boolean',
        'l' => 'list',
        'm' => 'map',
        'c' => 'callable',  // TODO: change this to 'f' and use 'c' for char?
        '*' => 'non-null',
        '_' => 'any',
    ];

    static $PHP_TO_THT_CLASS = [
        'string'   => '\o\OString',
        'resource' => '\o\OString',  // temp placeholder for stack traces
        'array'    => '\o\OList',
        'boolean'  => '\o\OBoolean',
        'double'   => '\o\ONumber',
        'integer'  => '\o\ONumber',
        'Closure'  => '\o\OFunction',

        'NULL'     => '\o\ONull',
    ];
}

// Wrap a PHP value in a THT object to temporarily call a method in-place.
//
// PERF: This is extremely hot path.  It wraps every single method call
// in the transpiled PHP.
//
// Originally I used pre-made singletons of every type,
// but it resulted in bugs around nested autoboxing.
//
// TODO: Look into some kind of pooling. Really only impacts Strings
// since Lists and Maps are usually objects from the beginning.
function v($v) {

    $phpType = gettype($v);

    if ($phpType == 'object') {
        if ($v instanceof \Closure) {
            $phpType = 'Closure';
        }
        else {
            return $v;
        }
    }

    $thtClass = UtilConfig::$PHP_TO_THT_CLASS[$phpType];
    $o = new $thtClass ();

    $o->val = $v;

    return $o;
}

function vnullsafe($v) {
    if ($v === null) {
        return null;
    }
    else {
        return v($v);
    }
}

// Unwrap a THT object to its PHP native value.
function unv($v) {

    if (OMap::isa($v) || OList::isa($v)) {
        $r = $v->val;
        foreach ($r as $k => $v) {
            $r[$k] = unv($v);
        }
        return $r;
    }
    else {
        return $v;
    }
}

// Convert camelCase to userland_underscore_case (with userland `u_` prefix)
function u_($s) {

    $out = preg_replace('/([A-Z])/', '_$1', $s);

    return 'u_' . $out;
}

// Convert userland_underscore_case to camelCase (no userland `u_` prefix)
function unu_($s) {

    $s = preg_replace('/^u_/', '', $s);
    $s = preg_replace_callback('/_([A-Za-z])/', function ($m) {
        return strtoupper($m[1]);
    }, $s);

    return $s;
}

// Same as unu_, but strip namespace
function unu_ns_($s) {

    $s = preg_replace('#.*\\\\#', '', $s);

    return unu_($s);
}

// Check that var has a userland `u_` prefix
function hasu_($v) {

    return substr($v, 0, 2) == 'u_';
}

// Because PHP is_numeric includes numeric strings.
function vIsNumber($v) {
    return is_int($v) || is_float($v);
}


// Validate function arguments for user-facing methods
//
// PERF: Super Hot Path. This is called on every standard library function call.
//       According to cachegrind, this is the most expensive runtime hit.
// TODO: Probably merge this with native argument checking
//       or replace native with this logic.
//       func_get_args() makes copy of the arguments so it might not be totally efficient.
// TODO: support '?' for variable length signatures using splat '...'
function validateFunctionArgs($sig, $arguments) {

    $err = '';

    // NOTE: Passing in not enough args are caught by PHP at runtime.
    // Default values are not passed in func_get_args()

    if (count($arguments) > strlen($sig)) {

        $num = strlen($sig);
        $argumentLabel = $num == 1 ? 'argument' : 'arguments';

        $err = [
            'msg' => "expects $num $argumentLabel.",
            'argName' => '',
            'needType' => '',
            'gotType' => '',
        ];
    }
    else {

        $i = -1;
        foreach ($arguments as $arg) {

            $i += 1;

            $slot = $sig[$i];

            // Allow anything
            if ($slot == '_') { continue; }

            // Anything but null
            $isNull = is_null($arg);
            if ($slot == '*' && !$isNull) { continue; }

            if ($isNull) {
                $argType = 'null';
            }
            else {
                $argType = gettype($arg);
            }

            if ($argType == 'double') {
                $argType = 'float';
            }

            $slotIsPositive = false;
            if ($slot == 'I' || $slot == 'N') {
                $slotIsPositive = true;
                $slot = lcfirst($slot);
            }

            $slotAllowsEmptyString = false;
            if ($slot == 'S') {
                $slotAllowsEmptyString = true;
                $slot = lcfirst($slot);
            }

            if ($argType == 'integer' || $argType == 'float') {

                if ($slotIsPositive) {
                    if ($arg < 0) {
                        // Error: negative number - force mismatch below
                        $slot = ucfirst($slot);
                        $argType = 'negative ' . $argType;
                    }
                }

                if ($slot == 'n') {
                    // `number` allows float or int
                    $argType = 'number';
                }
                else if ($argType == 'float' && floor($arg) === intval($arg)) {
                    // Allow float for integer, if the float is a whole number
                    $argType = 'integer';
                }
                else if ($slot == 's') {
                    // allow numbers to be cast as strings
                    $argType = 'string';
                }
            }
            else if ($argType == 'object') {

                $type = v($arg)->u_type();

                if ($type == 'map') {
                    $argType = 'map';
                }
                else if ($type == 'list') {
                    $argType = 'list';
                }
                else if (get_class($arg) == 'Closure') {
                    $argType = 'callable';
                }
            }
            else if ($argType == 'string') {

                if ($arg == '' && !$slotAllowsEmptyString) {
                    // Error: empty string - force mismatch below
                    $slot = ucfirst($slot);
                    $argType = 'empty ' . $argType;
                }
            }


            $typeLabel = UtilConfig::$TYPE_TO_LABEL[$slot];

            // Type Mismatch
            if ($argType !== $typeLabel) {

                $actualType = $argType;

                $try = '';
                if ($actualType == 'float' && $typeLabel == 'integer') {
                    $try = 'Try: `$floatNum.toInt()`';
                }

                $argNum = 'argument #' . ($i + 1);
                $err = [
                    'msg' => "expects $argNum to be: `$typeLabel`  Got: `" . $actualType . "`" . $try,
                    'argName' => $i,
                    'needType' => $typeLabel,
                    'gotType' => $actualType,
                ];

                break;
            }
        }
    }

    if ($err) {

        ErrorHandler::addSubOrigin('arguments');

        $callerFun = Tht::getUserlandCaller()['function'];
        $err['function'] = $callerFun;
        $err['msg'] = "Function `$callerFun()` " . $err['msg'];

        return $err;
    }
    else {
        return false;
    }
}


