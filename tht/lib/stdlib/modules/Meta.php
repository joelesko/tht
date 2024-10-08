<?php

namespace o;

// TODO
// bind/call/methods/getClass
// bind = via Closure class
// equivalent of __filename, __dirname?
class u_Meta extends OStdModule {

    function getCallerNamespace() {
        $trace = debug_backtrace(0, 2);
        $nameSpace = ModuleManager::getNamespace(Tht::getThtPathForPhp($trace[1]['file']));
        return $nameSpace;
    }

    function u_function_exists($fn) {

        $this->ARGS('s', func_get_args());

        $fullFn = $this->getCallerNamespace() . '\\' . u_($fn);

        return function_exists($fullFn);
    }

    function u_call_function($fn, $params=[]) {

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
    function u_parse($source) {

        $this->ARGS('s', func_get_args());

        return Compiler::safeParseString($source);
    }

    // TODO:  debug_backtrace is slow. support inline splats instead (supported in PHP 5.6+).
    // function u_get_args() {

    //     $this->ARGS('', func_get_args());

    //     $trace = debug_backtrace(0, 2);
    //     $args = $trace[1]['args'];

    //     return OList::create($args);
    // }

    // TODO: filter and clean -- sourcemap
    // function u_stack_trace($ignoreArgs=false) {
    //     $arg = $ignoreArgs ? DEBUG_BACKTRACE_IGNORE_ARGS : 0;
    //     return debug_backtrace($arg);
    // }

    // function u_function_caller() {
    //     return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
    // }

    // function u_is_command_mode() {
    //     return Tht::isMode('cli');
    // }
    //
    // function u_is_web_mode() {
    //     return Tht::isMode('web');
    // }

    // TODO: isTestServer

    function u_fail_if_template_mode() {

        $this->ARGS('', func_get_args());

        if (Runtime::inTemplate()) {
            Runtime::resetTemplateLevel();
            $this->callerError('can\'t be called in Template mode.  Try: Process the data first, then pass it into the template.');
        }

        return NULL_NORETURN;
    }

    // TODO: Undocumented
    function u_fail_if_admin_mode() {

        $this->ARGS('', func_get_args());

        if (!Security::isDev()) {
            Tht::module('Output')->u_send_error(403, 'Permission Denied');
            exit(0);
        }

        return NULL_NORETURN;
    }

    function u_fail_if_web_mode() {

        $this->ARGS('', func_get_args());

        if (!Tht::isMode('cli')) {
            $this->callerError('can\'t be called in Web mode.  Try: Process data in a scheduled job (e.g. via cron).');
        }

        return NULL_NORETURN;
    }
    //
    // function u_no_command_mode() {
    //     if (Tht::isMode('cli')) {
    //         $this->callerError('can\'t be called in command line mode.');
    //     }
    // }

    function u_get_tht_version($flags=null) {

        $this->ARGS('m', func_get_args());

        $flags = $this->flags($flags, [
            'num'  => false,
        ]);

        return Tht::getThtVersion($flags['num']);
    }

    function callerError($msg) {

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

        $fun = $callerFrame['function'];
        $fun = ModuleManager::cleanNamespacedFunction($fun);

        // TODO: ugly.  This should be consolidated somewhere
        $class = $callerFrame['class'] ?? '';
        $class = str_replace('o\\', '', $class);

        if ($class) { $class .= '.'; }

        $this->error("`$class$fun()` " . $msg);
    }

    // Undocumented
    function u_z_handle_inbound_telemetry() {

        $this->ARGS('', func_get_args());

        $data = Tht::getPhpGlobal('post', '*');

        //return ErrorTelemetry::handleInbound($data);
    }

    // Undocumented
    function u_z_get_std_lib() {

        $rawJson = file_get_contents(Tht::systemPath('lib/core/data/stdLibMethods.json'));
        $json = new JsonTypeString($rawJson);

        return Tht::module('Json')->u_decode($json);
    }

    // Undocumented
    function u_z_get_std_modules() {

        $mods = [];
        foreach (StdLibModules::$files as $f) {
            $mods []= $f;
        }

        return OList::create($mods);
    }

    // Undocumented
    function u_z_get_std_classes() {

        $classes = [];
        foreach (StdLibClasses::$files as $f) {
            $classes []= ltrim($f, 'O');
        }

        return OList::create($classes);
    }

    function u_you_can_do_this() {
        $this->ARGS('', func_get_args());
        return true;
    }
}
