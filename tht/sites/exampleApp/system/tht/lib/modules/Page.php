<?php

namespace o;

class u_Page extends OStdModule {

    public $didCreatePage = false;

    function u_create($fields) {

        $this->ARGS('m', func_get_args());

        $this->didCreatePage = true;

        return new u_Page_Object($fields);
    }
}


class u_Page_Object extends OClass {

    protected $errorClass = 'Page';
    protected $cleanClass = 'Page';

    private $parts = [
        'title' => '',

        'appName' => '',
        'joiner' => '-',
        'tagline' => '',

        'description' => '',
        'comment' => '',
        'image' => '',
        'icon' => '',
        'header' => [],
        'footer' => [],
        'main' => [],
        'body' => [],
        'css' => [],
        'js' => [],
        'bodyClass' => [],
        'head' => '',
    ];

    private $partType = [
        'title' => '',

        'appName' => '',
        'joiner' => '',
        'tagline' => '',

        'description' => '',
        'comment' => 'html',
        'image' => 'url',
        'icon' => 'url',
        'header' => 'html',
        'footer' => 'html',
        'main' => 'html',
        'body' => 'html',
        'head' => 'html',
        'css' => 'url',
        'js' => 'url',
        'bodyClass' => '',
    ];

    function __construct($initParts) {
        $this->setParts($initParts);
    }

    // Make sure helpLink points to module, not class
    function error($msg, $method = '') {
        Tht::module('Page')->error($msg, $method);
    }

    function setParts($parts) {

        foreach ($parts as $k => $v) {
            if (!isset($this->parts[$k])) {
                $this->error("Invalid Page field: `$k`");
            }
            $this->setPart($k, $v, true);
        }
    }


    // Setters
    //-----------------------------------------------


    function setPart($part, $val, $fromDefault = false) {

        if (OList::isa($val)) {
            foreach ($val as $v) {
                $this->setPart($part, $v, $fromDefault);
            }
            return $this;
        }

        $type = $this->partType[$part];
        if ($type) {
            // Validate TypeString
            if (OTypeString::isa($val)) {
                $gotType = $val->u_string_type();
                // e.g. 'css' can either be url or css TypeString
                if ($gotType != $type && $gotType != $part) {
                    OTypeString::getUntyped($val, $type);
                }
            }
        }

        if ($part == 'bodyClass') {
            if (!preg_match('/^[a-zA-Z0-9\-\_ ]+$/', $val)) {
                $this->error("Value for `bodyClass` contains invalid characters. Got: `$val`");
            }
        }
        else if ($part == 'description') {
            if (strlen($val) > 200) {
                $this->error('Value for `description` is longer than max size of 200 characters.');
            }
        }
        else if ($part == 'tagline') {
            if (strlen($val) > 50) {
                $this->error('Value for `tagline` is longer than max size of 50 characters.');
            }
        }
        else if ($part == 'image' || $part == 'icon') {
            $val = Tht::module('Web')->u_asset_url($val)->u_render_string();
        }

        if (is_array($this->parts[$part])) {
            $this->parts[$part] []= $val;
        }
        else {
            $this->parts[$part] = $val;
        }

        return $this;
    }


    // HTML Parts
    //-----------------------------------------------

    function u_set_header($val) {

        $this->ARGS('*', func_get_args());

        return $this->setPart('header', $val);
    }

    function u_set_footer($val) {

        $this->ARGS('*', func_get_args());

        return $this->setPart('footer', $val);
    }

    function u_set_main($val) {

        $this->ARGS('*', func_get_args());

        return $this->setPart('main', $val);
    }

    function u_set_body($val) {

        $this->ARGS('*', func_get_args());

        return $this->setPart('body', $val);
    }


    // Meta Parts
    //-----------------------------------------------

    function u_set_title ($val) {

        $this->ARGS('s', func_get_args());

        return $this->setPart('title', $val);
    }

    function u_set_description ($val) {

        $this->ARGS('s', func_get_args());

        return $this->setPart('description', $val);
    }

