<?php

namespace o;


class u_Json extends OStdModule {

    protected $suggestMethod = [
        'stringify' => 'encode()',
        'parse'     => 'decode()',
    ];

    // Convert to JSON TypeString
    function u_encode ($v, $flags=null) {

        $this->ARGS('*m', func_get_args());

        $flags = $this->flags($flags, [
            'format' => false,
        ]);

        $json = json_encode($v, JSON_UNESCAPED_UNICODE);

        if ($flags['format']) {
            $json = $this->u_format($json);
        }

        return new JsonTypeString ($json);
    }

    // Convert JSON string to data
    function u_decode ($jsonTypeString, $useAlt='') {

        $this->ARGS('*s', func_get_args());

        $rawJsonString = OTypeString::getUntyped($jsonTypeString, 'json', true);

        return Security::jsonDecode($rawJsonString, $useAlt);
    }

    function deepSortKeys ($obj) {

        ksort($obj);

        foreach ($obj as $key => $value) {
            $uvObj = unv($obj[$key]);
            if (is_array($uvObj)) {
                $obj[$key] = $this->deepSortKeys($uvObj);
            }
        }

        return $obj;
    }

    function formatOneLineSummary($obj, $maxLen) {

        $json = $this->u_encode($obj)->u_render_string();

        $json = OClass::tokensToBareStrings($json);

        $json = preg_replace('/,\n/', ', ', $json);
        $json = preg_replace('/{\s+/', '{ ', $json);
        $json = preg_replace('/\s+}/', ' }', $json);
        $json = preg_replace('/\s*\n+\s*/', '', $json);
        $json = preg_replace('/\s+/', ' ', $json);

        // TODO: would prefer not to need this, but Windows paths are still escaped?
        $json = preg_replace('!\\\/!', '/', $json);
        $json = preg_replace('!\\\\\\\\!', '\\', $json);

        $json = OClass::tokensToBareStrings($json);

        // truncate
        if (strlen($json) > $maxLen) {
            $json = substr($json, 0, $maxLen - 1) . '…';
            if ($json[0] == '[') {
                $json .= ' ]';
            }
            else if ($json[0] == '{') {
                $json .= ' }';
            }
            else if ($json[0] == "'") {
                $json .= "'";
            }
        }

        return $json;
    }

    // Make JSON output human-readable
    function u_format($jsonOrMap, $flags=null) {

        $this->ARGS('*m', func_get_args());

        if (OMap::isa($jsonOrMap)) {
            $jsonTypeString = $this->u_encode($jsonOrMap);
        }
        else if (OTypeString::isa($jsonOrMap, 'json')) {
            $jsonTypeString = $jsonOrMap;
        }
        else {
            $this->argumentError('Argument #1 must be of type `JsonTypeString` or `Map`.');
        }

        $rawJson = $jsonTypeString->u_render_string();


        $flags = $this->flags($flags, [
            'strict' => false,
        ]);
        $isStrict = $flags['strict'];


        $tab = str_repeat(' ', 4);
        $out = '';
        $indentLevel = 0;
        $inString = false;

        $len = strlen($rawJson);
        for ($i = 0; $i < $len; $i++) {
            $c = $rawJson[$i];

            if ($c === "'" && !$isStrict) {
                $c = "\\'";
            }
            else if ($c === '"') {
                if (($i > 0 && $rawJson[$i-1] !== '\\') || $i == 0) {
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
                // TODO: ugly workaround. Not sure why these can be mis-matched
                if ($indentLevel < 0) {
                    $indentLevel = 0;
                }
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
        $out = str_replace("'{EMPTY_MAP}'", '{}', $out);
        $out = str_replace("'[EMPTY_LIST]'", '[]', $out);

        return new JsonTypeString($out);
    }
}

