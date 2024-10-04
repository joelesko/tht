<?php

namespace o;

require_once('ErrorPageComponent.php');

class ErrorPageObjectDetail extends ErrorPageComponent {

    // TODO: fix/implement this
    function get() {

        // $obj = $this->error['objectDetail'];

        // if ($obj) {

        //     $formatted = Tht::module('Bare')->formatObjectDetail($obj);
        //     $className = $this->error['objectDetailName'] ?: $obj->bareClassName();
        //     $firstLine = $this->out("<div class=\"tht-error-trace-heading\">Object Detail: $className</div>", "Object Detail:\n\n");
        //     $formatted = $this->out('<div class="tht-color-code theme-dark">' . $formatted . '</div>', $formatted);

        //     return $firstLine . $formatted;
        // }

        return '';
    }
}
