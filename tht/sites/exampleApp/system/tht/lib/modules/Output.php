<?php

namespace o;

class u_Output extends OStdModule {

    private $gzipBufferOpen = false;
    public $sentResponseType = '';

    function u_run_route($path) {

        $this->ARGS('s', func_get_args());
        WebMode::runRoute($path);

        return new \o\ONothing('runRoute');
    }

    function u_redirect ($lUrl, $code=303) {

        $this->ARGS('*I', func_get_args());

        $this->sentResponseType = 'redirect';

        $url = OTypeString::getUntyped($lUrl, 'url');
        header('Location: ' . $url, true, $code);
        Tht::exitScript(0);
    }

    function u_set_response_code ($code) {

        $this->ARGS('I', func_get_args());
        http_response_code($code);

        return new \o\ONothing('setResponseCode');
    }

    function u_set_header ($name, $value, $multiple=false) {

        $this->ARGS('ssb', func_get_args());

        if ($this->hasPreOutput()) {
            $this->error("Can not set response header because output was already sent: `$name: $value`");
        }

        $value = preg_replace('/\s+/', ' ', $value);
        $name = preg_replace('/[^a-z0-9\-]/', '', strtolower($name));
        header($name . ': ' . $value, !$multiple);

        return new \o\ONothing('setHeader');
    }

    function u_set_cache_header ($expiryDelta='365 days') {

        $this->ARGS('s', func_get_args());

        $expiry = Tht::module('Date')->u_now()->u_add($expiryDelta);

        $this->u_set_header('Expires', gmdate('D, d M Y H:i:s \G\M\T', $expiry->u_unix_time()));

        return new \o\ONothing('setCacheHeader');
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
        else if (OMap::isa($out)) {
            $this->u_send_json($out);
        }
        else if (u_Page_Object::isa($out)) {
            $out->u_send();
        }
    }

    function renderChunks($chunks) {

        // Normalize. Could be a single TypeString, OList, or a PHP array
        if (! (is_object($chunks) && v($chunks)->u_type() == 'list')) {
            $chunks = OList::create([ $chunks ]);
        }

        $out = '';
        foreach ($chunks->val as $c) {
            $out .= OTypeString::getUntyped($c, '');
        }
        return $out;
    }

    function u_send_json ($map, $expiryDelta=0) {
        $this->ARGS('mi', func_get_args());

        $this->u_set_header('Content-Type', 'application/json; charset=utf-8');
        $this->u_set_cache_header($expiryDelta);

        $this->output(json_encode(unv($map)), 'json');

        Tht::module('Web')->u_skip_hit_counter(true);

        return new \o\ONothing('sendJson');
    }

    function u_send_text ($text, $expiryDelta=0) {
        $this->ARGS('si', func_get_args());

        $this->u_set_header('Content-Type', 'text/plain; charset=utf-8');
        $this->u_set_cache_header($expiryDelta);

        $this->output($text, 'text');

        return new \o\ONothing('sendText');
    }

    // function u_send_css ($chunks, $expiryDelta=null) {

    //     $this->ARGS('*i', func_get_args());

    //     $this->u_set_header('Content-Type', 'text/css; charset=utf-8');
    //     $this->u_set_cache_header($expiryDelta);

    //     $out = $this->renderChunks($chunks);
    //     $this->output($out, 'css');

    //     Tht::module('Web')->u_skip_hit_counter(true);

    //     return new \o\ONothing('sendCss');
    // }

    // function u_send_js ($chunks, $expiryDelta=null) {

    //     $this->ARGS('*i', func_get_args());

    //     $this->u_set_header('Content-Type', 'application/javascript; charset=utf-8');
    //     $this->u_set_cache_header($expiryDelta);

    //     $out = "(function(){\n";
    //     $out .= $this->renderChunks($chunks);
    //     $out .= "\n})();";

    //     $this->output($out, 'js');

    //     Tht::module('Web')->u_skip_hit_counter(true);

    //     return new \o\ONothing('sendJs');
    // }

    function u_send_html ($html) {

        $html = OTypeString::getUntyped($html, 'html');

        $html = $this->updateAssetUrls($html);

        $this->output($html, 'html');

        flush();

        return new \o\ONothing('sendHtml');
    }

    function u_x_danger_send ($s) {

        $this->ARGS('s', func_get_args());

        print $s;

        return new \o\ONothing('xDangerSend');
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

            ?><html><head><title><?= $title ?></title></head><body style="margin: 0; padding: 0">
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

        if ($forceGzip || Tht::getConfig('optimizeOutput')) {
            if ($this->hasPreOutput()) {
                ErrorHandler::printInlineWarning('(Response module) Can\'t enable GZIP compression because output was already sent. Solution: Either delay this output or set `optimizeOutput` = `false` in `app.jcon` (not recommended).');
            }

            ob_start("ob_gzhandler");
            $this->gzipBufferOpen = true;
        }
    }

    function endGzip () {
        if ($this->gzipBufferOpen) {
            ob_flush();
        }
    }

    function hasPreOutput() {

        $ob = ob_get_length();

        if ($ob) {
            ob_flush();
        }

        return $ob || headers_sent($atFile, $atLine);
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
    function parseIndentCss($str) {

        if (!preg_match('/^@outline/', ltrim($str))) {
            return $str;
        }

        $lines = explode("\n", $str);

        array_shift($lines); // remove @outline heading

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
            $this->error("Unknown fontId `$fontId`.  Try: `serif`, `sansSerif`, `monospace`");
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

    // Add client-cache params to all asset URLs in the HTML payload.
    // PERF: This is like 0.5ms on the THT home page.
    // Would rather not do this as a regex on the entire output, but it
    // covers URLs from every source (Litemark, templates, etc.), and it needs to be dynamic.
    function updateAssetUrls($plainHtml) {

        if (!Tht::getConfig('addAssetUrlParam')) {
            return $plainHtml;
        }

        Tht::module('Perf')->u_start('Output.addAssetUrlParam');

        $fnUpdateUrl = function($m) {
            $url = OTypeString::create('url', $m[1]);
            if ($url->u_is_relative()) {
                return 'src="' . Tht::module('Web')->u_asset_url($url)->u_render_string() . '"';
            }
            return $m[0];
        };

        $out = preg_replace_callback('#\bsrc\s*=\s*[\'"](.*?\.\w{2,4})[\'"]#', $fnUpdateUrl, $plainHtml);

        Tht::module('Perf')->u_stop();

        return $out;
    }
}