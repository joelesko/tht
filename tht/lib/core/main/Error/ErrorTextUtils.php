<?php

namespace o;

class ErrorTextUtils {

    // Put extra info on new lines
    static function formatMessage($msg) {

        $msg = preg_replace("/(Got|See):/", "\n\n\$1:", $msg);
        $msg = preg_replace("/  ([\w ]+):/", "\n\n\$1:", $msg);  // need 2 preceding spaces
        $msg = preg_replace("# // #", "\n", $msg);

        return $msg;
    }

    static function cleanVars($raw) {

        $fnCamel = function ($m) {
            $isUpper = false;
            if (preg_match('/^[A-Z]/', $m[1][0])) {
                $isUpper = true;
            }
            $token = v($m[1])->u_to_token_case('camel');

            return $isUpper ? v($token)->u_to_upper_case(OMap::create(['first' => true])) : $token;
        };

        $v = $raw;
        $v = preg_replace('#[a-zA-Z0-9_\\\\]*\\\\([a-zA-Z])#', '$1', $v);   // namespace
        $v = preg_replace_callback('/\bu_([a-zA-Z_]+)/', $fnCamel, $v);  // to camelCase
        $v = preg_replace('/(?<=\w)(::|->)/', '.', $v);                  // to dot .
        $v = preg_replace('/\bO(?=[A-Z][a-z])/', '', $v);                // internal classes e.g. "OString"
        $v = ltrim($v, '\\');

        $v = preg_replace("/```/", '`(backtick)`', $v);   // prevent ambigiuity & double-wrapping of code tags later

        return $v;
    }

    static function cleanPath($path, $keepExt = false) {

        if (preg_match('#\.jcon$#', $path)) {
            $path = Tht::stripAppRoot($path);
            return $path;
        }

        $path = Tht::getThtPathForPhp($path);

        $path = Tht::stripAppRoot($path);
        $path = preg_replace('#^code/?#', '', $path);

        if (!$keepExt) {
            $path = preg_replace('#\.tht$#', '', $path);
        }

        return $path;
    }

    // Make PHP errors more human-readable and THT-centric
    public static function cleanString($m) {

        $m = self::cleanVars($m);
        $m = OClass::tokensToBareStrings($m);
        $m = Tht::normalizeWinPath($m);
        $m = Tht::stripAppRoot($m);

        // A workaround for cases where we want to keep the full path
        $m = str_replace('[[APP_PATH]]', Tht::path('app'), $m);

        $fnReplaceStrings = function ($m, $replacements) {
            foreach ($replacements as $r) {
                $m = preg_replace($r[0], $r[1], $m);
            }
            return $m;
        };

        $m = $fnReplaceStrings($m, [

            ['/Uncaught ArgumentCountError.*few arguments to function (\S+)\(\), (\d+).*/',
                'Not enough arguments passed to `$1()`.'],
            ['/supplied for/', 'in'],
            ['/stack trace:.*/is', ''], // Suppress leaked stack trace
            ['/Use of undefined constant (\w+).*/', 'Unknown token: `$1`'],
            ['/unexpected token "(.*?)"/i', 'unexpected token: `$1`'],
            ['/expecting \'(.*?)\'/', 'expecting `$1`'],
            ['/Call to undefined function (.*)\(\)/', 'Unknown function: `$1`'],
            ['/Call to undefined method (.*)\(\)/', 'Unknown method: `$1`'],
            ['/Missing argument (\d+) for (.*)\(\)/', 'Missing argument $1 for `$2()`'],
            ['/Unsupported operand types: (\S+) (\S+) (\S+)/i', 'Invalid type in math operation: `$1 $2 $3`'],
            ['/(.*?):.*?Argument (.*?) .*? must be of type (.*?), null given/', 'Null passed to non-nullable argument $2 of function: `$1`  Try: Append "OrNull" to argument name to make it nullable.'],
            ['/(.*?):.*?Argument (.*?) .*? must be of type (.*?), (.*?) given/', 'Wrong type passed to argument $2 of function: `$1`  Expected: `$3`  Got: `$4`'],
            ['/object\|array\|string\|float\|bool/i', 'any'], // compress default arg type
            ['/\{closure\}/i', '{function}'],
            ['/callable/i', 'function'],
            ['/, called.*/', ''],
            ['/preg_\w+\(\)/', 'Regex Pattern'],
            ['/\(T_.*?\)/', ''],
            ['/Uncaught error:\s*/i', ''],
            ['/in .*?.php:\d+/i', ''],
            ['/\[\] operator not supported for strings/i', "Can't use List-push `#=` on string."],
            ['/Cannot use a scalar value as an array/i', "Can't use List operation on non-List value."],  // #= on non-list type

            ['/\b(?<!\$)array\b/', 'List'],
            ['/\b(?<!\$)bool\b/',  'Boolean'],
            ['/\b(?<!\$)int\b/',   'Number'],
            ['/\b(?<!\$)float\b/', 'Number'],

            ['/[a-z_]+\\\\/i', ''],  // namespaces

            ['/\bO(list|map|regex|string)/i', '$1'], // OList -> List
            ['/\bBare./', ''], // Bare.print -> print

            ['/``/', '`(empty string)`'],
        ]);

        if (preg_match('/TypeError/', $m)) {
            $m = $fnReplaceStrings($m, [
                ['/Uncaught TypeError:\s*/i', ''],
                ['/passed to (\S+)/i', 'passed to `$1`'],
                ['/of the type (.*?),/i', 'of type `$1`.'],
                ['/\.\s*?(\S*?) given/i', '. Got: `$1`'],
            ]);
        }

        // Capitalize all types
        $fnTypeCase = function ($t) {
            return ucfirst(strtolower($t[1]));
        };
        $m = preg_replace_callback('/\b(?<!\$)(string|list|map|regex|number|integer|float)\b/i', $fnTypeCase, $m);

        $m = ucfirst($m);

        return $m;
    }

}