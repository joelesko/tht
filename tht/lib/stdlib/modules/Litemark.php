<?php

namespace o;

require_once(Tht::getCoreVendorPath('php/Litemark.php'));

class u_Litemark extends OStdModule {

    function parseWithFullPerms($s) {

        $flags = OMap::create([
            'features' => ':xDangerAll',
        ]);

        $s = OTypeString::create('lm', $s);
        $lite = Tht::module('Litemark')->u_parse($s, $flags);

        return $lite;
    }

    function initFlags($flags) {

        if (isset($flags['features'])) {

            if (!isset($flags['customTags'])) {
                $flags['customTags'] = OMap::create([]);
            }

            // add icons
            if (in_array($flags['features'], [':xDangerAll', ':blog', ':wiki'])) {

                $flags['customTags']['icon1'] = function ($iconId) {
                    return u_Litemark::iconCallback($iconId);
                };
            }

            // add tags from app.jcon
            if (in_array($flags['features'], [':xDangerAll'])) {

                $jconTags = Tht::getThtConfig('litemarkCustomTags');

                if ($jconTags) {
                    foreach ($jconTags as $t => $v) {
                        $flags['customTags'][$t] = $v;
                    }
                }
            }
        }

        // Wrap urlHandler
        $lm = $this;

        if (isset($flags['urlHandler'])) {

            $origCallback = $flags['urlHandler'];

            $flags['urlHandler'] = function ($url) use ($origCallback, $lm) {

                $url = OTypeString::create('url', $url);
                $out = call_user_func_array($origCallback, [$url]);

                if (!OTypeString::isa($out) || $out->u_string_type() !== 'html') {
                    $lm->error("Function `urlHandler` must return an HTML TypeString. Ex: `html'...'`");
                }

                $out = OTypeString::getUntyped($out, 'html', false);

                return $out;
            };
        }

        // Wrap errorHandler
        $flags['errorHandler'] = function($msg) use($lm) {
            $lm->error($msg);
        };

        return $flags;
    }

    function u_parse_file($file, $flags=false) {

        if (!$flags) { $flags = OMap::create([]); }

        $this->ARGS('sm', [$raw, $flags]);
        $flags = $this->initFlags($flags);

        $path = Tht::path('files', $file);
        $lm = new \Litemark();

        return $lm->parseFile($path, $flags);
    }

    function u_parse($raw, $flags=false) {

        $raw = \o\OTypeString::getUntyped($raw, 'lm');

        if (!$flags) { $flags = OMap::create([]); }

        $this->ARGS('Sm', [$raw, $flags]);
        $flags = $this->initFlags($flags);

        $perfTask = Tht::module('Perf')->u_start('Litemark.parse', $raw);

        $lm = new \Litemark\LitemarkParser($flags);
        $out = $lm->parse($raw);

        $perfTask->u_stop();

        return new \o\HtmlTypeString ($out);
    }

    static function iconCallback($iconId) {

        $icons = Tht::module('Web')->icons();

        if (!isset($icons[$iconId])) {
            Tht::module('Bare')->u_die("unknown icon: '$iconId' (<a href=\"https://tht.dev/reference/icons\">see icon list</a>)");
        }

        return Tht::module('Web')->u_icon($iconId)->u_render_string();
    }
}

