<?php

namespace Litemark;

class LitemarkParser {

    private $html = [];
    private $blockMode = '';
    private $blockLines = [];
    private $tableRows = [];
    private $paraLines = [];
    private $blockContent = '';
    private $toc = [];
    private $numParas = 0;

    public $flags = [];
    public $features = [];
    private $squareTags = [];

    private $EMPTY_CELL = '-';

    var $squareTagDefs = [

        // links
        'link1'   => '<a href="{1}"{linkrel}>{1}</a>',
        'link2'   => '<a href="{1}"{linkrel}>{2}</a>',
        'link3'   => '<a href="{1}" title="{2}"{linkrel}>{3}</a>',

        // inline
        'dfn1'    => '<dfn>{1}</dfn>',
        'del1'    => '<del>{1}</del>',
        'kbd1'    => '<kbd>{1}</kbd>',
        'abbr2'   => '<abbr title="{2}">{1}</abbr>',
        'sup1'    => '<sup>{1}</sup>',
        'sub1'    => '<sub>{1}</sub>',
        'small1'  => '<small>{1}</small>',
        'hr0'     => '<hr />',
        'br0'     => '<br />',
        'nobr1'   => '<nobr>{1}</nobr>',
        'pbr0'    => '<p>&nbsp;</p>',
        'sp0'     => '&nbsp;',

        // images
        'image1'  => '<img loading="lazy" src="{1}" />',
        'image2'  => '<img loading="lazy" src="{1}" alt="{2}" />',

        // tables
        'table0'    => '<table class="table">',
        'table1'    => '<table class="table {1}">',

        // toc
        'toc0'     => '<<<<<TOC>>>>>',

        // callouts
        'info1'    => '<div class="alert" role="alert">{1}</div>',
        'info2'    => '<div class="alert" role="alert"><strong>{1}</strong>{2}</div>',
        'success1' => '<div class="alert alert-success" role="alert">{1}</div>',
        'success2' => '<div class="alert alert-success" role="alert"><strong>{1}</strong>{2}</div>',
        'warning1' => '<div class="alert alert-error" role="alert">{1}</div>',
        'warning2' => '<div class="alert alert-error" role="alert"><strong>{1}</strong>{2}</div>',
    ];

    var $featureTags = [
        'links'    => 'link1|link2|link3',
        'images'   => 'image1|image2',
        'tables'   => 'table0|table1',
        'inline'   => 'dfn1|del1|kbd1|abbr2|sup1|sub1|small1|hr0|br0|nobr1|pbr0|sp0',
        'callouts' => 'info1|info2|success1|success2|warning1|warning2',
        'toc'      => 'toc0',
    ];

    var $featureSets = [
        ':comment'    => 'text',
        ':forum'      => 'text|lists|headings|blocks|images|links',
        ':blog'       => 'text|lists|headings|blocks|images|tables|links|inline|callouts|icons|toc',
        ':wiki'       => 'text|lists|headings|blocks|images|tables|links|inline|callouts|icons|toc',
        ':xDangerAll' => 'text|lists|headings|blocks|images|tables|links|inline|callouts|icons|toc|indexLinks|xDangerHtml',
    ];

    function __construct($flags=[]) {

        $this->flags = $flags;

        $this->validateFlag('features', '');
        $this->validateFlag('customTags', []);
        $this->validateFlag('errorHandler', null);
        $this->validateFlag('urlHandler', null);

        $this->initFeatures($this->flags['features']);
        $this->initSquareTags();
    }

    // TODO: validate types
    function validateFlag($name, $default) {

        if (!isset($this->flags[$name])) {
            $this->flags[$name] = $default;
        }
    }

    function error($msg) {

        if ($this->flags['errorHandler']) {
            call_user_func_array($this->flags['errorHandler'], [$msg]);
        }
        else {
            $msg = 'Litemark: ' . $msg;
            throw new \Exception ($msg);
        }
    }

