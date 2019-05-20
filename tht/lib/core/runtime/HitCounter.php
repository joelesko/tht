<?php

namespace o;

class HitCounter {

    static public $skipThisPage = false;

    static private $BOT_REGEX = '/bot\b|crawl|spider|slurp|baidu|\bbing|duckduckgo|yandex|teoma|aolbuild/i';

    static public function add() {

        if (!Tht::getConfig('hitCounter')) {
            return;
        }

        if (self::skip()) {
            return;
        }

        Tht::module('Perf')->u_start('tht.hitCounter');

        self::countDate();
        self::countPage();
        self::countReferrer();

        Tht::module('Perf')->u_stop();
    }

    static private function skip() {

        if (self::$skipThisPage) {
            return true;
        }

        // bots
        $ua = Tht::module('Request')->u_user_agent();
        if (preg_match(self::$BOT_REGEX, $ua['full'])) {
            return true;
        }
        // only count routes
        $path = Tht::module('Request')->u_url()->u_path();
        if (strpos($path, '.') !== false) {
            return true;
        }

        return false;
    }

    static private function logPath($dir, $file) {
        return realpath(DATA_ROOT . '/counter/' . $dir) . '/' . $file . '.txt';
    }

    static private function logDir($dir) {
        return realpath(DATA_ROOT . '/counter/' . $dir);
    }

    // Date counter - 1 byte per hit
    static private function countDate() {
        $date = strftime('%Y%m%d');
        $dateLogPath = self::logPath('date', $date);
        file_put_contents($dateLogPath, '+', FILE_APPEND|LOCK_EX);
    }

    // Page counter - 1 byte per hit
    static private function countPage() {

        $page = Tht::module('Request')->u_url()->u_path();

        $page = preg_replace('#/+#', '__', $page);
        $page = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $page);
        $page = trim($page, '_') ?: 'home';

        $pageLogPath = self::logPath('page', $page);
        file_put_contents($pageLogPath, '+', FILE_APPEND|LOCK_EX);
    }

    // Referrer log - 1 line per external referrer
    static private function countReferrer() {

        // Only log a 20% sample
        if (rand(1, 100) > 20) {
            return;
        }
        $url = Tht::module('Request')->u_url();

        $ref = Tht::module('Request')->u_referrer();
        if ($ref) {
            if (stripos($ref, $url->u_parts()['host']) === false) {
                // format search query
                if (preg_match('/(google|bing|yahoo|duckduckgo|ddg)\./i', $ref) && strpos($ref, 'q=') !== false) {
                    preg_match('/q=(.*)(&|$)/', $ref, $m);
                    $cleanQuery = trim(preg_replace('/[^a-zA-Z0-9]+/', ' ', $m[1]));
                    $ref = 'search: "' . $cleanQuery . '"';
                }
                $logDate = strftime('%Y%m');
                $lineDate = strftime('%Y-%m-%d');
                $referrerLogPath = self::logPath('referrer', $logDate);
                file_put_contents($referrerLogPath, "[$lineDate] $ref -> " . $url->u_path() . "\n", FILE_APPEND|LOCK_EX);
            }
        }
    }





