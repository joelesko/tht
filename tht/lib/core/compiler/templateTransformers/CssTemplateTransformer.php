<?php

namespace o;

class CssTemplateTransformer extends TemplateTransformer {
    function onEndString($str) {

        if (Tht::getConfig('tempParseCss')) {
            $str = Tht::module('Css')->u_parse($str);
        }
        if (Tht::getConfig('minifyCssTemplates')) {
            $str = Tht::module('Css')->u_minify($str);
        }

        return $str;
    }
}