    function initFeatures($sFeatures) {

        $features = [];

        $fs = explode('|', $sFeatures);

        foreach ($fs as $f) {

            $f = trim($f);
            if ($f == '') { continue; }

            // expand featureSet
            if ($f[0] == ':') {
                if (!isset($this->featureSets[$f])) {
                    $this->error("Unknown feature set: `$f`");
                }
                $fsFeatures = explode('|', $this->featureSets[$f]);
                foreach ($fsFeatures as $fsf) {
                    $features[$fsf] = true;
                }
            }
            else {
                // standalone feature
                $features[$f] = true;
            }
        }

        $allFeatures = explode('|', $this->featureSets[':xDangerAll']);

        // validate
        foreach ($features as $f => $x) {
            if (!in_array($f, $allFeatures)) {
                $this->error("Unknown feature: `$f`");
            }
        }

        // fill in false values
        foreach ($allFeatures as $f) {
            if (!isset($features[$f])) {
                $features[$f] = false;
            }
        }

        $this->features = $features;
    }

    function initSquareTags() {

        $this->squareTags = [];

        // add tags for each feature, e.g. images = image1|image2
        foreach ($this->features as $f => $hasFeature) {

            if (!$hasFeature || !isset($this->featureTags[$f])) { continue; }

            $tags = $this->featureTags[$f];
            $tags = explode('|', $tags);

            foreach ($tags as $t) {
                $this->squareTags[$t] = $this->squareTagDefs[$t];
            }
        }

        // Add custom squareTags
        foreach ($this->flags['customTags'] as $k => $v) {

            if (!preg_match('/^[a-z]+[0-9]$/', $k)) {
                $this->error("Custom tag name `$k` must be all lowercase and end with number of expected params. Ex: `video2`");
            }

            $this->squareTags[$k] = $v;
        }
    }

    function parseFile($file) {

        if (!file_exists($file)) {
            $this->error('File does not exist: ' . $file);
        }
        $raw = file_get_contents($file);

        return $this->parse($text);
    }

    function parse ($raw, $flags=[]) {

        $lines = explode("\n", $raw);

        foreach ($lines as $line) {
            $this->parseLine($line);
        }

        // Force blocks, etc. to close.
        $this->onBlankLine();

        // Don't wrap in a single <p> tag
        // if ($this->numParas == 1 && $this->html[0] === '<p>') {
        //     array_pop($this->html);
        //     array_shift($this->html);
        // }

        $out = implode("\n", $this->html);

        // Add TOC
        $tocOut = $this->tocHtml();
        if ($tocOut) {
            $out = str_replace('<<<<<TOC>>>>>', $tocOut, $out);
        }

        return $out;
    }

    function tocHtml() {

        if (!count($this->toc)) { return ''; }
        if (!$this->features['toc']) { return ''; }

        $tocHtml = ['<ul>'];

        foreach ($this->toc as $h) {

            // This is a bit ugly, but prevents double-escaping
            $h = preg_replace('/<.*?>/', '', $h);
            $h = preg_replace('/&lt;/', '<', $h);
            $h = preg_replace('/&gt;/', '>', $h);

            $tocHtml []= '<li><a href="#' . $h['token'] . '">' . escapeHtml($h['plain']) . '</a></li>';
        }

        $tocHtml []= '</ul>';

        $out = implode("\n", $tocHtml);
        $out .= "\n\n<style> .toc-anchor { position: relative; top: -2rem; display: inline-block; width: 0; height: 0; overflow: hidden } </style>";

        return $out;
    }

    function add($h) {

        $this->clearPara();
        $this->html []= $h;

        return true;
    }

    function clearPara() {

        if (count($this->paraLines)) {

            $this->html = array_merge($this->html, ['<p>'], $this->paraLines, ['</p>']);
            $this->numParas += 1;

            $this->paraLines = [];
        }
    }

    function addParaLine ($l) {

        $l = trim($l);
        if ($l) {
            $this->paraLines []= $l;
        }
    }

