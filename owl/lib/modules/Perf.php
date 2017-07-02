<?php

namespace o;


class u_Perf extends StdModule {

    private $results = [];
    private $startTime = [];
    private $taskCount = [];
    private $tasks = [];
    private $forceActive = false;

    function isActive () {
        return Owl::getConfig('showPerfScore') || $this->forceActive;
    }

    function cleanValue ($value) {
        if ($value) {
            $value = trim(substr($value, 0, 80));
            $value = preg_replace("/\s+/", ' ', $value);
        }
        return $value;
    }

    function u_force_active($onOff) {
        $this->forceActive = $onOff;
    }

    function u_start ($baseTaskId, $value='') {
        if ($this->isActive()) {
            $this->start($baseTaskId, $value);
        }
    }

    function start ($taskId, $value='') {
        $this->tasks []= [
            'task' => $taskId,
            'value' => $value,
            'startTime' => microtime(true),
            'subTaskTime' => 0,
            'subs' => []
        ];
    }

    function u_stop () {

        if (!$this->isActive()) { return; }

        if (!count($this->tasks)) {
            Owl::error('There are no benchmark tasks left to stop');
        }
        $task = array_pop($this->tasks);
        $elapsedAll = (microtime(true) - $task['startTime']);
        $elapsed = ($elapsedAll - $task['subTaskTime']);

        $result = [ 'task' => $task['task'] ];
        if ($task['value']) {
            $result['value'] = $this->cleanValue($task['value']);
        }
        $result['durationMs'] = round($elapsed * 1000, 2);
        $result['peakMemoryMb'] = Owl::module('System')->u_peak_memory_usage();
        $this->results []= $result;

        foreach ($this->tasks as &$t) {
            $t['subTaskTime'] += $elapsed;
            $t['subs'] []= $result;
        }
    }

    function u_results () {
        if (!$this->isActive()) { return []; }

        usort($this->results, function ($a, $b) {
            $d = $b['durationMs'] > $a['durationMs'];
            return $d > 0 ? 1 : ($d < 0 ? -1 : 0);
        });

        return $this->results;
    }

    function printResults () {

        if (! $this->isActive()) { return; }

        $unstoppedTasks = array_keys($this->startTime);
        if (count($unstoppedTasks)) {
           Owl::error('Unstopped Perf task: ' . $unstoppedTasks[0]);
        }
        usort($this->results, function ($a, $b) {
            $d = $b['durationMs'] > $a['durationMs'];
            return $d > 0 ? 1 : ($d < 0 ? -1 : 0);
        });

        $start = Owl::getPhpGlobal('server', 'REQUEST_TIME_FLOAT');
        $allDuration = round((microtime(true) - $start) * 1000, 2);
        $peakMem = Owl::module('System')->u_peak_memory_usage();

        $owlDocLink = Owl::getOwlSiteUrl('/reference/perf-score');

        $formatted = Owl::module('Json')->u_format([ 'benchmark'=>$this->results ]);
        if (Owl::isMode('cli')) {
            print $formatted;
        } else {
            $nonce = Owl::module('Web')->u_nonce();
            $table = OLockString::getUnlocked(
                Owl::module('Web')->u_table($this->results,
                    [ 'task', 'durationMs', 'peakMemoryMb', 'value' ],
                    [ 'Task', 'Duration (ms)', 'Peak Memory (mb)', 'Detail' ],
                    'bench-result'
                )
            );
            $compileMessage = Source::getDidCompile() ? '<div class="bench-compiled">Files were updated.  Refresh to see compiled results.</div>' : '';

            ?><style>
            #perf-score-container { background-color: #f6f6f6; border-top: solid 2px #ddd; color: #111; font: 18px <?= u_Css::u_monospace_font() ?>; padding: 32px 32px 64px; }
                .bench-result { font-size: 14px; border-collapse: collapse; margin: 32px auto; }
                .bench-result td,
                .bench-result th { text-align: left; padding: 8px 12px; border-bottom: solid 1px #ccc; }
                .bench-result td:nth-child(4) { font-size: 80%;  }
                .bench-compiled { background-color: #a33; color: #fff; padding: 16px 0; font-weight: bold; margin-top: 36px; text-align: center; }
                #perfScoreTotal { font-weight: bold; }
                #perfScoreTotalLabel { margin-right: 24px; font-size: 90%; }
                #perfTotals { width: 350px; margin: 48px auto; }
                #perfTotals div { margin-bottom: 12px; }
                #perfTotals span { font-weight: bold; }
                #perfHeader { font-size: 30px; font-weight: bold; text-align: center; letter-spacing: -2px }
            </style>
            <div id="perf-score-container">

                <div id='perfHeader'>Perf Score: <span id='perfScoreTotalLabel'></span> <span id='perfScoreTotal'></span></div>

                <?= $compileMessage ?>

                <div id="perfTotals">
                    <div>Server - Response Time: <span id='perfScoreServer'></span></div>
                    <div>Client - window.onLoad: <span id='perfScoreClient'></span></div>
                </div>

                <?= $table ?>


                <div style='text-align:center; margin-top:48px;'>
                    <p style="font-size: 80%"> Sub-task time is not included in parent tasks.</p>
                    <a href="<?= $owlDocLink ?>">See Perf Help &raquo;</a>
                </div>

            </div>

            <script nonce="<?= $nonce ?>">
            window.onload = function () {
                requestAnimationFrame(function(){

                    var perf = window.performance.timing;
                    var stats = {
                        server: perf.responseEnd - perf.requestStart,
                        client: perf.loadEventEnd - perf.responseEnd,
                        memory: <?= $peakMem ?>
                    };

                    var clientScore = Math.max(stats.client - 100, 0) * 0.1;
                    var serverScore = Math.max(stats.server - 100, 0) * 0.1;
                    var totalTime = stats.client + stats.server;

                    var grade = { label: '<b>D</b> (Slow)', color: '#933' };
                    if (totalTime <= 200) { grade = { label: '<b>A+</b> (Excellent!)', color: '#3a3' }; }
                    else if (totalTime <= 500) { grade = { label: '<b>A</b> (Great!)', color: '#3a3' }; }
                    else if (totalTime <= 1000) { grade = { label: '<b>B</b> (Good)', color: '#94c114' }; }
                    else if (totalTime <= 2000) { grade = { label: '<b>C</b> (Okay)', color: '#dc7434' }; }

                    var getId = document.getElementById.bind(document);

                    getId('perfScoreServer').innerText = stats.server + ' ms';
                    getId('perfScoreClient').innerText = stats.client + ' ms';
                    getId('perfScoreTotal').innerText = totalTime + ' ms';
                    getId('perfScoreTotalLabel').innerHTML = grade.label;
                    getId('perfScoreTotal').style.color = grade.color;
                    getId('perfScoreTotalLabel').style.color = grade.color;
                });
            }
            </script>
            <?php
        }
    }
}

