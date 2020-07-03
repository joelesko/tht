<?php

namespace o;

require_once('helpers/vendor/Litemark.php');

class u_Litemark extends OStdModule {

    function parseWithFullPerms($s) {

        $flags = OMap::create([
            'allowHtml' => true,
            'addNoFollow' => false,
            'squareTags' => 'all',
            'extraSquareTags' => [
                'icon1' => function($iconId) {
                    return u_Litemark::iconCallback($iconId);
                }
            ]
        ]);

        // TODO: get extra square tags from app.jcon

        $lite = Tht::module('Litemark')->u_parse($s, $flags);
        return $lite;
    }

    function u_parse_file($file, $flags=false) {
        if (!$flags) { $flags = OMap::create([]); }
        $this->ARGS('sm', [$raw, $flags]);

        $path = Tht::path('files', $file);
        $lm = new \Litemark();
        return $lm->parseFile($path, $flags);
    }

    function u_parse ($raw, $flags=false) {
        if (!$flags) { $flags = OMap::create([]); }
        $this->ARGS('sm', [$raw, $flags]);

        $raw = \o\OTypeString::getUntypedNoError($raw);

        Tht::module('Perf')->u_start('Litemark.parse', $raw);

        $lm = new \Litemark\LitemarkParser($flags);
        $out = $lm->parse($raw);

        Tht::module('Perf')->u_stop();

        return new \o\HtmlTypeString ($out);
    }

    static function iconCallback($iconId) {
        $icons = Tht::module('Web')->icons();
        if (!isset($icons[$iconId])) {
            return $this->errorTag("unknown icon: '$iconId' (<a href=\"https://tht-lang.org/reference/icons\">see icon list</a>)");
        }
        return Tht::module('Web')->u_icon($iconId)->u_stringify();
    }
}