    function newBlockMode($mode) {

        $this->clearPara();
        $this->blockMode = $mode;
    }

    function isNumeric($raw) {
        return $raw == $this->EMPTY_CELL || preg_match('/^[+\-$€£¥]?[0-9][0-9\.,]+[%]?$/', $raw);
    }

    function addTable() {

        // Check which cols should be right-aligned
        $isNumericCol = [];
        foreach ($this->tableRows as $rowNum => $row) {
            // Header
            if ($rowNum == 0) {
                continue;
            }

            foreach ($row as $cellNum => $cell) {
                if (!isset($isNumericCol[$cellNum])) {
                    $isNumericCol[$cellNum] = true;
                }
                if ($cell != '' && !$this->isNumeric($cell)) {
                    $isNumericCol[$cellNum] = false;
                }
            }
        }

        // Generate markup
        foreach ($this->tableRows as $rowNum => $row) {
            $tag = $rowNum == 0 ? 'th' : 'td';
            $this->add('<tr>');
            foreach ($row as $colNum => $cell) {
                if ($cell == $this->EMPTY_CELL) { $cell = '&nbsp;'; }
                $style = isset($isNumericCol[$colNum]) && $isNumericCol[$colNum] ? ' style="text-align: right"' : '';
                $this->add("<$tag$style>" . $cell . "</$tag>");
            }
            $this->add('</tr>');
        }

        $this->add('</table>');

        $this->tableRows = [];
    }

    function command ($raw) {

        $parts = preg_split('#\s+#', trim($raw), 2);
        $cmd = trim($parts[0]);

        // allow shorthand for links
        if (preg_match('#^(http|\/)#', $cmd)) {
            $parts[1] = $raw;
            $cmd = 'link';
        }

        $right = count($parts) > 1 ? $parts[1] : '';
        $right = trim($right, '|');
        $args = explode('|', $right);

        // look up command by arity
        $key = $cmd . ($right ? count($args) : 0);

        if (!isset($this->squareTags[$key])) {
            if (isset($this->squareTags[strtolower($key)])) {
                return '[' . $key . ' - tag name must be lower case]';
            }
            return '[' . $raw . ']';
        }

        $template = $this->squareTags[$key];

        // trim args
        foreach (range(0, count($args) - 1) as $i) {
            $args[$i] = trim($args[$i]);
        }

        $out = '';
        if (is_callable($template)) {

            // call custom callback
            try {
                $out = call_user_func_array($template, $args);
            } catch (\Exception $e) {
                return '[ERROR: ' . $raw . ']';
            }
        }
        else {
            // fill args in template
            $out = $template;

            foreach (range(0, count($args) - 1) as $i) {
                $val = $this->parseInline($args[$i], false);
                // One-indexed
                $out = str_replace('{' . ($i + 1) . '}', $val, $out);
            }
        }

        // add nofollow param
        if ($cmd == 'link') {
            $linkRel = $this->features['indexLinks'] ? '' : ' rel="nofollow"';
            $out = str_replace('{linkrel}', $linkRel, $out);
        }

        if ($cmd == 'table') {
            $this->newBlockMode('table');
        }

        return $out;
    }

    function countIndent ($line) {

        $len = strlen($line);

        for ($i = 0; $i < $len; $i += 1) {
            if ($line[$i] !== ' ') {
                return $i;
            }
        }
        return 0;
    }

    function onBlankLine() {

        if ($this->blockMode === 'pre') {
            $this->blockContent .= "\n";
            return;
        }

        // Close up current block
        if ($this->blockMode === 'ul') {
            $this->add('</ul>');
        }
        else if ($this->blockMode === 'ol') {
            $this->add('</ol>');
        }
        else if ($this->blockMode === 'blockquote') {
            $this->add('</blockquote>');
        }
        else if ($this->blockMode === 'table') {
            $this->addTable();
        }
        else {
            $this->clearPara();
        }

        $this->blockMode = '';
    }

