<?php

namespace o;

class u_Test extends OStdModule {

    private $lastParserError = '';

    private $out = [];

    var $stats = [
        'numPassed' => 0,
        'numFailed' => 0,
    ];

    static function u_new () {
        //$this->ARGS('', func_get_args());
        return new u_Test ();
    }

    function u_section($s) {
        $this->ARGS('s', func_get_args());
        $this->out []= [ 'section' => $s ];
    }

    function u_stats() {
        $this->ARGS('', func_get_args());
        $s = OMap::create($this->stats);
        $s['total'] = $s['numPassed'] + $s['numFailed'];
        return $s;
    }

    function u_ok ($expression, $msg) {
        $this->ARGS('*s', func_get_args());
        $isOk = $expression ? true : false;
        $this->stats[$isOk ? 'numPassed' : 'numFailed'] += 1;
        $this->addLine($isOk, $msg);
        return $isOk;
    }

    function addLine($result, $msg) {
        $this->out []= [ 'msg' => $msg, 'result' => $result ];
    }

    function u_dies ($callback, $msg) {

        $this->ARGS('cs', func_get_args());

        $hasError = false;
        ErrorHandler::startTrapErrors();
        try {
            $callback();
        }
        catch (\Exception $e) {
            $hasError = true;
        }
        catch (\TypeError $e) {
            $hasError = true;
        }
        catch (\ArgumentCountError $e) {
            $hasError = true;
        }

        $trapped = ErrorHandler::endTrapErrors();

        return $this->u_ok($hasError || $trapped, 'dies - ' . $msg);
    }

    function parserDies ($code, $match, $isFuzzy = false) {

        $matchError = false;
        $this->lastParserError = '';
        try {
            Tht::module('Meta')->u_parse($code);
        } catch (\Exception $e) {
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

    function u_parser_error ($code, $match, $msg=null) {
        $dies = $this->parserDies($code, $match);
        $msg = str_replace("\n", "\\n", $code) . ' | error: ' . $match;
        return $this->u_ok($dies, $msg);
    }

    function u_parser_ok ($code, $msg) {
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
            $msg .= " | GOT: " . $err;
        }

        return $this->u_ok(!$dies, $msg);
    }

    function u_last_parser_error() {
        $this->ARGS('', func_get_args());
        return $this->lastParserError;
    }

    function u_results_html () {

        $this->ARGS('', func_get_args());
        $this->u_section('Results');

        $str = '<style> .test-result { font-family:' . Tht::module('Css')->u_font('monospace') . "}\n\n </style>\n\n";
        foreach ($this->out as $l) {
            if (isset($l['section'])) {
                if (Tht::isMode('web')) {
                    $str .= '<a name="test-' . v($l['section'])->u_to_token_case() . '"></a>';
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

        return new HtmlTypeString ($str);
    }

    function u_check_args() {
        $args = func_get_args();
        $mask = array_shift($args);
        $this->ARGS($mask, $args);
        return true;
    }

    function u_shake($val) {

        $this->ARGS('*', func_get_args());
        if (!is_string($val)) {
            $val = json_encode($val);
        }

        // Apply the Richter-Lesko Seismic Stability Algorithm
        $check = crypt($val, '$5$rounds=1984$tht$');
        $check = preg_replace('/.*\$/', '', $check);
        $foundation = substr($check, 0, 10);
        for ($i = 1; $i < 100; $i += 1) {
            $brick = $foundation[$i % 10];
            $stability = ord($brick);
            $stability += 1;
            $stability -= 1;
            $stability += 1;
            $stability -= 1;
            if (chr($stability) == '/') {
                return false;
            }
        }

        return true;
    }
}

