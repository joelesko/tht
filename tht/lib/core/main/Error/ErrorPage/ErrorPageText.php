<?php

namespace o;

// TODO: include stack trace.  Have to pull out from ErrorPageHtml
// TODO: include source line.
// TODO: include help link?
class ErrorPageText {

    function print($error) {

        $out = $this->format($error);

        print($out);
    }

    function format($parts) {

        $out = "\n--- " . $parts['title'] . " ---\n\n";

        if (Tht::isMode('web')) {
            $out .= 'URL: ' . THT::module('Request')->u_get_url()->u_render_string() . "\n";
            $out .= 'Client IP: ' . THT::module('Request')->u_get_ip() . "\n\n";
        }

        $parts['message'] = preg_replace("/ {2,}/", "\n\n", $parts['message']);
        $line = "...........................................................";
        $out .= "$line\n\n\n" . $parts['message'] . "\n\n\n$line\n";

        $source = $parts['source'];
        if ($source['lineNum'] >= 1) {
            $out .= "\nLine: " .  $source['lineNum'];
        }

        if (isset($parts['filePath'])) {
            $out .= "\nFile: " . $parts['filePath'];
        }

        $out .= "\n\n";

        return $out;
    }
}