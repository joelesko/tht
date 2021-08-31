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

            if ($a === '') {
                $a = '(empty string)';
            }
            else if ($a === false) {
                $a = 'false';
            }
            else if (is_string($a)) {
                // keep as-is, no quotes
            }
            else {
                $rawJson = Tht::module('Json')->u_encode($a);
                $a = Tht::module('Json')->u_format($rawJson);

                $a = OClass::tokensToBareStrings($a);
            }

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

        return EMPTY_RETURN;
    }

    function u_load($relPath) {

        $this->ARGS('s', func_get_args());

        return ModuleManager::loadUserModule($relPath);
    }

    // PERF: This is about 5x slower than a flat C-style for loop.
    // I tried flattening in PhpEmitter, but handling both ascending
    // and descending ranges got overcomplicated.
    // TODO: Revisit flattening to standard loop.
    function u_range ($start, $end, $step=1) {

        $this->ARGS('nnN', func_get_args());

        $i = ONE_INDEX - 1;
        if ($start < $end) {
            for ($num = $start; $num <= $end; $num += $step) {
                $i += 1;
                yield $i => $num;
            }
        } else {
            for ($num = $start; $num >= $end; $num -= $step) {
                $i += 1;
                yield $i => $num;
            }
        }
    }

    function u_die ($msg, $data=null) {

        $this->ARGS('s*', func_get_args());

        ErrorHandler::addOrigin('die');
        Tht::error($msg, $data);
    }
}

