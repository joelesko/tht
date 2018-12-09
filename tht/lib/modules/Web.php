<?php

namespace o;

class u_Web extends StdModule {

    private $jsData = [];
    
    private $request;
    private $isCrossOrigin = null;
    private $includedFormJs = false;


    // REQUEST
    // --------------------------------------------

    function u_request_headers () {
        return OMap::create(Tht::data('requestHeaders'));
    }

    function u_request_header ($val) {
        return Tht::data('requestHeaders', $val);
    }

    function u_request () {

        if (!$this->request) {

            $ip = Tht::getPhpGlobal('server', 'REMOTE_ADDR');
            $ips = preg_split('/\s*,\s*/', $ip);

            $r = [
                'ip'          => $ips[0],
                'ips'         => $ips,
                'isHttps'     => $this->isHttps(),
                'userAgent'   => Tht::getPhpGlobal('server', 'HTTP_USER_AGENT'),
                'method'      => strtolower(Tht::getPhpGlobal('server', 'REQUEST_METHOD')),
                'referrer'    => Tht::getPhpGlobal('server', 'HTTP_REFERER', ''),
                'languages'   => $this->languages(),
                'isAjax'      => $this->isAjax(),
                'headers'     => OMap::create(WebMode::getWebRequestHeaders()),
            ];

            $relativeUrl = $this->relativeUrl();
            $scheme = $r['isHttps'] ? 'https' : 'http';
            $hostWithPort = Tht::getPhpGlobal('server', 'HTTP_HOST');
            $fullUrl = $scheme . '://' . $hostWithPort . $relativeUrl;

            $r['url'] = $this->u_parse_url($fullUrl);

            $fullUrl = rtrim($fullUrl, '/');
            $r['url']['full'] = $fullUrl;
            $r['url']['relative'] = $relativeUrl;

            $urlParts = explode('/', $r['url']['path']);
            $r['url']['page'] = end($urlParts);

            $this->request = OMap::create($r);
        }

        return $this->request;
    }

    // TODO: support proxies (via HTTP_X_FORWARDED_PROTO?)
    function isHttps () {
        $https = Tht::getPhpGlobal('server', 'HTTPS', '');
        $port = Tht::getPhpGlobal('server', 'SERVER_PORT');

		return (!empty($https) && $https !== 'off') || intval($port) === 443;
    }

    function isAjax () {
        $requestedWith = WebMode::getWebRequestHeader('x-requested-with');
        return (strtolower($requestedWith) === 'xmlhttprequest');
        
    }

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

    // THANKS: http://www.thefutureoftheweb.com/blog/use-accept-language-header
    function languages () {
        $langs = [];
        $acceptLang = strtolower(Tht::getPhpGlobal('server', 'HTTP_ACCEPT_LANGUAGE', ''));
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
        return array_keys($langs);
    }







    // QUERY / URL PARSING
    // --------------------------------------------

    function u_parse_url ($url) {
        ARGS('s', func_get_args());
        return OMap::create(parse_url($url));
    }

    function u_unparse_url($u) {
        ARGS('s', func_get_args());

        $u = uv($u);
        $parts = [
            $this->unparseVal($u, 'scheme',   '__://'),
            $this->unparseVal($u, 'host',     '__'),
            $this->unparseVal($u, 'port',     ':__'),
            $this->unparseVal($u, 'path',     '__'),
            $this->unparseVal($u, 'query',    '?__'),
            $this->unparseVal($u, 'fragment', '#__'),
        ];
        return implode('', $parts);
    }

    function unparseVal($u, $k, $template) {
        if (!isset($u[$k])) { return ''; }
        return str_replace('__', $u[$k], $template);
    }


    function u_parse_query ($s, $multiKeys=[]) {

        ARGS('sl', func_get_args());

        $ary = [];
        $pairs = explode('&', $s);

        foreach ($pairs as $i) {
            list($name, $value) = explode('=', $i, 2);
            if (in_array($name, $multiKeys)) {
                if (!array_key_exists($ary[$name])) {
                    $ary[$name] = [];
                }
                $ary[$name] []= $value;
            }
            else {
                $ary[$name] = $value;
            }
        }
        return OMap::create($ary);
    }

    function query ($params) {
        return http_build_query($params);
    }





	// RESPONSE
    // --------------------------------------------

