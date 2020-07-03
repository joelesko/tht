<?php

namespace Litemark;

class LitemarkParser {

    private $html = [];
    private $blockMode = '';
    private $blockLines = [];
    private $paraLines = [];
    private $blockContent = '';
    private $toc = [];
    private $numParas = 0;

    public $flags = [];

    var $squareCommands = [

        // base commands
        'link1'   => '<a href="{1}" rel=”{linkrel}”>{1}</a>',
        'link2'   => '<a href="{1}" rel=”{linkrel}”>{2}</a>',
        'link3'   => '<a href="{1}" title="{2}" rel="{linkrel}">{3}</a>',
        'image1'  => '<img src="{1}" />',
        'image2'  => '<img src="{1}" alt="{2}" />',

        // HTML Equivalents
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

        // Documentation
        'toc0'     => '<<<<<TOC>>>>>',
        'info1'    => '<div class="alert">{1}</div>',
        'info2'    => '<div class="alert"><strong>{1}</strong>{2}</div>',
        'success1' => '<div class="alert alert-success">{1}</div>',
        'success2' => '<div class="alert alert-success"><strong>{1}</strong>{2}</div>',
        'warning1' => '<div class="alert alert-error">{1}</div>',
        'warning2' => '<div class="alert alert-error"><strong>{1}</strong>{2}</div>',
    ];

    function __construct($flags=[]) {

        $this->flags = $flags;

        $this->validateFlag('allowHtml', false);
        $this->validateFlag('addNoFollow', true);
        $this->validateFlag('squareTags', '');
        $this->validateFlag('extraSquareTags', []);
        $this->validateFlag('errorHandler', null);
        $this->validateFlag('urlHandler', null);

        $this->initSquareTags();
    }

    // TODO: validate types
    function validateFlag($name, $default) {
        if (!isset($this->flags[$name])) {
            $this->flags[$name] = $default;
        }
    }

    function error($msg) {
        $msg = 'Litemark: ' . $msg;
        if ($this->flags['errorHandler']) {
            call_user_func_array($this->flags['errorHandler'], []);
        }
        else {
            throw new \Exception ($msg);
        }
    }

    function initSquareTags() {

        // filter allowed square tags
        if ($this->flags['squareTags'] == 'none' || !$this->flags['squareTags']) {
            $this->squareCommands = [];
        }
        else if ($this->flags['squareTags'] != 'all') {
            foreach ($this->squareCommands as $k => $v) {
                $bareName = substr($k, 0, strlen($k) - 1);
                if (strpos($this->flags['squareTags'], $bareName) === false) {
                    unset($this->squareCommands[$k]);
                }
            }
        }

        // Add custom squareTags
        foreach ($this->flags['extraSquareTags'] as $k => $v) {
            // TODO: check for arity, name format
            $this->squareCommands[$k] = $v;
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

        // force blocks to close
        $lines []= '';

        foreach ($lines as $line) {
            $this->parseLine($line);
        }

        // Don't wrap in a single <p> tag
        if ($this->numParas == 1 && $this->html[0] === '<p>') {
            array_pop($this->html);
            array_shift($this->html);
        }

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

        $tocHtml = ['<ul>'];
        foreach ($this->toc as $h) {
            $tocHtml []= '<li><a href="#' . toTokenCase($h) . '">' . escapeHtml($h) . '</a></li>';
        }
        $tocHtml []= '</ul>';

        $out = implode("\n", $tocHtml);
        $out .= "\n\n<style> .toc-anchor { position: relative; top: -2rem; display: inline-block; width: 0; visibility: hidden } </style>";

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
            $this->paraLines = [];
            $this->numParas += 1;
        }
    }

