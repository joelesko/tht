<?php

namespace o;

TemplateTransformer::loadTransformers();

class TemplateTransformer {

    protected $tokenizer = null;
    protected $currentContext = 'none';

    static function loadTransformers() {

        $transformers = [
            'Html',
            'Css',
            'Lite',
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

    function onEndString($s) {
        return $s;
    }

    function onEndTemplateBody() {}

    function onEndFile() {}

    function currentContext() {
        return $this->currentContext;
    }
}
