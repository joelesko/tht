<?php

namespace o;


class ErrorTelemetry {

    // TODO: Get new send rate as response, to adjust downward over time.
    static private $SEND_RATE = 50; // per thousand (currently 5%)


    static function send($thtFile) {

        if (!Security::isDev() || !Tht::getThtConfig('sendErrors')) {
            return;
        }

        // Don't send repeat errors
        $prevError = Tht::module('Cache')->u_get('tht.prevError', '');
        if ($prevError && $prevError['message'] == $error['message']) {
            return;
        }
        Tht::module('Cache')->u_set('tht.prevError', $error['message']);

        $file = Tht::getRelativePath('code', $error['source']['file']);

        $srcLine = preg_replace('/^\d+:\s*/', '', $error['source']['lineNum']);
        $srcLine = preg_replace('/\s*\^\s*$/', '', $srcLine);

        $sendError = [
            'type'    => $error['origin'],
            'srcFile' => $file,
            'srcLine' => $srcLine,
            'message' => $error['message'],
        ];

        // Merge in source stats
        $sourceStats = self::getSourceFileStats($thtFile);
        $error = array_merge($error, $sourceStats);

        // Add meta-info
        $error['thtVersion'] = Tht::getThtVersion(true);
        $error['phpVersion'] = PHP_VERSION_ID;
        $error['os'] = Tht::module('System')->u_get_os();

        $url = Tht::getThtConfig('_sendErrorsUrl');
        if ($url->u_render_string() == '/local') {
            self::handleInbound($error);
        }
        else if (rand(1, 1000) <= self::$SEND_RATE) {
            // Fire and forget. Don't do anything with response.
            Tht::module('Net')->u_set_timeout_secs(2);
            $res = Tht::module('Net')->u_http_post($url, OMap::create($error));
        }
    }

    // Get this so we can roughly correlate source code complexity with errors.
    static private function getSourceFileStats($thtFile) {

        require_once(Tht::systemPath('lib/core/compiler/SourceAnalyzer/SourceAnalyzer.php'));

        $sa = new SourceAnalyzer ($thtFile);
        $stats = $sa->getCurrentStats();
        $sourceStats = [
            'linesInFile'      => $stats['numLines'],
            'functionsInFile'  => $stats['numFunctions'],
            'linesPerFunction' => $stats['numLinesPerFunction'],
        ];

        return $sourceStats;
    }

    // Inbound telemetry - Save to 'errors' database.
    // Including here for transparency.

    /*

        CREATE TABLE errors (
            errorId INTEGER PRIMARY KEY,

            ip TEXT NOT NULL,
            errorDate INTEGER,

            type TEXT NOT NULL,
            message TEXT NOT NULL,
            srcLine TEXT NOT NULL,

            fixDurationSecs INTEGER,

            linesInFile INTEGER,
            functionsInFile INTEGER,
            linesPerFunction INTEGER,

            thtVersion INTEGER,
            phpVersion INTEGER,
            os TEXT NOT NULL
        );

        CREATE INDEX idx_error_date ON errors(errorDate);
        CREATE INDEX idx_error_type ON errors(type);

    */
    public static function handleInbound($errorMap) {

        $errorMap = OMap::create($errorMap);

        $rulesMap = OMap::create([
            'type'             => 's|max:100',
            'message'          => 'xDangerRaw',
            'srcLine'          => 'xDangerRaw',
            'linesInFile'      => 'i',
            'functionsInFile'  => 'i',
            'linesPerFunction' => 'f',
            'thtVersion'       => 'i',
            'phpVersion'       => 'i',
            'os'               => 's|max:20',
        ]);

        $result = Tht::module('Input')->u_validate_values($errorMap, $rulesMap);

        if ($result['ok']) {

            $params = $result['params'];

            $params['message'] = v($params['message'])->u_limit(120);
            $params['srcLine'] = v($params['srcLine'])->u_limit(120);

            $params['ip'] = v(v(Tht::module('Request')->u_get_ip())->u_fingerprint())->u_left(20);
            $params['errorDate'] = Tht::module('Date')->u_unix_time();

            $errorDb = Tht::module('Db')->u_use_database('errors');
            $errorDb->u_insert_row('errors', $params);

            return OMap::create([ 'status' => 'ok' ]);
        }
        else {
            return OMap::create([
                'status' => 'invalidData',
                'errors' => v($result['errors']),
            ]);
        }
    }

}