    function parseLine ($rawLine) {

        $indent = $this->countIndent($rawLine);
        $line = trim($rawLine);

        if (!$line) {
            $this->onBlankLine();
            return;
        }

        // code blocks
        $c3 = substr($line, 0, 3);

        if ($this->blockMode === 'pre') {

            if ($c3 === "```") {

                $this->add(trimIndent($this->blockContent));
                $this->blockMode = '';
                $this->blockContent = '';

                $this->add('</pre>');
            }
            else {
                $this->blockContent .= escapeHtml($rawLine) . "\n";
            }
        }
        else if ($this->blockMode === 'table') {

            $cells = preg_split('/\s{2,}/', trim($rawLine));
            $cellsHtml = [];
            foreach ($cells as $c) {
                $cellsHtml []= $this->parseInline($c, true);
            }

            $this->tableRows []= $cellsHtml;
        }
        else if ($c3 === "```" && $this->features['blocks']) {

            $this->newBlockMode('pre');
            $this->blockLines = [];

            $line = ltrim($line, '`');
            $classes = preg_replace('/[^a-zA-Z0-9\-]/', '', $line);

            $this->add('<pre class="' . $classes . '">', true);
        }
        else if (substr($line, 0, 1) === '<' && $this->features['xDangerHtml']) {

           $this->add($this->parseInline($line, false));
           $this->onBlankLine();
        }
        else {
            // Single-liners
            $isOneLiner = $this->parseOneLiner($line, $indent);

            // Paragraph line
            if (!$isOneLiner) {
                $inline = $this->parseInline($line, true);
                $this->addParaLine($inline);
            }
        }
    }

    // markup that spans an entire line, based on prefix (e.g. '## heading')
    function parseOneLiner($line, $indent) {

        $c = substr($line, 0, 1);

        if ($c === '#' && $this->features['headings']) {

            // heading
            preg_match('/^(#+)\s*(.*)/', $line, $matches);
            $hnum = min(strlen($matches[1]), 6);
            $title = $this->parseInline($matches[2], false);

            if ($hnum == 2) {
                $titleToken = toTokenCase(preg_replace('/&.*;/', '', $title));
                $this->toc []= [ 'plain' => $title, 'token' => $titleToken ];

                $tag = "<a class='toc-anchor' name='" . $titleToken . "'>&nbsp;</a>";
                $title .= $tag;
            }

            return $this->add('<h' . $hnum . '>' . $title . '</h' . $hnum . '>');
        }
        else if ($c === '-' || $c === '+') {

            // list item
            if (!$this->features['lists']) { return $this->add($line); }

            if (!$this->blockMode) {
                $this->newBlockMode($c === '-' ? 'ul' : 'ol');
                $this->add('<' . $this->blockMode . '>');
            }

            return $this->add('<li>' . $this->parseInline(substr($line, 1), true)  . '</li>');
        }
        else if ($c === '>') {

            // quote
            if (!$this->features['blocks']) { return $this->add($line); }

            if (!$this->blockMode) {
                $this->newBlockMode('blockquote');
                $this->add('<blockquote>');
            }

            $quoteLine = $this->parseInline(substr($line, 1), true);

            if (strpos($quoteLine, '&mdash;') === 0) {
                $quoteLine = '<cite style="font-size: 90%">' . $quoteLine . '</cite>';
            }

            return $this->add($quoteLine . '<br />', true);
        }

        return false;
    }

    function parseInline ($line, $autoLinkUrls=true) {

        $lp = new LitemarkInlineParser($this, $line, $autoLinkUrls);

        return trim($lp->parse());
    }
}


class LitemarkInlineParser {

    private $i = -1;
    private $line = '';
    private $lineLen = 0;

    private $char = '';
    private $char2 = '';
    private $prevChar = '';

    private $tags = [];
    private $parent = null;
    private $autoLinkUrls = true;

