<?php

namespace o;

class JsTemplateTransformer extends TemplateTransformer {

    function onEndChunk($str) {

        if (Tht::getThtConfig('minifyAssetTemplates')) {
            $str = Tht::module('Output')->minifyJs($str);
        }

        return $str;
    }
}
