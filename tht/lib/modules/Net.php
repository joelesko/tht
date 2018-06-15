<?php

namespace o;


class u_Net extends StdModule {

	function u_http_get($lUrl, $headers=[]) {

        ARGS('*mm', func_get_args());
    	return $this->request('httpGet', 'GET', $lUrl, '', $headers);
    }

    function u_http_post($lUrl, $postData, $headers=[]) {

        ARGS('**m', func_get_args());
    	return $this->request('httpPost', 'POST', $lUrl, $postData, $headers);
    }

    function u_http_request($method, $lUrl, $postData='', $headers=[]) {

    	ARGS('s**m', func_get_args());
    	return $this->request('httpRequest', $method, $lUrl, $postData, $headers);
    }

	private function formatHeaders($headersMap) {
		$sHeaders = '';
        foreach ($headersMap as $k => $v) {
            $sHeaders .= "$k: $v\r\n";
        }
        return $sHeaders;
	} 

    private function request($functionName, $method, $lUrl, $postData, $headers) {

    	if (!OLockString::isa($lUrl)) {
    		Tht::error('`' . $functionName . "()` expects a URL as a LockString. Ex: `L'...'`");
    	}
    	$url = OLockString::getUnlocked($lUrl);
        if (!preg_match('/http(s?):\/\//i', $url)) {
			Tht::error('`' . $functionName . '()` expects a URL starting with `http://` or `https://`');
		}

		$method = strtoupper($method);
		$headers = uv($headers);

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
            $postData = http_build_query(uv($postData));
        }

        $opts = [
            'http' => [
                'method' => $method,
                'header' => $this->formatHeaders($headers),
                'content' => $postData,
            ]
        ];
        $context = stream_context_create($opts);

        Tht::module('Perf')->u_start('Net.httpRequest', $method . ' ' . $url);
        // TODO: unable to wrap this in a try catch, or use @supression
        $responseText = file_get_contents($url, false, $context);
        Tht::module('Perf')->u_stop();

        return $responseText;
    }
}