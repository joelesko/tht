<?php

namespace Jcon;

class JconParser {

    private $context = [ 'type' => '' ];
    private $contexts = [];

    private $leafs = [];
    private $leaf = [];

    private $bTagString = '';
    private $bTagStringKey = '';

    private $file = '';
    private $line = '';
    private $lineNum = 0;
    private $pos = 0;

    private $text = '';
    private $length = 0;

    private function error($msg) {
        throw new \Exception ("JconParser: " . $msg);
    }

    function getState() {
        return array(
            'file'     => $this->file,
            'lineNum'  => $this->lineNum,
            'lineText' => $this->line,
            'pos'      => $this->pos,
            'fullText' => $this->text
        );
    }

    function parse($text) {

        $this->text = trim($text);
        $this->len = strlen($this->text);
        $this->pos = 0;
        $this->lineNum = 0;

        if (!$this->length) {
            $this->error('Empty JCON string.');
        }

        while (true) {
            $isLastLine = $this->readLine();
            $this->parseLine($this->line);
            if ($isLastLine) { break; }
        }

        $this->validateResult();

        return $this->leaf[0];
    }

    private function validateResult() {

        // Mismatched / un-closed brace
        if (count($this->leafs) > 0) {
            $missingBrace = '}';
            if ($this->context['type'] == 'list') {
                $missingBrace = ']';
            }
            else if ($this->bTagString) {
                $missingBrace = "'''";
            }
            $this->error("Reached end of file with unclosed `$missingBrace`");
        }

        if (!count($this->leaf)) {
            $this->error("Unable to parse JCON text");
        }
    }

    private function readLine() {

        $line = '';
        $isLastLine = false;
        while (true) {
            $char = $this->text[$this->pos];
            $this->pos += 1;
            if ($char === "\n" || $char === "\r") {
                break;
            }
            $line .= $char;
            if ($this->pos >= $this->length) {
                $isLastLine = true;
                break;
            }
        }

        $this->line = $line;
        $this->lineNum += 1;

        return $isLastLine;
    }

    function parseLine($rawLine) {

        $line = trim($rawLine);

        // in multi-line string
        if ($this->bTagString !== null) {
            if ($line === "'''") {
                // close quotes
                $trimmed = v($this->bTagString)->u_trim_indent();  //!!!
                $this->assignVal($this->bTagStringKey, $trimmed);
                $this->bTagString = null;
            } else {
                $this->bTagString .= $rawLine . "\n";
            }
            return;
        }

        // Blank line
        if (!strlen($line)) {
            return;
        }

        // Read first 2 chars (c0 and c1)
        $c0 = $line[0];
        $c1 = strlen(line) > 1 ? $line[1] : '';

        // comment
        if ($c0 == '/' && $c1 == '/') {
            return;
        }

        // closing brace
        if ($c0 === '}' || $c0 === ']') {
            // must be on its own line
            if ($c1 !== '') {
                $this->error("Missing newline after closing brace `" . $c0 . "`.");
            }
            $this->closeChild();
        }
        // top-level open brace
        else if (!$this->context['type']) {
            $this->handleOpenBrace($c0, $c1);
        }
        else {
            $this->readKeyValuePair($line);
        }
    }

    private function readKeyValuePair($l) {

        $key = '';
        $val = $l;

        if ($this->context['type'] == 'map') {
            $parts = explode(':', $l, 2);
            if (count($parts) < 2) {
                $this->error("Missing colon `:`");
            }
            $key = $parts[0];
            $val = trim($parts[1]);

            if (isset($this->leaf[$key])) {
                $this->error("Duplicate key: `$key`");
            }
            else if ($parts[1] && $parts[1][0] != ' ') {
                $this->error("Missing space after colon `:`");
            }
            else if ($parts[0] && $parts[0][strlen($parts[0]) - 1] == ' ') {
                $this->error("Extra space before colon `:`");
            }
        }

        // handle value
        if ($val === '{') {
            $this->openChild('map', $key);
        }
        else if ($val === '[') {
            $this->openChild('list', $key);
        }
        else if ($val === "'''") {
            $this->bTagStringKey = $key;
            $this->bTagString = '';
        }
        else {
            // literal value
            $this->assignVal($key, $val);
        }
    }

    private function handleOpenBrace($c0, $c1) {
        if ($c0 === '{') {
            $this->openChild('map', 0);
        }
        else if ($c0 === '[') {
            $this->openChild('list', 0);
        }
        else {
            $this->error("Missing top-level open brace `{` or `[`.");
        }

        if ($c1 !== '') {
            $this->error("Missing newline after open brace `" . $c0 . "`.");
        }
    }

    private function openChild($type, $parentIndex) {
        $this->leafs []= $this->leaf;

        $this->leaf = ($type == 'map') ? OMap::create([]) : [];   //!!!

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

        $val = $this->stringToTypedValue($val);

        $isLitemark = substr($key, -4, 4) === 'Lite';

        // escapes
        if (is_string($val) && !$isLitemark) {
            $val = str_replace('\\n', "\n", $val);
            $val = preg_replace('#\\\\(\S)#', '$1', $val);
        }

        if ($this->context['type'] == 'map')  {
            // key/value pair
            if ($isLitemark) {
               // $val = Tht::module('Litemark')->u_parse($val);  //!!!
            }
            $this->leaf[$key] = $val;
        }
        else {
            // array value
            $this->leaf []= $val;
        }
    }

    // Convert a text value into a typed version
    // e.g. "123" => 123 [number], "true" => true [boolean]
    private function stringToTypedValue($v) {

        if ($v === '') { return ''; }

        if ($v[0] === '"') {
            // trim surrounding double quotes
            if ($v[strlen($v) - 1] === $v[0]) {
                $v = trim($v, $v[0]);
            }
            return $v;
        }
        if ($v === 'true') {
            return true;
        }
        else if ($v === 'false') {
            return false;
        }
        else if (preg_match('/^-?[0-9\.]+$/', $v)) {
            if (strpos($v, '.') !== false) {
                return floatval($v);
            } else {
                return intval($v);
            }
        } else {
            return $v;
        }
    }

    private function trimIndent($lines) {

        $trimmed = rtrim($lines);

        if (!strlen($trimmed)) { return ''; }
        $lines = explode("\n", $trimmed);

        $numLines = count($lines);

        // if ($numLines === 1) { return ltrim($trimmed); }

        // Find the smallest level of indent
        $minIndent = 9999;
        foreach ($lines as $line) {
            if (!preg_match('/\S/', $line)) { continue; }
            preg_match('/^(\s*)/', $line, $match);
            $indent = strlen($match[1]);
            $minIndent = min($indent, $minIndent);
            if (!$indent) { break; }
        }

        for ($i = 0; $i < $numLines; $i += 1) {
            $lines[$i] = substr($lines[$i], $minIndent);
        }
        return implode("\n", $lines);
    }
}

