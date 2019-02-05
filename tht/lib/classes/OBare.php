<?php

namespace o;

// Functions without a module namespace
class OBare {

    static $FUNCTIONS = [ 'import', 'print', 'range', 'die' ];

    static function isa ($word) {
        return in_array($word, OBare::$FUNCTIONS);
    }

    static function formatPrint($parts) {

        $outs = [];
        foreach ($parts as $a) {

            if (is_string($a)) {
                if ($a === '') {
                    $a = '(nothing)';
                }
            }
            else {
                $a = Tht::module('Json')->u_format($a);
            }
            
            $a = preg_replace("/'<<<(.*?)>>>'/", '<$1>', $a);
            if (Tht::isMode('web')) {
                $a = htmlentities($a);
            }

            $outs []= $a;
        }

        return implode("\n", $outs);
    }

    static function u_print () {

        $out = OBare::formatPrint(func_get_args());

        if (Tht::isMode('web')) {
            WebMode::queuePrint($out);
        }
        else {
            echo $out, "\n";
        }

        return new \o\ONothing('print');
    }

    static function u_import ($relPath) {
        ModuleManager::loadUserModule($relPath);
    }

    static function u_range ($start, $end, $step=1) {
        ARGS('nnn', func_get_args());
        return range($start, $end, $step);
    }

    // TODO: Iterator Version
    // static function u_range ($start, $end, $step=1) {
    //         if ($step <= 0) {
    //             Tht::error('Step argument ' . $step . ' must be positive');
    //         }
    //     if ($start < $end) {
    //         for ($i = $start; $i <= $end; $i += $step) {
    //             yield $i;
    //         }
    //     } else {
    //         for ($i = $start; $i >= $end; $i -= $step) {
    //             yield $i;
    //         }
    //     }
    // }

    static function u_die ($msg, $data=null) {
        ARGS('s*', func_get_args());
        Tht::error($msg, $data);
    }
}

