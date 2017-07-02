<?php

namespace o;

class u_System extends StdModule {

    function _call ($fn, $args=[], $checkReturn=true) {

        $ret = \call_user_func_array($fn, $args);
        if ($checkReturn && $ret === false) {
            Owl::error("Error in system call '$fn'");
        }

        return is_null($ret) ? false : $ret;
    }

    function u_command_args () {

        Owl::module('Meta')->u_no_web_mode();
        Owl::module('Meta')->u_no_template_mode();

        global $argv;
        return $argv;
    }

    // TODO: allow placeholders w shellescapearg.  same api as PDO
    function u_command ($lockedCmd, $isPassThru) {

        Owl::module('Meta')->u_no_template_mode();
        Owl::module('Meta')->u_no_web_mode();

        $cmd = OLockString::getUnlocked($lockedCmd, 'command()');

        if ($isPassThru) {
            passthru($cmd, $returnVal);
            return $returnVal;
        } else {
            $output = [];
            exec($cmd, $output, $returnVal);
            return [ 'output' => $output, 'returnCode' => $returnVal ];
        }
    }

    function u_exit ($ret=0) {
        exit($ret);
    }

    function u_sleep ($ms=0) {

        Owl::module('Perf')->u_start('System.sleep');
        $r = u_System::_call('usleep', [$ms * 1000]);
        Owl::module('Perf')->u_stop();

        return $r;
    }

    function u_cpu_load_average () {
        return u_System::_call('sys_getloadavg');
    }

    function u_memory_usage () {
        $mem = u_System::_call('memory_get_usage', [true]);
        return $mem / 1048576;
    }

    function u_peak_memory_usage () {
        $mem = u_System::_call('memory_get_peak_usage', [true]);
        return $mem / 1048576;
    }

    function u_owl_version () {
        return Owl::$VERSION;
    }

    function u_app_compile_time () {
        return Source::getAppCompileTime();
    }

    function u_start_time () {
        return Owl::getPhpGlobal('server', 'REQUEST_TIME');
    }

    function u_log_globals () {
        $dump = v(Owl::$rawGlobals)->u_dump();
        Owl::module('OBare')->u_log($dump);
    }

    function u_input () {
        Owl::module('Meta')->u_no_web_mode();
        return trim(fgets(STDIN));
    }

    function u_confirm ($msg, $default=false) {
        Owl::module('Meta')->u_no_web_mode();
        $yn = $default ? '(Y/n)' : '(y/N)';
        print $msg . " $yn? ";
        $in = $this->u_input();
        if (!$in) { return $default; }
        $ans = strtolower($in[0]);
        if ($ans === 'y') { return true; }
        if ($ans === 'n') { return false; }
        return $default;
    }

}

