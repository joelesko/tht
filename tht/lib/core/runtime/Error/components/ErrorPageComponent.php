<?php

namespace o;

class ErrorPageComponent {

    protected $error = null;
    protected $errorPage = null;
    protected $isHtml = false;

    function __construct($errorPage) {
        $this->errorPage = $errorPage;
        $this->error = $errorPage->error;
    }

    function get() {
        return '';
    }

    function getHtml() {
        $this->isHtml = true;
        return $this->get();
    }

    function wrapHtml($el, $className, $out) {

        return "<$el class=\"$className\">$out</$el>";
    }
}

