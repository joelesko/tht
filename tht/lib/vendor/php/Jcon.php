<?php

namespace Jcon;

class JconException extends \Exception {}

class JconParser {

    private $flags = [];

    private $context = [ 'type' => '' ];
    private $contexts = [];
    private $leafs = [];
    private $leaf = [];
    private $mlString = null;
    private $mlStringKey = '';

    private $file = '';
    private $line = '';
    private $lineNum = 0;
    private $pos = 0;
    private $len = 0;
    private $text = '';

    function __construct($flags=[]) {

        $this->flags = $flags;

        $this->validateFlag('errorHandler', null);
        $this->validateFlag('mapHandler', null);
        $this->validateFlag('listHandler', null);
        $this->validateFlag('valueHandler', null);
    }

    // TODO: validate types
    private function validateFlag($name, $default) {

        if (!isset($this->flags[$name])) {
            $this->flags[$name] = $default;
        }
    }

    private function error($msg) {

        throw new JconException ($msg);
    }

    private function handleError($msg) {
        if (is_callable($this->flags['errorHandler'])) {
            call_user_func_array($this->flags['errorHandler'], [$msg, $this->getState()]);
        }
        else {
            $msg = 'JCON Error - Line ' . $this->lineNum . ' - ' . $this->file . ': ' . $msg;
            throw new \Exception ($msg);
        }
    }

    // for more error info
    public function getState() {

        return [
            'file'    => $this->file,
            'lineNum' => $this->lineNum,
            'line'    => $this->line,
        ];
    }

    public function parseFile($file) {

        try {
            $this->file = $file;

            if (!file_exists($file)) {
                $this->error("JCON file not found: `$file`");
            }

            $raw = file_get_content($file);

            $parsed = $this->parse($raw);
        }
        catch (JconException $e) {
            $this->handleError($e->getMessage());
            return [];
        }

        return $parsed;
    }

    public function parse($text) {

        $text = trim($text);

        $this->file = '';
        $this->len = strlen($text);
        $this->text = $text;
        $this->pos = 0;
        $this->lineNum = 0;

        try {

            if (!$this->len) {
                $this->error('Empty JCON string.');
            }

            while (true) {
                $isLastLine = $this->readLine();
                if ($isLastLine) { break; }
            }

            $this->validateFinalState();

        } catch (JconException $e) {
            $this->handleError($e->getMessage());
            return [];
        }

         return $this->leaf[0];
    }

    private function validateFinalState() {
        if (count($this->leafs)) {
            $brace = '}';
            if ($this->context['type'] == 'list') {
                $brace = ']';
            }
            else if ($this->mlString) {
                $brace = "'''";
            }
            $this->error("Reached end of file with unclosed `$brace`");
        }

        if (!count($this->leaf)) {
            $this->error('Unable to parse JCON.');
        }
    }

    private function readLine() {
        $line = '';
        $isLastLine = false;
        while (true) {
            $c = $this->text[$this->pos];
            $this->pos += 1;
            if ($c === "\n") {
                break;
            }
            $line .= $c;
            if ($this->pos >= $this->len) {
                $isLastLine = true;
                break;
            }
        }

        $this->line = $line;
        $this->lineNum += 1;

        $this->parseLine($line);

        return $isLastLine;
    }

