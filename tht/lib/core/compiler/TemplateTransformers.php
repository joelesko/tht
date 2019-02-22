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
            include_once('templateTransformers/' . $t . '.php');
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

    function cleanHtmlSpaces($str) {
        $str = preg_replace('#>\s+$#',      '>', $str);
        $str = preg_replace('#^\s+<#',      '<', $str);
        $str = preg_replace('#>\s*\n+\s*#', '>', $str);
        $str = preg_replace('#\s*\n+\s*<#', '<', $str);
        $str = preg_replace('#\s+</#',      '</', $str);

        return $str;
    }
}
