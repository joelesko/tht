<?php

namespace o;

require_once(Tht::getCoreVendorPath('php/Jcon.php'));

class u_Jcon extends OStdModule {

    private $jconObject = null;

    public function getFilePath($file) {

        return Tht::path('config', $file);
    }

    function u_file_exists($file) {

        $this->ARGS('s', func_get_args());

        return file_exists($this->getFilePath($file));
    }

    function u_parse_file($file) {

        $this->ARGS('s', func_get_args());

        $path = $this->getFilePath($file);

        if (!file_exists($path)) {
            $this->error("JCON file not found: `$path`");
        }

        $cacheKey = 'jcon:' . $file;
        $cached = Tht::module('Cache')->u_get_sync($cacheKey, filemtime($path));

        if ($cached) {
            return $cached;
        }

        $text = Tht::module('*File')->u_read($path, OMap::create(['join' => true]));
        $data = $this->u_parse($text);

        Tht::module('Cache')->u_set($cacheKey, $data, 0);

        return $data;
    }

    function u_get_state() {

        $this->ARGS('', func_get_args());

        if (!$this->jconObject) {
            return OMap::create([]);
        }

        return OMap::create($this->jconObject->getState());
    }

    function u_parse($text) {

        $this->ARGS('s', func_get_args());

        $text = OTypeString::getUntypedNoError($text);

        Tht::module('Perf')->u_start('jcon.parse', $text);

        $this->jconObject = new \Jcon\JconParser([

            'mapHandler' => function () {
                return OMap::create([]);
            },

            'listHandler' => function () {
                return OList::create([]);
            },

            'valueHandler' => function ($key, $value) {

                if (substr($key, -2, 2) === 'Lm') {
                    return Tht::module('Litemark')->parseWithFullPerms($value);
                }
                else if (substr($key, -3, 3) === 'Url') {
                    return OTypeString::create('url', $value);
                }
                else {
                    return $value;
                }
            },
        ]);

        $data = $this->jconObject->parse($text);

        Tht::module('Perf')->u_stop();

        return $data;
    }
}

