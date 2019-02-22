<?php

namespace o;

/*

TODO

    @mentions

    :emojicode:
      https://github.com/github/gemoji/tree/master/db

*/



class u_Litemark extends StdModule {
    private $html = [];
    private $blockMode = '';
    private $blockLines = [];
    private $paraLines = [];
    private $flags = [];
    private $blockContent = '';
    private $toc = [];
    private $numParas = 0;
    private $reader = null;

    var $commands = [

        // base commands
        'link1'   => '<a class="lite" href="{1}" rel=”{linkrel}”>{1}</a>',
        'link2'   => '<a class="lite" href="{1}" rel=”{linkrel}”>{2}</a>',
        'link3'   => '<a class="lite" href="{1}" title="{2}" rel="{linkrel}">{3}</a>',
        'image1'  => '<img class="lite" src="{1}" />',
        'image2'  => '<img class="lite" src="{1}" alt="{2}" />',
        'br0'     => '<br />',
        'pbr0'    => '<p> </p>',
        'sp0'     => '&nbsp;',

        // documentation
        'toc0'     => '<<<<<TOC>>>>>',
        'message1' => '<div class="lite message">{1}</div>',
        'message2' => '<div class="lite message"><strong>{1}</strong>{2}</div>',
        'success1' => '<div class="lite message success">{1}</div>',
        'success2' => '<div class="lite message success"><strong>{1}</strong>{2}</div>',
        'error1'   => '<div class="lite message error">{1}</div>',
        'error2'   => '<div class="lite message error"><strong>{1}</strong>{2}</div>',
        'quote2'   => '<blockquote class="lite"><p>{1}</p><footer>{2}</footer></blockquote>',

        // HTML equivalents
        'dfn1'    => '<dfn class="lite">{1}</dfn>',
        'del1'    => '<del class="lite">{1}</del>',
        'kbd1'    => '<kbd class="lite">{1}</kbd>',
        'abbr2'   => '<abbr class="lite" title="{2}">{1}</abbr>',
        'sup1'    => '<sup class="lite">{1}</sup>',
        'sub1'    => '<sub class="lite">{1}</sub>',
        'small1'  => '<small class="lite">{1}</small>',
    ];

    function __construct($flags=[]) {
        $this->flags = $flags;
        $this->flags['html'] = isset($this->flags['html']) && $this->flags['html'];
        if (isset($flags['reader'])) {
            $this->reader = $flags['reader'];
        }
    }

    function error($msg) {
        if ($this->reader) {
            // TODO: try to make this more exact
            $this->reader->error($msg, '1,0');
        }
        else {
            Tht::error($msg);
        }
    }

    function u_parse_file($file) {
        $path = Tht::path('files', $file);
        $text = Tht::module('File')->u_read($path, true);

        return $this->u_parse($text);
    }

    function u_parse ($raw, $flags=[]) {
        $raw = \o\OLockString::getUnlockedNoError($raw);
        Tht::module('Perf')->u_start('Litemark.parse', $raw);

        $this->flags = $flags;

        $e = new u_Litemark ($this->flags);

        $lines = explode("\n", $raw);
        $lines []= ''; // force blocks to close
        foreach ($lines as $line) {
            $e->parseLine($line);
        }

        // Don't wrap in a single <p> tag
        if ($e->numParas == 1 && $e->html[0] === '<p>') {
            array_pop($e->html);
            array_shift($e->html);
        }

        $out = implode("\n", $e->html);

        if (count($e->toc)) {
            $nonce = Tht::module('Web')->u_nonce();
            $out .= "\n\n<style nonce='$nonce'> .toc-anchor { position: relative; top: -2rem; display: inline-block; width: 0; visibility: hidden } </style>";
            $out = str_replace('<<<<<TOC>>>>>', $e->createToc(), $out);
        }

        Tht::module('Perf')->u_stop();

        return new \o\HtmlLockString ($out);
    }

    function createToc() {

        $out = ['<ul>'];
        foreach ($this->toc as $h) {
            $out []= '<li><a href="#' . v($h)->u_to_token_case() . '">' . htmlspecialchars($h) . '</a></li>';
        }
        $out []= '</ul>';
        return implode("\n", $out);
    }

    function add ($h) {
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
        if (preg_match('#[\.\/]#', $cmd)) {
            $parts[1] = $cmd . $parts[1];
            $cmd = 'link';
        }

        $right = count($parts) > 1 ? $parts[1] : '';
        $right = trim($right, '|');
        $args = explode('|', $right);

        // look up command by arity
        $key = $cmd . ($right ? count($args) : 0);
        if (! isset($this->commands[$key])) {
            return "[? $key ?]";
        }
        $template = $this->commands[$key];

        // replace arguments
        $out = '';
        if (is_callable($template)) {
            $out = $template($args);
        } else {
            $out = $template;
            foreach (range(0, count($args) - 1) as $i) {
                $val = $this->parseInline(trim($args[$i]));
                $out = str_replace('{' . ($i+1) . '}', $val, $out);
            }
        }

        // add nofollow param
        if (!isset($this->flags['noFollowLinks']) || $this->flags['noFollowLinks']) {
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
                $this->add(v($this->blockContent)->u_trim_indent());
                $this->blockMode = '';
                $this->blockContent = '';
                return $this->add('</pre>');
            }
            else {
                $this->blockContent .= htmlspecialchars($rawLine) . "\n";
                return;
            }
        }
        else if ($c3 === "```") {
            $this->blockMode = 'pre';
            $this->blockLines = [];
            return $this->add('<pre>', true);
        }

