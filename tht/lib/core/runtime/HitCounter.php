<?php

namespace o;

class HitCounter {

    static private $BOT_REGEX = '/bot\b|crawl|spider|slurp|baidu|\bbing|duckduckgo|yandex|teoma|aolbuild/i';

    static public function add() {

        if (!Tht::getConfig('hitCounter')) {
            return;
        }

        $req = uv(Tht::module('Web')->u_request());

        if (self::skip($req)) {
            return;
        }

        Tht::module('Perf')->u_start('tht.hitCounter');

        self::countDate();
        self::countPage($req);
        self::countReferrer($req);

        Tht::module('Perf')->u_stop();
    }

    static private function skip($req) {

        // bots
        if (preg_match(self::$BOT_REGEX, $req['userAgent']['full'])) {
            return true;
        }
        // only count routes
        if (strpos($req['url']['path'], '.') !== false) {
            return true;
        }

        return false;
    }

    static private function logPath($dir, $file) {
        return realpath(DATA_ROOT . '/counter/' . $dir) . '/' . $file . '.txt';
    }

    // Date counter - 1 byte per hit
    static private function countDate() {
        $date = strftime('%Y%m%d');
        $dateLogPath = self::logPath('date', $date);
        file_put_contents($dateLogPath, '+', FILE_APPEND|LOCK_EX);
    }

    // Page counter - 1 byte per hit
    static private function countPage($req) {

        $page = $req['url']['path'];

        $page = preg_replace('#/+#', '__', $page);
        $page = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $page);
        $page = trim($page, '_') ?: 'home';

        $pageLogPath = self::logPath('page', $page);
        file_put_contents($pageLogPath, '+', FILE_APPEND|LOCK_EX);
    }

    // Referrer log - 1 line per external referrer
    static private function countReferrer($req) {

        // Only log a 20% sample
        if (rand(1, 100) > 20) {
            return;
        }

        $ref = isset($req['referrer']) ? $req['referrer'] : '';
        if ($ref) {
            if (stripos($ref, $req['url']['host']) === false) {
                // format search query
                if (preg_match('/(google|bing|yahoo|duckduckgo|ddg)\./i', $ref) && strpos($ref, 'q=') !== false) {
                    preg_match('/q=(.*)(&|$)/', $ref, $m);
                    $cleanQuery = trim(preg_replace('/[^a-zA-Z0-9]+/', ' ', $m[1]));
                    $ref = 'search: "' . $cleanQuery . '"';
                }
                $logDate = strftime('%Y%m');
                $lineDate = strftime('%Y-%m-%d');
                $referrerLogPath = self::logPath('referrer', $logDate);
                file_put_contents($referrerLogPath, "[$lineDate] $ref -> " . $req['url']['path'] . "\n", FILE_APPEND|LOCK_EX);
            }
        }
    }
}
