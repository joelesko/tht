<?php

namespace o;

class u_Output extends OStdModule {

    private $gzipBufferOpen = false;
    public $sentResponseType = '';
    private $didSendPage = false;

    function u_run_route($path) {

        $this->ARGS('s', func_get_args());
        WebMode::runRoute($path);

        return EMPTY_RETURN;
    }

    function u_redirect ($lUrl, $code=303) {

        $this->ARGS('*I', func_get_args());

        $url = OTypeString::getUntyped($lUrl, 'url');

        header('Location: ' . $url, true, $code);

        $this->sentResponseType = 'redirect';

        Tht::exitScript(0);
    }

    function u_set_response_code ($code) {

        $this->ARGS('I', func_get_args());
        http_response_code($code);

        return EMPTY_RETURN;
    }

    function u_set_header ($name, $value, $flags=null) {

        $this->ARGS('ssm', func_get_args());

        if ($this->sentResponseType) {
            $this->error("Can not set response header `$name` because output was already sent.");
        }

        $value = preg_replace('/\s+/', ' ', $value);
        $name = preg_replace('/[^a-z0-9\-]/', '', strtolower($name));
        header($name . ': ' . $value, ($flags && $flags['append']) ? false : true);

        return EMPTY_RETURN;
    }

    function u_set_cache_header ($expiryDelta='365 days') {

        $this->ARGS('s', func_get_args());

        $expiry = Tht::module('Date')->u_now()->u_add($expiryDelta);

        $this->u_set_header('Expires', gmdate('D, d M Y H:i:s \G\M\T', $expiry->u_unix_time()));

        return EMPTY_RETURN;
    }



    // SEND DOCUMENTS
    // --------------------------------------------

    // For HTMX partials
    // function u_print_block($h, $title='') {
    //     $html = OTypeString::getUntyped($h);
    //     $this->u_send_json([
    //         'status' => 'ok',
    //         'title' => $title,
    //         'html' => $html
    //     ]);
    // }

    function output($out, $type) {

        $sentType = $this->sentResponseType;
        if ($sentType && $sentType !== $type) {
            $this->error("Output was already sent with type `$sentType`");
        }
        $this->sentResponseType = $type;

        $this->startGzip();

        print $out;
    }

    function sendByType($out) {

        if ($out === true) {
            // Refresh
            Tht::module('Output')->u_redirect(
                Tht::module('Request')->u_get_url()->u_clear_query()
            );
        }
        else if (HTMLTypeString::isa($out)) {
            $this->u_send_html($out);
        }
        else if (URLTypeString::isa($out)) {
            $this->u_redirect($out);
        }
        else if (OMap::isa($out) || JsonTypeString::isa($out)) {
            $this->u_send_json($out);
        }
        else if (u_Page_Object::isa($out)) {
            $out->u_send();
        }
    }

    function renderChunks($chunks, $type='') {

        // Normalize. Could be a single TypeString, OList, or a PHP array
        if (! (is_object($chunks) && v($chunks)->u_type() == 'list')) {
            $chunks = OList::create([ $chunks ]);
        }

        $out = '';
        foreach ($chunks->val as $c) {
            $chunkOut = $c->u_render_string();
            if ($type == 'css') {
                $chunkOut = Tht::module('Output')->parseIndentCss($chunkOut);
                $chunkOut = Tht::module('Output')->minifyCss($chunkOut);
                $out .= $chunkOut;
            }
            else if ($type == 'js') {
                $chunkOut = Tht::module('Output')->minifyJs($chunkOut);
                $out .= $chunkOut;
            }
        }
        return $out;
    }

    function u_send_json ($jsonOrMap, $expiryDelta=0) {

        $this->ARGS('*i', func_get_args());

        if (OMap::isa($jsonOrMap)) {
            $jsonTypeString = Tht::module('Json')->u_encode($jsonOrMap);
        }
        else if (OTypeString::isa($jsonOrMap, 'json')) {
            $jsonTypeString = $jsonOrMap;
        }
        else {
            $this->argumentError('Argument #1 must be of type: `JsonTypeString` or `Map`');
        }

        $rawJson = $jsonTypeString->u_render_string();

        $this->u_set_header('Content-Type', 'application/json; charset=utf-8');
        $this->u_set_cache_header($expiryDelta);

        $this->output($rawJson, 'json');

        Tht::module('Web')->u_skip_hit_counter(true);

        return EMPTY_RETURN;
    }

