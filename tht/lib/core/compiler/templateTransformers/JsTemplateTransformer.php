<?php

namespace o;

class JsTemplateTransformer extends TemplateTransformer {
    function onEndString($str) {
        if (Tht::getConfig('minifyJsTemplates')) {
            $str = Tht::module('Js')->u_minify($str);
        }
        return $str;
    }
}