    function u_redirect ($url, $code=303) {
        ARGS('*n', func_get_args());

        if (OLockString::isa($url)) {
            $url = $url->u_unlocked();
        } else {
            if (v($url)->u_is_url()) {
                Tht::error("Redirect URL `$url` must be relative or a LockString.");
            }
        }

        header('Location: ' . $url, true, $code);
        exit();
    }

    function u_set_response_code ($code) {
        ARGS('n', func_get_args());
        http_response_code($code);
    }

    function u_set_header ($name, $value, $multiple=false) {
        ARGS('ssf', func_get_args());

        header($name . ': ' . $value, !$multiple);
    }

    function u_set_cache_header ($expiry='+1 year') {
        ARGS('s', func_get_args());

        $this->u_set_header('Expires', gmdate('D, d M Y H:i:s \G\M\T', strtotime($expiry)));
    }

    function u_nonce () {
        return Security::getNonce();
    }

    function u_csrf_token() {
        return Security::getCsrfToken();
    }

    // SEND DOCUMENTS
    // --------------------------------------------

    // function u_print_block($h, $title='') {
    //     $html = OLockString::getUnlocked($h);
    //     $this->u_send_json([
    //         'status' => 'ok',
    //         'title' => $title,
    //         'html' => $html
    //     ]);
    // }

    function output($out) {
        print $out;
    }

    function sendByType($lout) {
        $type = $lout->u_get_string_type();

        if ($type == 'css') {
            return $this->u_return_css($lout);
        }
        else if ($type == 'js') {
            return $this->u_return_js($lout);
        }
    }

    function renderChunks() {

        // Normalize. Could be a single LocKString, OList, or a PHP array
        if (! (is_object($chunks) && v($chunks)->u_is_list())) {
            $chunks = OList::create([ $chunks ]); 
        }

        $out = '';
        foreach ($chunks->val as $c) {
            $out .= OLockString::getUnlocked($c);
        }
        return $out;
    }

    function u_send_json ($map) {
        ARGS('m', func_get_args());

        $this->u_set_header('Content-Type', 'application/json');
        $this->output(json_encode(uv($map)));
    }

    function u_send_text ($text) {
        ARGS('s', func_get_args());

        $this->u_set_header('Content-Type', 'text/plain');

        $this->output($text);
    }

    function u_send_css ($chunks) {

        ARGS('*', func_get_args());

        $this->u_set_header('Content-Type', 'text/css');
        $this->u_set_cache_header();

        return $this->renderChunks($chunks);
    }

    function u_send_js ($chunks) {

        ARGS('*', func_get_args());

        $this->u_set_header('Content-Type', 'application/javascript');
        $this->u_set_cache_header();

        $out = "(function(){\n";
        $out .= $this->renderChunks($chunks);
        $out .= "\n})();";

        $this->output($out);
    }

    function u_send_html ($html) {
        $html = OLockString::getUnlocked($html);  
        $this->output($html);
    }

    function u_danger_danger_send ($s) {
        ARGS('s', func_get_args());
        print $s;
    }

    // Print a well-formed HTML document with sensible defaults
    function u_send_page ($doc) {

        ARGS('m', func_get_args());

        $body = '';

        if ($doc['body']) {
            $chunks = [];
            if (OList::isa($doc['body'])) {
                $chunks = $doc['body'];
            } else {
                $chunks = [$doc['body']];
            }

            foreach ($chunks as $c) {
                $body .= OLockString::getUnlocked($c);
            }
        }

        // if (u_Web::u_is_ajax()) {
        //     u_Web::u_send_block($body, $header['title']);
        //     return;
        // }

        $css = $this->assetTags(
            'css',
            $doc['css'],
            '<link rel="stylesheet" href="{URL}" />',
            '<style nonce="{NONCE}">{BODY}</style>'
        );

        $js = $this->assetTags(
            'js',
            $doc['js'],
            '<script src="{URL}" nonce="{NONCE}"></script>',
            '<script nonce="{NONCE}">{BODY}</script>'
        );

        $title = $doc['title'];
        $description = isset($doc['description']) ?: '';

        // TODO: get updateTime of the files
        // TODO: allow base64 urls
        // $cacheTag = '?cache=' . Source::getAppCompileTime();
        $cacheTag = '';

        $image = isset($doc['image']) ? '<meta property="og:image" content="'. $doc['image'] . $cacheTag .'">' : "";
        $icon = isset($doc['icon']) ? '<link rel="icon" href="'. $doc['icon'] . $cacheTag .'">' : "";
        // $jsData = Tht::module('Js')->serializeData();

        $bodyClasses = uv($doc['bodyClasses']) ?: [];
        $bodyClass = implode(' ', $bodyClasses);
        $bodyClass = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $bodyClass); // TODO: call a lib to untaint instead

        

