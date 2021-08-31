<?php

namespace o;

require_once('ErrorPageComponent.php');

class ErrorPageMessage extends ErrorPageComponent {

    public static function cleanString($errorPage, $m) {

        $m = Tht::normalizeWinPath($m);
        $m = $errorPage->cleanVars($m);

        $m = OClass::tokensToBareStrings($m);

        // Strip root directory from paths
        $m = str_replace(Tht::path('files') . '/', '', $m);
        $m = str_replace(Tht::path('app') . '/', '', $m);

        $m = self::replaceList($m, [
            ['/Uncaught ArgumentCountError.*few arguments to function (\S+)\(\), (\d+).*/',
                'Not enough arguments passed to `$1()`.'],
            ['/supplied for/', 'in'],
            ['/stack trace:.*/is', ''], // Suppress leaked stack trace
            ['/Use of undefined constant (\w+).*/', 'Unknown token: `$1`'],
            ['/expecting \'(.*?)\'/', 'expecting `$1`'],
            ['/Call to undefined function (.*)\(\)/', 'Unknown function: `$1`'],
            ['/Call to undefined method (.*)\(\)/', 'Unknown method: `$1`'],
            ['/Missing argument (\d+) for (.*)\(\)/', 'Missing argument $1 for `$2()`'],
            ['/\{closure\}/i', '{function}'],
            ['/callable/i', 'function'],
            ['/, called.*/', ''],
            ['/preg_\w+\(\)/', 'Regex Pattern'],
            ['/\(T_.*?\)/', ''],

            ['/\barray\b/', 'List'],
            ['/\bbool\b/',  'Boolean'],
            ['/\bint\b/',   'Integer'],
            ['/\bfloat\b/', 'Float'],

            ['/\bO(list|map|regex|string)/i', '$1'],

            ['/Uncaught error:\s*/i', ''],
            ['/in .*?.php:\d+/i', ''],
            ['/[a-z_]+\\\\/i', ''],  // namespaces
        ]);

        if (preg_match('/TypeError/', $m)) {

            $m = self::replaceList($m, [
                ['/Uncaught TypeError:\s*/i', ''],
                ['/passed to (\S+)/i', 'passed to `$1`'],
                ['/of the type (.*?),/i', 'of type `$1`.'],
                ['/`float`/i', '`number`'],
                ['/\.\s*?(\S*?) given/i', '. Got: `$1`'],
            ]);
        }

        $m = preg_replace('/``/', '`(empty string)`', $m);

        $m = ucfirst($m);

        return $m;
    }

    static function replaceList($m, $replacements) {
        foreach ($replacements as $r) {
            $m = preg_replace($r[0], $r[1], $m);
        }
        return $m;
    }

    function get() {

        $m = $this->error['message'];

        return $m;
    }

    function getHtml() {

        $out = $this->get();

        $out = Security::escapeHtml($out);

        // Put hints on a separate line
        $out = preg_replace("/(Did you mean.*)/i", '<br /><br />$1', $out);
        $out = preg_replace("/(Try|See|Got|Query|Object):(.*?)/", '<br /><br />$1: $2', $out);

        // Convert backticks to code lines
        $out = preg_replace("/`(.*?)`/", '<span class="tht-error-code">$1</span>', $out);

        return $out;
    }

}