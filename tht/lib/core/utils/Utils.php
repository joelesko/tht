<?php

namespace o;

//// Global internal utility functions


// PHP to THT type
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
        Tht::error("Leaked `null` value found in transpiled PHP.");
    }

    $o = Runtime::$SINGLE[$phpType];
    $o->val = $v;

    return $o;
}

// THT to PHP type
function uv ($v) {

    if (OMap::isa($v) || OList::isa($v)) {
        $r = $v->val;
        foreach ($r as $k => $v) {
            $r[$k] = uv($v);
        }
        return $r;
    }
    else {
        return $v;
    }
}

// Assert Number type value
function vn ($v, $isAdd) {
    if (!is_numeric($v)) {
        $tag = $isAdd ? "Did you mean '~'?" : '';
        Tht::error("Can't use math on non-number value. $tag");
    }
    return $v;
}

// Convert camelCase to user_underscore_case (with u_ prefix)
function u_ ($s) {
   // $out = preg_replace('/([^_])([A-Z])/', '$1_$2', $s);
    $out = preg_replace('/([A-Z])/', '_$1', $s);
    return 'u_' . strtolower($out);
}

// user_underscore_case back to camelCase (without u_ prefix)
function unu_ ($s) {
    $s = preg_replace('/^u_/', '', $s);
    return v($s)->u_to_camel_case();
}

// var has a u_ prefix
function hasu_ ($v) {
    return substr($v, 0, 2) === 'u_';
}



// Validate function arguments

// sig:
//   n = number
//   s = string
//   f = boolean  (TODO: change)
//   l = list
//.  m = map
//   c = callable
//   * = any

// NOTE: Fewer args are already handled by PHP.
function ARGS($sig, $arguments) {

    $err = '';

    if (count($arguments) > strlen($sig)) {
        $err = 'expects ' . strlen($sig) . ' arguments.';
    }
    else {
        $i = 0;
        foreach ($arguments as $arg) {

            $s = $sig[$i];

            if ($s === '*') { continue; }
            if (is_null($arg)) { continue; }

            $t = gettype($arg);

            if ($t === 'integer' || $t === 'double' || $t === 'float') {
                $t = 'number';
                if ($s === 's') {
                    $t = 'string';  // allow numbers to be cast as strings
                }
            }
            else if ($t === 'object') {
                $varg = v($arg);
                if ($varg->u_is_map()) {
                    $t = 'map';
                }
                else if ($varg->u_is_list()) {
                    $t = 'list';
                }
                else if (get_class($arg) == 'Closure') {
                    $t = 'callable';
                }
            }

            // Type mismatch
            if ($t !== Runtime::$SIG_TYPE_KEY_TO_LABEL[$s]) {
                $name = $t;
                $err = "expects argument $i to be a `" . Runtime::$SIG_TYPE_KEY_TO_LABEL[$s] . "`.  Got: `" . $name . "`";
                break;
            }
            $i += 1;
        }
    }

    if ($err) {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[1]['function'];
        Tht::error("(Argument Checker) Function `$caller()`" . $err);
    }
}

