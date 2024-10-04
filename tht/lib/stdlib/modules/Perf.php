<?php

namespace o;

class u_Perf extends OStdModule {

    private $results = [];
    private $groupResults = [];
    private $startTime = [];
    private $taskCount = [];
    private $tasks = [];
    private $forceActive = false;
    private $taskCounts = [];

    static private $MIN_TASK_THRESHOLD_MS = 0.1;

    function isActive() {

        $show = Tht::getThtConfig('showPerfPanel') || $this->forceActive;
        if ($show && Security::isDev()) {
            return true;
        }
        return false;
    }

    function u_is_active() {
        $this->ARGS('', func_get_args());

        return $this->isActive();
    }

    function u_now() {

        $this->ARGS('', func_get_args());

        // ns to ms
        return hrtime(true) / 1e+6;
    }

    function u_force_active($onOff) {

        $this->ARGS('b', func_get_args());

        $this->forceActive = $onOff;

        return NULL_NORETURN;
    }

    function u_start($baseTaskId, $value='') {

        $this->ARGS('sS', func_get_args());

        return $this->start($baseTaskId, $value);
    }

    function start($taskId, $value='') {

        if (!$this->isActive()) {
            return new u_Perf_Task();
        }

        $value = Tht::stripAppRoot($value);
        $value = preg_replace("/\s+/", ' ', $value);
        $value = v($value)->u_limit(40);

        $task = [
            'task'          => $taskId,
            'value'         => $value,
            'startTimeMs'   => $this->u_now(),
            'startMemoryMb' => memory_get_peak_usage(false),
            'memoryMb'      => 0,
        ];

        return new u_Perf_Task($task);
    }

    function registerStop($task) {
        if (!$this->isActive() || !$task) {
            return 0;
        }

        $timeDeltaMs = $this->u_now() - $task['startTimeMs'];
        $memDelta = max(0, memory_get_peak_usage(false) - $task['startMemoryMb']);

        $result = [
            'task'  => $task['task'],
            'value' => $task['value']
        ];

        $result['durationMs'] = $timeDeltaMs;
        $result['memoryMb'] = $memDelta ? $memDelta / 1048576 : 0;

        if ($result['durationMs'] >= self::$MIN_TASK_THRESHOLD_MS) {
            $this->results []= $result;
        }

        // Aggregate results for each task
        $this->groupResults[$task['task']] ??= [
            'durationMs' => 0,
            'memoryMb' => 0,
            'numCalls' => 0,
        ];

        $group = $this->groupResults[$task['task']];
        $group['durationMs'] += $timeDeltaMs;
        $group['memoryMb'] += $memDelta;
        $group['numCalls'] += 1;
        $this->groupResults[$task['task']] = $group;

        return number_format($timeDeltaMs, 2);
    }

    function results() {
        if (!$this->isActive()) { return []; }

        $groupResults = [];
        foreach ($this->groupResults as $taskName => $task) {
            $task['task'] = $taskName;
            $task['durationMs'] = $task['durationMs'];
            $task['memoryMb'] = $task['memoryMb'] ? $task['memoryMb'] / 1048576 : 0;

            if ($task['durationMs'] >= 0.1) {
                $groupResults []= $task;
            }
        }

        $this->results = $this->filterResults($this->results);
        $groupResults = $this->filterResults($groupResults);

        $groupResults = array_slice($groupResults, 0, 10);

        $scriptTimeMs = ceil((microtime(true) - Tht::getPhpGlobal('server', "REQUEST_TIME_FLOAT")) * 1000);
        $peakMem = round(memory_get_peak_usage(false) / 1048576, 1);

        return OMap::create([
            'single'     => $this->results,
            'group'      => $groupResults,
            'peakMemory' => $peakMem,
            'scriptTime' => $scriptTimeMs,
        ]);
    }

    function filterResults($tasks) {

        usort($tasks, function ($a, $b) {
            return $b['durationMs'] <=> $a['durationMs'];
        });

        foreach ($tasks as $i => $t) {
            $tasks[$i]['durationMs'] = number_format($tasks[$i]['durationMs'], 1);
            $tasks[$i]['memoryMb'] = number_format($tasks[$i]['memoryMb'], 2);
        }
        return $tasks;
    }

