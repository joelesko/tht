<?php

namespace o;

class u_Jcon extends StdModule {

    private $context = [ 'type' => '' ];
    private $contexts = [];
    private $leafs = [];
    private $leaf = [];
    private $mlString = null;
    private $mlStringKey = '';

    function u_parse_file($file) {
        $path = Owl::path('settings', $file);
        $text = Owl::module('File')->u_read($path, true);

        return $this->u_parse($text);
    }

    function u_parse($text) {
        $jc = new u_Jcon ();
        return $jc->parse($text);
    }

    function parse($text) {
        Owl::module('Perf')->start('Jcon.parse', $text);

        $text = OLockString::getUnlocked($text, true);

        $lines = explode("\n", $text);
        foreach ($lines as $l) {
            $this->parseLine($l);
        }

        Owl::module('Perf')->u_stop();

        return array_pop($this->leaf);
    }

    function open($type, $parentIndex) {
        $this->leafs []= $this->leaf;
        $this->leaf = ($type == 'map') ? OMap::create([]) : [];

        $this->contexts []= $this->context;
        $this->context = [ 'type' => $type, 'parentIndex' => $parentIndex ];
    }

    function close() {
        $parentLeaf = array_pop($this->leafs);
        $parentContext = array_pop($this->contexts);
        if ($parentContext['type'] == 'map') {
            if (isset($parentLeaf[$this->context['parentIndex']])) {
                Owl::error("Duplicate JCON key: '" . $this->context['parentIndex'] . "'"    );
            }
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

        if (!strlen($l)) { return; } // blank;

        if ($l[0] == '/' && $l[1] == '/') {  return; } // comment

        // closing brace
        if ($l[0] === '}' || $l[0] === ']') {
            if (strlen($l) > 1) {
                Owl::error("Closing brace should be on its own line:\n\n  " . $l);
            }
            $this->close();
            return;
        }

        // open brace
        if (!$this->context) {
            if ($l[0] === '{') {
                $this->open('map', 0);
                return;
            }
            else if ($l[0] === '[') {
                $this->open('list', 0);
                return;
            }
        }


        // key / value pair
        $key = '';
        $val = $l;
        if ($this->context['type'] == 'map') {
            $parts = explode(':', $l, 2);
            // TODO: need a general parser error to report lines in content
            if (count($parts) < 2) { Owl::error("Missing colon at line:\n\n  " . $l); }
            $key = $parts[0];

            if ($parts[1] && $parts[1][0] != ' ') {
                Owl::error("Missing space after colon (:) at line:\n\n  " . $l);
            }
            if ($parts[0] && $parts[0][strlen($parts[0]) - 1] == ' ') {
                Owl::error("Extra space before colon (:) at line:\n\n  " . $l);
            }
            $val = trim($parts[1]);
        }

        // handle value
        if ($val === '{') {
            $this->open('map', $key);
        }
        else if ($val === '[') {
            $this->open('list', $key);
        }
        else if ($val === "'''") {
            $this->mlStringKey = $key;
            $this->mlString = '';
        }
        else {
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
                $val = Owl::module('Litemark')->u_parse($val);
            }

            if (isset($this->leaf[$key])) {
                Owl::error("Duplicate JCON key: '$key'");
            }
            $this->leaf[$key] = $val;
        }
        else {
            // array value
            $this->leaf []= $val;
        }
    }
}

