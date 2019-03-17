<?php

namespace o;

class u_Perf extends StdModule {

    private $results = [];
    private $groupResults = [];
    private $startTime = [];
    private $taskCount = [];
    private $tasks = [];
    private $forceActive = false;
    private $taskCounts = [];

    static private $MAX_TASKS = 100;

    function isActive () {
        $show = Tht::getConfig('showPerfPanel') || $this->forceActive;
        if ($show && Security::isAdmin()) {
            return true;
        }
        return false;
    }

    function u_force_active($onOff) {
        ARGS('f', func_get_args());
        $this->forceActive = $onOff;
    }

    function u_start ($baseTaskId, $value='') {
        ARGS('ss', func_get_args());
        if ($this->isActive()) {
            $this->start($baseTaskId, $value);
        }
    }

    function start ($taskId, $value='') {

        $value = Tht::stripAppRoot($value);
        $value = substr($value, 0, 40);
        $value = preg_replace("/\s+/", ' ', $value);

        $this->tasks []= [
            'task' => $taskId,
            'value' => $value,
            'startTime' => microtime(true),
            'subTaskTime' => 0,
            'subTaskMem' => 0,
            'startMemoryMb' => memory_get_usage(false),
            'memoryMb' => 0,
        ];
    }

    function u_stop () {

        if (!$this->isActive()) { return; }

        if (!count($this->tasks)) {
            Tht::error('There are no benchmark tasks left to stop.');
        }
        $task = array_pop($this->tasks);

        $timeDelta = (microtime(true) - $task['startTime']);
        $thisTimeDelta = $timeDelta - $task['subTaskTime'];

        $memDelta = max(0, memory_get_usage(false) - $task['startMemoryMb']);
        $thisMemDelta = max(0, $memDelta - $task['subTaskMem']);

        $result = [
            'task'  => $task['task'],
            'value' => $task['value']
        ];

        $result['durationMs'] = round($thisTimeDelta * 1000, 2);
        $result['memoryMb'] = round($thisMemDelta / 1048576, 2);

        if ($result['durationMs'] >= 0.1) {
            $this->results []= $result;
        }

        // Aggregate results for each task
        if (!isset($this->groupResults[$task['task']])) {
            $this->groupResults[$task['task']] = [
                'durationMs' => 0,
                'memoryMb' => 0,
                'numCalls' => 0,
            ];
        }
        $group = $this->groupResults[$task['task']];
        $group['durationMs'] += $thisTimeDelta;
        $group['memoryMb'] += $thisMemDelta;
        $group['numCalls'] += 1;
        $this->groupResults[$task['task']] = $group;

        // Don't include sub-task stats in parent tasks
        foreach ($this->tasks as &$parentTask) {
            $parentTask['subTaskTime'] += $thisTimeDelta;
            $parentTask['subTaskMem'] += $thisMemDelta;
        }
    }

    function results () {
        if (!$this->isActive()) { return []; }

        usort($this->results, function ($a, $b) {
            $d = $b['durationMs'] > $a['durationMs'];
            return $d > 0 ? 1 : ($d < 0 ? -1 : 0);
        });

        $groupResults = [];
        foreach ($this->groupResults as $taskName => $task) {
            $task['task'] = $taskName;
            $task['durationMs'] = round($task['durationMs'] * 1000, 2);
            $task['memoryMb'] = round($task['memoryMb'] / 1048576, 2);

            if ($task['durationMs'] >= 0.1) {
                $groupResults []= $task;
            }
        }
        usort($groupResults, function ($a, $b) {
            $d = $b['durationMs'] > $a['durationMs'];
            return $d > 0 ? 1 : ($d < 0 ? -1 : 0);
        });
        $groupResults = array_slice($groupResults, 0, 10);


        $scriptTime = ceil((microtime(true) - Tht::getPhpGlobal('server', "REQUEST_TIME_FLOAT")) * 1000);
        $peakMem = round(memory_get_peak_usage(false) / 1048576, 1);

        return OMap::create([
            'single' => $this->results,
            'group' => $groupResults,
            'peakMemory' => $peakMem,
            'scriptTime' => $scriptTime,
        ]);
    }

