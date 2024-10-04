<?php

namespace o;

// TODO: Yeah, this is pretty much old-school mixing of HTML and PHP.
// Try to organize this better without depending on the template system in case there are circular errors.
class ErrorPageHtml {

    use ErrorPageHtmlAssets;
    use ErrorPageSourceLine;
    use ErrorPageStackTrace;
    use ErrorPageHelpLink;

    private $error = [];

    function print($error) {

        $this->error = $error;

        http_response_code(500);

        $this->printTemplate();
    }

    function printTemplate() {

        ?>
        <!doctype html><html>
        <body>

        <div style="<?php echo $this->panelOuterCss() ?>">

            <?php echo $this->innerCss() ?>

            <div class='tht-error-content'>

                <?php echo $this->getHeaderHtml() ?>
                <?php echo $this->getMessageHtml() ?>

                <div class="tht-inner-margin">

                    <?php echo $this->getFilePathHtml() ?>
                    <?php echo $this->getSourceLineHtml() ?>
                    <?php echo $this->getStackTraceHtml() ?>

                </div>
            </div>
        </div>


        <?php echo self::colorCodeJs() ?>
        <?php PrintPanel::flush(true) ?>

    </body>
    </html>

        <?php
    }

    function getHeaderHtml() {
        ?>
        <div class='tht-error-header'>
            <div class='tht-inner-margin'>
                ⚡ &nbsp;THT <?php echo $this->error['title'] ?>
            </div>
        </div>
        <?php
    }

    // TODO: Re-consider including this or not.
    // <span class="tht-error-header-sub">&lt;<?php echo $this->error['origin'] >&gt;</span>

    function getMessageHtml() {

        $msg = $this->error['message'];
        $msg = ErrorTextUtils::formatMessage($msg);

        $msg = Security::escapeHtml($msg);
        $msg = str_replace("\n", '<br>', $msg);

        // Convert backticks to code
        $msg = preg_replace("/`(.*?)`/", '<span class="tht-error-code">$1</span>', $msg);

        $msg .= $this->getHelpLinkHtml();

        ?>
        <div class='tht-error-message'>
            <div class='tht-inner-margin'>
                <?php echo $msg ?>
            </div>
        </div>

        <?php
    }

    function getFilePathHtml() {

        if (!isset($this->error['source']['file']) || !$this->error['source']['file']) { return ''; }

        $filePath = $this->formatFilePath($this->error['source']['fileClean']);

        ?>
        <div class="tht-error-filepath">
            <span class="tht-error-heading">📄</span>
            <span class="tht-src-filepath"><?php echo $filePath ?></span>
        </div>
        <?php
    }
}

trait ErrorPageSourceLine {

    private $MAX_SOURCE_LINE_LENGTH = 60;
    private $POINTER_CHAR = '↑';

    function getSourceLine() {

        $source = $this->error['source'];

        if ($source['lineSource']) {
            return $source['lineSource'];
        }

        if (!$source['lineNum']) {
            return '';
        }

        return $this->getSourceLines($source);
    }

    // TODO: This is probably redundant with something in the Compiler class.
    function readSourceFile($srcPath) {

        if (PathTypeString::create($srcPath)->u_is_relative()) {
            $srcPath = Tht::path('app', $srcPath);
        }

        $source = file_get_contents($srcPath);
        $lines = preg_split('/\n/', $source);

        return $lines;
    }

