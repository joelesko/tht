<?php

namespace o;

class ErrorPageFilePath extends ErrorPageComponent {

    function get() {

        return ErrorTextUtils::cleanPath(
            $this->error['source']['file'], true
        );
    }

    function getHtml() {

        $out = $this->get();

        if (!$out) { return ''; }

        $out = preg_replace('#(.*/)(.*)#', '<span class="tht-error-file-dir">$1</span>$2', $out);
        $out = preg_replace('#(.*)(\.\w{2,4})$#', '$1<span class="tht-error-file-ext">$2</span>', $out);
        $out = $this->wrapHtml('span', '', $out);

        $out = '<span class="tht-error-label">File:</span>' . $out;

        return $out;
    }

}