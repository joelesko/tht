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

        $mod = '';
        $fun = '';

        if ($m) {
            // TODO: Is this necessary? Maybe just rely entirely on stack trace.
            $mod = $m[1];
            $fun = $m[2];
        }
        else if ($this->error['trace']) {
            // Find most recent stdlib call in stack trace
            foreach ($this->error['trace'] as $frame) {
                // Only look for user-facing functions
                if (hasu_($frame['function'])) {
                    $mod = ErrorTextUtils::cleanVars($frame['class']);
                    $fun = ErrorTextUtils::cleanVars($frame['function']);
                    break;
                }
            }
        }

        if (!$mod) { return; }

        // Checking class first because of modules like String that are also classes.
        // TODO: Disambiguate String class & module
        if (StdLibClasses::isa('O' . $mod)) {
            $urlDir = 'class';
        }
        else if (StdLibModules::isa($mod)) {
            $urlDir = 'module';
        }
        else {
            return;
        }

        $label = $mod . '.' . $fun;
        $urlStem = v($mod)->u_to_token_case('-') . '/' . v($fun)->u_to_token_case('-');

        if ($mod == 'Bare') { return; }

        $this->setLink("/manual/$urlDir/$urlStem", $label);
    }

    function initHelpLinkForSpecialCases() {

        if (preg_match('/SQLSTATE.*authentication method unknown to the client/i', $this->error['message'])) {
            $this->setLink('https://stackoverflow.com/questions/52364415/php-with-mysql-8-0-error-the-server-requested-authentication-method-unknown-to',
                'Stackoverflow Solution'
            );
        }
        else if (v($this->error['origin'])->u_contains('.formatChecker')) {

            $url = '/reference/format-checker';
            $label = 'Format Checker';
            if (preg_match('/formatChecker\.(\w+)$/', $this->error['origin'], $m)) {
                $token = v($m[1])->u_to_token_case(' ');
                $url .= '#' . v($token)->u_to_url_slug();
                $label .= ' - ' . v($token)->u_to_title_case();
            }

            $this->setLink($url, $label);
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
        $rawJson = file_get_contents(Tht::systemPath('lib/core/data/stdLibMethods.json'));
        $package = Security::jsonDecode($rawJson);

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