    function __construct($parent, $line, $autoLinkUrls = true) {

        $this->parent = $parent;
        $this->line = $line;
        $this->lineLen = strlen($line);
        $this->autoLinkUrls = $autoLinkUrls;

        $this->nextChar();
    }

    function nextChar() {

        $this->i += 1;
        $this->prevChar = $this->char;

        $this->char = substr($this->line, $this->i, 1);

        $nextI = $this->i + 1;
        $this->char2 = ($nextI >= $this->lineLen) ? '' : $this->line[$nextI];

        return $this->char;
    }

    function parse() {

        $str = '';

        while (true) {

            $c = $this->char;
            $cc = $c . $this->char2;

            if ($c == '') {
                break;
            }
            else if ($c == '\\') {
                $str .= $this->parseEscape();
            }
            else if ($c == '<') {
                $str .= $this->parseHtmlTag();
            }
            else if ($c == '`') {
                $str .= $this->parseCodeTerm();
            }
            else if ($cc == '**') {
                $str .= $this->parseWrapper('bold', '<strong>', '</strong>', 2);
            }
            else if ($cc == '__') {
                $str .= $this->parseWrapper('italic', '<em>', '</em>', 2);
            }
            else if ($c == "'") {
                $str .= $this->parseApostrophe();
            }
            else if ($c == 'h' && $this->autoLinkUrls) {
                $str .= $this->parseUrl();
            }
            else if ($c == '[') {
               $str .= $this->parseSquareTag();
            }
            else if ($c == '"') {
                $str .= $this->parseWrapper('quote', '&ldquo;', '&rdquo;', 1);
            }
            else if ($cc == '->') {
                $str .= $this->parseGlyph('&rarr;');
            }
            else if ($cc == '--') {
                $str .= $this->parseGlyph('&mdash;');
            }
            else {
                $str .= $this->parseInlineChar($c);
            }

            $this->nextChar();
        }

        $str .= $this->closeInlineTags();

        return $str;
    }

    private function parseUrl() {

        $next7 = substr($this->line, $this->i, 7);
        $next8 = substr($this->line, $this->i, 8);

        if ($next7 != 'http://' && $next8 != 'https://') {
            return $this->char;
        }

        $url = '';

        while (true) {
            $url .= $this->char;
            $this->nextChar();

            if ($this->char == '' || $this->char == ' ') { break; }
        }

        $after = '';
        $lastChar = $url[strlen($url) - 1];

        if (strpos('()[]{},.!"\'', $lastChar) !== false) {
            // exclude common separators
            $after = $lastChar;
            $url = substr($url, 0, strlen($url) - 1);
        }

        if ($this->parent->flags['urlHandler']) {
            $autoStr = call_user_func_array($this->parent->flags['urlHandler'], [$url]);
            if ($autoStr) {
                return $autoStr;
            }
        }

        $nofollow = $this->parent->features['indexLinks'] ? '' : ' rel="nofollow"';

        $str = '<a href="'. $url . '"' . $nofollow . '>' . $url . '</a>';
        $str .= $after;

        return $str;
    }

    // Apostrophe / single quote
    private function parseApostrophe() {

        $prev = $this->prevChar;
        $next = $this->char2;

        if ($prev >= 'a' && $prev <= 'z' && $next >= 'a' && $next <= 'z') {

            // Insert between two lower case letters.
            // Unicode says this is the preferred apostrophe.
            return "&#8217;";
        }
        else {

            return "'";
        }
    }

    // '\'
    private function parseEscape() {

        $this->nextChar();

        return $this->char;
    }

    // '--' and '->'
    private function parseGlyph($g) {

        $this->nextChar(); // 2nd char

        return $g;
    }

    private function parseWrapper($wrapperId, $openTag, $closeTag, $numChars) {

        if ($numChars == 2) {
            $this->nextChar();
        }

        if (end($this->tags) === $wrapperId) {
            array_pop($this->tags);

            return $closeTag;
        }
        else {
            $this->tags []= $wrapperId;

            return $openTag;
        }
    }

