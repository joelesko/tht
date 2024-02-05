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

        return call_user_func_array($fullFn, unv($params));
    }

    function u_new_object($cls, $params=[]) {

        $this->ARGS('sl', func_get_args());

        $o = \o\ModuleManager::newObject($cls, unv($params));

        return $o;
    }

    // TODO: Undocumented
    function u_parse ($source) {

        $this->ARGS('s', func_get_args());

        return Compiler::safeParseString($source);
    }

    // TODO:  debug_backtrace is slow. support inline splats instead (supported in PHP 5.6+).
    function u_get_args () {

        $this->ARGS('', func_get_args());

        $trace = debug_backtrace(0, 2);
        $args = $trace[1]['args'];

        return OList::create($args);
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

        $this->ARGS('', func_get_args());

        if (Runtime::inTemplate()) {
            Runtime::resetTemplateLevel();
            $this->callerError('can not be called in Template mode. Try: Process data outside the template and pass it in.');
        }

        return EMPTY_RETURN;
    }

    // TODO: Undocumented
    function u_admin_only () {

        $this->ARGS('', func_get_args());

        if (!Security::isDev()) {
            Tht::module('Output')->u_send_error(403, 'Permission Denied');
            exit(0);
        }

        return EMPTY_RETURN;
    }

    function u_no_web_mode () {

        $this->ARGS('', func_get_args());

        if (!Tht::isMode('cli')) {
            $this->callerError('can not be called in Web mode. Try: Process data in a scheduled job (e.g. via cron).');
        }

        return EMPTY_RETURN;
    }
    //
    // function u_no_command_mode () {
    //     if (Tht::isMode('cli')) {
    //         $this->callerError('can not be called in command line mode.');
    //     }
    // }

    function u_get_tht_version($flags=null) {

        $this->ARGS('m', func_get_args());

        $flags = $this->flags($flags, [
            'num'  => false,
        ]);

        return Tht::getThtVersion($flags['num']);
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

    // Undocumented
    function u_z_handle_inbound_telemetry() {

        $this->ARGS('', func_get_args());

        $data = Tht::getPhpGlobal('post', '*');

        return ErrorTelemetry::handleInbound($data);
    }

    // Undocumented
    function u_z_get_std_lib() {

        $rawJson = file_get_contents(__DIR__ . '/../core/data/stdLibMethods.json');
        $json = new JsonTypeString($rawJson);

        return Tht::module('Json')->u_decode($json);
    }

    // Undocumented
    function u_z_get_std_modules() {

        $mods = [];
        foreach (LibModules::$files as $f) {
            $mods []= $f;
        }

        return OList::create($mods);
    }

    // Undocumented
    function u_z_get_std_classes() {

        $classes = [];
        foreach (LibClasses::$files as $f) {
            $classes []= ltrim($f, 'O');
        }

        return OList::create($classes);
    }
}