    function u_set_comment ($val) {

        $this->ARGS('*', func_get_args());

        return $this->setPart('comment', $val);
    }

    function u_set_icon ($val) {

        $this->ARGS('*', func_get_args());

        return $this->setPart('icon', $val);
    }

    function u_set_image ($val) {

        $this->ARGS('*', func_get_args());

        return $this->setPart('image', $val);
    }

    function u_add_to_head ($val) {

        $this->ARGS('*', func_get_args());

        return $this->setPart('head', $val);
    }


    // Asset Parts
    //-----------------------------------------------

    function u_add_body_class($val) {

        $this->ARGS('s', func_get_args());

        return $this->setPart('bodyClass', $val);
    }

    // TODO: allow css or url typestrings?
    function u_add_css ($val) {

        $this->ARGS('*', func_get_args());

        return $this->setPart('css', $val);
    }

    // TODO: allow js or url typestrings?
    function u_add_js ($val) {

        $this->ARGS('*', func_get_args());

        return $this->setPart('js', $val);
    }



    // Output
    //-----------------------------------------------


    // Print a well-formed HTML document with sensible defaults
    function u_send () {

        $this->ARGS('', func_get_args());

        $out = new HtmlTypeString ($this->u_render());

        Tht::module('Output')->u_send_html($out);

        return $this;
    }

    // Undocumented.
    function u_render() {

        $this->ARGS('m', func_get_args());

        $val = $this->getRenderedParts();

        $out = $this->pageTemplate($val);

        return $out;
    }

    function getRenderedParts() {

        $part = $this->parts;

        $val = [];

        if ($part['body']) {
            $val['body'] = $this->htmlSection('body', $part['body']);
        }
        else {
            $val['body'] = '';
            $val['body'] .= $this->htmlSection('header', $part['header']);
            $val['body'] .= $this->htmlSection('main',   $part['main']);
            $val['body'] .= $this->htmlSection('footer', $part['footer']);
        }

        $val['css'] = $this->assetTags('css', $part['css'], '<style>{body}</style>');
        $val['js']  = $this->assetTags('js', $part['js'], '<script nonce="{nonce}">{body}</script>');

        // TODO: add 'og:type' = 'website' or 'article' support (with publishDate etc)
        // TODO: add 'og:url' for canonical URL
        // TODO: add 'og:locale' for current locale

        $val['ogSiteName']  = $this->getOgSiteNameHtml();
        $val['ogTitle']     = $this->getOgTitleHtml();
        $val['title']       = $this->getTitleHtml();
        $val['comment']     = $this->getCommentHtml();
        $val['description'] = $this->getDescHtml();
        $val['bodyTag']     = $this->getBodyTagHtml();

        if ($this->parts['head']) {
            $val['head'] = $this->parts['head']->u_render_string();
        } else {
            $val['head'] = '';
        }

        // TODO: get updateTime of the files, allow base64 urls
        $val['image'] = $this->headTag($part['image'], '<meta property="og:image" content="{url}">');
        $val['icon'] = $this->headTag($part['icon'], '<link rel="icon" href="{url}">');

        return $val;
    }

    function pageTemplate($val) {

        $chunks = [
            '<!doctype html>',
            $val['comment'],
            '<html>',
            '<head>',
                $val['title'],
                $val['description'],
                '<meta name="viewport" content="width=device-width, initial-scale=1.0"/>',
                $val['ogTitle'],
                $val['ogSiteName'],
                $val['head'],
                $val['css'],
                $val['image'],
                $val['icon'],
            '</head>',
            $val['bodyTag'],
                $val['body'],
                $val['js'],
            '</body>',
            '</html>',
        ];

        $out = '';
        foreach ($chunks as $c) {
            if ($c !== '') { $out .= trim($c) . "\n"; }
        }

        return $out;
    }

