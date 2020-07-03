<?php

namespace o;

require_once('helpers/vendor/Jcon.php');


class u_Jcon extends OStdModule {

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

    function u_parse_file($file) {

        $this->ARGS('s', func_get_args());

        $path = Tht::path('settings', $file);
        if (!file_exists($path)) {
            Tht::error("JCON file not found: '$path'");
        }

        $cacheKey = 'jcon:' . $file;
        $cached = Tht::module('Cache')->u_get_sync($cacheKey, filemtime($path));
        if ($cached) {
          //  return $cached;
        }

        $text = Tht::module('*File')->u_read($path, true);

        $data = $this->u_parse($text);

        Tht::module('Cache')->u_set($cacheKey, $data, 0);

        return $data;
    }

    function u_parse($text) {

        $this->ARGS('s', func_get_args());

        $text = OTypeString::getUntypedNoError($text);

        Tht::module('Perf')->u_start('jcon.parse', $text);

        $jcon = new \Jcon\JconParser([
            'mapHandler' => function () {
                return OMap::create([]);
            },
            'valueHandler' => function ($key, $value) {
                $isLitemark = substr($key, -4, 4) === 'Lite';
                if ($isLitemark) {
                    return Tht::module('Litemark')->parseWithFullPerms($value);
                } else {
                    return $value;
                }
            },
        ]);
        $data = $jcon->parse($text);

        Tht::module('Perf')->u_stop();

        return $data;
    }

}




