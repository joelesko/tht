<?php

namespace o;


class u_Log extends OStdModule {

    private $LEVELS = [
        'trace',
        'debug',
        'info',
        'warn',
        'error',
        'fatal'
    ];

    function u_trace($data) {
        $this->ARGS('*', func_get_args());
        return $this->appendLog($data, 'TRACE');
    }

    function u_info($data) {
        $this->ARGS('*', func_get_args());
        return $this->appendLog($data, 'INFO');
    }

    function u_debug($data) {
        $this->ARGS('*', func_get_args());
        return $this->appendLog($data, 'DEBUG');
    }

    function u_warn($data) {
        $this->ARGS('*', func_get_args());
        return $this->appendLog($data, 'WARN');
    }

    function u_error($data) {
        $this->ARGS('*', func_get_args());
        return $this->appendLog($data, 'ERROR');
    }

    function u_fatal($data) {
        $this->ARGS('*', func_get_args());
        return $this->appendLog($data, 'FATAL');
    }

    function u_get_file() {

        $this->ARGS('', func_get_args());

        $logConfigFile = Tht::path('logFile');
        $file = new FileTypeString ($logConfigFile);

        return $file;
    }

    function appendLog($data, $level) {

        $levelLimit = THT::getTopConfig('logs', 'logLevel');

        // TODO: validate exact match
        $levelLimit = strtolower($levelLimit);

        if ($levelLimit == 'none') { return $this; }
        if ($levelLimit !== 'all') {
            $levelLimitNum = array_search($levelLimit, $this->LEVELS, true);
            $eventLevelNum = array_search(strtolower($level), $this->LEVELS, true);
            if ($levelLimitNum !== false) {
                if ($eventLevelNum < $levelLimitNum) {
                    return $this;
                }
            }
        }

        $entry = [
            'date'  => date('Y-m-d H:i:s'),
            'level' => $level,
            'ip'    => Tht::isMode('cli') ? 'cli' : Tht::module('Request')->u_get_ip(),
            'url'   => Tht::module('Request')->u_get_url()->u_to_relative()->u_render_string(),
        ];

        if (OMap::isa($data)) {

            // Remove keys in the data that would override the base logging fields.
            foreach ($entry as $ek => $v) {
                $data->u_remove($ek);
            }

            $entry = array_merge($entry, unv($data));
        }
        else if (is_object($data)) {
            $gotClass = $data->bareClassName();
            $this->error("Can only log a Map or String.  Got: `$gotClass`");
        }
        else {
            $message = trim('' . $data);
            $message = str_replace("\n", '\\n', $message);
            $entry['message'] = $message;
        }

        $logLine = Tht::module('Json')->u_encode($entry)->u_render_string();

        $file = $this->u_get_file();

        $this->checkRotation($file);

        $file->u_append($logLine . "\n");

        return $this;
    }

    function checkRotation($file) {

        // TODO: make these things configurable

        // Only check 1% of the time.
        if (rand(1, 100) !== 1) { return; }

        $sizeMb = $file->u_get_size('MB', OMap::create(['ifExists' => true]));

        if ($sizeMb >= 10) {
            $target = $file->u_with_file_name('app.log.bak');
            $file->u_move($target, OMap::create(['overwrite' => true]));
        }

    }
}
