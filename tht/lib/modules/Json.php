<?php

namespace o;

class u_Json extends StdModule {

    static function u_encode ($v) {
        return json_encode($v);
    }

    static function u_decode ($v) {
        $dec = json_decode($v, false);
        if (is_null($dec)) {
            Tht::error("Unable to decode JSON string");
        }
        return u_Json::convertToMaps($dec);
    }

    static function convertToMaps ($obj) {
        if (!is_object($obj)) { return $obj; }
        $map = [];
        foreach (get_object_vars($obj) as $key => $val) {
            $map[$key] = u_Json::convertToMaps($val);
        }
        return OMap::create($map);
    }

    static function deepSortKeys ($obj) {
        ksort($obj);
        foreach ($obj as $key => $value) {
            if (is_array($obj[$key])) {
                $obj[$key] = u_Json::deepSortKeys($obj[$key]);
            }
        }
        return $obj;
    }

    // Make human-readable
    static function u_format($obj, $isStrict=false) {

        $tab = str_repeat(' ', 4);
        $out = '';
        $indentLevel = 0;
        $inString = false;

        if (is_string($obj)) {
            $obj = json_decode($obj);
        }

        if ($obj === false) {
            return 'false';
        }

        if (is_array($obj)) {
            $obj = u_Json::deepSortKeys($obj);
        }

        $rawJson = json_encode($obj);
        $len = strlen($rawJson);

        for ($i = 0; $i < $len; $i++) {
            $c = $rawJson[$i];

            if ($c === "'" && !$isStrict) {
                $c = "\\'";
            }
            else if ($c === '"') {
                if ($i > 0 && $rawJson[$i-1] !== '\\') {
                    $inString = !$inString;
                    if (!$isStrict) { $c = "'"; }
                }
            }

            if ($inString) {
                $out .= $c;
            }
            else if ($c === '{' || $c === '[') {
                $out .= $c . "\n" . str_repeat($tab, $indentLevel + 1);
                $indentLevel += 1;
            }
            else if ($c === '}' || $c === ']') {
                $indentLevel -= 1;
                $out .= "\n" . str_repeat($tab, $indentLevel) . $c;
            }
            else if ($c === ',') {
                $out .= ",\n" . str_repeat($tab, $indentLevel);
            }
            else if ($c === ':') {
                $out .= ": ";
            }
            else {
                $out .= $c;
            }
        }

        if (!$isStrict) { $out = preg_replace("/'(.*?)':/", '$1:', $out); }

        $out = preg_replace('/\{\s+\}/', '{}', $out);
        $out = preg_replace('/\[\s+\]/', '[]', $out);

        $out = preg_replace('!\\\\/!', '/', $out);

        return $out;
    }
}

