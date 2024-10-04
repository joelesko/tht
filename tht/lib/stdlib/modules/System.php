<?php

namespace o;

// Note: there is a silent error when calling require() on any file called 'System.php'.
// Hence, the extra 'X'.

class u_System extends OStdModule {

    function _call($fn, $args=[], $checkReturn=true) {

        Tht::module('Meta')->u_fail_if_template_mode();

        $ret = \call_user_func_array($fn, $args);
        if ($checkReturn && $ret === false) {
            Tht::error("Error in system call '$fn'");
        }

        return $ret;
    }

    // TODO: Undocumented
    // function u_command_args() {

    //     $this->ARGS('', func_get_args());

    //     Tht::module('Meta')->u_fail_if_web_mode();
    //     Tht::module('Meta')->u_fail_if_template_mode();

    //     global $argv;

    //     return OList::create($argv);
    // }

    // TODO: Undocumented
    // function u_command($typedCmd, $flags=null) {

    //     $this->ARGS('*m', func_get_args());

    //     Tht::module('Meta')->u_fail_if_web_mode();
    //     Tht::module('Meta')->u_fail_if_template_mode();

    //     $cmd = OTypeString::getUntyped($typedCmd, 'cmd');

    //     $perfTask = Tht::module('Perf')->u_start('System.command', $cmd);
    //     $ret = '';

    //     if ($flags && $flags['passthru']) {
    //         passthru($cmd, $returnVal);
    //         $ret = $returnVal;
    //     }
    //     else {
    //         $output = [];
    //         exec($cmd, $output, $returnVal);
    //         $ret = [ 'output' => OList::create($output), 'returnCode' => $returnVal ];
    //     }

    //     $perfTask->u_stop();

    //     return $ret;
    // }

    function u_exit($ret=0) {

        $this->ARGS('I', func_get_args());

        Tht::module('Meta')->u_fail_if_template_mode();

        Tht::exitScript($ret);
    }

    function u_sleep($ms=0) {

        $this->ARGS('I', func_get_args());

        $perfTask = Tht::module('Perf')->u_start('System.sleep');
        $r = u_System::_call('usleep', [$ms * 1000]);
        $perfTask->u_stop();

        return $r;
    }

    // TODO: undocumented
    function u_get_cpu_load_average() {

        $this->ARGS('', func_get_args());

        return u_System::_call('sys_getloadavg');
    }

    // TODO: undocumented
    function u_get_memory_usage() {

        $this->ARGS('', func_get_args());

        $mem = u_System::_call('memory_get_usage', [true]);

        return $mem / 1048576;
    }

    // TODO: undocumented
    function u_get_peak_memory_usage() {

        $this->ARGS('', func_get_args());

        $mem = u_System::_call('memory_get_peak_usage', [true]);

        return $mem / 1048576;
    }

    // TODO: undocumented
    function u_get_app_compile_time() {

        $this->ARGS('', func_get_args());

        Tht::module('Meta')->u_fail_if_template_mode();

        return Compiler::getAppCompileTime();
    }

    // TODO: undocumented
    function u_get_start_time() {

        $this->ARGS('', func_get_args());

        Tht::module('Meta')->u_fail_if_template_mode();

        return Tht::getPhpGlobal('server', 'REQUEST_TIME');
    }

    function u_input($msg='', $def = '') {

        $this->ARGS('ss', func_get_args());

        if ($msg) {
            print trim($msg) . " ";
        }

        Tht::module('Meta')->u_fail_if_web_mode();

        $in = trim(fgets(STDIN));
        if ($in === '') {
            $in = $def;
        }
        return $in;
    }

    function u_get_env_var($key, $default = '') {

        $this->ARGS('ss', func_get_args());

        Tht::module('Meta')->u_fail_if_template_mode();

        $raw = Tht::getPhpGlobal('env', $key, $default);

        return v($raw)->u_to_value();
    }

    // TODO: undocumented
    function u_confirm($msg, $default=false) {

        Tht::module('Meta')->u_fail_if_web_mode();

        $yn = $default ? '(Y/n)' : '(y/N)';
        print $msg . " $yn? ";
        $in = $this->u_input();
        if (!$in) { return $default; }
        $ans = strtolower($in[0]);

        if ($ans === 'y') { return true; }
        if ($ans === 'n') { return false; }

        return $default;
    }

    function u_get_os() {

        $this->ARGS('', func_get_args());

        Tht::module('Meta')->u_fail_if_template_mode();

        $os = strtolower(PHP_OS);

        if (substr($os, 0, 3) == 'win') {
            return 'windows';
        }
        else if ($os == 'darwin') {
            return 'mac';
        }

        return $os;
    }

    function u_set_max_run_time_secs($maxSecs) {

        $this->ARGS('I', func_get_args());

        Tht::module('Meta')->u_fail_if_template_mode();

        if ($maxSecs <= 1) {
            $this->error('Config value `maxRunTimeSecs` must be greater than 0.');
        }
        ini_set('max_execution_time', $maxSecs);

        return null;
    }

    function u_set_max_memory_mb($maxMb) {

        $this->ARGS('I', func_get_args());

        Tht::module('Meta')->u_fail_if_template_mode();

        if ($maxMb <= 1) {
            $this->error('Config value `maxMemoryMb` must be greater than 0.');
        }
        ini_set('memory_limit', $maxMb . 'M');

        return null;
    }

}