    function u_send_text ($text, $expiryDelta=0) {
        $this->ARGS('si', func_get_args());

        $this->u_set_header('Content-Type', 'text/plain; charset=utf-8');
        $this->u_set_cache_header($expiryDelta);

        $this->output($text, 'text');

        return EMPTY_RETURN;
    }

    function u_send_css ($chunks, $expiryDelta=0) {

        $this->ARGS('*i', func_get_args());

        $this->u_set_header('Content-Type', 'text/css; charset=utf-8');
        $this->u_set_cache_header($expiryDelta);

        $out = $this->renderChunks($chunks, 'css');

        $out = $this->scanAssetUrls($out);

        $this->output($out, 'css');

        Tht::module('Web')->u_skip_hit_counter(true);

        return EMPTY_RETURN;
    }

    function u_send_js ($chunks, $expiryDelta=0) {

        $this->ARGS('*i', func_get_args());

        $this->u_set_header('Content-Type', 'application/javascript; charset=utf-8');
        $this->u_set_cache_header($expiryDelta);

        $out = "(function(){\n";
        $out .= $this->renderChunks($chunks, 'js');
        $out .= "\n})();";

        $this->output($out, 'js');

        Tht::module('Web')->u_skip_hit_counter(true);

        return EMPTY_RETURN;
    }

    function u_send_html ($html) {

        $html = OTypeString::getUntyped($html, 'html');

        $html = $this->scanAssetUrls($html);

        $this->output($html, 'html');

        return EMPTY_RETURN;
    }

    function u_send_image ($imageObj) {

        $html = OTypeString::getUntyped($html, 'html');

        $html = $this->scanAssetUrls($html);

        $this->output($html, 'html');

        return EMPTY_RETURN;
    }

    function u_send_page ($page) {

        $this->ARGS('*', func_get_args());

        if (!u_Page_Object::isa($page)) {
            $this->error("First argument must be a Page object. Got: " . get_class($page));
        }

        if ($this->didSendPage) {
            $this->error('Page object was already sent.');
        }
        $this->didSendPage = true;

        $out = new HtmlTypeString ($page->u_to_html());

        $this->u_send_html($out);

        return EMPTY_RETURN;
    }

    function u_x_danger_send ($s) {

        $this->ARGS('s', func_get_args());

        print $s;

        return EMPTY_RETURN;
    }

    function u_send_error ($code, $title='', $descHtml='') {

        $this->ARGS('Is*', func_get_args());

        $desc = $descHtml ? OTypeString::getUntyped($descHtml, 'html') : '';

        http_response_code($code);

        // User custom error page
        WebMode::runStaticRoute($code);

        if (!Tht::module('Request')->u_is_ajax()) {

            if (!$title) {
                $title = $code === 404 ? 'Page Not Found' : 'Website Error';
            }

            ?><!doctype html><html><head><title><?= $title ?></title></head><body style="margin: 0; padding: 0">
            <div style="border-top: solid 1em #ccc; text-align: center; color:#333; font-family: <?= Tht::module('Output')->font('sansSerif') ?>;">
            <h1 style="margin-top: 1em;"><?= $title ?></h1>
            <?php if ($desc) { ?>
            <div style="margin-top: 1em;"><?= $desc ?></div>
            <?php } ?>
            <div style="margin-top: 3em"><a style="text-decoration: none; font-size: 20px;" href="/">Home Page</a></div></div>
            </body></html><?php
        }

        Tht::exitScript(1);
    }

    function startGzip ($forceGzip=false) {

        if ($this->gzipBufferOpen) { return; }

        if ($forceGzip || Tht::getConfig('compressOutput')) {

            $this->checkOutputAlreadySent();

            ob_start("ob_gzhandler");
            $this->gzipBufferOpen = true;
        }
    }

