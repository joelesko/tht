<?php

namespace o;

// Note: there is a silent error when calling require() on any file called 'System.php'.
// Hence, the extra 'X'.

class u_System extends StdModule {

    function _call ($fn, $args=[], $checkReturn=true) {

        Tht::module('Meta')->u_no_template_mode();

        $ret = \call_user_func_array($fn, $args);
        if ($checkReturn && $ret === false) {
            Tht::error("Error in system call '$fn'");
        }

        return is_null($ret) ? false : $ret;
    }

    function u_command_args () {

        Tht::module('Meta')->u_no_web_mode();
        Tht::module('Meta')->u_no_template_mode();

        global $argv;
        return OList::create($argv);
    }

    function u_command ($lockedCmd, $isPassThru=false) {

        Tht::module('Meta')->u_no_web_mode();
        Tht::module('Meta')->u_no_template_mode();

        $cmd = OLockString::getUnlocked($lockedCmd, 'cmd');

        Tht::module('Perf')->u_start('System.command', $cmd);
        $ret = '';
        if ($isPassThru) {
            passthru($cmd, $returnVal);
            $ret = $returnVal;
        } else {
            $output = [];
            exec($cmd, $output, $returnVal);
            $ret = [ 'output' => OList::create($output), 'returnCode' => $returnVal ];
        }
        Tht::module('Perf')->u_stop();

        return $ret;
    }

    function u_exit ($ret=0) {
        Tht::exitScript($ret);
    }

    function u_sleep ($ms=0) {
        Tht::module('Perf')->u_start('System.sleep');
        $r = u_System::_call('usleep', [$ms * 1000]);
        Tht::module('Perf')->u_stop();

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

    function u_app_compile_time () {
        return Source::getAppCompileTime();
    }

    function u_start_time () {
        return Tht::getPhpGlobal('server', 'REQUEST_TIME');
    }

    function u_log_globals () {
        $dump = v(Tht::$rawGlobals)->u_dump();
        Tht::module('OBare')->u_log($dump);
    }

    function u_input () {
        Tht::module('Meta')->u_no_web_mode();
        return trim(fgets(STDIN));
    }

    function u_confirm ($msg, $default=false) {
        Tht::module('Meta')->u_no_web_mode();
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