    // TODO: no CLI mode
    function printResults () {

        if (! $this->isActive()) { return; }

        $unstoppedTasks = array_keys($this->startTime);
        if (count($unstoppedTasks)) {
           Tht::error('Unstopped Perf task: `' . $unstoppedTasks[0] . '`');
        }

        $results = $this->results();

        // Have to do this outside of results() or the audit calls will show up in the perf tasks.
        $results['imageAudit'] = Tht::module('Image')->auditImages(Tht::path('docRoot'));

        $thtDocLink = Tht::getThtSiteUrl('/reference/perf-score');
        $compileMessage = Compiler::getDidCompile() ? '<div class="bench-compiled">Files were updated.  Refresh to see compiled results.</div>' : '';

        $table = OLockString::getUnlocked(
            Tht::module('Web')->u_table(OList::create($results['single']),
                OList::create([ 'task', 'durationMs', 'memoryMb', 'value' ]),
                OList::create([ 'Task', 'Duration (ms)', 'Memory (mb)', 'Detail' ]),
                'bench-result'
        ), 'html');

        $tableGroup = OLockString::getUnlocked(
            Tht::module('Web')->u_table(OList::create($results['group']),
                OList::create([ 'task', 'durationMs', 'memoryMb', 'numCalls' ]),
                OList::create([ 'Task', 'Duration (ms)', 'Memory (mb)', 'Calls' ]),
                'bench-result'
        ), 'html');

        // TODO: this is all pretty ugly

        echo $this->perfPanelCss();
        echo $this->perfPanelJs($results['scriptTime']);

        ?>
        <div id="perf-score-container">

            <div class="perfSection">
            <div class='perfHeader'>Perf Score: <span id='perfScoreTotalLabel'></span><span id='perfScoreTotal'></span></div>

            <div class="perfHelp"><a href="<?= $thtDocLink ?>" style="font-weight:bold">About This Score</a></div>
            </div>

            <?= $compileMessage ?>

            <div class="perfSection">
            <div id="perfTotals">
                <div>Server - Page Execution: <span id="perfScoreServer"><?= $results['scriptTime'] ?> ms</span></div>
                <div>Network - Transfer: <span id='perfScoreNetwork'></span></div>
                <div>Browser - window.onLoad: <span id='perfScoreClient'></span></div>

                <div style="margin:32px 0 0;">Server - Peak Memory: <span><?= $results['peakMemory'] ?> mb</span></div>
            </div>
            </div>

            <div class="perfSection tasksGrouped">
                <div class="perfSubHeader">Top Tasks (Grouped)</div>
                <?= $tableGroup?>
            </div>

            <div class="perfSection">
                <div class="perfSubHeader">Top Tasks (Individual)</div>
                <?= $table ?>
                <div style='text-align:center; margin-top:48px;'>
                    <p style="font-size: 80%"> Sub-task time is not included in parent tasks.</p>
                </div>
            </div>

            <div class="perfSection">
            <div id="perfImages">
                <div class="perfHeader">Image Checker</div>
                <?php if ($results['imageAudit']['numImages']) { ?>

                    <p style="color: #c33; font-weight: bold">Found <b>(<?= $results['imageAudit']['numImages'] ?>)</b> images that can be optimized.</p>
                    <p>Estimated Savings: <b><?= $results['imageAudit']['savingsKb'] ?> kb -> <?= $results['imageAudit']['savingsPercent'] ?>%</b></p>

                    <p style="margin-top: 48px">To optimize, run <code>tht images</code> in your document root.</p>

                <?php } else { ?>
                    <b style="color: #393">&#10004; Great!</b> &nbsp; No un-optimized images were found in your document root.
                <?php } ?>
            </div>
            </div>

        </div>
        <?php

    }

    function perfPanelJs($scriptTime) {

        $nonce = Tht::module('Web')->u_nonce();

        ?>
        <script nonce="<?= $nonce ?>">
            window.onload = function () {
                requestAnimationFrame(function(){

                    var perf = window.performance.timing;
                    var stats = {
                        network: perf.responseEnd - perf.responseStart,
                        server: perf.requestEnd - perf.requestStart,
                        client: perf.loadEventEnd - perf.responseEnd,
                    };

                    var totalTime = stats.client + <?= $scriptTime ?> + stats.network;

                    var grade = { label: 'VERY SLOW', color: '#d80000' };
                    if (totalTime <= 500) { grade = { label: 'FAST', color: '#3a3' }; }
                    else if (totalTime <= 1000) { grade = { label: 'OK', color: '#e26c00' }; }
                    else if (totalTime <= 2000) { grade = { label: 'SLOW', color: '#e6544a' }; }

                    var getId = document.getElementById.bind(document);

                    getId('perfScoreNetwork').innerText = stats.network + ' ms';
                    getId('perfScoreServer').innerText = <?= $scriptTime ?> + ' ms';
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

    function perfPanelCss() {

        $nonce = Tht::module('Web')->u_nonce();

        ?>
        <style nonce="<?= $nonce ?>" scoped>
            #perf-score-container { background-color: #f6f6f6; border-top: solid 16px #ddd; color: #111; font: 18px <?= Tht::module('Css')->u_monospace_font() ?>; padding-bottom: 32px; margin-top: 64px; text-align: center; }
            .bench-result { font-size: 14px; border-collapse: collapse; margin: 32px auto; }
            .bench-result td,
            .bench-result th { text-align: left; padding: 8px 12px; border-bottom: solid 1px #ccc; }
            .bench-result td:nth-child(2), .bench-result td:nth-child(3), .tasksGrouped .bench-result td:nth-child(4) { text-align: right;  }
            .bench-compiled { background-color: #a33; color: #fff; padding: 16px 0; font-weight: bold; margin-top: 36px; text-align: center; }

            #perfScoreTotal { font-weight: bold; }
            #perfScoreTotalLabel { margin-right: 24px; font-size: 100%; font-weight: bold;  }
            #perfTotals { width: 400px; margin: 0px auto; white-space: nowrap; border: }
            .perfSection { border-bottom: solid 2px #ddd; padding: 32px 0 36px; }
            #perfTotals div { margin-bottom: 12px; width: 100%; text-align: left; }
            #perfTotals span { font-weight: bold; float: right; }
            .perfHeader { font-size: 30px; font-weight: bold; text-align: center; letter-spacing: -2px }
            .perfSubHeader { font-size: 22px; font-weight: bold; text-align: center; letter-spacing: -2px }
            .perfHelp { font-size: 20px; margin-top: 16px; text-align: center; letter-spacing: -1px; }
            .perfHelp a { color: #34c !important; font-size: 90%; text-decoration: none; }
            #perfImages .perfHeader { margin-bottom: 32px }
            #perf-score-container b { color: inherit; }
            </style>
        <?php
    }
}

