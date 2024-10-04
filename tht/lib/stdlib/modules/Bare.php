<?php

namespace o;

// Functions without a module namespace
class u_Bare extends OStdModule {

    static $FUNCTIONS = [ 'load', 'print', 'range', 'die' ];

    static function isa($word) {

        return in_array($word, u_Bare::$FUNCTIONS);
    }

    function u_print() {

        $out = $this->formatPrint(func_get_args());

        if (Tht::isMode('web')) {
            PrintPanel::add($out);
        }
        else {
            echo $out, "\n";
        }

        return NULL_NORETURN;
    }

    function formatPrintedObject($a) {

        $a = v($a)->u_z_to_print_string();

        if (Tht::isMode('web')) {
            if (HtmlTypeString::isa($a)) {
                $a = $a->u_render_string();
            }
            else {
                $a = htmlentities($a);
            }
        }

        return $a;
    }

    function formatPrint($parts) {

        $outs = [];
        foreach ($parts as $a) {
            $outs []= $this->formatPrintedObject($a);
        }

        return implode("\n", $outs);
    }

    function u_load($relPath) {

        $this->ARGS('s', func_get_args());

        return ModuleManager::loadUserModule($relPath);
    }

    // PERF: This is about 5x slower than a flat C-style for loop.
    // I tried flattening in PhpEmitter, but handling both ascending and descending ranges got complicated.
    // TODO: Revisit flattening to standard loop in the Emitter.  This isn't nearly as common as iterating over a List, at least.
    function u_range($start, $end, $step=1) {

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

    function u_die($msg, $data=null) {

        $this->ARGS('s*', func_get_args());

        ErrorHandler::addOrigin('die');
        Tht::error($msg, $data);
    }
}

