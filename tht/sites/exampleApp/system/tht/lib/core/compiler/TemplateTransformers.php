<?php

namespace o;

TemplateTransformer::loadTransformers();

class TemplateTransformer {

    protected $tokenizer = null;
    protected $currentContext = 'none';
    protected $currentIndent = 0;

    static function loadTransformers() {

        $transformers = [
            'Html',
            'Css',
            'Lm',
            'Js',
            'Text',
            'Jcon',
        ];

        foreach ($transformers as $t) {
            include_once('templateTransformers/' . $t . 'TemplateTransformer.php');
        }
    }

    function __construct ($reader) {
        $this->reader = $reader;
    }

    function transformNext() {
        return false;
    }

    function onEndChunk($s) {
        return $s;
    }

    function onEndBody() {}

    function onEndFile() {}

    function currentContext() {
        return $this->currentContext;
    }
}