    // TODO: Work In Progress
    function codeQuality() {
        // scan through all compiled files in code/modules and code/pages
        // get stats
        // compile a score

        $totalStats = [
            'numFiles'               => 0,
            'numLines'               => 0,
            'numFunctions'           => 0,
            'longestFunctionLines'   => 0,
            'longestFunctionName'    => '',
            'longestFunctionLineNum' => 0,
            'longestFunctionFile'    => '',
            'longestFileLines'       => 0,
            'longestFile'            => '',
            'workTime'               => 0,
            'numCompiles'            => 0,
        ];

        $fnEachFile = function ($file) use (&$totalStats) {

            $phpPath = Tht::getPhpPathForTht($file['path']);
            if (!file_exists($phpPath)) { return; }

            $content = PathTypeString::create($phpPath)->u_read(OMap::create(['join' => true]));

            preg_match('#/\* STATS=(\{.*\})#', $content, $m);
            $jsonString = $m[1];

            $stats = unv(Security::jsonDecode($jsonString));

            $totalStats['numFiles'] += 1;

            $totalStats['workTime'] += $stats['totalWorkTime'];
            $totalStats['numCompiles'] += $stats['numCompiles'];

            $totalStats['numLines'] += $stats['numLines'];
            $totalStats['numFunctions'] += $stats['numFunctions'];

            if ($stats['longestFunctionLines'] > $totalStats['longestFunctionLines']) {
                $totalStats['longestFunctionLines'] = $stats['longestFunctionLines'];
                $totalStats['longestFunctionName'] = $stats['longestFunctionName'];
                $totalStats['longestFunctionLineNum'] = $stats['longestFunctionLineNum'];
                $totalStats['longestFunctionFile'] = Tht::stripAppRoot($file['path']);
            }
            if ($stats['numLines'] > $totalStats['longestFileLines']) {
                $totalStats['longestFileLines'] = $stats['numLines'];
                $totalStats['longestFile'] = Tht::stripAppRoot($file['path']);
            }
        };

        $flags = OMap::create([ 'deep' => true, 'filter' => 'files' ]);

        PathTypeString::create(THT::path('pages'))->u_loop_dir($fnEachFile, $flags);

        $linesPerFile = floor($totalStats['numLines'] / $totalStats['numFiles']);
        $linesPerFunction = floor($totalStats['numLines'] / $totalStats['numFunctions']);

        $fileDenom = 500;
        $functionDenom = 50;

        $cqScore = array_sum([
            ($fileDenom - $linesPerFile) / $fileDenom,
            ($fileDenom - $totalStats['longestFileLines']) / $fileDenom,
            ($functionDenom - $linesPerFunction) / $functionDenom,
            ($functionDenom - $totalStats['longestFunctionLines']) / $functionDenom,
        ]) / 4;

        $cqStats = [
            'SCORE'             => floor($cqScore * 100) . '/100',
            'linesPerFile'      => $linesPerFile,
            'linesPerFunction'  => $linesPerFunction ,
            'longestFile'       => $totalStats['longestFile'] . ' - ' . $totalStats['longestFileLines'] . ' lines',
            'longestFunction'   => $totalStats['longestFunctionName'] . ' - ' . $totalStats['longestFunctionLines'] . ' lines',
            'numCompiles'       => $totalStats['numCompiles'],
            'workTime'          => round($totalStats['workTime'] / 3600, 1) . ' hours',
        ];

        // Longest function lines
        // avg function size

        // avg lines per file
        // longest file

        Tht::dump($cqStats);
    }

