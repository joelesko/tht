<?php

namespace o;

// Functions without a module namespace
class u_Bare extends OStdModule {

    static $FUNCTIONS = [ 'load', 'print', 'range', 'die' ];

    static function isa ($word) {
        return in_array($word, u_Bare::$FUNCTIONS);
    }

    function formatPrint($parts) {

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

    function u_print () {

        $out = $this->formatPrint(func_get_args());

        if (Tht::isMode('web')) {
            PrintBuffer::add($out);
        }
        else {
            echo $out, "\n";
        }

        return new \o\ONothing('print');
    }

    function u_load($relPath) {
        $this->ARGS('s', func_get_args());
        return ModuleManager::loadUserModule($relPath);
    }

    function u_range ($start, $end, $step=1) {

        $this->ARGS('nnn', func_get_args());

        if ($step <= 0) {
            $this->error('Step argument ' . $step . ' must be positive');
        }

        if ($start < $end) {
            for ($i = $start; $i <= $end; $i += $step) {
                yield $i;
            }
        } else {
            for ($i = $start; $i >= $end; $i -= $step) {
                yield $i;
            }
        }
    }

    function u_die ($msg, $data=null) {
        $this->ARGS('s*', func_get_args());
        ErrorHandler::addOrigin('die');
        Tht::error($msg, $data);
    }
}

