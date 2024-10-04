<?php

namespace o;

class u_Test extends OStdModule {

    private $lastParserError = '';

    private $out = [];
    private $perfTask = null;
    private $isBenchmarkMode = false;

    var $stats = [
        'numPassed' => 0,
        'numFailed' => 0,
    ];

    function newObject() {
        return new u_Test ();
    }

    function u_set_benchmark_mode() {
        $this->isBenchmarkMode = true;
        return true;
    }

    function u_skip_slow_tests() {
        return Tht::module('Perf')->u_is_active() || $this->isBenchmarkMode;
    }

    function u_is_benchmark_mode() {
        return $this->isBenchmarkMode;
    }

    function u_section($s, $skipPerfTask = false) {

        $this->ARGS('sb', func_get_args());

        if ($this->perfTask) {
            $this->perfTask->u_stop();
        }

        if (!$skipPerfTask) {
            $this->perfTask = Tht::module('Perf')->u_start('test.section[' . $s . ']');
        }

        $this->out []= [ 'section' => $s ];

        return $this;
    }

    function u_stats() {
        $this->ARGS('', func_get_args());
        $s = OMap::create($this->stats);
        $s['total'] = $s['numPassed'] + $s['numFailed'];
        return $s;
    }

    function u_ok($expression, $msg) {
        $this->ARGS('*s', func_get_args());
        $isOk = $expression ? true : false;
        $this->stats[$isOk ? 'numPassed' : 'numFailed'] += 1;
        $this->addLine($isOk, $msg);
        return $this;
    }

    function u_todo($msg) {
        $this->ARGS('s', func_get_args());
        $this->stats['numFailed'] += 1;
        $this->addLine(false, 'TODO: ' . $msg);
        return $this;
    }

    function addLine($result, $msg) {
        $this->out []= [ 'result' => $result, 'msg' => $msg ];
    }

    function skip($msg) {
        $this->addLine(true, 'SKIP - ' . $msg);
        return $this;
    }

    function u_dies($callback, $desc, $matchError='') {

        $this->ARGS('css', func_get_args());

        if (Tht::module('Perf')->isActive() || $this->isBenchmarkMode) {
            return $this->skip('Perf Panel ON | ' . $desc);
        }

        if (!$matchError) {
            $matchError = $desc;
        }

        ErrorHandler::startTrapErrors();

        $errorMsg = '';

        try {
            $callback();
        }
        catch (\Error $e) {
            $errorMsg = $e->getMessage();
        }
        catch (\Exception $e) {
            $errorMsg = $e->getMessage();
        }
        catch (\TypeError $e) {
            $errorMsg = $e->getMessage();
        }
        catch (\ArgumentCountError $e) {
            $errorMsg = $e->getMessage();
        }

        $trapped = ErrorHandler::endTrapErrors();
        if ($trapped) {
            $errorMsg = $trapped['message'];
        }

        Tht::loadLib('lib/core/main/Error/ErrorTextUtils.php');
        $errorMsg = ErrorTextUtils::cleanString($errorMsg);

        $caughtError = !!$errorMsg;
        if ($matchError) {
            $fuzzy1 = str_replace('`', "'", strtolower($errorMsg));
            $fuzzy1 = preg_replace('/\s+/', ' ', $fuzzy1);

            $fuzzy2 = str_replace('`', "'", strtolower($matchError));
            $fuzzy2 = preg_replace('/\s+/', ' ', $fuzzy2);

            $caughtError = str_contains($fuzzy1, $fuzzy2);
        }

        $out = 'dies - ' . $desc;
        if (!$caughtError) {
            $out .= ' | got: ' . $errorMsg;
        }

        return $this->u_ok($caughtError, $out);
    }

    function parserDies($code, $match, $isFuzzy = false) {

        $matchError = false;
        $this->lastParserError = '';

        try {
            Tht::module('Meta')->u_parse($code);
        }
        catch (\Exception $e) {
            $this->lastParserError = $e->getMessage();

            $matchError = strpos(strtolower($e->getMessage()), strtolower($match));
            if (!$matchError && !$isFuzzy) {
                // allow for matching of backticks without needing to escape
                $match = str_replace("'", "`", $match);
                return $this->parserDies($code, $match, true);
            }
        }

        ErrorHandler::resetState();

        return $matchError;
    }