        $comment = '';
        if (isset($doc['comment'])) {
            $comment = "<!--\n\n" . v(v(v($doc['comment'])->u_unlocked())->u_indent(4))->u_trim_right() . "\n\n-->";
        }

        $out = <<<HTML
<!doctype html>
$comment
<html>
    <head>
        <title>$title</title>
        <meta name="description" content="$description"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <meta property="og:title" content="$title"/>
        <meta property="og:description" content="$description"/>
        $image $icon $css
    </head>
    <body class="$bodyClass">
        $body
        $js
    </body>
</html>
HTML;

        $this->sendGzip($out);
     
       
        $cacheTag = defined('STATIC_CACHE_TAG') ? constant('STATIC_CACHE_TAG') : '';
        if ($cacheTag && isset($doc['staticCache']) && $doc['staticCache']) {
            $cacheFile = md5(Tht::module('Web')->u_request()['url']['relative']);
            $cachePath = Tht::path('cache', 'html/' . $cacheTag . '_' . $cacheFile . '.html');
            file_put_contents($cachePath, $out);
        }

    }

    function u_send_error ($code, $title='') {

        ARGS('ns', func_get_args());

        http_response_code($code);

        if ($code !== 500) {
            // User custom error page
            WebMode::runStaticRoute($code);
        }

        // User custom error page
        // $errorPage = Tht::module('File')->u_document_path($code . '.html');
        // if (file_exists($errorPage)) {
        //     print(file_get_contents($errorPage));
        //     exit(1);
        // }

        if (!$this->u_request()['isAjax']) {

            if (!$title) {
                $title = $code === 404 ? 'Page Not Found' : 'Website Error';
            }

            ?><html><head><title><?= $title ?></title></head><body>
            <div style="text-align: center; font-family: <?= u_Css::u_sans_serif_font() ?>;">
            <h1 style="margin-top: 40px;"><?= $title ?></h1>
            <div style="margin-top: 40px"><a style="text-decoration: none; font-size: 20px;" href="/">Home Page</a></div></div>
            </body></html><?php
        }

        exit(1);
    }

    // print css & js tags
    function assetTags ($type, $paths, $incTag, $blockTag) {

        $paths = uv($paths);

        if (!$paths) { return ''; }

        if (!is_array($paths)) {
            $paths = [$paths];
        }

        $nonce = Tht::module('Web')->u_nonce();

        $includes = [];
        $blocks = [];
        foreach ($paths as $path) {
            if (OLockString::isa($path)) {
                // Inline it in the HTML document
                $str = OLockString::getUnlocked($path);
                $tag = str_replace('{BODY}', $str, $blockTag);
                $tag = str_replace('{NONCE}', $nonce, $tag);
                $blocks []= $tag;
            }
            else {

                if (preg_match('/^http(s?):/i', $path)) {
                    
                } else {
                    // Link to asset, with cache time set to file modtime
                    $basePath = preg_replace('/\?.*/', '', $path);
                    if (defined('BASE_URL')) {
                        $basePath = preg_replace('#' . BASE_URL . '#', '', $basePath);
                    }
                    $filePath = Tht::getThtFileName(Tht::path('pages', $basePath));
                    $cacheTag = strpos($path, '?') === false ? '?' : '&';
                    $cacheTag .= 'cache=' . filemtime($filePath);
                    $path .= $cacheTag;
                }

                $tag = str_replace('{URL}', $path, $incTag);
                $tag = str_replace('{NONCE}', $nonce, $tag);
                $includes []= $tag;
            }
        }

        $sIncludes = implode("\n", $includes);
        $sBlocks = implode("\n\n", $blocks);

        return $sIncludes . "\n" . $sBlocks;
    }



    // TODO: Removed for now.  Letting webserver handle this.

    // GZIP
    // --------------------------------------------

    // function sendGzip ($xOut) {
    //     $out = OLockString::getUnlocked($xOut);
    //     $this->startGzip(true);
    //     print $out;
    //     $this->endGzip(true);
    //     flush();
    // }

    // function startGzip ($forceGzip=false) {
    //     if ($forceGzip || Tht::getConfig('compressOutput')) {
    //         ob_start("ob_gzhandler");
    //     }
    // }

    // function endGzip ($forceGzip=false) {
    //     if ($forceGzip || Tht::getConfig('compressOutput')) {
    //         ob_end_flush();
    //     }
    // }





    // MARKUP
    // --------------------------------------------

    function u_parse_html($raw) {
        return Tht::parseTemplateString('html', $raw);
    }

    function u_table ($rows, $keys, $headings=[], $class='') {
        $class = preg_replace('/[^a-zA-Z0-9_-]/', '', $class);  // [security]

        $str = "<table class='$class'>\n";
        $rows = uv($rows);
        $keys = uv($keys);
        $headings = uv($headings);
        $str .= '<tr>';
        foreach ($headings as $h) {
            $str .= '<th>' . $h . '</th>';
        }
        $str .= '</tr>';
        foreach ($rows as $row) {
            $str .= '<tr>';
            $row = uv($row);
            foreach ($keys as $k) {
                $str .= '<td>' . (isset($row[$k]) ? $row[$k] : '') . '</td>';
            }
            $str .= '</tr>';
        }
        $str .= "</table>\n";

        return new \o\HtmlLockString ($str);
    }

    function u_link($label, $url) {
        if ($label === '') {
            $label = $url;
        }
        $url   = v($url)->u_encode_html();
        $label = v($label)->u_encode_html();

        $str = "<a href=\"$url\">$label</a>";
        return new \o\HtmlLockString ($str);
    }

    function u_breadcrumbs($links, $joiner = '&gt;') {
        $aLinks = [];
        foreach ($links as $l) {
            $aLinks []= Tht::module('Web')->u_link($l['label'], $l['url'])->u_unlocked();
        }

        $joiner = '<span class="breadcrumbs-joiner">' . v($joiner)->u_unlocked() . '</span>';
        $h = implode($aLinks, $joiner);
        $h = "<div class='breadcrumbs'>$h</div>";

        return OLockString::create('\o\HtmlLockString', $h);
    }

    // DO NOT REMOVE
    // function makeStarIcon($centerX, $centerY, $outerRadius, $innerRadius) {
    //     $arms = 5;
    //     $angle = pi() / $arms;
    //     $offset = -0.31;
    //     $points = [];
    //     for ($i = 0; $i < 2 * $arms; $i++) {
    //         $r = ($i & 1) == 0 ? $outerRadius : $innerRadius;
    //         $currX = $centerX + cos(($i * $angle) + $offset) * $r;
    //         $currY = $centerY + sin(($i * $angle) + $offset) * $r;
    //         $points []= number_format($currX,2) . "," . number_format($currY, 2);
    //     }
    //     return implode(' ', $points);
    // }

    function icons() {

        // TODO: mail, cart
        return [

            'arrowLeft'  => '<path d="M30,50H90z"/><polyline points="60,10 20,50 60,90"/>',
            'arrowRight' => '<path d="M10,50H70z"/><polyline points="40,10 80,50 40,90"/>',
            'arrowUp'    => '<path d="M50,30V90z"/><polyline points="10,60 50,20 90,60"/>',
            'arrowDown'  => '<path d="M50,10V70z"/><polyline points="10,40 50,80 90,40"/>',

            'chevronLeft'  => '<polyline points="70,10 30,50 70,90"/>',
            'chevronRight' => '<polyline points="30,10 70,50 30,90"/>',
            'chevronUp'    => '<polyline points="10,70 50,30 90,70"/>',
            'chevronDown'  => '<polyline points="10,30 50,70 90,30"/>',

            'wideChevronLeft'  => '<polyline points="60,-5 30,50 60,105"/>',
            'wideChevronRight' => '<polyline points="40,-5 70,50 40,100"/>',
            'wideChevronUp'    => '<polyline points="-5,60 50,30 105,60"/>',
            'wideChevronDown'  => '<polyline points="-5,40 50,70 105,40"/>',

            'caretLeft'   => '<path class="svgfill" d="M60,20 30,50 60,80z"/>',
            'caretRight'  => '<path class="svgfill" d="M40,20 70,50 40,80z"/>',
            'caretUp'     => '<path class="svgfill" d="M20,60 50,30 80,60z"/>',
            'caretDown'   => '<path class="svgfill" d="M20,40 50,70 80,40z"/>',

            'menu'         => '<path d="M0,20H100zM0,50H100zM0,80H100z"/>',
            'plus'         => '<path d="M15,50H85zM50,15V85z"/>',
            'minus'        => '<path d="M15,50H85z"/>',
            'cancel'       => '<path d="M20,20 80,80z M80,20 20,80z"/>',
            'check'        => '<polyline points="15,45 40,70 85,15"/>',

            'home'   => '<path class="svgfill" d="M0,50 50,15 100,50z"/><rect class="svgfill" x="15" y="50" height="40" width="25" /><rect class="svgfill" x="60" y="50" height="40" width="25" /><rect class="svgfill" x="40" y="50" height="15" width="40" /><rect class="svgfill" x="70" y="20" height="20" width="15" />',
            'download' => '<path class="svgfill" d="M10,40 50,75 90,40z"/><rect class="svgfill" x="35" y="0" height="42" width="30" /><rect class="svgfill" x="0" y="88" height="12" width="100" />',
            'upload'   => '<path class="svgfill" d="M10,35 50,0 90,35z"/><rect class="svgfill" x="35" y="33" height="40" width="30" /><rect class="svgfill" x="0" y="88" height="12" width="100" />',
            'search'    => '<circle cx="45" cy="45" r="30"/><path d="M95,95 65,65z"/>',
            'lock'      => '<rect class="svgfill" x="15" y="35" height="50" width="70" rx="5" rx="5" /><rect style="stroke-width:12" x="31" y="7" height="50" width="38" rx="15" rx="15" />',
            'heart'     => '<path class="svgfill" d="M90,45 50,85 10,45z"/><rect class="svgfill" x="48" y="43" height="4" width="4"/><circle class="svgfill" cx="29" cy="31" r="23"/><circle class="svgfill" cx="71" cy="31" r="23"/>',

            // generated from $this->starIcon(50,50,55,22)
            'star'     => '<path class="svgfill" d="M102.38,33.22 70.89,56.89 82.14,94.63 49.91,72.00 17.49,94.36 29.05,56.71 -2.24,32.79 37.14,32.15 50.23,-5.00 63.01,32.26z"/>',

            'twitter' => '<svg class="ticonx" viewBox="0 0 33 33"><g><path d="M 32,6.076c-1.177,0.522-2.443,0.875-3.771,1.034c 1.355-0.813, 2.396-2.099, 2.887-3.632 c-1.269,0.752-2.674,1.299-4.169,1.593c-1.198-1.276-2.904-2.073-4.792-2.073c-3.626,0-6.565,2.939-6.565,6.565 c0,0.515, 0.058,1.016, 0.17,1.496c-5.456-0.274-10.294-2.888-13.532-6.86c-0.565,0.97-0.889,2.097-0.889,3.301 c0,2.278, 1.159,4.287, 2.921,5.465c-1.076-0.034-2.088-0.329-2.974-0.821c-0.001,0.027-0.001,0.055-0.001,0.083 c0,3.181, 2.263,5.834, 5.266,6.438c-0.551,0.15-1.131,0.23-1.73,0.23c-0.423,0-0.834-0.041-1.235-0.118 c 0.836,2.608, 3.26,4.506, 6.133,4.559c-2.247,1.761-5.078,2.81-8.154,2.81c-0.53,0-1.052-0.031-1.566-0.092 c 2.905,1.863, 6.356,2.95, 10.064,2.95c 12.076,0, 18.679-10.004, 18.679-18.68c0-0.285-0.006-0.568-0.019-0.849 C 30.007,8.548, 31.12,7.392, 32,6.076z"></path></g></svg>',

            'facebook' => '<svg class="ticonx" viewBox="0 0 33 33"><g><path d="M 17.996,32L 12,32 L 12,16 l-4,0 l0-5.514 l 4-0.002l-0.006-3.248C 11.993,2.737, 13.213,0, 18.512,0l 4.412,0 l0,5.515 l-2.757,0 c-2.063,0-2.163,0.77-2.163,2.209l-0.008,2.76l 4.959,0 l-0.585,5.514L 18,16L 17.996,32z"></path></g></svg>',

        ];
    }

    function u_get_icons() {
        return array_keys($this->icons());
    }

    function u_icon($id) {

        $icons = $this->icons();

        if (!isset($icons[$id])) { Tht::error("Unknown icon: `$id`"); }

        if (substr($icons[$id], 0, 4) == '<svg') {
            return new \o\HtmlLockString($icons[$id]);
        }

        return new \o\HtmlLockString('<svg class="ticon" viewBox="0 0 100 100">' . $icons[$id] . '</svg>');
    }



    function u_mask_email($email) {

        // TODO: show placeholder if not logged in user
        // if (!Tht::module('User')->u_logged_in() && $placeholder) {
        //     return $placeholder;
        // }

        $spanPos = rand(1, strlen($email) - 5);
        $begin = substr($email, 0, $spanPos);
        $end = substr($email, $spanPos);

        $r = strtolower(Tht::module('String')->u_random(rand(6,12)));
        $r = preg_replace('/[^a-z]/', '', $r);

        $r2 = strtolower(Tht::module('String')->u_random(rand(6,12)));
        $r2 = preg_replace('/[^a-z]/', '', $r2);

        $e = Tht::module('String')->u_random(rand(5,80));
        $e = preg_replace('/[0-9xz\/\+]+/', ' ', $e);

        $xe = $begin . "<span class=\"$r\">$e</span><span class=\"$r2\">" . $end . "</span>";
        $xe .= "<style> .$r { display: none; } </style>";

        return new HtmlLockString ($xe);
    }


    function u_route_param ($key) {
        return WebMode::getWebRouteParam($key);
    }




    // USER INPUT
    // --------------------------------------------


    function u_form ($schema, $formId='defaultForm') {
        return new u_Form ($schema, $formId);
    }

    // function u_query($name, $sRules='id') {

    //     ARGS('ss', func_get_args());

    //     $rawVal = trim(Tht::getPhpGlobal('get', $name, false));

    //     $validated = $this->u_temp_validate_input($val, $type);

    //     return $validated['value'];
    // }

    function u_temp_get_input($method, $name, $type='id') {

        ARGS('sss', func_get_args());

        if (strpos('get|post|dangerDangerRemote', $method) === false) {
            Tht::error("Invalid input method: `$method`.  Supported methods: `get`, `post`, `dangerDangerRemote`");
        }

        // Disallow cross-origin request
        if ($method === 'post') {
            if (Security::isCrossOrigin()) {
                Tht::module('Web')->u_send_error(403, 'Remote Origin Not Allowed');
            }
        }
        if ($method == 'dangerDangerRemote') {
            $method = 'post';
        }

        $val = trim(Tht::getPhpGlobal($method, $name, false));
        $validated = $this->u_temp_validate_input($val, $type);

        return $validated['value'];
    }

    function u_temp_validate_input($val, $type='id') {

        $isFail = false;
        if ($type === 'id') {
            if (preg_match('/[^a-zA-Z0-9_]/', $val)) {
                $isFail = true;
            }
            if (strlen($val) > 64) {
                $isFail = true;
            }
        }
        else if ($type === 'number') {
            $val = preg_replace("/[',]/", '', $val);
            if (preg_match('/[^0-9]/', $val)) {
                $isFail = true;
            }
            if (strlen($val) > 8) {
                $isFail = true;
            }
            $val = intval($val);
        }
        else if ($type === 'numberAny') {
            $val = preg_replace("/[',]/", '', $val);
            if (preg_match('/[^0-9\.\-]/', $val)) {
                $isFail = true;
            }
            $val = strpos($val, '.') !== false ? floatval($val) : intval($val);
        }
        else if ($type === 'flag') {
            if ($val !== 'true' && $val !== 'false' && $val !== '1' && $val !== '0') {
                $isFail = true;
            }
            $val = ($val === 'true' || $val === '1');
        }
        else if ($type === 'email') {
            if (!preg_match('/^\S+?@[^@\s]+\.\S+$/', $val)) {
                $isFail = true;
            }
            if (strlen($val) > 80) {
                $isFail = true;
            }
        }
        else if ($type === 'text') {
            // one line of text
            $val = preg_replace('/\s+/', ' ', $val);
            $val = preg_replace('/<.*?>/', '', $val);
            if (strlen($val) > 120) {
                $isFail = true;
            }
        }
        else if ($type === 'textarea') {
            // multiline text
            $val = preg_replace('/<.*?>/', '', $val);
            $val = preg_replace('/ +/', ' ', $val);
            $val = preg_replace('/\n{2,}/', "\n\n", $val);
            $val = trim($val);
        }
        else if ($type === 'dangerDangerRaw') {
            // pass through as-is
        }
        else {
            Tht::error("Unknown input type: `$type`");
        }
        return OMap::create([
            'value' => $isFail ? '' : $val,
            'ok' => !$isFail
        ]);
    }

}