    function addParaLine ($l) {
        $l = trim($l);
        if ($l) {
            $this->paraLines []= $l;
        }
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
        if (!isset($this->squareCommands[$key])) {
            if (isset($this->squareCommands[strtolower($key)])) {
                return '[' . $key . ' - tag name must be lower case]';
            }
            return '[' . $raw . ']';
        }
        $template = $this->squareCommands[$key];

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
                $val = $val;
                $out = str_replace('{' . ($i+1) . '}', $val, $out);
            }
        }

        // add nofollow param
        if ($this->flags['addNoFollow']) {
            $out = str_replace('{linkrel}', 'nofollow', $out);
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

    function onBlankLine($line) {

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
        else {
            $this->clearPara();
        }

        $this->blockMode = '';
    }

    function parseLine ($rawLine) {

        $indent = $this->countIndent($rawLine);
        $line = trim($rawLine);

        if (!$line) {
            $this->onBlankLine($line);
            return;
        }

        // code blocks
        $c3 = substr($line, 0, 3);
        if ($this->blockMode === 'pre') {
            if ($c3 === "```") {
                $this->add(trimIndent($this->blockContent));
                $this->blockMode = '';
                $this->blockContent = '';
                return $this->add('</pre>');
            }
            else {
                $this->blockContent .= escapeHtml($rawLine) . "\n";
                return;
            }
        }
        else if ($c3 === "```") {
            $this->blockMode = 'pre';
            $this->blockLines = [];
            $line = ltrim($line, '`');
            $classes = preg_replace('/[^a-zA-Z0-9\-]/', '', $line);
            $this->add('<pre class="' . $classes . '">', true);
            return;
        }
        else if (substr($line, 0, 1) === '<') {
            if ($this->flags['allowHtml']) {
               $this->add($this->parseInline($line, false));
               $this->onBlankLine($line);
               return;
            }
        }

        // Single-liners
        $isOneLiner = $this->parseOneLiner($line, $indent);

        // Paragraph line
        if (!$isOneLiner) {
            $inline = $this->parseInline($line, true);
            $this->addParaLine($inline);
        }
    }

    // markup that spans an entire line, based on prefix (e.g. '## heading')
    function parseOneLiner($line, $indent) {

        $c = substr($line, 0, 1);

        if ($c === '#') {

            // heading
            preg_match('/^(#+)\s*(.*)/', $line, $matches);
            $hnum = min(strlen($matches[1]), 6);
            $title = $this->parseInline($matches[2], false);

            if ($hnum == 2) {
                $this->toc []= $title;
                $tag = "<a class='toc-anchor' name='" . toTokenCase($title) . "'>&nbsp;</a>";
                $title .= $tag;
            }
            return $this->add('<h' . $hnum . '>' . $title . '</h' . $hnum . '>');
        }
        else if ($c === '-' || $c === '+') {

            // list item
            if (!$this->blockMode) {
                $this->blockMode = $c === '-' ? 'ul' : 'ol';
                $this->add('<' . $this->blockMode . '>');
            }
            return $this->add('<li>' . trim($this->parseInline(substr($line, 1), true))  . '</li>');
        }
        else if ($c === '>') {

            // quote
            if (!$this->blockMode) {
                $this->blockMode = 'blockquote';
                $this->add('<blockquote>');
            }
            $quoteLine = trim($this->parseInline(substr($line, 1), true));
            if (strpos($quoteLine, '&mdash;') === 0) {
                $quoteLine = '<span style="font-size: 90%">' . $quoteLine . '</span>';
            }
            return $this->add($quoteLine . '<br />', true);
        }

        return false;
    }

    function parseInline ($line, $autoLinkUrls=true) {
        $lp = new LitemarkInlineParser($this, $line, $autoLinkUrls);
        return $lp->parse();
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
            else if ($c === '\\') {
                $str .= $this->parseEscape();
            }
            else if ($c === '<') {
                $str .= $this->parseHtmlTag();
            }
            else if ($c === '`') {
                $str .= $this->parseCodeTerm();
            }
            else if ($cc === '**') {
                $str .= $this->parseWrapper('bold', '<strong>', '</strong>', 2);
            }
            else if ($cc === '__') {
                $str .= $this->parseWrapper('italic', '<em>', '</em>', 2);
            }
            else if ($c === "'") {
                $str .= $this->parseApostrophe();
            }
            else if ($c === 'h' && $this->autoLinkUrls) {
                $str .= $this->parseUrl();
            }
            else if ($c === '[') {
               $str .= $this->parseSquareTag();
            }
            else if ($c === '"') {
                $str .= $this->parseWrapper('quote', '&ldquo;', '&rdquo;', 1);
            }
            else if ($cc === '->') {
                $str .= $this->parseGlyph('&rarr;');
            }
            else if ($cc === '--') {
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
            if ($autoStr) { return $autoStr; }
        }

        $nofollow = $this->parent->flags['addNoFollow'] ? ' rel="nofollow"' : '';
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
        } else {
            return "'";
        }
    }

    // '\'
    private function parseEscape() {
        $this->next();
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

        $str = '<code>';
        while (true) {
            $c = $this->nextChar();
            if ($c === '\\') {
                if ($this->char2 == '`' || $this->char2 == '\\') {
                    $str.= $this->char2;
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

        if (!$this->parent->flags['allowHtml']) {
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
        if ($this->parent->flags['allowHtml']) {
            return $c;
        } else {
            return escapeHtml($c);
        }
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
                $this->parent->error('Unclosed square tag: `[' . substr($cmd, 0, 15) . "...`");
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

function toTokenCase($v) {
    $v = preg_replace("/([A-Z]+)/", " $1", $v);
    $v = trim(strtolower($v));
    $v = str_replace("'", '', $v);
    $v = preg_replace('/[^a-z0-9]+/', '-', $v);
    $v = rtrim($v, '-');
    return $v;
}