    function u_parser_error($code, $match, $msg=null) {

        if (Tht::module('Perf')->isActive() || $this->isBenchmarkMode) {
            return $this->skip('Perf Panel ON | ' . $match);
        }

        $dies = $this->parserDies($code, $match);
        $msg = str_replace("\n", "\\n", $code) . ' | error: ' . $match;
        if (!$dies) {
            $msg .= ' | got: ' . $this->u_last_parser_error();
        }
        return $this->u_ok($dies, $msg);
    }

    function u_parser_ok($code, $msg) {

        if (Tht::module('Perf')->isActive() || $this->isBenchmarkMode) {
            return $this->skip('Perf Panel ON | ' . $msg);
        }

        $dies = false;
        $err = '';
        try {
            Tht::module('Meta')->u_parse($code);
        } catch (\Exception $e) {
            $dies = true;
            $err = $e->getMessage();
        }
        $msg = str_replace("\n", "\\n", $code) . ' | ok: ' . $msg;
        if ($dies) {
            $msg .= " | got: " . $err;
        }

        return $this->u_ok(!$dies, $msg);
    }

    function u_last_parser_error() {
        $this->ARGS('', func_get_args());
        return $this->lastParserError;
    }

    function u_results_html() {

        $this->ARGS('', func_get_args());

        if ($this->perfTask) {
            $this->perfTask->u_stop();
            $this->perfTask = null;
        }


        $this->u_section('Results', true);

        $str = '<style> .test-result { font-family:' . Tht::module('Output')->font('monospace') . "}\n\n </style>\n\n";
        foreach ($this->out as $l) {
            if (isset($l['section'])) {
                if (Tht::isMode('web')) {
                    $str .= '<a name="test-' . v($l['section'])->u_to_token_case('-') . '"></a>';
                    $str .= "<h2>" . $l['section'] . "</h2>\n";
                } else {
                    $str .= "\n# " . $l['section'] . "\n\n";
                }
            } else {
                $msg = $l['msg'];
                $fmtResult = $l['result'] ? '(OK)' : 'FAIL';
                if (Tht::isMode('web')) {
                    $color = $l['result'] ? '#090' : '#c33';
                    $str .= "<div class='test-result'><b style='color:$color'>" . $fmtResult . '</b>  ' . Security::escapeHtml($msg) . "</div>";
                } else {
                    $str .= '  ' . $fmtResult . '  ' . $msg . "\n";
                }
            }
        }

        if (Tht::isMode('web')) {
            $str .= "<div style='font-size: 150%; margin: 2rem 0'>\n";
            $str .= "Passed:  <b style='color: #393'>" . $this->stats['numPassed'] . "</b> &nbsp;\n";
            $str .= "Failed:  <b style='color: #e33'>" . $this->stats['numFailed'] . "</b>\n\n";
            $str .= "</div>\n\n";
        } else {
            $str .= "Passed:  " . $this->stats['numPassed'] . "\n";
            $str .= "Failed:  " . $this->stats['numFailed'] . "\n\n";
        }

        if ($this->u_is_benchmark_mode()) {
            return new HtmlTypeString(Security::jsonEncode($this->stats));
        }

        return new HtmlTypeString ($str);
    }

    // Undocumented
    function u_check_args() {

        $args = func_get_args();
        $mask = array_shift($args);

        $error = validateFunctionArgs($mask, $args);
        if ($error) {
            Tht::error($error['msg']);
        }

        return true;
    }

    function u_shake($val) {

        $this->ARGS('*', func_get_args());

        if (!is_string($val)) {
            $val = json_encode($val);
        }

        // Apply the Richter-Lesko Seismic Stability Algorithm
        $structure = md5($val . 'tht');
        $foundation = str_split(substr($structure, 0, 10));
        $stability = 0;
        foreach ($foundation as $brick) {
            $stability += hexdec($brick);
        }

        // Shake it
        for ($i = 0; $i < 1_000_000; $i += 1) {
            $stability += 1;
            $stability -= 1;
            $stability += 1;
            $stability -= 1;
        }

        $isStable = $stability >= 80;

        return $isStable;
    }
}

