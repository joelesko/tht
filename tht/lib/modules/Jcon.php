<?php

namespace o;

class u_Jcon extends StdModule {

    private $context = [ 'type' => '' ];
    private $contexts = [];
    private $leafs = [];
    private $leaf = [];
    private $mlString = null;
    private $mlStringKey = '';
    private $file = '';
    private $line = '';
    private $lineNum = 0;

    function u_parse_file($file) {

        $path = Tht::path('settings', $file);
        $text = Tht::module('File')->u_read($path, true);

        return $this->start($text, $file);
    }

    function u_parse($text) {
        return $this->start($text, '');
    }

    function start($text, $file) {
        $jc = new u_Jcon ();
        $jc->file = $file;
        return $jc->parse($text);
    }

    function error($msg) {
        if ($this->file) {
            ErrorHandler::handleJconError($msg, $this->file, $this->lineNum, $this->line);
        } else {
            Tht::error($msg);
        }
    }

    function parse($text) {
        Tht::module('Perf')->start('Jcon.parse', $text);

        if (!trim($text)) {
            $this->error('Empty JCON string.');
        }

        $text = OLockString::getUnlocked($text, true);

        $lines = explode("\n", $text);
        $this->lineNum = 0;
        $this->line = '';
        foreach ($lines as $l) {
            $this->line = $l;
            $this->lineNum += 1;
            $this->parseLine($l);
        }

        Tht::module('Perf')->u_stop();

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
            print $text; exit();
        }
       // print_r($this->leaf); exit();

         return $this->leaf[0];
    }

    function openChild($type, $parentIndex) {
        $this->leafs []= $this->leaf;
        $this->leaf = ($type == 'map') ? OMap::create([]) : [];

        $this->contexts []= $this->context;
        $this->context = [ 'type' => $type, 'parentIndex' => $parentIndex ];
    }

    function closeChild() {
        $parentLeaf = array_pop($this->leafs);
        $parentContext = array_pop($this->contexts);
        if ($parentContext['type'] == 'map') {
          //  $this->checkMapValue($parentLeaf, $this->context['parentIndex']);
            $parentLeaf[$this->context['parentIndex']] = $this->leaf;
        } else {
            $parentLeaf []= $this->leaf;
        }

        $this->leaf = $parentLeaf;
        $this->context = $parentContext;
    }

    function parseLine($al) {

        $l = trim($al);

        // in multi-line string
        if ($this->mlString !== null) {
            if ($l === "'''") {
                // close quotes
                $trimmed = v($this->mlString)->u_trim_indent();
                $this->assignVal($this->mlStringKey, $trimmed);
                $this->mlString = null;
            } else {
                $this->mlString .= $al . "\n";
            }
            return;
        }

        // blank
        if (!strlen($l)) { return; }

        // comment
        if ($l[0] == '/' && $l[1] == '/') { return; }

        // closing brace
        if ($l[0] === '}' || $l[0] === ']') {
            if (strlen($l) > 1) {
                $this->error("Missing newline after closing brace `" . $l[0] . "`.");
            }
            $this->closeChild();
            return;
        }

        // top-level open brace
        if (!$this->context['type']) {

            if ($l[0] === '{') {
                $this->openChild('map', 0);
            }
            else if ($l[0] === '[') {
                $this->openChild('list', 0);
            }
            else {
                $this->error("Missing top-level open brace `{` or `[`.");
            }
            if (strlen($l) > 1) {
                $this->error("Missing newline after open brace `" . $l[0] . "`.");
            }
            return;
        }


        // key / value pair
        $key = '';
        $val = $l;
        if ($this->context['type'] == 'map') {
            $parts = explode(':', $l, 2);
            if (count($parts) < 2) { $this->error("Missing colon `:`"); }
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
            $this->assignVal($key, $val);
        }
    }

    function assignVal($key, $val) {

        $val = v($val)->u_to_value();

        $isLitemark = substr($key, -4, 4) === 'Lite';

        // escapes
        if (is_string($val) && !$isLitemark) {
            $val = str_replace('\\n', "\n", $val);
            $val = preg_replace('#\\\\(\S)#', '$1', $val);
        }

        if ($this->context['type'] == 'map')  {
            // key/value pair
            if ($isLitemark) {
                $val = Tht::module('Litemark')->u_parse($val);
            }
            $this->leaf[$key] = $val;
        }
        else {
            // array value
            $this->leaf []= $val;
        }
    }
}

