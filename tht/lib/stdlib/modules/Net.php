<?php

namespace o;

// TODO:  Probably replace fopen with cUrl, but that means including (or dynamically fetching)
// the large-ish mozilla ca cert ca bundle.

// Required for `file_get_contents`

if (!ini_get('allow_url_fopen')) {
    $msg = "To use the Net module, the php.ini setting `allow_url_fopen` must be set to `On`.";
    Tht::phpIniError($msg);
}

class u_Net extends OStdModule {

    private $timeoutSecs = -1;
    private $lastErrorMessage = '';

    function u_set_timeout_secs($secs) {
        $this->ARGS('n', func_get_args());

        $this->timeoutSecs = $secs;

        return NULL_NORETURN;
    }

    function u_last_error() {

        $this->ARGS('', func_get_args());

        return $this->lastErrorMessage;
    }

    function u_http_status($lUrl, $headers=[]) {

        $this->ARGS('*m', func_get_args());

        $url = OTypeString::getUntyped($lUrl, 'url');

        return $this->request('httpStatus', 'HEAD', $url, '', $headers);
    }

    function u_http_head($lUrl, $headers=[]) {

        $this->ARGS('*m', func_get_args());

        $url = OTypeString::getUntyped($lUrl, 'url');

        return $this->request('httpHead', 'HEAD', $url, '', $headers);
    }

    function u_http_get($lUrl, $headers=[]) {

        $this->ARGS('*mm', func_get_args());

        $url = OTypeString::getUntyped($lUrl, 'url');

        return $this->request('httpGet', 'GET', $url, '', $headers);
    }

    function u_http_post($lUrl, $postData, $headers=[]) {

        $this->ARGS('**m', func_get_args());

        $url = OTypeString::getUntyped($lUrl, 'url');

        return $this->request('httpPost', 'POST', $url, $postData, $headers);
    }

    function u_http_request($method, $lUrl, $postData='', $headers=[]) {

        $this->ARGS('s**m', func_get_args());

        $url = OTypeString::getUntyped($lUrl, 'url');

        return $this->request('httpRequest', $method, $url, $postData, $headers);
    }

    private function formatHeaders($headersMap) {
        $sHeaders = '';
        foreach ($headersMap as $k => $v) {
            $sHeaders .= "$k: $v\r\n";
        }
        return $sHeaders;
    }

    private function headerListToMap($headersList) {

        $headerMap = OMap::create([
            'raw' => OList::create($headersList),
            'status' => 0,
        ]);

        foreach ($headersList as $headerLine) {
            if (preg_match('/^HTTP.* (\d{3})/i', $headerLine, $m)) {
                $headerMap['status'] = intval($m[1]);
            }
            else {
                $parts = preg_split('/:\s+/', $headerLine, 2);
                if (count($parts) == 2) {
                    $key = v($parts[0])->u_to_token_case('camel');
                    $val = trim($parts[1]);
                    if (preg_match('/^\d+$/', $val)) { $val = intval($val); }
                    if (preg_match('/GMT$/', $val)) { $val = Tht::module('Date')->u_create($val); }

                    $headerMap[$key] = $val;
                }
            }
        }
        return $headerMap;
    }

    private function request($functionName, $method, $url, $postData, $headers) {

        Tht::module('Meta')->u_fail_if_template_mode();

        if (!preg_match('/http(s?):\/\//i', $url)) {
            Tht::error('Function `' . $functionName . '()` expects a URL starting with `http://` or `https://`');
        }

        $method = strtoupper($method);
        $headers = unv($headers);

        // Add content-type for POST
        if ($method == 'POST') {
            $hasContentType = false;
            foreach ($headers as $k => $v) {
                if (strtolower($k) == 'content-type') {
                    $hasContentType = true;
                    break;
                }
            }
            if (!$hasContentType) {
                $headers['Content-type'] = 'application/x-www-form-urlencoded';
            }
        }

        if (!is_string($postData)) {
            if (!OMap::isa($postData)) {
                Tht::error('`postData` must be a String or a Map.');
            }
            $postData = http_build_query(unv($postData));
        }

        $opts = [
            'http' => [
                'method' => $method,
                'header' => $this->formatHeaders($headers),
                'content' => $postData,
                'timeout' => $this->timeoutSecs,
            ]
        ];
        $context = stream_context_create($opts);


        $perfTask = Tht::module('Perf')->u_start('Net.httpRequest', $method . ' ' . $url);

        $this->lastErrorMessage = '';
        set_error_handler(function($errorNum, $errorMsg) {
            // Ignore Errors
            $this->lastErrorMessage = $errorMsg;
        });

        if ($functionName == 'httpStatus' || $functionName == 'httpHead') {
            $response = get_headers($url, 0, $context);
        }
        else {

            // Note: if we need to do a request-level timeout instead, we can use fsockopen
            // https://www.php.net/manual/en/function.fsockopen.php#34887

            $response = file_get_contents($url, false, $context);
        }

        restore_error_handler();
        $perfTask->u_stop();


        // Handle Response

        if ($functionName == 'httpStatus') {
            if (!$response) {
                return 0;
            }

            $didMatch = preg_match('/http.*?(\d{3})\b/i', $response[0], $m);

            if (!$didMatch || !$m || !$m[1]) {
                return 0;
            }
            return (int)$m[1];
        }
        else if ($functionName == 'httpHead') {
            if (!$response) {
                return OMap::create([
                    'status' => 0,
                ]);
            }
            return $this->headerListToMap($response);
        }
        else if ($response === false) {
            return '';
        }
        else {

            // Interpret as JSON
            if ($response[0] == '{' && $response[strlen($response) - 1] == '}') {
                $response = OMap::create(Security::jsonDecode($response));
            }

            return $response;
        }
    }
}