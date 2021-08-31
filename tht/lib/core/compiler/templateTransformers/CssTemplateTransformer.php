<?php

namespace o;

class CssTemplateTransformer extends TemplateTransformer {

    function onEndString($str) {

        if (Tht::getConfig('minifyAssetTemplates')) {

            $hasTrailingSpace = preg_match('/ $/', $str);
            $hasLeadingSpace = preg_match('/^ /', $str);

            $str = Tht::module('Output')->minifyCss($str);

            // Handle case like 'border: solid {{ $width }}'
            // to prevent value from connecting with prev string
            if ($hasTrailingSpace) {
                $str .= ' ';
            }
            if ($hasLeadingSpace) {
                $str = ' ' . $str;
            }
        }

        return $str;
    }
}
