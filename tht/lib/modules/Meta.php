<?php

namespace o;

// TODO
// bind/call/methods/getClass
// bind = via Closure class
// equivalent of __filename, __dirname?
class u_Meta extends OStdModule {

    function getCallerNamespace () {
        $trace = debug_backtrace(0, 2);
        $nameSpace = ModuleManager::getNamespace(Tht::getThtPathForPhp($trace[1]['file']));
        return $nameSpace;
    }

    function u_function_exists ($fn) {
        $this->ARGS('s', func_get_args());
        $fullFn = $this->getCallerNamespace() . '\\' . u_($fn);
        return function_exists($fullFn);
    }

    function u_call_function ($fn, $params=[]) {
        $this->ARGS('sl', func_get_args());
        $fullFn = $this->getCallerNamespace() . '\\' . u_($fn);
        if (! function_exists($fullFn)) {
            $this->error("Function does not exist: `$fullFn`");
        }
        return call_user_func_array($fullFn, uv($params));
    }

    function u_new_object($cls, $params=[]) {
        $this->ARGS('sl', func_get_args());
        $o = \o\ModuleManager::newObject($cls, uv($params));

        return $o;
    }

    function u_parse ($source) {
        $this->ARGS('s', func_get_args());
        return Compiler::safeParseString($source);
    }

    // TODO:  debug_backtrace is slow. support inline splats instead (supported in PHP 5.6+).
    function u_arguments () {
        $trace = debug_backtrace(0, 2);
        $args = $trace[1]['args'];
        return $args;
    }

    // TODO: filter and clean -- sourcemap
    // function u_stack_trace ($ignoreArgs=false) {
    //     $arg = $ignoreArgs ? DEBUG_BACKTRACE_IGNORE_ARGS : 0;
    //     return debug_backtrace($arg);
    // }

    // function u_function_caller () {
    //     return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
    // }

    // function u_is_command_mode () {
    //     return Tht::isMode('cli');
    // }
    //
    // function u_is_web_mode () {
    //     return Tht::isMode('web');
    // }
    //

    // TODO: isTestServer

    function u_no_template_mode () {
        if (Runtime::inTemplate()) {
            Runtime::resetTemplateLevel();
            $this->callerError('can not be called in Template mode. Try: Process data outside the template and pass it in.');
        }
        return true;
    }

    function u_no_web_mode () {
        if (!Tht::isMode('cli')) {
            $this->callerError('can not be called in Web mode. Try: Process data in a scheduled job (e.g. via cron).');
        }
    }
    //
    // function u_no_command_mode () {
    //     if (Tht::isMode('cli')) {
    //         $this->callerError('can not be called in command line mode.');
    //     }
    // }

    function u_tht_version($numeric = false) {
        return Tht::getThtVersion($numeric);
    }

    function callerError ($msg) {
        $this->ARGS('s', func_get_args());
        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $callerFrame = false;
        foreach ($frames as $f) {
            if (hasu_($f['function'])) {
                if ($f['class'] !== 'o\\u_Meta') {
                    $callerFrame = $f;
                    break;
                }
            }
        }
        if (!$callerFrame) {
            $callerFrame = $frames[2];
        }

        $caller = $callerFrame['function'];
        $class = $callerFrame['class'];

        $this->error("`$class.$caller()` " . $msg);
    }
}