    function getSourceLines($source) {

        $srcPath     = $source['file'];
        $pos         = $source['linePos'];

        $srcLineNum1 = $source['lineNum'];
        $srcLineNum0 = $srcLineNum1 - 1; // zero-index

        $allLines = $this->readSourceFile($srcPath);

        if ($srcLineNum0 > count($allLines) - 1) {
            return '';
        }

        // Show 7 lines.  3 before and 3 after.
        $startLineNum0 = max(0, $srcLineNum0 - 3);
        $lines = array_slice($allLines, $startLineNum0, 7);

        // Calculate trimmed margin
        $marginSpaces = 999;
        foreach ($lines as $l) {
            if (!preg_match('#\S#', $l)) { continue; }
            preg_match('#^(\s*)#', $l, $margin);
            $marginSpaces = min($marginSpaces, strlen($margin[1]));
        }
        if ($marginSpaces == 999) { $marginSpaces = 0; }

        $maxLineNumLen = strlen($startLineNum0 + 6 + 1);
        $focusLineIndex = -1;
        $outLines = [];
        $hasPointer = ($pos !== null && $pos >= $marginSpaces);
        $pointerLineIndex = -1;
        $lineNumMargin = '   ';
        foreach ($lines as $i => $l) {
            $l = preg_replace('#^' . str_repeat(' ', $marginSpaces) . '#', '', $l);
            $l = Security::escapeHtml($l);
            $lineNum0 = $startLineNum0 + $i;
            $isFocusLine = $lineNum0 == $srcLineNum0;
            $lineNumLeft = ($lineNum0 + 1) . $lineNumMargin;
            $focusLineClass = $isFocusLine ? 'src-line-focus tht-color-code theme-dark' : '';
            if (strlen($lineNum0 + 1) != $maxLineNumLen) {
                $lineNumLeft = ' ' . $lineNumLeft;
            }
            $outLines []= "<div class='tht-error-src-line $focusLineClass'>" . $lineNumLeft . $l . '</div>';

            // Pointer (↑)
            if ($isFocusLine && $pos !== null && $pos >= $marginSpaces) {
                $pointerPos = $maxLineNumLen + strlen($lineNumMargin) + $pos - ($marginSpaces + 1);
                $pointerPos = max($pointerPos, 0);
                $pointerChar = '<span class="tht-error-line-pointer">' . $this->POINTER_CHAR . "</span>";
                $pointerLine = "<div class='tht-error-src-line'>" . str_repeat(' ', $pointerPos) . $pointerChar . "</div>";
                $outLines []= $pointerLine;
            }
        }


        return implode("", $outLines);
    }

    function getSourceLineHtml() {

        $out = $this->getSourceLine();

        if (!$out) { return ''; }

        // Add style to pointer.
        $colorPointer = "<span class='tht-error-line-pointer'>$this->POINTER_CHAR</span>";
        $out = preg_replace("/" . $this->POINTER_CHAR . "$/", $colorPointer , $out);

        $out = "<div class='tht-error-src'>" . $out . "</div>";

        return $out;
    }
}

trait ErrorPageStackTrace {

    function getStackTraceHtml() {

        if ($this->error['trace']) {
            return $this->formatTrace($this->error['trace']);
        }

        return '';
    }

    function formatTrace($frames) {

        if (!count($frames)) {
            return "";
        }

        $topBullet = '↧';
        $midBullet = '↓';
        $endBullet = '×';
        $out = '';

        foreach (array_reverse($frames) as $num => $phpFrame) {

            $filePath = $phpFrame['fileClean'] ?? '';

            $filePath = $this->formatFilePath($filePath);
            $funName = $this->formatFunction($phpFrame);
            $lineNum = $this->getLineNum($phpFrame);

            $args = $this->formatArgs($phpFrame);

            $bullet = $midBullet;
            if ($num == count($frames) - 1 || count($frames) == 1) {
                $bullet = $endBullet;
            }
            else if ($num == 0) {
                $bullet = $topBullet;
            }

            // Combine everything
            $sep = ' &nbsp;&bull;&nbsp; ';
            $frameOut = '';

            $frameOut .= '<div class="tht-error-trace-bullet">' . $bullet . '</div>';

            if ($funName) {
                $frameOut .= "<span class='tht-trace-bright' style=\"margin-right: 2px\">$funName</span>()" . $sep;
            }
            $frameOut .= '<span class="tht-error-trace-file">';
            if ($lineNum) { $frameOut .= 'line ' . $lineNum . $sep; }
            if ($filePath) { $frameOut .= $filePath; }
            $frameOut .= '</span>';

            if ($funName) {
                $frameOut .= $args;
            }

            $out .= '<div class="tht-error-trace-line">' . $frameOut . "</div>";
        }

        $firstLine = "<div class=\"tht-error-heading tht-error-trace-heading\">🔎 &nbsp;Trace</div>";
        $out =  $firstLine . $out;

        return '<div class="tht-error-trace">' . trim($out) . '</div>';
    }

