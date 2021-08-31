<?php

namespace o;

class ErrorPageHelpLink extends ErrorPageComponent {

    private $signature = '';

    function getUrl() {

        $this->init();

        $link = $this->error['helpLink'];

        if (!$link) {
            return '';
        }

        $url = $link['url'];

        // URLs are relative to the THT website.
        if ($url[0] == '/') {
             $url = Tht::getThtSiteUrl($url);
        }

        // Add referral param
        $referralParam = '?fromError=true&v=' . Tht::getThtVersion(true);
        if (strpos($url, '#') > -1) {
            $url = preg_replace('/(#.*)/', $referralParam . '$1', $url);
        } else {
            $url .= $referralParam;
        }

        return $url;
    }

    function get() {
        return $this->getUrl();
    }

    function getHtml() {

        $url = $this->getUrl();

        if (!$url) { return ''; }

        return "<a href=\"$url\">" . $this->error['helpLink']['label'] . "</a>";
    }

    function init() {
        if (!$this->error['helpLink']) {
            $this->initHelpLinkForStdMethod();
            $this->initHelpLinkForSpecialCases();
        }

        if ($this->error['helpLink']) {
            $this->signature = $this->getStdLibSignature(
                $this->error['helpLink']['label']
            );
        }
    }

    function setLink($url, $name) {
        $this->error['helpLink'] = [
            'url' => $url,
            'label' => $name,
        ];
    }

    function initHelpLinkForStdMethod() {

        // Match `Module.method()`
        preg_match('/`([A-Z]\w+)\.(\w+)\(\)`/', $this->error['message'], $m);

        if (!$m) { return; }


        if (LibModules::isa($m[1])) {
            $urlDir = 'module';
        }
        else if (LibClasses::isa('O' . $m[1])) {
            $urlDir = 'class';
        }
        else {
            return;
        }

        $label = $m[1] . '.' . $m[2];
        $urlStem = v($m[1])->u_slug() . '/' . v($m[2])->u_slug();

        $this->setLink("/manual/$urlDir/$urlStem", $label);
    }

    function initHelpLinkForSpecialCases() {

        if (preg_match('/SQLSTATE.*authentication method unknown to the client/i', $this->error['message'])) {
            $this->setLink('https://stackoverflow.com/questions/52364415/php-with-mysql-8-0-error-the-server-requested-authentication-method-unknown-to',
                'Stackoverflow Solution'
            );
        }
        else if ($this->error['origin'] == 'tht.compiler.parser.formatChecker') {
            $this->setLink('/reference/format-checker', 'Format Checker');
        }
    }

    function getSignature() {
        return $this->signature;
    }

    function getStdLibSignature($fullCall) {

        // Split module and method
        // e.g. Math.abs = [Math, abs]
        $m = explode('.', $fullCall, 2);
        if (count($m) != 2) {
            return '';
        }

        $module = $m[0];
        $method = $m[1];

        // Method data from tht.dev/manual?main=allMethods&asData=true
        $rawJson = file_get_contents(__DIR__ . '/../../../data/stdLibMethods.json');
        $package = json_decode($rawJson, true);

        if (isset($package[$module])) {

            $p = $package[$module];

            if (isset($p[$method])) {
                $sig = $p[$method];

                // Just return the arguments.
                preg_match('/(\(.*?\))/', $sig, $m);

                return $m[1] == '()' ? '' : $m[1];
            }
        }

        return '';
    }
}