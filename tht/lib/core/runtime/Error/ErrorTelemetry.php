<?php

namespace o;

class ErrorTelemetry {

    // TODO: Lower this as overall THT usage increases
    static private $SEND_RATE = 20; // per cent

    // Called from ErrorHandlerOutput
    static function save($error) {

        if (!Security::isDev() || !isset($error['source'])) {
            return;
        }

        $file = Tht::getRelativePath('code', $error['source']['file']);
        $cacheKey = self::getCacheKey($file);

        $prevError = Tht::module('Cache')->u_get($cacheKey, '');
        if ($prevError && $prevError['message'] == $error['message']) {
            return;
        }

        $srcLine = preg_replace('/^\d+:\s*/', '', $error['source']['lineNum']);
        $srcLine = preg_replace('/\s*\^\s*$/', '', $srcLine);

        $sendError = [
            'type'    => $error['origin'],
            'time'    => time(),
            'srcFile' => $file,
            'srcLine' => $srcLine,
            'message' => $error['message'],
        ];

        Tht::module('Cache')->u_set($cacheKey, $sendError, 0);
    }

    static function getCacheKey($filePath) {
        $relPath = Tht::getRelativePath('code', $filePath);
        return 'tht.lastError|' . $relPath;
    }

    static function send($thtFile) {

        // Only send if error is followed by a good compile
        if (!Security::isDev() || !Compiler::getDidCompile() || !Tht::getConfig('sendErrors')) {
            return;
        }

        $cacheKey = self::getCacheKey($thtFile);
        $error = Tht::module('Cache')->u_get($cacheKey, '');

        if (!$error) {
            return;
        }

        Tht::module('Cache')->u_delete($cacheKey);


        require_once(__DIR__ . '/../../compiler/SourceAnalyzer.php');

        $sa = new SourceAnalyzer ($thtFile);
        $stats = $sa->getCurrentStats();

        $mergeStats = [
            'linesInFile'      => $stats['numLines'],
            'functionsInFile'  => $stats['numFunctions'],
            'linesPerFunction' => $stats['numLinesPerFunction'],
            'totalWorkTime'    => $stats['totalWorkTime'],
            'numCompiles'      => $stats['numCompiles'],
        ];

        $error = array_merge($error, $mergeStats);

        $error['fixDurationSecs'] = time() - $error['time'];
        $error['thtVersion'] = Tht::getThtVersion(true);
        $error['phpVersion'] = PHP_VERSION_ID;
        $error['os'] = Tht::module('System')->u_get_os();

        $url = Tht::getConfig('_sendErrorsUrl');
        if ($url->u_render_string() == '/local') {
            self::handleInbound($error);
        }
        else {

            if (rand(1, 100) <= self::$SEND_RATE) {
                try {
                    $res = Tht::module('Net')->u_http_post($url, OMap::create($error));
                }
                catch (\Exception $e) {
                    // NOOP - Drop on floor
                }
            }
        }
    }

    // Inbound telemetry - Save to 'errors' database
    /*

        CREATE TABLE errors (
            errorId INTEGER PRIMARY KEY,

            ip TEXT NOT NULL,
            errorDate INTEGER,

            type TEXT NOT NULL,
            message TEXT NOT NULL,
            srcLine TEXT NOT NULL,
            srcFile TEXT NOT NULL,

            fixDurationSecs INTEGER,

            linesInFile INTEGER,
            functionsInFile INTEGER,
            linesPerFunction INTEGER,

            totalWorkTime INTEGER,
            numCompiles INTEGER,

            thtVersion INTEGER,
            phpVersion INTEGER,
            os TEXT NOT NULL
        );

        CREATE INDEX idx_error_date ON errors(errorDate);
        CREATE INDEX idx_error_type ON errors(type);

    */
    public static function handleInbound($error) {

        $rulesMap = OMap::create([
            'type' => 's|max:100',
            'message' => 'xDangerRaw',
            'srcLine' => 'xDangerRaw',
            'srcFile' => 's|max:120',
            'fixDurationSecs' => 'i',
            'linesInFile' => 'i',
            'functionsInFile' => 'i',
            'linesPerFunction' => 'f',
            'thtVersion' => 'i',
            'phpVersion' => 'i',
            'totalWorkTime' => 'i',
            'numCompiles' => 'i',
            'os' => 's|max:20',
        ]);

        $error = OMap::create($error);

        $val = Tht::module('Input')->validateRawFields($rulesMap, $error);

        if ($val['ok']) {

            $val['fields']['message'] = v($val['fields']['message'])->u_limit(120);
            $val['fields']['srcLine'] = v($val['fields']['srcLine'])->u_limit(120);

            $val['fields']['ip'] = v(v(Tht::module('Request')->u_get_ip())->u_fingerprint())->u_left(20);
            $val['fields']['errorDate'] = Tht::module('Date')->u_unix_time();

            $errorDb = Tht::module('Db')->u_use_database('errors');
            $errorDb->u_insert_row('errors', $val['fields']);

            return OMap::create([ 'status' => 'ok' ]);
        }

        return OMap::create([
            'status' => 'invalidData',
            'errors' => v($val['errors']),
        ]);
    }

}