    function getLineNum($phpFrame) {

        if (!isset($phpFrame['line'])) { return ''; }
        if (!isset($phpFrame['function'])) { $phpFrame['function'] = ''; }

        if (!$phpFrame['file'] || !$phpFrame['line']) {
            return '';
        }

        $src = Compiler::sourceLinePhpToTht($phpFrame['file'], $phpFrame['line'], $phpFrame['function']);

        return $src['lineNum'];
    }

    function formatFunction($phpFrame) {

        $class = isset($phpFrame['class']) ? $phpFrame['class'] : '';
        $fun = isset($phpFrame['function']) ? $phpFrame['function'] : '';

        // e.g. Bare.print -> print
        if (u_Bare::isa($fun)) {
            $class = '';
        }

        // Prepend classname.  e.g. MyClass.doSomething
        if ($class) {
            $fun = ErrorTextUtils::cleanVars($class) . '.' . $fun;
        }

        return $fun;
    }

    function formatFilePath($filePath) {

        // $filePath = ErrorTextUtils::cleanPath($filePath, true);
        $filePath = preg_replace('#(.*/)(.*)#', '<span class="tht-error-file-dir">$1</span>$2', $filePath);
        $filePath = preg_replace('#(.*)(\.\w{2,4})$#', '$1<span class="tht-error-file-ext">$2</span>', $filePath);

        return $filePath;
    }

    function formatArgs($phpFrame) {

        if (!isset($phpFrame['args']) || !count($phpFrame['args'])) {
            return '';
        }

        $numArgs = count($phpFrame['args']);

        // Convert argument data to strings
        $args = [];
        foreach ($phpFrame['args'] as $a) {
            $argLabel = is_null($a) ? 'null' : v($a);
            $argLabel = Tht::module('Json')->formatOneLineSummary($argLabel, 80);
            $args []= $argLabel;
        }

        // Return indented arguments
        $out = "<div class=\"tht-error-args-block\">";
        foreach ($args as $i => $a) {
            $a = str_replace('\"', '"', $a);  // strip extra escaping for json strings
            $num = $i + 1;
            $out .= "<div class='tht-error-args-line'>$num. " . v($a)->u_to_encoding('html') . "</div>";
            $out .= "\n";
        }
        $out .= "</div>";

        return rtrim($out);
    }



}

trait ErrorPageHelpLink {

    private $signature = '';

    function getHelpLinkHtml() {

        $this->initHelpLink();

        $url = $this->getHelpLinkUrl();

        if (!$url) { return ''; }

        $out = '<br><br><div class="tht-error-see"><span class="tht-error-label">See:</span>';
        $out .= "<a href=\"$url\">" . $this->error['helpLink']['label'] . "</a>";
        if ($this->signature) {
            $out .= '<span class="tht-error-signature">' . $this->signature . "</span>";
        }
        $out .= "</div>";

        return $out;
    }

    function initHelpLink() {
        if (!$this->error['helpLink']) {
            $this->initHelpLinkForStdMethod();
            $this->initHelpLinkForSpecialCases();
        }

        if ($this->error['helpLink']) {
            $this->signature = $this->getStdLibSignature(
                $this->error['helpLink']['label']
            );
        }
    }

    function getHelpLinkUrl() {

        $link = $this->error['helpLink'];

        if (!$link) {
            return '';
        }

        $url = $link['url'];

        // URLs are relative to the THT website.
        if ($url[0] == '/') {
             $url = Tht::getThtSiteUrl($url);
        }

        // Add referral param
        $referralParam = '?fromError=true&v=' . Tht::getThtVersion(true);
        if (strpos($url, '#') > -1) {
            $url = preg_replace('/(#.*)/', $referralParam . '$1', $url);
        } else {
            $url .= $referralParam;
        }

        return $url;
    }

    function setLink($url, $name) {
        $this->error['helpLink'] = [
            'url' => $url,
            'label' => $name,
        ];
    }

