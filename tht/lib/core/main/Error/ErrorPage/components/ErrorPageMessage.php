<?php

namespace o;

require_once('ErrorPageComponent.php');

class ErrorPageMessage extends ErrorPageComponent {

    function get() {

        $m = $this->error['message'];

        $m = ErrorTextUtils::formatMessage($m);


        return $m;
    }

    function getHtml() {

        $out = $this->get();

        $out = Security::escapeHtml($out);

        $out = str_replace("\n", '<br>', $out);

        // Convert backticks to code
        $out = preg_replace("/`(.*?)`/", '<span class="tht-error-code">$1</span>', $out);

        return $out;
    }

}