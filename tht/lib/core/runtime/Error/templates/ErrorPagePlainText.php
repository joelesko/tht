<?php

namespace o;

class ErrorPagePlainText {

    function print($components) {

        $parts = [];
        foreach ($components as $k => $c) {
            $parts[$k] = $c->get(true);
        }

        $parts['signature'] = $components['helpLink']->getSignature();

        $out = $this->format($parts);

        print($out);
    }

    function format ($parts) {

        $out = "######### " . $parts['title'] . " #########\n\n";

        if (Tht::isMode('web')) {
            $out .= 'URL: ' . THT::module('Request')->u_get_url()->u_render_string() . "\n";
            $out .= 'Client IP: ' . THT::module('Request')->u_get_ip() . "\n\n";
        }

        $out .= $parts['message'];

        if (isset($parts['filePath'])) {
            $out .= "\n\nFile: " . $parts['filePath'];
        }

        if (isset($parts['sourceLine'])) {
            $out .= "\n\n" . $parts['sourceLine'];
        }

        $src = isset($parts['source']) ? $parts['source'] : null;

        if ($parts['stackTrace']) {
            $out .= "\n\n" . $parts['stackTrace'];
        }
        else if (isset($src['file'])) {
            $out .= "\n\nFile: " . $src['file'] . "  Line: " . $src['lineNum'];
            if (isset($src['pos'])) {
                $out .= "  Pos: " . $src['linePos'];
            }
        }

        $out .= "\n\n";

        return $out;
    }
}