    private function parseLine($al) {

        $l = trim($al);

        // in multi-line string
        if ($this->mlString !== null) {
            if ($l === "'''") {
                // close quotes
                $trimmed = trimIndent($this->mlString);
                $this->assignVal($this->mlStringKey, $trimmed);
                $this->mlString = null;
            } else {
                // escape closing triple quotes
                if ($l == '\\\'\'\'') {
                    $al = str_replace('\\\'\'\'', "'''", $al);
                }
                $this->mlString .= $al . "\n";
            }
            return;
        }

        // blank
        if (!strlen($l)) { return; }

        $c0 = $l[0];
        $c1 = strlen($l) > 1 ? $l[1] : '';

        // comment
        if ($c0 == '/' && $c1 == '/') { return; }

        // closing brace
        if ($c0 === '}' || $c0 === ']') {
            // must be on its own line
            if ($c1 !== '') {
                $this->error("Missing newline after closing brace: `" . $c0 . "`");
            }
            $this->closeChild();
        }

        // top-level open brace
        else if (!$this->context['type']) {

            if ($c0 === '{') {
                $this->openChild('map', 0);
            }
            else if ($c0 === '[') {
                $this->openChild('list', 0);
            }
            else {
                $this->error("Missing top-level open brace: `{` or `[`");
            }
            if ($c1 !== '') {
                $this->error("Missing newline after open brace: `" . $c0 . "`");
            }
        }
        else {
            // key / value pair
            $key = '';
            $val = $l;
            if ($this->context['type'] == 'map') {
                $parts = explode(':', $l, 2);
                if (count($parts) < 2) {
                    $this->error("Missing colon `:`");
                }
                $key = $parts[0];

                if (isset($this->leaf[$key])) {
                    $this->error("Duplicate key: `$key`");
                }
                if ($parts[1] && $parts[1][0] != ' ') {
                    $this->error("Missing space after colon `:`");
                }
                if ($parts[0] && $parts[0][strlen($parts[0]) - 1] == ' ') {
                    $this->error("Extra space before colon `:`");
                }

                $val = trim($parts[1]);
            }

            // handle value
            if ($val === '{') {
                $this->openChild('map', $key);
            }
            else if ($val === '[') {
                $this->openChild('list', $key);
            }
            else if ($val === "'''") {
                $this->mlStringKey = $key;
                $this->mlString = '';
            }
            else {
                // literal value
                if (mb_substr($val, -1) == ',' && mb_strlen($val) > 1) {
                    $this->error("Please remove trailing comma: `,`");
                }
                $this->assignVal($key, $val);
            }
        }
    }

    private function openChild($type, $parentIndex) {
        $this->leafs []= $this->leaf;
        $this->leaf = [];

        if ($type == 'map' && is_callable($this->flags['mapHandler'])) {
            // This is so THT can initialize it as a Map object
            $this->leaf = call_user_func_array($this->flags['mapHandler'], []);
        }
        else if ($type == 'list' && is_callable($this->flags['listHandler'])) {
            // This is so THT can initialize it as a List object
            $this->leaf = call_user_func_array($this->flags['listHandler'], []);
        }

        $this->contexts []= $this->context;
        $this->context = [ 'type' => $type, 'parentIndex' => $parentIndex ];
    }

    private function closeChild() {
        $parentLeaf = array_pop($this->leafs);
        $parentContext = array_pop($this->contexts);
        if ($parentContext['type'] == 'map') {
            $parentLeaf[$this->context['parentIndex']] = $this->leaf;
        } else {
            $parentLeaf []= $this->leaf;
        }

        $this->leaf = $parentLeaf;
        $this->context = $parentContext;
    }

    private function assignVal($key, $val) {

        $val = stringToValue($val);

        if ($this->context['type'] == 'map')  {
            // key/value pair
            if (is_callable($this->flags['valueHandler'])) {
                $this->leaf[$key] = call_user_func_array($this->flags['valueHandler'], [$key, $val]);
            } else {
                $this->leaf[$key] = $val;
            }
        }
        else {
            // array value
            $this->leaf []= $val;
        }
    }
}

// Utils
// --------------------------------------------

function stringToValue($v) {

    if ($v === '') {
        return '';
    }
    else if ($v[0] === '"' || $v[0] === "'" || $v[0] === '`') {
        // trim surrounding quotes
        if ($v[mb_strlen($v) - 1] === $v[0]) {
            $v = trim($v, $v[0]);
        }
        return $v;
    }
    else if ($v === 'true') {
        return true;
    }
    else if ($v === 'false') {
        return false;
    }
    else if (preg_match('/^-?[0-9\.]+$/', $v)) {
        if (str_contains($v, '.')) {
            return floatval($v);
        } else {
            return intval($v);
        }
    } else {
        return $v;
    }
}

function trimLines($val) {
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

function trimIndent($v) {
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