    // `code term`
    private function parseCodeTerm() {

        if (!$this->parent->features['inline']) { return "`"; }

        $str = '<code>';

        while (true) {

            $c = $this->nextChar();

            if ($c === '\\') {

                if ($this->char2 == '`' || $this->char2 == '\\') {
                    $str .= $this->char2;
                    $this->nextChar();

                    continue;
                }
            }
            else if ($c === '' || $c === '`') {
                break;
            }

            $str .= escapeHtml($c);
        }

        $str .= '</code>';

        return $str;
    }

    private function parseHtmlTag() {

        if (!$this->parent->features['xDangerHtml']) {
            return escapeHtml($this->char);
        }

        $str = '';

        while (true) {

            $str .= $this->char;

            if ($this->char === '>') { break; }

            $this->nextChar();

            if ($this->char == '') {
                return '>[UNCLOSED TAG]';
            }
        }

        return $str;
    }

    private function parseInlineChar($c) {

        if ($this->parent->features['xDangerHtml']) {
            return $c;
        }

        return escapeHtml($c);
    }

    private function parseSquareTag() {

        $cmdOut = '';
        $cmd = '';
        $cmds = [];

        while (true) {

            $this->nextChar();
            $c = $this->char;

            if ($c === '[') {
                // nested square tag
                $cmds []= $cmd;
                $cmd = '';
                continue;
            }
            else if ($c === ']') {

                // end of square tag
                $thisOut = $this->parent->command($cmd);

                if (count($cmds)) {
                    $cmd = array_pop($cmds);
                    $cmd .= $thisOut;
                } else {
                    $cmdOut .= $thisOut;
                    break;
                }
            }
            else {
                $cmd .= $c;
            }

            if ($this->char == '') {
                return '[' . substr($cmd, 0, 20) . "... (MISSING `]`)";
            }
        }

        return $cmdOut;
    }

    private function closeInlineTags() {

        $str = '';

        while (true) {

            if (!count($this->tags)) { break; }

            $tag = array_pop($this->tags);

            if ($tag !== 'quote') {
                $str .= "</$tag>";
            }
        }

        return $str;
    }
}



// Utils
//--------------------------------------------------------------

function escapeHtml($in) {

    return htmlspecialchars($in, ENT_QUOTES|ENT_HTML5, 'UTF-8');
}

function trimLines ($val) {

    $trimmed = rtrim($val);
    $lines = explode("\n", $trimmed);

    while (count($lines)) {
        $line = $lines[0];
        if (preg_match('/\S/', $line)) {
            break;
        } else {
            array_shift($lines);
        }
    }

    return implode("\n", $lines);
}

function trimIndent ($v) {

    $minIndent = 999;
    $trimmed = trimLines($v);

    if (!strlen($trimmed)) { return ''; }

    $lines = explode("\n", $trimmed);

    if (count($lines) === 1) { return ltrim($trimmed); }

    foreach ($lines as $line) {
        if (!preg_match('/\S/', $line)) { continue; }

        preg_match('/^(\s*)/', $line, $match);
        $indent = strlen($match[1]);
        $minIndent = min($indent, $minIndent);

        if (!$indent) { break; }
    }

    $numLines = count($lines);

    for ($i = 0; $i < $numLines; $i += 1) {
        $lines[$i] = substr($lines[$i], $minIndent);
    }

    return implode("\n", $lines);
}

// e.g. convert 'camelCase' to 'token-case'
function toTokenCase($v) {

    $v = preg_replace('/<.*?>/', '', $v);
    $v = preg_replace("/([A-Z]+)/", " $1", $v);
    $v = trim(strtolower($v));
    $v = str_replace("'", '', $v);
    $v = preg_replace('/[^a-z0-9]+/', '-', $v);
    $v = rtrim($v, '-');

    return $v;
}


