<?php

namespace o;

class u_Request extends StdModule {

    private $userAgent = null;
    private $url = null;

    function u_ip($allIps=false) {
        ARGS('f', func_get_args());

        $ip = Tht::getPhpGlobal('server', 'REMOTE_ADDR');
        $ips = preg_split('/\s*,\s*/', $ip);

        if ($allIps) {
            return OList::create($ips);
        } else {
            return $ips[0];
        }
    }

    // TODO: support proxies (via HTTP_X_FORWARDED_PROTO?)
    function u_is_https () {
        ARGS('', func_get_args());
        $https = Tht::getPhpGlobal('server', 'HTTPS');
        $port = Tht::getPhpGlobal('server', 'SERVER_PORT');

        return (!empty($https) && $https !== 'off') || intval($port) === 443;
    }

    function u_user_agent() {
        ARGS('', func_get_args());
        if ($this->userAgent) {
            return $this->userAgent;
        }
        $rawUa = Tht::getPhpGlobal('server', 'HTTP_USER_AGENT');
        $ua = $this->parseUserAgent($rawUa);
        $this->userAgent = OMap::create($ua);
        return $this->userAgent;
    }

    function u_method() {
        ARGS('', func_get_args());
        $method = strtolower(Tht::getPhpGlobal('server', 'REQUEST_METHOD'));
        return $method;
    }

    function u_referrer() {
        ARGS('', func_get_args());
        return Tht::getPhpGlobal('headers', 'referrer');
    }

    // THANKS: http://www.thefutureoftheweb.com/blog/use-accept-language-header
    function u_languages () {
        ARGS('', func_get_args());

        $langs = [];
        $acceptLang = strtolower(Tht::getPhpGlobal('server', 'HTTP_ACCEPT_LANGUAGE'));
        if ($acceptLang) {
            preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/', $acceptLang, $matches);
            if (count($matches[1])) {
                $langs = array_combine($matches[1], $matches[4]);
                foreach ($langs as $lang => $val) {
                    if ($val === '') $langs[$lang] = 1;
                }
                arsort($langs, SORT_NUMERIC);
            }
        }
        $langKeys = array_keys($langs);

        return OList::create($langKeys);
    }

    function u_is_ajax () {
        ARGS('', func_get_args());
        $requestedWith = Tht::getWebRequestHeader('x-requested-with');
        return (strtolower($requestedWith) === 'xmlhttprequest');
    }

    function u_headers() {
        ARGS('', func_get_args());
        $headers = Tht::getPhpGlobal('headers', '*');
        return OMap::create($headers);
    }

    function u_url() {
        ARGS('', func_get_args());

        if ($this->url) {
            return $this->url;
        }

        $isHttps = $this->u_is_https();

        $relativeUrl = $this->relativeUrl();
        $scheme = $isHttps ? 'https' : 'http';
        $hostWithPort = Tht::getPhpGlobal('server', 'HTTP_HOST');
        $fullUrl = $scheme . '://' . $hostWithPort . $relativeUrl;

        $lUrl = new UrlTypeString($fullUrl);

        $this->url = $lUrl;
        return $this->url;
    }


    // UTILS
    // ======================================


    function relativeUrl() {
        $path = Tht::getPhpGlobal('server', "REQUEST_URI");  // SCRIPT_URL
        if (!$path) {
            // Look for PHP dev server path instead.
            $path = Tht::getPhpGlobal('server', "SCRIPT_NAME");
            if (!$path) {
                Tht::configError("Unable to determine route path.  Only Apache and PHP dev server are supported.");
            }
        }
        return $path;
    }

    // Very basic parsing to get browser & OS.
    // Not really concerned with version numbers, etc.
    function parseUserAgent($rawUa) {

        $ua = strtolower($rawUa);

        // Operating System
        $os = 'other';
        if (preg_match('/\b(ipad|ipod|iphone)\b/', $ua)) {
            $os = 'ios';
        }
        else if (strpos($ua, 'android') !== false) {
            $os = 'android';
        }
        else if (strpos($ua, 'linux') !== false) {
            $os = 'linux';
        }
        else if (strpos($ua, 'macintosh') !== false) {
            $os = 'mac';
        }
        else if (strpos($ua, 'windows') !== false) {
            $os = 'windows';
        }

        // Browser
        // (order matters because browsers often include Safari & Chrome)
        $browser = 'other';
        if (strpos($ua, 'trident') !== false) {
            $browser = 'ie';
        }
        else if (strpos($ua, 'firefox') !== false) {
            $browser = 'firefox';
        }
        else if (preg_match('/\bedge\b/', $ua)) {
            $browser = 'edge';
        }
        else if (strpos($ua, 'chrome') !== false) {
            $browser = 'chrome';
        }
        else if (strpos($ua, 'safari') !== false) {
            $browser = 'safari';
        }

        $out = [
            'full' => trim($rawUa),
            'os' => $os,
            'browser' => $browser,
        ];

        return $out;
    }
}