    function initHelpLinkForStdMethod() {

        // Match `Module.method()`
        preg_match('/`([A-Z]\w+)\.(\w+)\(\)`/', $this->error['message'], $m);

        $mod = '';
        $fun = '';

        if ($m) {
            // TODO: Is this necessary? Maybe just rely entirely on stack trace.
            $mod = $m[1];
            $fun = $m[2];
        }
        else if ($this->error['trace']) {
            // Find most recent stdlib call in stack trace
            foreach ($this->error['trace'] as $frame) {
                // Only look for user-facing functions
                if (isset($frame['function']) && hasu_($frame['function'])) {
                    $mod = ErrorTextUtils::cleanVars($frame['class']);
                    $fun = ErrorTextUtils::cleanVars($frame['function']);
                    break;
                }
            }
        }

        if (!$mod) { return; }

        // Checking class first because of modules like String that are also classes.
        // TODO: Disambiguate String class & module
        if (StdLibClasses::isa('O' . $mod)) {
            $urlDir = 'class';
            if (!method_exists('\o\O' . $mod, u_($fun))) {
                return;
            }
        }
        else if (StdLibModules::isa($mod)) {
            $urlDir = 'module';
            if (!method_exists('\o\\u_' . $mod, u_($fun))) {
                return;
            }
        }
        else {
            return;
        }

        $label = $mod . '.' . $fun;
        $urlStem = v($mod)->u_to_token_case('-') . '/' . v($fun)->u_to_token_case('-');

        if ($mod == 'Bare') { return; }

        $this->setLink("/manual/$urlDir/$urlStem", $label);
    }

    function initHelpLinkForSpecialCases() {

        if (preg_match('/SQLSTATE.*authentication method unknown to the client/i', $this->error['message'])) {
            $this->setLink('https://stackoverflow.com/questions/52364415/php-with-mysql-8-0-error-the-server-requested-authentication-method-unknown-to',
                'Stackoverflow Solution'
            );
        }
        else if (v($this->error['origin'])->u_contains('.formatChecker')) {

            $url = '/reference/format-checker';
            $label = 'Format Checker';
            if (preg_match('/formatChecker\.(\w+)$/', $this->error['origin'], $m)) {
                $token = v($m[1])->u_to_token_case(' ');
                $url .= '#' . v($token)->u_to_url_slug();
                $label .= ' - ' . v($token)->u_to_title_case();
            }

            $this->setLink($url, $label);
        }
    }

    function getStdLibSignature($fullCall) {

        // Split module and method
        // e.g. Math.abs = [Math, abs]
        $m = explode('.', $fullCall, 2);
        if (count($m) != 2) {
            return '';
        }

        $module = $m[0];
        $method = $m[1];

        // Method data from tht.dev/manual?main=allMethods&asData=true
        $rawJson = file_get_contents(Tht::systemPath('lib/core/data/stdLibMethods.json'));
        $package = Security::jsonDecode($rawJson);

        if (isset($package[$module])) {

            $p = $package[$module];

            if (isset($p[$method])) {
                $sig = $p[$method];

                // Just return the arguments.
                preg_match('/(\(.*?\))/', $sig, $m);

                return $m[1] == '()' ? '' : $m[1];
            }
        }

        return '';
    }
}

trait ErrorPageHtmlAssets {

    function colorCodeJs() {

        $jsPath = Tht::getCoreVendorPath('frontend/colorCode.js');
        $colorCodeJs = file_get_contents($jsPath);

        $colorCodeJs .= "colorCode('dark', '.tht-color-code');";

        return Tht::module('Output')->wrapJs($colorCodeJs);
    }

    function panelOuterCss() {

        $zIndex = 99998;  // one less than print layer

        $css = "
            position: fixed;
            overflow: auto;
            z-index: $zIndex;
            background-color: #153352;
            margin: 0;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        ";

        return trim(preg_replace('/\s*\n\s*/', ' ', $css));
    }

