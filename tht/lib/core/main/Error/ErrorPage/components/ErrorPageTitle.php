<?php

namespace o;

require_once('ErrorPageComponent.php');

class ErrorPageTitle extends ErrorPageComponent {

    function get() {
        return $this->getTitle();
    }

    function getTitle() {
        return 'THT ' . ucfirst($this->error['category']) . ' Error';
    }

    function getHtml() {

        $out = $this->getTitle();
        $out .= '<span class="tht-error-header-sub">' . $this->error['origin'] . '</span>';

        return $out;
    }

}