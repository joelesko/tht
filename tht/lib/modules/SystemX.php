<?php

namespace o;

// Note: there is a silent error when calling require() on any file called 'System.php'.
// Hence, the extra 'X'.

class u_System extends OStdModule {

    function _call ($fn, $args=[], $checkReturn=true) {

        Tht::module('Meta')->u_no_template_mode();

        $ret = \call_user_func_array($fn, $args);
        if ($checkReturn && $ret === false) {
            Tht::error("Error in system call '$fn'");
        }

        return is_null($ret) ? false : $ret;
    }

    function u_command_args () {

        $this->ARGS('', func_get_args());

        Tht::module('Meta')->u_no_web_mode();
        Tht::module('Meta')->u_no_template_mode();

        global $argv;
        return OList::create($argv);
    }

    function u_command ($typedCmd, $isPassThru=false) {

        $this->ARGS('*f', func_get_args());

        Tht::module('Meta')->u_no_web_mode();
        Tht::module('Meta')->u_no_template_mode();

        $cmd = OTypeString::getUntyped($typedCmd, 'cmd');

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
        $this->ARGS('n', func_get_args());
        Tht::exitScript($ret);
    }

    function u_sleep ($ms=0) {
        $this->ARGS('n', func_get_args());
        Tht::module('Perf')->u_start('System.sleep');
        $r = u_System::_call('usleep', [$ms * 1000]);
        Tht::module('Perf')->u_stop();

        return $r;
    }

    function u_cpu_load_average () {
        $this->ARGS('', func_get_args());
        return u_System::_call('sys_getloadavg');
    }

    function u_memory_usage () {
        $this->ARGS('', func_get_args());
        $mem = u_System::_call('memory_get_usage', [true]);
        return $mem / 1048576;
    }

    function u_peak_memory_usage () {
        $this->ARGS('', func_get_args());
        $mem = u_System::_call('memory_get_peak_usage', [true]);
        return $mem / 1048576;
    }

    function u_app_compile_time () {
        $this->ARGS('', func_get_args());
        return Compiler::getAppCompileTime();
    }

    function u_start_time () {
        $this->ARGS('', func_get_args());
        return Tht::getPhpGlobal('server', 'REQUEST_TIME');
    }

    function u_log_globals () {
        $this->ARGS('', func_get_args());
        $dump = v(Tht::$rawGlobals)->u_dump();
        Tht::module('OBare')->u_log($dump);
    }

    function u_input ($msg='', $def = '') {
        $this->ARGS('ss', func_get_args());
        if ($msg) {
            print trim($msg) . " ";
        }
        Tht::module('Meta')->u_no_web_mode();
        $in = trim(fgets(STDIN));
        if ($in === '') {
            $in = $def;
        }
        return $in;
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

    function u_os() {
        $this->ARGS('', func_get_args());
        $os = strtolower(PHP_OS);
        if (substr($os, 0, 3) == 'win') {
            return 'windows';
        } else if ($os == 'darwin') {
            return 'mac';
        }
        return $os;
    }

}

