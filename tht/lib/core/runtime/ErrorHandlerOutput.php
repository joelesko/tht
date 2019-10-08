<?php

namespace o;

class ErrorHandlerOutput {

    function doDisplayWebErrors () {
        if (Security::isAdmin()) {
            return true;
        }
        return Compiler::getAppCompileTime() > time() - Tht::getConfig('showErrorPageForMins') * 60;
    }

    static function printError ($error, $logOut='') {

        if (Compiler::isSandboxMode()) {
            throw new \Exception ('[Sandbox] ' . $error['message']);
        }

        $eh = new ErrorHandlerOutput();

        $prepError = $eh->prepError($error);
        $plainOut = $eh->formatError($prepError);

        if (Tht::isMode('cli')) {
            $eh->printToConsole($plainOut);
        } else {
            if ($eh->doDisplayWebErrors() || $error['origin'] == 'tht.settings') {
                $eh->printToWeb($prepError);
            } else {
                if (Tht::getConfig('logErrors')) {
                    if (!$logOut) { $logOut = $plainOut; }
                    $eh->printToLog($logOut);
                }
                // file_put_contents('php://stderr', $plainOut . "\n\n");
                Tht::module('Response')->u_send_error(500);
            }
        }

        ErrorHandler::saveTelemetry($prepError);

        Tht::exitScript(1);
    }

    // TODO: refactor/cleanup
    function prepError($error) {

        // Convert to called line/pos
        if (preg_match('/too few arguments to function/i', $error['message'])) {
            $error['origin'] .= '.arguments.less';
            $hasCallerInfo = preg_match('/(\d+) passed in (.*?) on line (\d+)/', $error['message'], $m);
            if ($hasCallerInfo) {
                $error['phpFile'] = $m[2];
                $error['phpLine'] = $m[3];
            }
        }
        else if (preg_match('/Uncaught TypeError:.*must be of the type/i', $error['message'])) {
            $error['origin'] .= '.arguments.type';
            $hasCallerInfo = preg_match('/called in (.*?) on line (\d+)/i', $error['message'], $m);
            if ($hasCallerInfo) {
                $error['phpFile'] = $m[1];
                $error['phpLine'] = $m[2];
            }
        }

        $error['message'] = $this->cleanMessage($error['message']);

        if (!isset($error['src']) && $error['phpFile']) {
            $error['src'] = self::phpToSrc($error['phpFile'], $error['phpLine']);
        }

        $error['srcLine'] = '';
        if (isset($error['src'])) {
            if (isset($error['src']['srcLine'])) {
                $error['srcLine'] = $error['src']['srcLine'];
            } else {
                $error['srcLine'] = $this->getSourceLine($error['src']['file'], $error['src']['line'], $error['src']['pos']);
                if ($error['src']['file']) {
                    $error['src']['filePath'] = $error['src']['file'];
                    $error['src']['file'] = $this->cleanPath($error['src']['file']);
                }
            }
        }

        if ($error['trace']) {
            $forcePhp = isset($error['_rawTrace']) ? $error['_rawTrace'] : false;
            $error['trace'] = $this->cleanTrace($error['entry'], $error['trace'], $forcePhp);
        }

        $error['title'] = 'THT ' . ucfirst($error['category']) . ' Error';

        if ($error['subOrigin'] == 'formatChecker') {
            $error['errorDoc'] = [ 'link' => '/reference/format-checker', 'name' => 'Format Checker'];
        }

        // Solution Tips

        if (preg_match('/SQLSTATE.*authentication method unknown to the client/i', $error['message'])) {
            $error['errorDoc'] = [
                'link' => 'https://stackoverflow.com/questions/52364415/php-with-mysql-8-0-error-the-server-requested-authentication-method-unknown-to',
                'name' => 'Stackoverflow Solution'
            ];
        }

        return $error;
    }

    function formatError ($error) {

        $out = "######### " . $error['title'] . " #########\n\n";
        $out .= $error['message'];
        if (isset($error['srcLine'])) {
            $out .= "\n\n" . $error['srcLine'];
        }

        $src = isset($error['src']) ? $error['src'] : null;
        if ($error['trace']) {
            $out .= "\n" . $error['trace'];
        } else if ($src['file']) {
            $out .= "\n\nFile: " . $src['file'] . "  Line: " . $src['line'];
            if (isset($src['pos'])) {
                $out .= "  Pos: " . $src['pos'];
            }
            $out .= "\n\n";
        }

        return $out;
    }

