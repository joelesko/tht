<?php

namespace o;

class LmTemplateTransformer extends TemplateTransformer {

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
                    if (!$line) {
                        // TODO: find actual line number
                        Tht::error('Missing closing code fence in Litemark template.');
                    }
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

    function onEndChunk($s) {
        $str = Tht::module('Litemark')->parseWithFullPerms($s)->u_render_string();

        // This messes up leading whitespace in <pre> tags.
       // $str = HtmlTemplateTransformer::cleanHtmlSpaces($str);

        return $str;
    }
}