    function endGzip () {
        if ($this->gzipBufferOpen) {
            Tht::debug('flush');
            ob_end_flush();
        }
    }

    function checkOutputAlreadySent() {

        if (ob_get_length() || headers_sent()) {

            ob_flush();

            ErrorHandler::printInlineWarning('(Output module) Can\'t enable GZIP compression because output was already sent. Solution: Either delay this output or set `compressOutput` = `false` in `app.jcon` (not recommended).');
        }
    }

    function scanAssetUrls($out) {

        $optimizer = new AssetOptimizer ();

        return $optimizer->scan($out);
    }

    function minifyJs($str) {

        if (!trim($str)) { return ''; }

        $cacheKey = 'js_minified_' . md5($str);
        $cache = Tht::module('Cache');
        if ($cache->u_has($cacheKey)) {
            return $cache->u_get($cacheKey);
        }

        Tht::loadLib('utils/Minifier.php');

        $min = new Minifier($str);
        $minStr = $min->minify('js', '/ *([\(\)\[\]\{\}<>=!\?:\.;\+\-\*\/,\|\&]+) */', true);

        $cache->u_set($cacheKey, $minStr, '30 days');

        return $minStr;
    }

    function minifyCss($str) {

        if (!trim($str)) { return ''; }

        $cacheKey = 'css_minified_' . md5($str);
        $cache = Tht::module('Cache');
        if ($cache->u_has($cacheKey)) {
            return $cache->u_get($cacheKey);
        }

        $str = $this->parseIndentCss($str);

        $str = $this->scanAssetUrls($str);

        Tht::loadLib('utils/Minifier.php');
        $minifier = new Minifier($str);

        // Don't crunch (...) because it messes with media queries
        $minStr = $minifier->minify('css', "/\s*([:\{\},;\+\>]+)\s*/");
        $minStr = preg_replace('/;}/', '}', $minStr);

        $cache->u_set($cacheKey, $minStr, '30 days');

        return $minStr;
    }

    // Undocumented for now.
    // Indent-style CSS
    public function parseIndentCss($str) {

        if (!preg_match('/^@indent/', ltrim($str))) {
            return $str;
        }

        $lines = explode("\n", $str);

        array_shift($lines); // remove @indent heading

        $out = '';
        $inBlock = false;
        foreach ($lines as $line) {

            if (trim($line) == '') { continue; }
            if (preg_match('#^\s*(//|/\*)#', $line)) { continue; }

            if ($inBlock && preg_match('#^\S#', $line)) {
                $out .= "}\n\n";  // close prev
                $inBlock = false;
            }

            if (preg_match('#^\S#', $line) && preg_match('#[^}{;,/]$#', $line)) {
                // selector
                $inBlock = true;
                $out .= $line . " {\n";
            }
            else if (preg_match('/^\s/', $line) && preg_match('#[^}{;,/]$#', $line)) {
                // rule
                $out .= $line . ";\n";
            }
            else {
                $out .= $line . "\n";
            }
        }

        if ($inBlock) {
            $out .= "}\n";
        }

        return $out;
    }

    function font($fontId) {

        $fonts = [
            'serif'     => 'Georgia, Times New Roman, serif',
            'sansSerif' => '-apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif',
            'monospace' => 'Menlo, Consolas, "Ubuntu Mono", Courier, monospace',
        ];

        if (!isset($fonts[$fontId])) {
            $this->error("Unknown fontId: `$fontId`  Try: `serif`, `sansSerif`, `monospace`");
        }

        return $fonts[$fontId];
    }

    function wrapJs($str, $skipFunctionWrap = false) {

        $nonce = Tht::module('Web')->u_nonce();
        $min = $this->minifyJs($str);

        if (!$skipFunctionWrap) {
            $min = "(function(){" . $min . "})()";
        }

        return "<script nonce=\"$nonce\">$min</script>";
    }

