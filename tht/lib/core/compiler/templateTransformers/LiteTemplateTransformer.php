<?php

namespace o;

class LiteTemplateTransformer extends TemplateTransformer {

    // preserve code within code fences, so there is no need to escape anything
    function transformNext() {
        $r = $this->reader;
        $c = $r->char1;

        if ($r->atStartOfLine()) {
            $this->indent = $r->slurpChar(' ');
            $c = $r->char1;
            if ($r->isGlyph("```")) {
                $s = $r->getLine() . "\n";
                while (true) {
                    $line = $r->slurpLine();
                    $s .= $line['fullText'] . "\n";
                    if (substr($line['text'], 0,3) == "```" || $line['text'] === null) {
                        break;
                    }
                }
                return $s;
            }
        }
        else if ($c === "`") {
            $c = "`" . $r->slurpUntil('`') . $r->char1;
            $r->next();
            return $c;
        }

        $r->next();
        return $c;
    }

    function onEndString($s) {
        $lite = Tht::module('Litemark')->u_parse($s, ['html' => true, 'reader' => $this->reader]);
        $str = $lite->u_stringify();
        $str = HtmlTemplateTransformer::cleanHtmlSpaces($str);

        return $str;
    }
}