        if (substr($line, 0, 1) === '<') {
            if ($this->flags['html']) {
               $this->add($this->parseInline($line));
               $this->onBlankLine($line);
               return;
            }
        }

        // Single-liners
        $isOneLiner = $this->parseOneLiner($line);

        // Paragraph line
        if (!$isOneLiner) {
            $inline = $this->parseInline($line);
            $this->addParaLine($inline);
        }
    }

    // commands that span an entire line, based on prefix (e.g. '## heading')
    function parseOneLiner($line) {
        $c = substr($line, 0, 1);

        if ($c === '#') {

            // heading
            preg_match('/^(#+)\s*(.*)/', $line, $matches);
            $hnum = min(strlen($matches[1]), 6);
            $title = $this->parseInline($matches[2]);

            if ($hnum == 2) {
                $this->toc []= $title;
                $tag = "<a class='toc-anchor' name='" . v($title)->u_to_token_case() . "'>&nbsp;</a>";
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
            return $this->add('<li>' . $this->parseInline(substr($line, 1))  . '</li>');
        }
        else if ($c === '>') {

            // quote
            if (!$this->blockMode) {
                $this->blockMode = 'blockquote';
                $this->add('<blockquote>');
            }
            return $this->add($this->parseInline(substr($line, 1)) . '<br />');
        }

        return false;
    }

    // TODO: refactor all this
    function parseInline ($line) {

        $str = '';
        $c = '';
        $tags = [];
        $i = 0;
        $len = strlen($line);

        while (true) {
            if ($i >= $len) { break; }

            $prev = $c;
            $c = $line[$i];
            $i += 1;
            $c2 = ($i >= $len) ? '' : $line[$i];

            $tag = end($tags);

            if ($c === '\\') {

                // escape
                $i += 1;
                $str .= $c2;

            } else if ($c === '<') {

                // HTML tag
                if ($this->flags['html']) {
                    while (true) {
                        $str .= $c;
                        if ($i >= $len || $c === '>') { break; }
                        $c = $line[$i];
                        $i += 1;
                    }

                } else {
                    $str .= htmlspecialchars($c);
                }

            } else if ($c === '`') {

                // `code term`
                $str .= '<code>';
                while (true) {
                    $c = $line[$i];
                    $i += 1;
                    if ($c === '\\' && $i < $len-1) {
                        if ($line[$i] == '`' || $line[$i] == '\\') {
                            $str.= $line[$i];
                            $i += 1;
                            continue;
                        }
                    }
                    if ($i >= $len || $c === '`') { break; }
                    $str .= htmlspecialchars($c);
                }
                $str .= '</code>';

            } else if ($c === '*' && $c2 === '*') {

                // **bold text**
                if ($tag === 'strong') {
                    $str .= '</strong>';
                    array_pop($tags);
                } else {
                    $str .= '<strong>';
                    $tags []= 'strong';
                }
                $i += 1;

            } else if ($c === '_' && $c2 === '_') {

                // __italic text__
                if ($tag === 'em') {
                    $str .= '</em>';
                    array_pop($tags);
                } else {
                    $str .= '<em>';
                    $tags []= 'em';
                }

                $i += 1;

            }
            else if ($c === '-' && $c2 === '>') {

                // -> right arrow
                $str .= '&rarr;';
                $i += 1;
            }
            else if ($c === '-' && $c2 === '-') {

                // -- em dash
                $str .= '&mdash;';
                $i += 1;

            } else if ($c === '"') {

                // "smart quotes"
                if ($tag === 'quote') {
                    $str .= '&rdquo;';
                    array_pop($tags);
                } else {
                    $str .= '&ldquo;';
                    $tags []= 'quote';
                }

            } else if ($c === "'") {

                // Apostrophe / single quote
                if ($prev >= 'a' && $prev <= 'z' && $c2 >= 'a' && $c2 <= 'z') {
                    // Insert between two lower case letters.
                    // Unicode says this is the preferred apostrophe.
                    $str .= "&#8217;";
                } else {
                    $str .= "'";
                }

            } else if ($c === '[') {

                // custom command
                $cmdOut = '';
                $cmd = '';
                $cmds = [];
                while (true) {
                    $c = $line[$i];

                    // nested command
                    if ($c === '[') {
                        $cmds []= $cmd;
                        $cmd = '';
                        $i += 1;
                        continue;
                    }
                    if ($c === ']') {
                        $thisOut = $this->command($cmd);
                        $i += 1;
                        if (count($cmds)) {
                            $cmd = array_pop($cmds);
                            $cmd .= $thisOut;
                            continue;
                        } else {
                            $cmdOut .= $thisOut;
                            break;
                        }
                    }
                    $cmd .= $c;
                    $i += 1;
                    if ($i >= $len) {
                        $this->error('Unclosed command tag: `[' . substr($cmd, 0, 10) . "...`");
                    }
                }
                $str .= $cmdOut;

            }
            else {
                if ($this->flags['html']) {
                    $str .= $c;
                } else {
                    $str .= htmlspecialchars($c);
                }
            }
        }

        $str .= $this->closeInlineTags($tags);

        return $str;
    }

    function closeInlineTags($tags) {
        $str = '';
        while (true) {
            if (!count($tags)) { break; }
            $tag = array_pop($tags);
            if ($tag !== 'quote') {  // TODO: clean up special case
                $str .= "</$tag>";
            }
        }
        return $str;
    }

}