/*****  WORK IN PROGRESS





    static function counterPanel() {

        print "<!doctype html><html><head></head><body>";

        print "<style>" . Tht::module('Css')->u_include('base')->u_raw_string() . "</style>";

        $days = 'SMTWRFS';

        $num30DayHits = 0;
        $dateHits = [];
        $medianDays = [];
        $dayMod = strftime('%w', time());

        // 12 weeks
        for ($i = 84 - (6 - $dayMod); $i >= 0; $i--) {
            $dateTime = time() - ($i * 24 * 3600) - (60 * 24 * 3600);
            $ymdDate = strftime('%Y%m%d', $dateTime);
            $dateLogPath = self::logPath('date', $ymdDate);
            $num = 0;
            if (file_exists($dateLogPath)) {
                $num = filesize($dateLogPath);
                $medianDays []= $num;
            }
            if ($i <= 29) {
                $num30DayHits += $num;
            }

            $dateHits []= [ 'label' => $days[strftime('%w', $dateTime)], 'num' => $num ];
        }

        if ($dayMod < 6) {
            foreach (range($dayMod+1, 6) as $d) {
                $dateHits []= [ 'label' => $days[$d], 'num' => 0 ];
            }
        }

     //   print_r($dateHits);
        // get counts for current and previous month
        //self::logPath('referrer', $logDate);

        $medianDayHits = 0;
        if (count($medianDays)) {
            $medianDayHits = $medianDays[ceil(floor($medianDays) / 2)];
        }

        $hitsByPage = [];
        $pageDir = self::logDir('page');
        $handle = opendir($pageDir);
        while ($f = readdir($handle)) {
            if ($f != "." && $f != ".." && strpos($f, '.txt') !== false) {
                $log = $pageDir . '/' . $f;
                $label = str_replace('.txt', '', $f);
                $label = str_replace('__', '/', $label);
                $hitsByPage []= ['num' => filesize($log), 'label' => $label ];
            }
        }
        closedir($handle);

        $sortByHits = function($a, $b) {
            return $b['num'] <=> $a['num'];
        };
        usort($hitsByPage, $sortByHits);
        $hitsByPage = array_slice($hitsByPage, 0, 100);

        ?>

            <?php self::counterPanelCss() ?>
            <?= self::hitCounterChart($dateHits) ?>
            <div class="stat-row">
            Page Hits - Past 30 Days: <b><?= $num30DayHits ?> </b>
            </div>
            <div class="stat-row">
            Page Hits - Daily Average (Median): <b><?= $medianDayHits ?> </b>
            </div>

            <?= self::hitCounterChartVert($hitsByPage) ?>

        <?php

        print "</body></html>";
    }


    static function hitCounterChartVert($rows) {

        $max = 0;
        foreach ($rows as $row) {
            if ($row['num'] > $max) {
                $max = $row['num'];
            }
        }

        $out = '<table class="vert">';

        foreach ($rows as $row) {
            $out .= '<tr>';
            $pc = ceil(($row['num'] / $max) * 90);
            $out .= '<td>' . $row['label'] . '</td>';
            $out .= '<td><div class="bar" title="' . $row['num'] . '" style="width:' . $pc . '%"></div>' . $row['num'] . '</td>';
            $out .= '</tr>';
        }

        $out .= '</table>';
        return $out;
    }

    static function hitCounterChart($rows) {

        $max = 0;
        foreach ($rows as $row) {
            if ($row['num'] > $max) {
                $max = $row['num'];
            }
        }

        $out = '<div class="chart-container"><table>';
        $out .= '<tr class="bars">';
        $rowNum = 0;
        foreach ($rows as $row) {

            $pc = ceil(($row['num'] / $max) * 100);

            $out .= '<td><div class="bar" title="' . $row['num'] . '" style="height:' . $pc . '%"></div></td>';

            if ($rowNum % 7 == 0) {
                $out .= '<td></td>';
            }
            $rowNum += 1;
        }
        $out .= '</tr>';
        $out .= '<tr class="labels">';
        $rowNum = 0;
        foreach ($rows as $row) {
            $out .= '<td>' . $row['label'] . '</td>';

            if ($rowNum % 7 == 0) {
                $out .= '<td></td>';
            }
            $rowNum += 1;
        }
        $out .= '</tr>';
        $out .= '</table></div>';
        return $out;
    }

    static function counterPanelCss() {

        $nonce = Tht::module('Web')->u_nonce();

        ?>
        <style nonce="<?= $nonce ?>" scoped>

            .chart-container {
                height: 250px;
                width: 60vw;
                margin: 0 20vw;
                overflow-x: scroll;
                padding: 30px;
                border: solid 1px #ddd;
            }

            table.vert {
                width: 60vw;
                margin: 0 20vw;
                font-size: 14px;
            }
            table.vert .bar {
                height: 20px;
                border-radius: 0 2px 2px 0;
                display: inline-block;
                margin-right: 10px;
            }
            table.vert tr:hover {
                background-color: #f0f0f0;
            }
            table.vert tr:hover td:nth-child(2)  {
                font-weight: bold;
            }
            table.vert tr td:nth-child(1) {
                min-width: 50px;
                width: 40%;
                white-space: nowrap;
            }
            table.vert tr td:nth-child(2) {
                vertical-align: center;
                line-height: 0;
            }

            table {
                font-family: arial;
                font-size: 12px;
                border-collapse: collapse;
                table-layout: fixed !important;
            }

            .bar {
                width: 100%;
                background-color: #2c79bb;
                border-radius: 2px 2px 0 0;
            }
            tr.bars td {
                height: 180px;
                min-width: 20px;
                vertical-align: bottom;
                padding: 0 1px 0 0;
            }

            tr.labels td {
                height: 20px;
                vertical-align: bottom;
                text-align: center;
            }

            .stat-row {
                text-align: center;
                font-size: 24px;
                margin-top: 30px;
            }

            </style>
        <?php
    }

    **********/
}
