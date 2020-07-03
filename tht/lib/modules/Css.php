<?php

namespace o;

class u_Css extends OStdModule {

    private $included = [];

    function wrap($str) {
        $min = $this->u_minify($str);
        return "<style>$min</style>";
    }

    function escape($v) {
        return preg_replace('/[:;\{\}]/', '', $v);
    }

    function u_minify ($str) {

        $this->ARGS('s', func_get_args());

        if (!trim($str)) { return ''; }

        $cacheKey = 'css_minified_' . md5($str);
        $cache = Tht::module('Cache');
        if ($cache->u_has($cacheKey)) {
            return $cache->u_get($cacheKey);
        }

        $min = new Minifier($str);
        $minStr = $min->minify("/\s*([\\(\\):{\},;]+)\s*/");

        $cache->u_set($cacheKey, $minStr, Tht::module('Date')->u_hours(24));

        return $minStr;
    }


    function u_font($fontId) {
        $this->ARGS('s', func_get_args());
        $fonts = [
            'serif'     => 'Georgia, Times New Roman, serif',
            'sansSerif' => 'Helvetica Neue, Helvetica, Arial, sans-serif',
            'monospace' => 'Menlo, Consolas, monospace',
            'system'    => '-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif',
        ];

        if (!isset($fonts[$fontId])) {
            $this->error("Unknown fontId `$fontId`.  Try: `serif`, `sansSerif`, `monospace`, `system`");
        }

        return $fonts[$fontId];
    }

    function u_plugin($id, $arg1=null, $arg2=null) {

        $this->ARGS('sn*', func_get_args());

        if (isset($this->included[$id])) {
            return '';
        }
        $this->included[$id] = true;

        if ($id === 'base') {
            return $this->inc_base($arg1, $arg2);
        }
        if ($id === 'reset') {
            return $this->inc_reset();
        }
        if ($id === 'icons') {
            return $this->inc_icons();
        }
        if ($id === 'grid') {
            return $this->inc_grid();
        }

        $this->error("Unknown CSS include: `$id`.  Allowed: `base`, `reset`, `grid`, `icons`");
    }

