<?php

namespace o;

class u_Js extends OStdModule {

    private $jsData = [];
    private $included = [];

    function u_data($key, $data) {
        $this->jsData[$key] = $data;
    }

    function wrap($str) {
        $nonce = Tht::module('Web')->u_nonce();
        $min = $this->u_minify($str);
        return "<script nonce=\"$nonce\">(function(){" . $min . "})();</script>";
    }

    function escape($v) {
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        } else if (is_object($v)) {
            return json_encode($v->val);
        } else if (is_array($v)) {
            return json_encode($v);
        } else if (is_numeric($v)) {
            return $v;
        } else {
            $v = '' . $v;
            $v = str_replace('"', '\\"', $v);
            $v = str_replace("\n", '\\n', $v);
            return "\"$v\"";
        }
    }

    function u_minify ($str) {

        $this->ARGS('s', func_get_args());

        if (!trim($str)) { return ''; }

        $cacheKey = 'js_minified_' . md5($str);
        $cache = Tht::module('Cache');
        if ($cache->u_has($cacheKey)) {
            return $cache->u_get($cacheKey);
        }

        $min = new Minifier($str);
        $minStr = $min->minify("/\s*([\\(\\)<>=!\\?:\\.;\+\-\{\},\|\*]+)\s*/");

        $cache->u_set($cacheKey, $minStr, Tht::module('Date')->u_hours(24));

        return $minStr;
    }

    function u_plugin($id) {

        $args = func_get_args();
        array_shift($args);

        if ($id == 'colorCode') {
            return $this->incSyntaxHighlight($args);
        }
        if ($id == 'lazyLoadImages') {
            return $this->incLazyLoadImages();
        }
        if ($id == 'jsData') {
            return $this->incJsData();
        }

        Tht::error("Unknown JS plugin: `$id`. Supported plugins: `colorCode`, `lazyLoadImages`, `jsData`");
    }

    function incJsData () {
        if (isset($this->included['jsData'])) { return ''; }
        if (!count($this->jsData)) { return ''; }
        $data = json_encode($this->jsData);
        $nonce = Tht::module('Web')->u_nonce();
        $min = $this->u_minify("
            return {
                has: function (key) {
                    return data.hasOwnProperty(key);
                },
                get: function (key, def) {
                    if (frozen[key]) {
                        throw 'Can only retrieve JsData key=`' + key + '` one time.';
                    }
                    if (!this.has(key)) {
                        if (typeof def !== 'undefined') {  return def;  }
                        throw 'JsData key=`'+ key +'` does not exist.';
                    }
                    frozen[key] = true;
                    return data[key];
                }
            };
        ");

        return new JsTypeString("window.JsData=(function(){var data=$data;var frozen={}; $min })();");
    }

    function incLazyLoadImages () {

        $css = <<<EOLAZY

            /* Dynamic Loading
            ---------------------------------------------------------- */

            @keyframes lazyload-pulsate {
                0%   { opacity: 0.50; }
                50%  { opacity: 0.40; }
                100% { opacity: 0.50; }
            }

            img[data-src] {
                background-color: rgb(16,16,16);
                border: 0;
                height: 300px;
                width: 300px;
                opacity: 0.5;
                animation: lazyload-pulsate 1500ms linear;
                animation-iteration-count: infinite;
            }
EOLAZY;

        $minCss = Tht::module('Css')->u_minify($css);


        $js = <<<EOLAZY

            if (document.body.classList.contains('no-lazy-load')) {
                return;
            }

            var css = document.createElement('style');
            css.innerHTML = '$minCss';
            document.body.appendChild(css);

            var preloadMarginY = 500;
            var throttleTime = 0;
            var fnLazyLoad = function () {
                var now = new Date().getTime();
                if (throttleTime && now < throttleTime) {  return;  }
                throttleTime = now + 100;
                var els = document.querySelectorAll("img[data-src]");
                if (!els.length) {
                    document.removeEventListener("scroll", fnLazyLoad);
                    return;
                }
                var viewportSizeY = window.innerHeight || document.documentElement.clientHeight || 800;
                [].forEach.call(els, function(e){
                    if (e.getBoundingClientRect().top < viewportSizeY + preloadMarginY) {
                        var dataSrc = e.getAttribute("data-src");
                        if (dataSrc !== '') {
                            var img = new Image ();
                            img.src = dataSrc;
                            e.setAttribute("data-src", '');
                            img.addEventListener("load", function(){
                                e.src = dataSrc;
                                e.removeAttribute("data-src");
                                var event = new CustomEvent("imageLoaded", { "image": e });
                                document.dispatchEvent(event);
                            });
                        }
                    }
                });
            };
            window.addEventListener("scroll", fnLazyLoad);
            window.addEventListener("load", fnLazyLoad);

EOLAZY;

        $js = self::u_minify($js);
        return new JsTypeString($js);
    }

    function getArg($args, $i, $def) {
        return isset($args[$i]) ? $args[$i] : $def;
    }

    // 2.3k minified
    function incSyntaxHighlight($args) {

        $theme    = $this->getArg($args, 0, 'light');
        $sel      = $this->getArg($args, 1, 'pre, .tht-color-code');
        $keyWords = $this->getArg($args, 2, null);

        $keyWords = $keyWords ?: 'let|var|const|constant|template|function|for|foreach|loop|while|do|array|new|if|else|elsif|elif|this|break|continue|return|require|import|class|static|public|private|protected|final|int|double|boolean|bool|string|float|long|in|as|try|catch|throw|finally|select|from|join|inner join|outer join|cross join|insert|delete|update|switch|match|T|F|keep|use';


        $css = <<<EOCSS

            /* Syntax Highlighting
            ---------------------------------------------------------- */

            .has-color-code .cc-value span,
            .has-color-code .cc-comment span,
            .has-color-code .cc-prompt,
            .has-color-code .cc-template-code span {
                color: inherit !important;
                font-weight: inherit !important;
            }
            .has-color-code .cc-prompt {
                opacity: 0.5;
                user-select: none;
            }

            /* Light Theme (Default) */

            .has-color-code {
                color: #000;
            }
            .has-color-code .cc-comment {
                color: #36963f;
            }
            .has-color-code .cc-value {
                color: #c33524;
            }
            .has-color-code .cc-tag,
            .has-color-code .cc-keyword {
                color: #177bad;
                font-weight: bold;
            }
            .has-color-code .cc-template-code {
                color: #831ec0;
            }

            /* Dark Theme */

            .has-color-code.theme-dark {
                background-color: #282828;
                color: #ddd;
                border: 0;
            }
            .has-color-code.theme-dark .cc-comment {
                color: #9a9a9a;
            }
            .has-color-code.theme-dark .cc-value {
                color: #a0e092;
            }
            .has-color-code.theme-dark .cc-tag,
            .has-color-code.theme-dark .cc-keyword {
                color: #f3ac5b;
                font-weight: normal;
            }

            .has-color-code.theme-dark .cc-template-code {
                color: #cc9ee7;
            }


EOCSS;

        $minCss = Tht::module('Css')->u_minify($css);


        $js = <<<EOSYNTAX

        window.highlightSyntax = function () {

            if (document.body.classList.contains('no-color-code')) {
                return;
            }

            var css = document.createElement('style');
            css.innerHTML = '$minCss';
            document.body.appendChild(css);

            var themeClass = "theme-$theme";
            var hiClass = 'has-color-code';

            var codes = document.querySelectorAll('$sel');
            for (var i=0; i < codes.length; i++) {
                var block = codes[i];
                var classes = block.classList;
                if (classes.contains(hiClass) || classes.contains('no-color-code')) {
                    continue;
                }

                classes.add(hiClass);
                if (!classes.contains('theme-light') && !classes.contains('theme-dark')) {
                    classes.add(themeClass);
                }

                var c = block.innerHTML;

                // keywords
                c = c.replace(/\\b($keyWords)\\b([^=:])/gi, '<span class=(qq)cc-keyword(qq)>$1</span>$2');

                // HTML tags
                c = c.replace(/(&lt;\S.*?(&gt;)+)/g, '<span class=(qq)cc-tag(qq)>$1</span>');

                // numbers
                c = c.replace(/([^a-zA-Z\\d])(\\d[\\d\\.]*)/g, '$1<span class=(qq)cc-value(qq)>$2</span>');

                // booleans
                c = c.replace(/\\b(true|false)\\b/gi, '<span class=(qq)cc-value(qq)>$1</span>');

                // strings
                c = c.replace(/('''([\\w\\W]*?)''')/gm, '<span class=(qq)cc-value(qq)>$1</span>');
                c = c.replace(/("(.*?)")/g, '<span class=(qq)cc-value(qq)>$1</span>');
                c = c.replace(/('(.*?)'(?![a-zA-Z0-9]))/g, '<span class=(qq)cc-value(qq)>$1</span>');

                // command prompt ($ or %)
                c = c.replace(/(^|\\n)(\\$|\\%)(\s+)/gi, '<span class=(qq)cc-prompt(qq)>$1$2$3</span>');

                // block comments
                c = c.replace(/(\\/\\*([\\w\\W]*?)\\*\\/)/gm, '<span class=(qq)cc-comment(qq)>$1</span>');

                // single-line comments
                c = c.replace(/(^|\\s)(\\/\\/[^\\/].*)/gm, '$1<span class=(qq)cc-comment(qq)>$2</span>');

                // template: single-line code
                c = c.replace(/(^|\\s)((--)\s+.*)/g, '$1<span class=(qq)cc-template-code(qq)>$2</span>');

                // template: expression
                // c = c.replace(/(\{\{(.*?)\}\})/g, '<span class=(qq)cc-template-expr(qq)>$1</span>');

                // replace quotes
                c = c.replace(/\(qq\)/g, '"');

                block.innerHTML = c;
            }
        };

        document.addEventListener('DOMContentLoaded', window.highlightSyntax);

EOSYNTAX;

   //     $js = self::u_minify($js);
        return new JsTypeString($js);
    }
}

