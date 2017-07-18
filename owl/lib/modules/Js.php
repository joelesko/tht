<?php

namespace o;

class u_Js extends StdModule {

    private $jsData = [];
    private $included = [];

    // function u_data($key, $data) {
    //     $this->jsData[$key] = uv($data);
    // }
    //
    // function serializeData () {
    //     if (!count($this->jsData)) { return ''; }
    //     $data = json_encode($this->jsData);
    //     // TODO: nonce
    //     return "<script> window.jsData = (function(){
    //         var data = $data;
    //         return {
    //             has: function (key) {
    //                 return data.hasOwnProperty(key);
    //             },
    //
    //             get: function (key, def) {
    //                 if (!this.has(key)) {
    //                     if (typeof def !== 'undefined') {  return def;  }
    //                     throw 'jsData key `'+ key +'` does not exist.';
    //                 }
    //                 return data[key];
    //             },
    //
    //             getAll: function () {
    //                 return JSON.parse(JSON.stringify(data));
    //             }
    //         };
    //     })();
    //     </script>";
    // }


    function u_minify ($str) {

        $str1 = $str;

        Owl::module('Perf')->u_start('js.minify', $str);

        # comments
        $str = preg_replace("#\n\s*//[^!]?.*?\n#", '', $str);
        $str = preg_replace("#/\*(.*?)\*/#", '', $str);

        $str = preg_replace("#\n\s+#", "\n", $str);
        $str = preg_replace("#\ *([;\{\}\[\]\(\)])\ *#", '$1', $str);
        $str = preg_replace("#([;\{\}])\n#", '$1', $str);
        $str = preg_replace("#,\n\s*#", ',', $str);

        Owl::module('Perf')->u_stop('js.minify');

        return $str;
    }

    function u_plugin($id) {

        if (isset($this->included[$id])) {
            return '';
        }
        $this->included[$id] = true;

        $args = func_get_args();
        array_shift($args);

        if ($id == 'colorCode') {
            return $this->incSyntaxHighlight($args);
        }
        if ($id == 'lazyLoadImages') {
            return $this->incLazyLoadImages();
        }

        Owl::error("Unknown JS plugin: '$id'");
    }

    function incLazyLoadImages () {

        $nonce = Owl::data('cspNonce');

        $js = <<<EOLAZY

        <style nonce="$nonce">

        /* Dynamic Loading
        ---------------------------------------------------------- */

        @keyframes pulsate {
            0%   { opacity: 0.50; }
            50%  { opacity: 0.40; }
            100% { opacity: 0.50; }
        }

        img[data-src] {
            background-color: rgb(16,16,16);
            border: 0;
            height: 300px;
            width: 300px;
        }

        img[data-src], .ajax-loading {
            opacity: 0.5;
            animation: pulsate 1500ms linear;
            animation-iteration-count: infinite;
        }

        </style>

        <script nonce="$nonce">
            /* Image lazy loading */
            (function(){
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
            })();
            </script>

EOLAZY;

        return new OLockString ($js);

    }

    function getArg($args, $i, $def) {
        return isset($args[$i]) ? $args[$i] : $def;
    }

    function incSyntaxHighlight($args) {

        $theme    = $this->getArg($args, 0, 'light');
        $sel      = $this->getArg($args, 1, 'pre');
        $keyWords = $this->getArg($args, 2, null);

        $keyWords = $keyWords ?: 'let|var|const|template|function|for|foreach|while|do|array|new|if|else|this|break|continue|return|require|import|class|static|public|private|protected|int|double|boolean|string|float|long|in|as|try|catch|throw|finally|select|from|join|inner|outer|cross|insert|delete|update';

        $nonce = Owl::data('cspNonce');


        $themeCss = "

            /* Light Theme (Default) */

            .syntax-highlighted {
                color: #000;
            }
            .syntax-highlighted .sh-comment {
                color: #36963f;
            }
            .syntax-highlighted .sh-value {
                color: #c33524;
            }
            .syntax-highlighted .sh-tag,
            .syntax-highlighted .sh-keyword {
                color: #177bad;
                font-weight: bold;
            }
        ";
        if ($theme === 'dark') {
            $themeCss = "

                /* Dark Theme */

                .syntax-highlighted.theme-dark {
                    background-color: #282828;
                    color: #ddd;
                    border: 0;
                }
                .syntax-highlighted.theme-dark .sh-comment {
                    color: #9a9a9a;
                }
                .syntax-highlighted.theme-dark .sh-value {
                    color: #a0e092;
                }
                .syntax-highlighted.theme-dark .sh-tag,
                .syntax-highlighted.theme-dark .sh-keyword {
                    color: #f3ac5b;
                }
            ";
        }

        $themeCss = v($themeCss)->u_trim_indent();


        $js = <<<EOSYNTAX
<style nonce="$nonce">

/* Syntax Highlighting
---------------------------------------------------------- */

.syntax-highlighted .sh-value span,
.syntax-highlighted .sh-comment span,
.syntax-highlighted .sh-prompt {
    color: inherit !important;
    font-weight: inherit !important;
}
.syntax-highlighted .sh-prompt {
    opacity: 0.5;
    -webkit-user-select: none;
       -moz-user-select: none;
        -ms-user-select: none;
            user-select: none;
}

$themeCss

</style>

<script nonce="$nonce">
    (function (){
        window.highlightSyntax = function () {

            var themeClass = "theme-$theme";
            var hiClass = 'syntax-highlighted';

            var codes = document.querySelectorAll('$sel');
            for (var i=0; i < codes.length; i++) {
                var block = codes[i];
                var classes = block.classList;
                if (classes.contains(hiClass)) {
                    continue;
                }

                classes.add(hiClass);
                classes.add(themeClass);

                var c = block.innerHTML;

                c = c.replace(/\\b($keyWords)\\b([^=:])/gi, '<span class=(qq)sh-keyword(qq)>$1</span>$2');  // keywords
                c = c.replace(/(&lt;\S.*?(&gt;)+)/g, '<span class=(qq)sh-tag(qq)>$1</span>');               // HTML tags
                c = c.replace(/([^a-zA-Z])(\\d[\\d\\.]*)/g, '$1<span class=(qq)sh-value(qq)>$2</span>');    // tokens/vars
                c = c.replace(/\\b(true|false)\\b/gi, '<span class=(qq)sh-value(qq)>$1</span>');            // flags
                c = c.replace(/("(.*?)")/g, '<span class=(qq)sh-value(qq)>$1</span>');                      // strings
                c = c.replace(/('(.*?)'(?![a-zA-Z0-9]))/g, '<span class=(qq)sh-value(qq)>$1</span>');       // strings
                c = c.replace(/(^|\\n)(\\$|\\%)(\s+)/gi, '<span class=(qq)sh-prompt(qq)>$1$2$3</span>');    // command prompt ($ or %)

                // comments
                c = c.replace(/(\\/{3,}((.|\\s)*?)\\/{3,})/g, '<span class=(qq)sh-comment(qq)>$1</span>');  // OWL blocks
                c = c.replace(/(\\/\\*([\\w\\W]*?)\\*\\/)/gm, '<span class=(qq)sh-comment(qq)>$1</span>');  // C-style blocks
                c = c.replace(/(^|\\s)(\\/\\/[^\\/].*)/gm, '$1<span class=(qq)sh-comment(qq)>$2</span>');   // single-line comments

                // replace quotes
                c = c.replace(/\(qq\)/g, '"');

                block.innerHTML = c;
            }
        };

        document.addEventListener('DOMContentLoaded', window.highlightSyntax);
    })();
</script>

EOSYNTAX;


        return new OLockString ($js);
    }
}