    function htmlSection($part, $html) {

        if (!$html) { return ''; }

        $out = '';

        $chunks = $this->parts[$part];

        foreach ($chunks as $c) {
            $out .= OTypeString::getUntyped($c, 'html');
        }

        $out = '<' . $part . '>' .  $out . '</' . $part  . '>';

        return $out;
    }

    function getBodyTagHtml() {

        $part = $this->parts;

        $bodyClass = implode(' ', $part['bodyClass']);
        $bodyClass = $this->cleanOutputVal($bodyClass);

        return '<body class="' . $bodyClass . '">';
    }

    function getOgSiteNameHtml() {

        $part = $this->parts;

        $name = $part['appName'] ? $part['appName'] : '';
        if (!$name) { return ''; }

        $name = $this->cleanOutputVal($name);

        return "<meta property=\"og:site_name\" content=\"$name\"/>";
    }

    function getOgTitleHtml() {

        $part = $this->parts;

        $title = $part['title'] ? $part['title'] : ($part['tagline'] ? $part['tagline'] : '');
        if (!$title) { return ''; }

        $title = $this->cleanOutputVal($title);

        return "<meta property=\"og:title\" content=\"$title\"/>";
    }

    function getTitleHtml() {

        $title = $this->parts['title'];

        if ($this->parts['appName']) {
            if ($this->parts['title']) {
                $title = $this->parts['title'] . ' ' . $this->parts['joiner'] . ' ' . $this->parts['appName'];
            }
            else {
                if ($this->parts['tagline']) {
                    $title = $this->parts['appName'] . ' ' . $this->parts['joiner'] . ' ' . $this->parts['tagline'];
                }
                else {
                    // No tagline and no title
                    $title = $this->parts['appName'];
                }
            }
        }

        $title = $this->cleanOutputVal($title);

        return "<title>$title</title>";
    }

    function getCommentHtml() {

        $val = $this->parts['comment'];

        if ($val) {
            $rendered = v(v(v($val)->u_render_string())->u_indent(4))->u_trim_right();
            return "\n<!--\n\n" . $rendered . "\n\n-->";
        }

        return '';
    }

    function getDescHtml() {

        $val = $this->parts['description'];

        $val = $this->cleanOutputVal($val);

        if ($val) {
            return "<meta name=\"description\" property=\"og:description\" content=\"$val\"/>";
        }

        return '';
    }

    function cleanOutputVal($val) {
        $val = preg_replace('/\s+/', ' ', trim($val));
        $val = v($val)->u_encode_html();

        return $val;
    }

    function headTag($val, $template) {

        if (!$val) { return ''; }

        $tHtml = new HtmlTypeString($template);
        $fillParams = OMap::create(['url' => $val]);

        return $tHtml->u_fill($fillParams)->u_render_string() . "\n";
    }

    // Print css & js tags
    function assetTags ($type, $paths, $blockTag) {

        if (!count($paths)) { return ''; }

        $nonce = Tht::module('Web')->u_nonce();

        $includes = [];
        $blocks = [];

        foreach ($paths as $path) {

            if (HtmlTypeString::isa($path)) {
                // Pre-defined block
                $tag = $path->u_render_string();
                $blocks []= $tag;
            }
            else if (UrlTypeString::isa($path)) {
                // Link to asset
                $tag = Tht::module('Web')->assetTag($type, $path);
                $includes []= $tag->u_render_string();
            }
            else {

                // Inline it in the HTML document
                $str = $path->u_render_string();
                if ($type == 'js' && !preg_match('#\s*\(function\(\)\{#', $str)) {
                    $str = "(function(){" . $str . "})();";
                }

                $vals = OMap::create([
                    'body' => OTypeString::create('html', $str),
                    'nonce' => $nonce,
                ]);

                $blockTag = new HtmlTypeString($blockTag);
                $blockTag->u_fill($vals);
                $blocks []= $blockTag->u_render_string();
            }
        }

        $sIncludes = implode("\n", $includes);
        $sBlocks = implode("\n\n", $blocks);

        return $sIncludes . "\n" . $sBlocks;
    }
}