    function escapeJs($v) {

        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        else if (is_object($v)) {
            return json_encode($v->val);
        }
        else if (is_array($v)) {
            return json_encode($v);
        }
        else if (vIsNumber($v)) {
            return $v;
        }
        else {
            $v = '' . $v;
            $v = str_replace('"', '\\"', $v);
            $v = str_replace("\n", '\\n', $v);
            return "\"$v\"";
        }
    }

    function wrapCss($str) {

        $min = $this->minifyCss($str);

        return "<style>$min</style>";
    }

    function escapeCss($v) {
        return preg_replace('/[:;\{\}]/', '', $v);
    }
}

class AssetOptimizer {

    // Scan output HTML or CSS to optimize images and assets, and update URLs with cache 'v' param.
    // PERF: This is ~1ms on the THT home page.
    // Would rather not do this as a regex on the entire output, but it covers
    // URLs from every source (Litemark, templates, etc.), and it needs to be dynamic.
    function scan($plainText) {

        $config = Tht::getConfig('optimizeAssets');
        $configFlags = explode('|', $config);

        // TODO: validate options
        // images|minify|gzip|timestamps|none

        if (!$config || $config == 'none') {
            return $plainText;
        }

        if (in_array('images', $configFlags)) {
            if (!extension_loaded('gd')) {
                $msg = Tht::getLibIniError('gd');
                $msg .= "\n\nOr: Remove `images` from `optimizeAssets` in `config/app.jcon`. (e.g. optimizeAssets: minify|gzip)";
                Tht::startupError($msg);
            }
        }

        $fnUpdateUrl = function($m) use ($configFlags) {

            $url = OTypeString::create('url', $m[2]);
            $ext = $m[3];

            if ($url->u_is_absolute()) {
                return $m[0];
            }

            if (!preg_match('/(js|css|png|jpg|jpeg)/i', $ext)) {
                return $m[0];
            }

            $fullPath = $url->u_get_file_path();
            if (!preg_match('/(_thumb\d+|_asis)\./', $m[2]) && !file_exists($fullPath)) {
                return $m[0];
            }

            $newPath = $fullPath;

            if ($ext == 'js' || $ext == 'css') {
                if (in_array('minify', $configFlags) || in_array('gzip', $configFlags)) {
                    Tht::module('Perf')->u_start('Output.optimizeAsset', $fullPath);
                    $newPath = $this->minifyAsset($ext, $fullPath);
                    Tht::module('Perf')->u_stop();
                }
            }
            else if ($ext == 'png' || $ext == 'jpg' || $ext == 'jpeg') {
                if (in_array('images', $configFlags)) {
                    Tht::module('Perf')->u_start('Output.optimizeImage', $fullPath);
                    $newPath = $this->optimizeImage($fullPath);
                    Tht::module('Perf')->u_stop();
                }
            }

            $newUrl = Tht::module('File')->u_public_url($newPath);

            if (in_array('timestamps', $configFlags)) {
                // Add 'v' param
                $modTime = filemtime($newPath);
                $newUrl->u_get_query()->u_set(
                    OMap::create([ 'v' => $modTime ])
                );
            }

            $newUrlOut = $newUrl->u_render_string();
            if (preg_match('/url/', $m[1])) {
                return "url('$newUrlOut')";
            }
            else {
                return '"' . $newUrlOut . '"';
            }
        };

        Tht::module('Perf')->u_start('Output.optimizeAssets');

        // Match "some/path/image.png" or "url(some/path/image.png)"
        $out = preg_replace_callback(
            '#([\'"]|url\([\'"]?)(\S+?\.(\w{2,4}))[\'"]?\)?#',
            $fnUpdateUrl, $plainText
        );

        Tht::module('Perf')->u_stop();

        return $out;
    }

    function optimizeImage($fullPath) {

        require_once('helpers/ImageOptimizer.php');

        $im = new u_Image_Optimizer();

        $newImage = $im->optimize($fullPath, 1200, 'optimized');

        return isset($newImage['newFile']) ? $newImage['newFile'] : $fullPath;
    }

