<?php

namespace o;

class JsTemplateTransformer extends TemplateTransformer {

    function onEndChunk($str) {

        if (Tht::getConfig('minifyJsTemplates')) {
            $str = Tht::module('Output')->minifyJs($str);
        }

        return $str;
    }
}