    // TODO: no CLI mode
    // TODO: this is all pretty ugly - refactor
    function printResults() {

        if (! $this->isActive()) { return; }

        $unstoppedTasks = array_keys($this->startTime);
        if (count($unstoppedTasks)) {
           Tht::error('Unstopped Perf task: `' . $unstoppedTasks[0] . '`');
        }

        $results = $this->results();

        // Have to do this outside of results() or the audit calls will show up in the perf tasks.
        //   $results['imageAudit'] = Tht::module('Image')->auditImages(Tht::path('public'));

        $thtDocLink = Tht::getThtSiteUrl('/reference/perf-panel');
        $compileMessage = Compiler::getDidCompile() ? '<div class="bench-compiled">Files were updated.  Refresh to see compiled results.</div>' : '';

        $table = OTypeString::getUntyped(
            Tht::module('Web')->u_table(OList::create($results['single']),
                OList::create([ 'task', 'durationMs', 'memoryMb', 'value' ]),
                OList::create([ 'Task', 'Duration (ms)', 'Peak Memory (MB)', 'Detail' ]),
                OMap::create(['class' => 'bench-result'])
        ), 'html');

        $tableGroup = OTypeString::getUntyped(
            Tht::module('Web')->u_table(OList::create($results['group']),
                OList::create([ 'task', 'durationMs', 'memoryMb', 'numCalls' ]),
                OList::create([ 'Task', 'Duration (ms)', 'Memory (MB)', 'Calls' ]),
                OMap::create(['class' => 'bench-result'])
        ), 'html');


        $opCache = '';
        if (Tht::isOpcodeCacheEnabled()) {
            $opCache = '<span style="color: #393">ON</span>';
        }
        else {
            $opCache = '<span style="color: #c33">OFF</span>';
        }

        $appCache = Tht::module('Cache')->u_get_driver();
        if ($appCache == 'file') {
            $appCache = '<span style="color: #c33">' . $appCache . '</span>';
        }
        else {
            $appCache = '<span style="color: #393">' . $appCache . '</span>';
        }



        $phpVersion = phpVersion();


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
            <div class="perfTotals">
                <div>Server - Page Execution: <span id="perfScoreServer"><?= $results['scriptTime'] ?> ms</span></div>
                <div>Network - Transfer: <span id='perfScoreNetwork'></span></div>
                <div>Browser - window.onload: <span id='perfScoreClient'></span></div>
            </div>
            </div>

            <div class="perfSection">
            <div class="perfTotals">
                <div>Server - Peak Memory: <span><?= $results['peakMemory'] ?> MB</span></div>
            </div>
            </div>

            <div class="perfSection tasksGrouped">
                <div class="perfSubHeader">Top Tasks (Grouped)</div>
                <?= $tableGroup ?>
            </div>

            <div class="perfSection">
                <div class="perfSubHeader">Top Tasks (Individual)</div>
                <?= $table ?>
            </div>

            <div class="perfSection">
                <div class="perfHeader">PHP Info</div>

                <div style="text-align: left; width: 300px; display: inline-block; margin-top: 32px">
                <li>PHP Version: <b><?= $phpVersion ?></b></li>
                <li>Opcode Cache: <b><?= $opCache ?></b></li>
                <li>App Cache Driver: <b><?= $appCache ?></b></li>
                </div>
            </div>

            <div class="perfSection">
                Perf Panel only visible to localhost or <code>devIp</code> in <code>config/app.jcon</code>
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

                    // See:  https://developer.mozilla.org/en-US/docs/Web/API/PerformanceNavigationTiming
                    var perf = window.performance.getEntries()[0];
                    var stats = {
                        network: Math.ceil(perf.responseEnd - perf.responseStart),
                        server: Math.ceil(perf.requestEnd - perf.requestStart),
                        client: Math.ceil(perf.loadEventEnd - perf.responseEnd),
                    };

                    var totalTime = stats.client + <?= $scriptTime ?> + stats.network;

                    var grade = { label: 'VERY SLOW', color: '#d80000' };
                    if (totalTime < 500) { grade = { label: 'FAST', color: '#3a3' }; }
                    else if (totalTime < 1000) { grade = { label: 'OK', color: '#528ad2' }; }
                    else if (totalTime < 2000) { grade = { label: 'SLOW', color: '#e6544a' }; }

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
        <style scoped>
            #perf-score-container { background-color: #f6f6f6; border-top: solid 16px #ddd; color: #111; font: 18px <?= Tht::module('Output')->font('monospace') ?>; padding-bottom: 32px; margin-top: 64px; text-align: center; }
            .bench-result { font-size: 14px; border-collapse: collapse; margin: 32px auto; }
            .bench-result td,
            .bench-result th { text-align: left; padding: 8px 12px; border-bottom: solid 1px #ccc; }
            .bench-result td:nth-child(2), .bench-result td:nth-child(3), .tasksGrouped .bench-result td:nth-child(4) { text-align: right;  }
            .bench-compiled { background-color: #a33; color: #fff; padding: 16px 0; font-weight: bold; margin-top: 36px; text-align: center; }

            #perfScoreTotal { font-weight: bold; }
            #perfScoreTotalLabel { margin-right: 24px; font-size: 100%; font-weight: bold;  }
            .perfTotals { width: 400px; margin: 0px auto; white-space: nowrap; border: }
            .perfSection { border-bottom: solid 2px #ddd; padding: 32px 0 34px; }
            .perfTotals div { line-height: 2; width: 100%; text-align: left; }
            .perfTotals span { font-weight: bold; float: right; }
            .perfHeader { font-size: 30px; font-weight: bold; text-align: center; }
            .perfSubHeader { font-size: 22px; font-weight: bold; text-align: center;  }
            .perfHelp { font-size: 20px; margin-top: 16px; text-align: center;  }
            .perfHelp a { color: #34c !important; font-size: 90%; text-decoration: none; }
            #perfImages .perfHeader { margin-bottom: 32px }
            #perf-score-container b { color: inherit; }
            li { line-height: 1.5; }
            </style>
        <?php
    }
}

class u_Perf_Task extends OClass {

    private $task = null;

    function __construct($task=null) {

        $this->task = $task;
    }

    function u_stop() {
        Tht::module('Perf')->registerStop($this->task);
    }
}