    function minifyAsset($type, $origFullPath) {

        $config = Tht::getConfig('optimizeAssets');

        $origModTime = filemtime($origFullPath);

        $doMinify = false;
        $doGzip = false;
        $ext = '';

        if (strpos($config, 'minify') !== false) {
            $ext .= '.min';
            $doMinify = true;
        }

        // TODO: Apache on Dreamhost double-gzips .gz files.  Figure out how to either
        // turn it off and rely on THT, or detect it.  For now, this means we have to
        // default to 'minify' only in 'compressAssets' app config.
        if (strpos($config, 'gzip') !== false) {
            $ext .= '.gz';
            $doGzip = true;
        }

        $minFullPath = $origFullPath . $ext;

        if (file_exists($minFullPath)) {

            $minModTime = filemtime($minFullPath);

            // If file hasn't changed, just return
            if ($origModTime < $minModTime) {
                return $minFullPath;
            }

            // Prevent cache stampede
            // TODO: An issue with updating file time when file is deployed to a different server.
            //   May need to reset file perms/ownership.
            //touch($minFullPath, time());
        }

        $minContent = Tht::module('*File')->u_read($origFullPath, OMap::create(['join' => true]));

        if ($type == 'css') {
            $minContent = Tht::module('Output')->parseIndentCss($minContent);
        }


        // Minify
        if ($doMinify) {
            if ($type == 'js') {
                $minContent = Tht::module('Output')->minifyJs($minContent);
            }
            else {
                $minContent = Tht::module('Output')->minifyCss($minContent);
            }
        }

        // GZip
        if ($doGzip) {
            $maxLevel = 9; // This actual takes gzip less time to decompress than lower levels.
            $minContent = gzencode($minContent, $maxLevel);
        }

        file_put_contents($minFullPath, $minContent, LOCK_EX);
        chmod($minFullPath, 0775);

        return $minFullPath;
    }
}





    // // Minify and gzip a js or css file in the public folder.
    // // Return the new URL with a cache time in the query string.
    // // e.g. /css/app.css  -->  /css/app.css.min.gz?v=23525352
    // function assetUrl($assetType, $origUrl) {

    //     if ($origUrl->u_is_absolute()) {
    //         return $origUrl->u_render_string();
    //     }



    //     // Check current request cache
    //     $rawOrigUrl = OTypeString::getUntyped($origUrl, 'url');
    //     if (isset($this->assetUrlCache[$rawOrigUrl])) {
    //         return $this->assetUrlCache[$rawOrigUrl];
    //     }

    //     $origRelPath = $origUrl->u_get_path();
    //     $asset = $this->minifyAsset($assetType, $origRelPath);

    //     $newRawUrl = $asset['relPath'] . '?v=' . $asset['time'];

    //     $newUrl = new UrlTypeString($newRawUrl);

    //     $this->assetUrlCache[$rawOrigUrl] = $newUrl;

    //     return $newUrl;
    // }


    // function u_asset_url($url) {

    //     $this->ARGS('*', func_get_args());

    //     // Validate
    //     $plainUrl = OTypeString::getUntyped($url, 'url');

    //     if (isset($this->assetUrlCache[$plainUrl])) {
    //         return $this->assetUrlCache[$plainUrl];
    //     }

    //     Tht::module('Perf')->u_start('Web.assetUrl', $url->u_render_string());

    //     if (!$url->u_is_relative()) {
    //         $this->error("`Web.assetUrl` only takes paths relative to the `public` folder. Got: `$fullPath`");
    //     }

    //     $fullPublicPath = $this->getPublicPath($url);

    //     if (!file_exists($fullPath) && !preg_match('/_thumb/', $fullPath)) {
    //         $this->error("Asset file does not exist: `$fullPath`");
    //     }

    //     // If this is a route, don't do anything with it
    //     $fileExt = $url->u_get_file_parts()['fileExt'];
    //     if (!$fileExt) {
    //         Tht::module('Perf')->u_stop();
    //         return $url;
    //     }


    //     // Add 'v' param
    //     $modTime = filemtime($fullPath);
    //     $url->u_get_query()->u_set(
    //         OMap::create([ 'v' => $modTime ])
    //     );

    //     $this->assetUrlCache[$plainUrl] = $url;

    //     Tht::module('Perf')->u_stop();

    //     return $url;

    // }