    function inc_reset () {

        // THANKS: github.com/necolas/normalize.css
        $css = <<<EOCSS
        /* normalize.css v5.0.0 */
button,hr,input{overflow:visible}audio,canvas,progress,video{display:inline-block}progress,sub,sup{vertical-align:baseline}html{font-family:sans-serif;line-height:1.15;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%}body{margin:0} menu,article,aside,details,footer,header,nav,section{display:block}h1{font-size:2em;margin:.67em 0}figcaption,figure,main{display:block}figure{margin:1em 40px}hr{box-sizing:content-box;height:0}code,kbd,pre,samp{font-family:monospace,monospace;font-size:1em}a{background-color:transparent;-webkit-text-decoration-skip:objects}a:active,a:hover{outline-width:0}abbr[title]{border-bottom:none;text-decoration:underline;text-decoration:underline dotted}b,strong{font-weight:bolder}dfn{font-style:italic}mark{background-color:#ff0;color:#000}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative}sub{bottom:-.25em}sup{top:-.5em}audio:not([controls]){display:none;height:0}img{border-style:none}svg:not(:root){overflow:hidden}button,input,optgroup,select,textarea{font-family:sans-serif;font-size:100%;line-height:1.15;margin:0}button,input{}button,select{text-transform:none}[type=submit], [type=reset],button,html [type=button]{-webkit-appearance:button}[type=button]::-moz-focus-inner,[type=reset]::-moz-focus-inner,[type=submit]::-moz-focus-inner,button::-moz-focus-inner{border-style:none;padding:0}[type=button]:-moz-focusring,[type=reset]:-moz-focusring,[type=submit]:-moz-focusring,button:-moz-focusring{outline:ButtonText dotted 1px}fieldset{border:1px solid silver;margin:0 2px;padding:.35em .625em .75em}legend{box-sizing:border-box;color:inherit;display:table;max-width:100%;padding:0;white-space:normal}progress{}textarea{overflow:auto}[type=checkbox],[type=radio]{box-sizing:border-box;padding:0}[type=number]::-webkit-inner-spin-button,[type=number]::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}[type=search]::-webkit-search-cancel-button,[type=search]::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}summary{display:list-item}[hidden],template{display:none}

        html{box-sizing:border-box;overflow-y:scroll;-webkit-text-size-adjust:100%}*,:after,:before{box-sizing:inherit}
        html{ -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

EOCSS;
        return new \o\CssTypeString ($css);
    }

    function inc_base ($nSizeX=0, $breakCss=null) {

        $this->ARGS('n*', func_get_args());

        $nSizeX = $nSizeX ?: 760;
        $sizeX = $nSizeX . 'px';

        $css = OTypeString::getUntyped($this->inc_reset(), 'css');

        $gridCss = $this->inc_grid($nSizeX, $breakCss)->u_stringify();

        $css .= <<<EOCSS

        /* Main
        ---------------------------------------------------------- */

        html {
            font-size: 62.5%;
        }
        @viewport { width: device-width; }
        @-ms-viewport { width: device-width; }

        main {
            width: 100%;
            max-width: ${sizeX};
            min-height: 70vh;
            padding: 0 2rem;
            margin: 0 auto;
            padding-top: 2rem;
        }


        $gridCss



        /* Fonts
        ---------------------------------------------------------- */

        body {
          font-size: 1.6rem;
          line-height: 1.5;
          font-weight: 400;
          color: #222;
          background-color: #fff;
        }
        body, .sansSerif {
            font-family: "HelveticaNeue", "Helvetica Neue", Helvetica, Arial, sans-serif;
        }
        .serif {
            font-family: Georgia, "Times New Roman", serif;
        }
        code, pre, xmp, samp, .monospace {
            font-family: Menlo, Monaco, Lucida Console, Courier, monospace, monospace;
        }
        button, input, select, textarea {
            font: inherit;
        }


        /* Links
        ---------------------------------------------------------- */

        a {
            text-decoration: none;
            color: #1572d4;
            white-space: nowrap;
        }
        a:hover { text-decoration: underline; }
        .muted { color: #818a91; }


        /* Inline
        ---------------------------------------------------------- */

        .small, small { font-size: 80%; font-weight: 400; }
        b, strong, .strong, dt, optgroup { font-weight: 700; }
        em, .em { font-style: italic; }
        abbr[title], .abbr[title] { border-bottom: dotted 1px; cursor: help; }
        dfn { font-weight: bold; font-style: italic; }
        mark { padding: 0.2rem; background-color: #fcf8e3; }


        /* Code
        ---------------------------------------------------------- */


        code, kbd {
            font-size: 0.9em;
            padding: .2rem .5rem;
            white-space: nowrap;
            background-color: rgba(0,0,0,0.04);
            border-radius: 0.2rem;
            padding: .2rem .4rem;
        }
        kbd {
            border-radius: 0.2rem;
            border: solid 1px #e3e3e3;
            font-family: inherit;
            border-bottom-width: 0.2rem;
        }
        pre > code, code > pre {
            background-color: inherit;
            border: 0;
        }



        /* Headings
        ---------------------------------------------------------- */

        h1, h2, h3, h4, h5, h6,
        .h1, .h2, .h3, .h4, .h5, .h6 {
            font-family: inherit;
            color: inherit;
            font-weight: 500;
            line-height: 1.1;
            margin: 2em 0 1em 0; /* ems intended */
        }

        h1,.h1 { font-size: 4.00rem; }
        h2,.h2 { font-size: 3.00rem; }
        h3,.h3 { font-size: 2.50rem; }
        h4,.h4 { font-size: 2.25rem; }
        h5,.h5 { font-size: 2.00rem; }
        h6,.h6 { font-size: 1.75rem; }

        h1:first-child, .h1:first-child,
        h2:first-child, .h2:first-child,
        h3:first-child, .h3:first-child,
        h4:first-child, .h4:first-child,
        h5:first-child, .h5:first-child,
        h6:first-child, .h6:first-child {
            margin-top: 0;
        }



        /* Lists
        ---------------------------------------------------------- */

        ul, ol { margin: 0 0 2rem; }
        ul { padding: 0 0 0 2rem; }
        ol { padding: 0 0 0 2rem; }
        li { margin-bottom: 0.25rem; }
        ol ol, ol ul, ul ol, ul ul { margin: 0 0 1rem; }
        ol ol li { list-style-type: lower-alpha; }
        ol ol ol li { list-style-type: decimal; }
        dl { margin: 0 0 4rem; }
        dd { margin-bottom: 2rem; margin-left: 4rem; }

        .list-unstyled {
            padding-left: 0;
            list-style: none;
        }


        /* Blocks
        ---------------------------------------------------------- */

        p, .p { margin: 0 0 2rem; }
        * p:last-child { margin-bottom: 0; }

        blockquote, q {
            quotes: none;
            padding: 0rem 3rem;
            margin: 3rem 0 3rem 0;
            font-size: 110%;
            line-height: 3rem;
            color: #777;
            border-left: solid 2px #eee;
        }
        blockquote footer, q footer {
            width: 100%;
            font-size: 90%;
        }
        blockquote footer::before, q footer::before {
            margin-right: 0.5em;
            content: '\\2014';
        }
        pre, xmp {
            display: block;
            font-weight: normal;
            font-size: 1.5rem;
            margin: 0 0 2rem;
            white-space: pre;
            line-height: 1.5;
            overflow: auto;
            background-color: #fcfcfc;
            padding: 2rem;
            border: solid 0px #ddd;
            border-left-width: 1px;
            border-radius: 0.3rem;
        }


        /* Buttons
        ---------------------------------------------------------- */

        button, .button, input[type=button], input[type=submit] {
            color: #000;
            background-color: #f3f3f3;
            display: inline-block;
            padding: 0rem 3rem;
            line-height: 4rem;
            margin-right: 1rem;
            font-size: 1.8rem;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            -ms-touch-action: manipulation;
            touch-action: manipulation;
            cursor: pointer;
            user-select: none;
            border: solid 1px rgba(0,0,0,0.15);
            border-radius: 0.3rem;
            user-select: none;
        }
        button:hover, button:focus,
        .button:hover, .button:focus,
        input[type=button]:hover, input[type=submit]:hover,
        input[type=button]:focus, input[type=submit]:focus {
            background-color: #f9f9f9;
            outline: none;
        }
        .button-primary, input.button-primary {
            color: #fff;
            background-color: #3388E2;
        }
        .button-primary:hover, .button-primary:focus,
        input.button-primary:hover, input.button-primary:focus {
            background-color: #2376CE;
        }
        .button-large, input.button-large {
            padding: 0rem 4rem;
            font-size: 2rem;
            line-height: 5rem;
            border-radius: 3px;
        }
        .button-small, input.button-small {
            padding: 1rem 1.8rem;
            font-size: 1.4rem;
            line-height: 1rem;
        }
        .button-link {
            color: #1572d4;
            background: none;
            border: none;
            cursor: pointer;
            display: inline;
            padding: 0;
            margin: 0 1rem;
        }
        .button-link:hover {
            text-decoration: underline;
            background-color: transparent;
        }


        /* Forms
        ---------------------------------------------------------- */

        button, input, select, textarea { margin: 0; }
        input, textarea, select, fieldset { margin-bottom: 1.5rem; }

        textarea, select, .input,
        input[type='text'],
        input[type="email"],
        input[type='password'],
        input[type="search"],
        input[type="file"]
        {
            display: block;
            width: 100%;
            padding: 0.5rem 1rem;
            font-size: 2rem;
            background-color: #fff;
            background-image: none;
            border: 1px solid #ccc;
            border-radius: .25rem;
            background-color: #fcfcfc;
        }
        select,
        input[type='text'],
        input[type="email"],
        input[type='password'],
        input[type="search"],
        input[type="file"]
        {
            max-width: 400px;
            height: 4rem;
        }
        textarea:focus, select:focus, .input:focus,
        input[type="email"]:focus,
        input[type="number"]:focus,
        input[type="search"]:focus,
        input[type="text"]:focus,
        input[type="tel"]:focus,
        input[type="file"]:focus,
        input[type="url"]:focus,
        input[type="button"]:focus,
        input[type="password"]:focus,
        button:focus
        {
            border-color: #66afe9;
            outline: 0;
        }
        input[type="file"] {
            padding: 0;
            font-size: 1.5rem;
            overflow: hidden;
        }
        input[type=file]::-webkit-file-upload-button {
            height: 100%;
            color: #222;
            border: 0;
            border-right: solid 1px #ddd;
            background-color: #fff;
            padding: 0 2rem;
            font-size: 1.5rem;
        }
        select[multiple] {
            height: 15rem;
            padding: 0;
        }
        select option {
            padding: 0 0.75rem;
        }

        input[type="checkbox"],
        input[type="radio"] {
            display: inline;
            margin-bottom: 0rem;
            margin-top: -0.3em;
            vertical-align: middle;
        }

        .checkable-option {
            cursor: pointer;
        }

        input[disabled], textarea[disabled], select[disabled] {
            background-color: #f6f6f6;
            color: #666;
            border: 0;
        }
        input[readonly], textarea[readonly], select[readonly]{
            background: none;
            border: 0;
            padding: 0;
            margin-top: -1rem;
        }
        textarea {
            resize: vertical;
            padding-top: 1rem;
            height: 13rem;  /* about 4 lines */
            line-height: 1.25;
        }

        fieldset {
            max-width: 400px;
            background-color: #f5f5f5;
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1.5rem;
            border: 0;
        }


        /* Form Labels
        ---------------------------------------------------------- */

        label[disabled], fieldset input[disabled] + label { color: #999; }

        fieldset > label {
            display: block;
            cursor: pointer;
            margin-bottom: 0;
        }
        label {
            display: inline-block;
            margin-bottom: .5rem;
            user-select: none;
        }
        label > input {
            margin-right: 1rem;
        }
        input[type=radio] + label,
        input[type=checkbox] + label {
            margin-left: 1rem;
            margin-bottom: 0;
            cursor: pointer;
        }

        /* optional text */
        label + small {
            margin-left: 1rem;
            color: #999;
        }

        /* help text */
        input + small, select + small, fieldset + small {
            margin-top: -1rem;
            margin-bottom: 1.5rem;
            margin-left: 1rem;
            display: block;
            font-size: 90%;
            color: #777;
        }

        label.checkable-option {
            display: block;
        }

        .inline-label-field label {
            display: block;
            margin: 0;
            line-height: 0;
            top: 1.8rem;
            left: 1rem;
            position: relative;
        }

        .inline-label-field input {
            height: 6.5rem;
            padding-top: 3rem;
        }



        /* Misc
        ---------------------------------------------------------- */

        .lead { font-size: 3rem; font-weight: 300; }
        hr {
            display: block;
            padding: 0;
            border: 0;
            border-top: solid 1px #ddd;
            margin: 2rem 0;
        }



        /* Alerts
        ---------------------------------------------------------- */

        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            border-radius: 0.3rem;
            color: #124c77;
            border: solid 1px #d2dde6;
            background-color: #F8FCFF;
        }
        .alert b, .alert strong, .alert code {
            color: inherit;
        }
        .alert strong:first-child {
            color: #4683c3;
            font-size: inherit;
            margin-right: 1.5rem;
            font-weight: 800;
        }
        .alert.alert-error {
            color: #7d1616;
            background-color: #FFF5F5;
            border-color: #eacece;
        }
        .alert.alert-error strong:first-child {
            color: #B74A4A;
        }
        .alert.alert-success {
            color: #105d10;
            border-color: #c6e0c6;
            background-color: #FBFFFB;
        }
        .alert.alert-success strong:first-child {
            color: #393;
        }


        /* Panel
        ---------------------------------------------------------- */

        .panel {
            padding: 2rem;
            border: solid 1px #ddd;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }

        .panel > *:first-child {
            margin-top: 0;
        }

        .panel > *:last-child {
            margin-bottom: 0;
        }



        /* Images
        ---------------------------------------------------------- */

        img {
            max-width: 100%;
            height: auto;
        }


        /* Table
        ---------------------------------------------------------- */

        .table {
            max-width: 100%;
            margin: 0 auto 1rem;
            background-color: transparent;
            border-collapse: collapse;
        }
        .table th {
            text-align: left;
            text-transform: uppercase;
            font-size: 80%;
        }
        .table thead th {
            vertical-align: bottom;
        }
        .table th, .table td {
            padding: 1rem;
            line-height: 1.5;
            vertical-align: top;
            border-top: 1px solid #ddd;
        }
        .table tr:last-child th, .table tr:last-child td {
            border-bottom: 1px solid #ddd;
        }


        /* Util
        ---------------------------------------------------------- */

        .click { cursor: pointer; user-select: none; }
        .wide { width: 100%; max-width: 100%; height: auto; }
        .block { display: block; }
        .break { margin-bottom: 2rem; }




EOCSS;

        $icons = $this->inc_icons();
        $css .= $icons->u_stringify();

        $css = self::u_minify($css);

        return new \o\CssTypeString ($css);

    }

    // TODO: widths in rems?
    function inc_grid($nSizeX = 760, $breakCss = null) {

        $this->ARGS('n*', func_get_args());

        $breakX = ($nSizeX + 20) . 'px';
        $sizeX = $nSizeX . 'px';

        $breakCss = is_null($breakCss) ? '' : \o\OTypeString::getUntyped($breakCss);

        $css = <<<EOCSS

        /* Grid
        ---------------------------------------------------------- */

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 auto;
            padding: 0 2rem;
            max-width: $sizeX;
            position: relative;
        }

        main .row { padding: 0; }

        .col { flex: 1; padding-right: 1rem; padding-left: 1rem; }
        .col:first-child { padding-left: 0; }
        .col:last-child { padding-right: 0; }

        .no-gutters, .no-gutters .col { padding-left: 0; padding-right: 0; }

        .w1  { flex: 0 0 8.33% }
        .w2  { flex: 0 0 16.667%; }
        .w3  { flex: 0 0 25%; }
        .w4  { flex: 0 0 33.33%; }
        .w5  { flex: 0 0 41.66%; }
        .w6  { flex: 0 0 50%; }
        .w7  { flex: 0 0 58.33%; }
        .w8  { flex: 0 0 66.66%; }
        .w9  { flex: 0 0 75%; }
        .w10 { flex: 0 0 83.333%;}
        .w11 { flex: 0 0 91.66% }
        .w12 { flex: 0 0 100% }

        @media screen and (max-width: $breakX) {
            .row { min-width: 0; width: 100%; }
            .col { margin: 0 0 1rem; flex: 0 0 100%; }
            .no-margin-on-mobile { margin-bottom: 0 !important; }
            .hide-on-mobile { display: none !important; }
            .wide-on-mobile { display: block !important; max-width: 100%; }
            .center-on-mobile { text-align: center; margin-left: auto; margin-right: auto; }
            main pre, main .pre { font-size: 3vw; }

            $breakCss
        }
EOCSS;

        return new \o\CssTypeString ($this->u_minify($css));

    }

    // TODO: allow different stroke-width
    function inc_icons() {
        $css = <<<EOCSS

    /* Icons
    ---------------------------------------------------------- */

    .ticon, .ticonx {
        display: inline-block;
        position: relative;
        height: 1em;
        width: 1em;
        top: 0.2em;
        fill: currentColor;

    }
    .ticon * {
        stroke: currentColor;
        stroke-width: 15;
        fill: none;
    }
    .ticon .svgfill {
        stroke: none;
        fill: currentColor;
    }

EOCSS;

        return new \o\CssTypeString ($this->u_minify($css));
    }

    function u_parse($str) {

        $this->ARGS('s', func_get_args());

        $s = trim($str);
        if ($s === '') { return $str; }

        $out = [];
        $blockStack = [];
        $selectorChain = [];
        $currentBlock = [];

        $lines = preg_split('/\n+/', $s);

        foreach ($lines as $l) {

            $l = trim($l);
            if ($l === '' || substr($l, 0, 2) === '//') {
                continue;
            }

            if ($l === '}') {
                $currentBlock []= '}';

                // buffer inner blocks until we reach the top level again
                $out []= implode("\n", $currentBlock);
                $currentBlock = count($currentBlock) ? array_pop($blockStack) : [];

                array_pop($selectorChain);
            }
            else {

                $matched = preg_match('/^(.*?)\s*{\s*/', $l, $match);

             //   print_r($match);

                if ($matched) {

                    $selectorChain []= $match[1];

                    // derive entire selector
                    $selector = implode(' ', $selectorChain);

                    // handle & shortcut
                    $selector = str_replace(' &', '', $selector);

                    $selector .= ' {';

                    $blockStack []= $currentBlock;
                    $currentBlock = [$selector];
                }
                else {
                    // rule
                    $currentBlock []= '  ' . $l;
                }
            }
        }


        $css = implode("\n\n", $out);

        return $css;
    }
}




