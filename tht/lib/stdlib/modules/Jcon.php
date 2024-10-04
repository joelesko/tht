<?php

namespace o;

require_once(Tht::getCoreVendorPath('php/Jcon.php'));

/*

    NOTE: Why use JCON over HJSON?

    - At the time of development, I don't think HJSON was reliable enough?
    - JCON is 2x as fast, and half the code. But that shouldn't matter if results are cached.
    - JCON allows escapes in blockquotes, to inner quote fences (need this for docs content)
    - JCON has a few more hooks, for perf, etc.
    - HJSON does have Stringify, though

    - However, I'm open to migrating to HJSON in the future.

*/

class u_Jcon extends OStdModule {

    private $jconObject = null;
    private $parsedFile = '';

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

        $text = PathTypeString::create($path)->u_read(OMap::create(['join' => true]));

        $this->parsedFile = $path;
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

        $perfTask = Tht::module('Perf')->u_start('jcon.parse', $text);

        $this->jconObject = new \Jcon\JconParser([

            'errorHandler' => function ($msg, $info) {
                if ($this->parsedFile) {
                    $info['file'] = $this->parsedFile;
                    $this->parsedFile = '';
                }
                ErrorHandler::handleJconError($msg, $info['file'], $info['lineNum'], $info['line']);
            },

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
                else {

                    // Convert to typestring
                    $typeStringSuffixes = [
                        'Html',
                        'Css',
                        'Js',
                        'Cmd',
                        'Sql',
                        'Url',
                        'File',
                        'Dir',
                    ];

                    foreach ($typeStringSuffixes as $type) {
                        if (v($key)->u_ends_with($type)) {
                            return OTypeString::create(strtolower($type), $value);
                        }
                    }
                }

                return $value;
            },
        ]);

        $data = $this->jconObject->parse($text);

        $perfTask->u_stop();

        return $data;
    }
}