    function printToLog ($msg) {

        $msg = 'URL: ' . THT::module('Request')->u_url()->u_stringify() . "\n\n" . $msg;

        Tht::errorLog($msg);
    }

    function printToWeb ($error) {

        // Format heading
        $heading = $error['title'];

        $error['message'] = Security::escapeHtml($error['message']);
        $error['srcLine'] = Security::escapeHtml($error['srcLine']);

        $error['isLongSrc'] = strlen(rtrim($error['srcLine'], "^ \n")) > 50;

        // convert backticks to code
        $error['message'] = preg_replace("/`(.*?)`/", '<span class="tht-error-code">$1</span>', $error['message']);

        // Put hints on a separate line
        $error['message'] = preg_replace("/(Try|See|Got):(.*?)/", '<br /><br />$1: $2', $error['message']);

        if (isset($error['errorDoc'])) {
            $referralParam = '?fromError=1';
            $url = ($error['errorDoc']['link'][0] == '/' ? 'https://tht-lang.org' : '') . $error['errorDoc']['link'];
            if (strpos($url, '#') > -1) {
                $url = preg_replace('/(#.*)/', $referralParam . '$1', $url);
            } else {
                $url .= $referralParam;
            }
            $error['message'] .= "<br /><br />Manual: <a href=\"$url\">" . $error['errorDoc']['name'] . "</a>";
        }

        // format caret, wrap for color coding
        if ($error['srcLine']) {
            $error['srcLine'] = preg_replace("/\^$/", '</span><span class="tht-caret">&uarr;</span>', $error['srcLine']);
            $error['srcLine'] = '<span class="tht-color-code theme-dark">' . $error['srcLine'] . '</span>';
        }

        $this->printWebTemplate($heading, $error);

        $plugin = Tht::module('Js')->u_plugin('colorCode', 'dark');
        $colorJs = Tht::module('Js')->wrap($plugin->u_stringify());
        print($colorJs);
    }