    function innerCss() {

        $monospace = Tht::module('Output')->font('monospace');
        $sansSerif = Tht::module('Output')->font('sansSerif');

        $css = <<<CSS

        <style scoped>

            .tht-error-content {
                color: #fff;
                -webkit-font-smoothing: antialiased;
                font: 20px $sansSerif;
                line-height: 1.3;
                z-index: 1;
                position: relative;
                margin: 0;
                width: 100%;
            }

            a {
                color: #ffd267;
                text-decoration: none;
            }

            a:hover {
                text-decoration: underline;
            }

            .tht-inner-margin {
                margin: 0 auto;
                max-width: 700px;
            }

            .tht-error-header {
                font-size: 24px;
                font-weight: 600;
                padding: 20px 0;
            }

            .tht-error-header-sub {
                font-size: 50%;
                margin-left: 16px;
                font-weight: normal;
                color: #adbecf;
            }

            .tht-error-message {
                background-color: rgb(7, 26, 46);
                margin-bottom: 40px;
                padding: 0px 0;
            }

            .tht-error-message .tht-inner-margin {
                padding: 40px;
            }

            .tht-error-hint {
                margin-top: 64px;
                line-height: 2;
                color: #adbecf;
                font-size: 80%;
            }

            .tht-error-src-line {
                color: rgb(85, 113, 141);
                margin-bottom: 4px;
            }

            .src-line-focus {
                color: #fff;
            }

            .tht-error-src {
                font-size: 14px;
                border-radius: 4px;
                margin-bottom: 32px;
                padding: 24px 24px 24px;
                background-color: rgb(7, 26, 46);
                white-space: pre;
                font-family: $monospace;
                overflow: auto;
            }

            .has-cc.tht-color-code.theme-dark {
                background: transparent;
            }

            .tht-src-small {
                font-size: 14;
            }

            .tht-error-trace-line {
                border-radius: 4px;
                border-bottom: solid 1px #0f2e4e;
                padding: 12px 0px 12px 20px;
                background-color: rgb(7, 26, 46);
                color: #9dafc2;
                position: relative;
            }

            .tht-error-heading {
                font-size: 20px;
            }

            .tht-src-filepath {
                margin-left: 0.25em;
            }

            .tht-error-trace-heading {
                margin-bottom: 12px;
            }

            .tht-error-trace {
                font-size: 80%;
                border-radius: 4px;
                margin-bottom: 32px;
                margin-top: 32px;
                padding: 24px 24px;
                padding: 0;
                line-height: 180%;
                font-family: $sansSerif;
            }

            .tht-error-trace-file {
                font-size: 90%;
            }

            .tht-trace-bright {
                color: #fff;
            }

            .tht-error-object-detail {
                white-space: pre;
            }

            .tht-error-trace-bullet {
                font-family: arial, sans-serif;
                width: 0.8em;
                margin-right: 20px;
                text-align: center;
                float: left;
                font-size: 110%;
            }

            .tht-error-trace-line:last-child .tht-error-trace-bullet {
                scale: 1.2;
            }

            .tht-error-line-pointer {
                color: #eac222;
                font-size: 110%;
                font-family: arial, sans-serif;
                top: -4px;
                position: relative;
                display: inline-block;
                scale: 1.3 1;
                text-shadow: 0 0 1px #eac222;
            }

            .tht-error-filepath {
                margin-bottom: 20px;
            }

            .tht-error-file-dir {
                color: #879aad;
                margin: 0;
                margin-right: 0.15em;
            }

            .tht-error-file-ext {
                color: #879aad;
                margin-left: 0.15em;
            }

            .tht-error-trace-file .tht-error-file-dir, .tht-error-trace-file .tht-error-file-ext  {
                color: #506c87;
            }

            .tht-error-file span {
                font-size: 105%;
                color: inherit;
            }

            .tht-error-code {
                display: inline-block;
                margin: 4px 0;
                border-radius: 4px;
                font-size: 90%;
                font-family: $monospace;
                background-color: rgb(32, 66, 101);
                padding: 2px 8px;
            }

            .tht-error-signature {
                font-size: 80%;
                margin-left: 8px;
                color: #789ec5;
            }

            .tht-error-args {
                color: #adbecf;
                font-size: 80%;
            }

            .tht-error-args-line {
                color: #adbecf;
                font-size: 80%;
            }

            .tht-error-args-block {
                line-height: 120%;
                padding-left: 3.1em;
            }

            .tht-error-label {
                margin-right: 10px;
            }

            .tht-error-src-line.has-cc.theme-dark .cc-value:first-child {
                color: #fff;
            }

        </style>
CSS;

        return Tht::module('Output')->minifyCss($css);
    }
}