    // TODO: refactor/cleanup
    function printWebTemplate($heading, $error) {

        $zIndex = 99998;  // one less than print layer
        $cssMod = Tht::module('Css');

        $fmtFile = '';
        if (isset($error['src'])) {
            $fmtFile = preg_replace('#(.*/)(.*)#', '<span class="tht-error-dir">$1</span>$2', $error['src']['file']);
        }

        ?>

        <div style='position: fixed; overflow: auto; z-index: <?= $zIndex ?>; background-color: #333; color: #eee; margin: 0; top: 0; left: 0; right: 0; bottom: 0; color: #fff; padding: 32px 64px; -webkit-font-smoothing: antialiased;'>
            <style scoped>
                a { color: #ffd267; text-decoration: none; }
                a:hover { text-decoration: underline;  }
                .tht-error-header { font-weight: bold; margin-bottom: 32px; font-size: 140%; border-bottom: solid 4px #ecc25f; padding-bottom: 12px;  }
                .tht-error-header-sub { font-size: 50%; margin-left: 32px; font-weight: normal; opacity: 0.5; }
                .tht-error-message { margin-bottom: 32px;  }
                .tht-error-content { font: 22px <?= $cssMod->u_font('sansSerif') ?>; line-height: 1.3; z-index: 1; position: relative; margin: 0 auto; max-width: 700px; }
                .tht-error-hint {   margin-top: 64px; line-height: 2; opacity: 0.5; font-size: 80%; }
                .tht-error-srcline { font-size: 90%; border-radius: 4px; margin-bottom: 32px; padding: 24px 24px 24px; background-color: #282828; white-space: pre; font-family: <?= $cssMod->u_font('monospace') ?>; overflow: auto; }
                .tht-src-small { font-size: 65%; }
                .tht-error-trace { font-size: 70%; border-radius: 4px; margin-bottom: 32px; margin-top: -28px; padding: 24px 24px; background-color: #282828; white-space: pre; line-height: 150%; font-family: <?= $cssMod->u_font('monospace') ?>; }
                .tht-caret { color: #eac222; font-size: 30px; position: relative; left: -3px; top: 2px; line-height: 0; }
                .tht-src-small .tht-caret { font-size: 24px; }
                .tht-error-file { margin-bottom: 32px; border-top: solid 1px rgba(255,255,255,0.1); padding-top: 32px; }
                .tht-error-file .tht-error-dir { opacity: 0.5; margin: 0;  }
                .tht-error-file span { margin-right: 32px; margin-left: 4px; font-size: 105%; color: inherit; }
                .tht-error-code {  display: inline-block; margin: 4px 0; border-radius: 4px; font-size: 90%; font-weight: bold; font-family: <?= $cssMod->u_font('monospace') ?>; background-color: rgba(255,255,255,0.1); padding: 2px 8px; }
                .tht-error-args { color: #aaa;  }
                .tht-error-args-line { color: #aaa; font-size: 90%;  }
            </style>

            <div class='tht-error-content'>

                <div class='tht-error-header'><?= $heading ?><span class="tht-error-header-sub"><?= $error['origin'] ?></span></div>
                <div class='tht-error-message'><?= $error['message'] ?></div>

                <?php if (isset($error['src'])) { ?>
                <div class='tht-error-file'>
                    File: <span><?= $fmtFile ?></span>
                </div>
                <?php } ?>

                <?php if ($error['srcLine']) { ?>
                <div class='tht-error-srcline <?= $error['isLongSrc'] ? 'tht-src-small' : '' ?>'><?= $error['srcLine'] ?></div>
                <?php } ?>

                <?php if ($error['trace']) { ?>
                <div class='tht-error-trace'><?= $error['trace'] ?></div>
                <?php } ?>

                <?php self::printPrintBuffer() ?>

            </div>
        </div>

        <?php
    }

    function printToConsole ($out) {
        $out = "\n\n" . str_repeat('`', 80) . "\n\n" . $out;
        print $out;
    }

    function printPrintBuffer() {
        if (PrintBuffer::hasItems()) {
            PrintBuffer::flush(true);
            print "<style> .tht-print-panel { color: inherit; width: auto; box-shadow: none; background-color: #282828; position: relative; } </style>";
        }
    }

    // TODO: WIP
    function printObjectDetails($error) {
        if (!$error['objectDetails']) {
            return;
        }

        $items = $error['objectDetails']['details'];
        if (count($items)) {
            sort($items);
            $maxItems = 20;
            if (count($items) > $maxItems) {
                $numRest = count($items) - $maxItems;
                $items = array_slice($items, 0, $maxItems);
                $items []= "($numRest more)";
            }
            $items = implode("\n", $items);
        }
        else {
            $items = '-none-';
        }

        ?>
            <div class='tht-error-srcline tht-src-small'><?= $error['objectDetails']['title'] ?>:<?= "\n\n" . $items ?></div>
        <?php
    }



    /////////  UTILS


    static function phpToSrc ($phpFile, $phpLine) {

        $phpCode = file_get_contents($phpFile);
        $phpLines = explode("\n", $phpCode);
        $phpLines = array_reverse($phpLines);
        foreach ($phpLines as $l) {
            if (substr($l, 0, 2) === '/*') {
                $match = [];
                $found = preg_match('/SOURCE=(\{.*})/', $l, $match);
                if ($found) {
                    $json = $match[1];
                    $map = json_decode($json, true);
                    if (isset($map[$phpLine])) {
                        $src = [ 'file' => $map['file'], 'line' => $map[$phpLine], 'pos' => null ];
                        return $src;
                    }
                    break;
                }
            }
        }
        return [
            'line' => $phpLine,
            'file' => $phpFile,
            'pos'  => null
        ];
    }

    function getSourceLines($srcPath, $srcLineNum1) {

        if (Tht::module('File')->u_is_relative_path($srcPath)) {
            $srcPath = Tht::path('app', $srcPath);
        }

        $source = file_get_contents($srcPath);
        $lines = preg_split('/\n/', $source);

        return $lines;
    }

    function getSourceLine ($srcPath, $srcLineNum1, $pos=null) {

        $srcLineNum0 = $srcLineNum1 - 1;  // convert to zero-index

        $lines = $this->getSourceLines($srcPath, $srcLineNum1);
        $line = (count($lines) > $srcLineNum0) ? $lines[$srcLineNum0] : '';

        // have to convert to spaces for pointer to line up
        $line = preg_replace('/\t/', '    ', $line);

        // trim indent
        preg_match('/^(\s*)/', $line, $matches);
        $numSpaces = strlen($matches[1]);
        $line = preg_replace('/^(\s*)/', '', $line);
        if (!trim($line)) { return ''; }
        $prefix = '' . $srcLineNum1 . ':  ';

        // make sure pointer is visible in long lines
        if (strlen($line) > 50 && $pos > 50) {
            $trimNum = abs(50 - strlen($line));
            $line = substr($line, $trimNum);
            $pos -= $trimNum;
            $prefix .= '... ';
        }

        $fmtLine = $prefix . $line;

        // pos marker
        $marker = "\n";
        if ($pos !== null && $pos >= $numSpaces && preg_match('/\S/', $line)) {
            $pointerPos = max($pos - ($numSpaces + 1) + strlen($prefix), 0);
            $fmtLine .= "\n";
            $marker = str_repeat(' ', $pointerPos) . '^';
        }

        return $fmtLine . $marker;
    }

    // TODO: refactor/cleanup
    function cleanMessage ($raw) {

        $clean = $raw;

        $clean = $this->cleanVars($clean);

        $clean = str_replace('supplied for', 'in', $clean);

        // Suppress leaked stack trace
        $clean = preg_replace('/stack trace:.*/is', '', $clean);

        $clean = preg_replace('/Uncaught ArgumentCountError.*few arguments to function (\S+)\(\), (\d+).*/',
            'Not enough arguments passed to `$1()`.', $clean);

        if (preg_match("/function '(.*?)' not found or invalid function name/i", $clean, $m)) {
            $clean = "PHP function does not exist: `" . $m[1] . "`";
        }

        // TODO: link to timezone list. Make this a Config Error with source line.
        if (preg_match("/Timezone ID '(.*?)' is invalid/i", $clean, $m)) {
            $clean = "Timezone in `settings/app.jcon` is invalid: `" . $m[1] . "`";
        }

        if (preg_match('/Syntax error, unexpected \'return\'/i', $clean)) {
            $clean = 'Invalid statement at end of function.  Missing `return`?';
        }

        $clean = preg_replace('/Use of undefined constant (\w+).*/', 'Unknown token: `$1`', $clean);
        $clean = preg_replace('/unexpected \'use\'/', 'unexpected `keep`', $clean);
        $clean = preg_replace('/expecting \'(.*?)\'/', 'expecting `$1`', $clean);
        $clean = preg_replace('/Call to undefined function (.*)\(\)/', 'Unknown function: `$1`', $clean);
        $clean = preg_replace('/Call to undefined method (.*)\(\)/', 'Unknown method: `$1`', $clean);
        $clean = preg_replace('/Missing argument (\d+) for (.*)\(\)/', 'Missing argument $1 for `$2()`', $clean);
        $clean = preg_replace('/\{closure\}/i', '{function}', $clean);
        $clean = preg_replace('/callable/i', 'function', $clean);
        $clean = preg_replace('/, called.*/', '', $clean);
        $clean = preg_replace('/preg_\w+\(\)/', 'Regex Pattern', $clean);
        $clean = preg_replace('/\(T_.*?\)/', '', $clean);

        // Convert internal name conventions
        $clean = preg_replace('/<<<.*?\/(.*?)>>>/', '$1', $clean);
        $clean = preg_replace('/O(list|map|regex|string)/', '$1', $clean);

        if (preg_match('/TypeError/', $clean)) {
            $clean = preg_replace('/Uncaught TypeError:\s*/i', '', $clean);
            $clean = preg_replace('/passed to (\S+)/i', 'passed to `$1`', $clean);
            $clean = preg_replace('/of the type (.*?),/i', 'of type `$1`.', $clean);
            $clean = preg_replace('/`float`/i', '`number`', $clean);
            $clean = preg_replace('/\.\s*?(\S*?) given/i', '. Got: `$1`', $clean);
        }
        $clean = preg_replace('/Uncaught error:\s*/i', '', $clean);
        $clean = preg_replace('/in .*?.php:\d+/i', '', $clean);
        $clean = preg_replace('/[a-z_]+\\\\/i', '', $clean);  // namespaces

        // Strip root directory from paths
        $clean = str_replace(Tht::path('files') . '/', '', $clean);
        $clean = str_replace(Tht::path('app') . '/', '', $clean);

        $clean = ucfirst($clean);

        return $clean;
    }

    function cleanVars ($raw) {

        $fnCamel = function ($m) {
            $isUpper = false;
            if (preg_match('/^[A-Z]/', $m[1][0])) {
                $isUpper = true;
            }
            return v($m[1])->u_to_camel_case($isUpper);
        };

        $clean = $raw;
        $clean = preg_replace('/o\\\\/', '', $clean);                       // o namespace
        $clean = preg_replace('/tht.*?\\\\/', '', $clean);                  // tht namespage
        $clean = preg_replace_callback('/u_([a-zA-Z_]+)/', $fnCamel, $clean);  // to camelCase
        $clean = preg_replace('/(?<=\w)::/', '.', $clean);                  // :: to dot .
        $clean = preg_replace('/->/', '.', $clean);                         // -> to dot .
        $clean = preg_replace('/\bO(?=[A-Z][a-z])/', '', $clean);           // internal classes e.g. "OString"
        $clean = preg_replace('/\bu_/', '', $clean);                        // u_ prefix
        $clean = preg_replace('#[a-zA-Z0-9_\\\\]*\\\\#', '', $clean);       // namespace

        return $clean;
    }

    function cleanPath ($path) {
        $path = Tht::stripAppRoot($path);
        return $path;
    }

    // TODO: refactor/cleanup
    function cleanTrace ($entryFun, $trace, $showPhp=false) {

        $out = '';

        $filterTrace = [];
        foreach ($trace as $phpFrame) {
            if (! isset($phpFrame['file'])) { continue; }

            $phpFrame['class'] = isset($phpFrame['class']) ? $phpFrame['class'] : '';
            $phpFrame['function'] = $fun = $this->cleanVars($phpFrame['function']);

            // Internal PHP frame
            if ($phpFrame['class'] === 'o\\OTemplate'
                || $phpFrame['class'] === 'o\\Tht'
                || strpos($phpFrame['file'], '.tht') === false
                || substr($fun, 0, 2) === '__'
                || $fun == 'handlePhpRuntimeError') {

                $phpFrame['args'] = [];
                if (!Tht::getConfig('_coreDevMode') && !$showPhp) {
                    continue;
                }
            }

            $filterTrace []= $phpFrame;
        }

        if (!count($filterTrace)) {
            return "";
        }

        if ($entryFun) {
            $entryLine = '|  ' . $entryFun['file'] . ' · ' . $entryFun['fun'] . "()\n";
            $out = $entryLine . $out;
        }

        $frameNum = 0;
        foreach (array_reverse($filterTrace) as $phpFrame) {

            $file = $this->cleanPath(Tht::getThtPathForPhp($phpFrame['file']));
            $file = preg_replace('/\.tht$/', '', $file);

            $frameNum += 1;

            $cl = $phpFrame['class'];
            $fun = $phpFrame['function'];

            if (u_Bare::isa($fun)) {
                $cl = '';
            }
            else if ($phpFrame['class']) {
                $fun = $this->cleanVars($phpFrame['class']) . '.' . $fun;
            }

            $src = self::phpToSrc($phpFrame['file'], $phpFrame['line']);

            $lineMsg = $src['line'] ? $src['line'] : '--';

            $numArgs = count($phpFrame['args']);

            $args = [];
            foreach ($phpFrame['args'] as $a) {
                $argsJson = Tht::module('Json')->formatOneLineSummary(v($a), 60);
                $args []= $argsJson;
            }

            $argsLabel = '';
            $sepArgs = false;
            if ($numArgs == 1 && strlen($args[0]) <= 10) {
                $argsLabel = $args[0];
            } else if ($numArgs > 0) {
                $sepArgs = true;
            }
            $argsLink = count($phpFrame['args']) ? "<span class='tht-error-args'>" . v($argsLabel)->u_encode_html() . "</span>" : '';

            $fun = !$fun ? '' : "· $fun($argsLink";
            if (!$sepArgs) { $fun.= ')'; }
            $out .= "|  $file · $lineMsg $fun\n";

            if ($sepArgs) {
                foreach ($args as $i => $a) {
                    if ($i > 4) {
                        $more = $numArgs - $i;
                        $out .= "|      <span class='tht-error-args-line'>($more more)</span>\n";
                        break;
                    }
                    $out .= "|      <span class='tht-error-args-line'>" . v($a)->u_encode_html() . "</span>\n";
                }
                $out .= "|  )\n";
            }


        }

        $out = "+  start\n" . $out . 'V  error';

        return trim("- TRACE -\n\n" . $out);
    }
}

