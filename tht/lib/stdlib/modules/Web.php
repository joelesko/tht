<?php

namespace o;

class u_Web extends OStdModule {

    private $icons = null;
    private $assetUrlCache = [];

    public $skipHitCounter = false;

    function u_nonce() {

        $this->ARGS('', func_get_args());

        return Security::getNonce();
    }

    function u_csrf_token() {

        $this->ARGS('', func_get_args());

        return Security::getCsrfToken();
    }

    function assetTag($type, $url) {

        if ($type == 'css') {
            return $this->u_css_tag($url);
        }
        else if ($type == 'js') {
            return $this->u_js_tag($url);
        }
    }

    function u_js_tag($url) {

        $this->ARGS('*', func_get_args());

        OTypeString::assertType($url, 'url');

        if ($url->u_is_local()) {
            $tag = OTypeString::create(
                'html', '<script src="{}" nonce="{}"></script>'
            );
            $tag->u_fill(OList::create([
                $url,
                $this->u_nonce(),
            ]));
        }
        else {
            // TODO: add checksum
            // integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            $tag = OTypeString::create(
                'html', '<script src="{}" nonce="{}" crossorigin="anonymous"></script>'
            );
            $tag->u_fill(OList::create([
                $url,
                $this->u_nonce(),
            ]));
        }

        return $tag;
    }

    function u_css_tag(UrlTypeString $url): HtmlTypeString {

        $this->ARGS('*', func_get_args());

        $tag = OTypeString::create(
            'html', '<link rel="stylesheet" href="{}" />'
        );

        $tag->u_fill(OList::create([
            $url
        ]));

        return $tag;
    }

    // TODO: document, xDangerParseHtml?
    // function u_parse_html($raw) {
    //     $this->ARGS('*', func_get_args());
    //     return Tht::parseTemplateString('html', $raw);
    // }

    // TODO: document
    function u_table($rows, $keys, $headings=[], $params=[]) {

        $this->ARGS('lllm', func_get_args());

        $str = $this->openTag('table', $params);
        $rows = unv($rows);
        $keys = unv($keys);
        $headings = unv($headings);
        $str .= '<tr>';

        foreach ($headings as $h) {
            $str .= '<th>' . Security::escapeHtml($h) . '</th>';
        }
        $str .= '</tr>';

        foreach ($rows as $row) {
            $str .= '<tr>';
            $row = unv($row);
            foreach ($keys as $k) {
                $str .= '<td>' . (isset($row[$k]) ? Security::escapeHtml($row[$k]) : '') . '</td>';
            }
            $str .= '</tr>';
        }
        $str .= "</table>\n";

        return new \o\HtmlTypeString ($str);
    }

    // TODO: document?  or move to Url.link
    function u_link($lUrl, $label, $params=null) {

        if (is_null($params)) { $params = OMap::create([]); }

        $this->ARGS('*sm', [$lUrl, $label, $params]);

        $url = OTypeString::getUntyped($lUrl, 'url');

        $params['href'] = $url;
        $rawLink = $this->openTag('a', $params) . Security::escapeHtml($label) . '</a>';

        return OTypeString::create('html', $rawLink);
    }

    // Url.setHash, getHash
    function u_anchor_url($label, $params = null) {

        $this->ARGS('sm', func_get_args());

        $slug = v($label)->u_to_token_case('-');
        return OTypeString::create('url', '#' . $slug);
    }

    function u_anchor($label, $optionMap = null) {

        $this->ARGS('sm', func_get_args());

        $optionMap = $this->flags($optionMap, [
            'link' => false,
            'linkLabel' => '#',
        ]);

        $slug = v($label)->u_to_token_case('-');
        $html = "<a name=\"$slug\"></a>";

        if ($optionMap['link']) {
            $selfLink = "<a href=\"#$slug\" class=\"anchor-link\">" . $optionMap['linkLabel'] . "</a>";
            $html = $selfLink . $html;
        }

        return OTypeString::create('html', $html);
    }

    function u_post_link(string|HtmlTypeString $label, UrlTypeString $actionUrl, OMap $data, string $klass=''): HtmlTypeString {

        $this->ARGS('**ms', func_get_args());

        $data['csrfToken'] = $this->u_csrf_token();
        $fields = '';
        foreach ($data as $k => $v) {
            $k = Security::escapeHtml($k);
            $dataParam = [ 'type' => 'hidden' ];
            $dataParam['name'] = $k;
            $dataParam['value'] = $v;
            $fields .= $this->openTag('input', $dataParam, true);
        }

        $klass = Security::escapeHtml($klass);

        $action = $actionUrl ? OTypeString::getUntyped($actionUrl, 'url') : '';
        $html = trim("
<form method=\"post\" action=\"$action\" style=\"display: inline-block;\">
$fields
<button type=\"submit\" class=\"$klass\">{}</button>
</form>
        ");

        return OTypeString::create('html', $html)->u_fill($label);
    }

    function openTag($name, $params, $selfClose=false) {

        $out = '<' . $name . ' ';
        $lParams = [];

        $mainClass = isset($params['mainClass']) ? $this->getClassProp($params['mainClass']) : '';
        unset($params['mainClass']);

        $userClass = isset($params['class']) ? $this->getClassProp($params['class']) : '';
        $params['class'] = trim(implode(' ', [$mainClass, $userClass]));

        foreach ($params as $k => $v) {
            if ($v) {
                $lParams []= $k . '="' . Security::escapeHtml($v) . '"';
            }
        }

        $out .= implode(' ', $lParams);
        if ($selfClose) { $out .= '/'; }
        $out .= '>';

        return $out;
    }

    // TODO: document
    function u_breadcrumbs($links, $joiner = ' > ', $params=[]) {

        $this->ARGS('l*m', func_get_args());

        $aLinks = [];
        foreach ($links as $l) {
            $aLinks []= Tht::module('Web')->u_link($l['url'], $l['label'])->u_render_string();
        }

        if (is_string($joiner)) {
            $joiner = Security::escapeHtml($joiner);
        } else {
            $joiner = v($joiner)->u_render_string();
        }

        $joiner = '<span class="breadcrumbs-joiner">' . $joiner . '</span>';
        $h = implode($joiner, $aLinks);

        $params['mainClass'] = 'breadcrumbs';
        $h = $this->openTag('div', $params) . $h . "</div>";

        return OTypeString::create('html', $h);
    }

    function getClassProp($raw) {

        if (is_string($raw)) {
            return $this->untaintClassProp($raw);
        }
        else if (OList::isa($raw)) {
            $prop = implode(' ', $raw);
            return $this->untaintClassProp($prop);
        }
        else if (OMap::isa($raw)) {
            $prop = '';
            foreach ($raw as $c => $onOff) {
                if ($onOff) {
                    $prop .= $c . ' ';
                }
            }
            return $this->untaintClassProp(rtrim($c));
        }

        return '';
    }

    function untaintClassProp($raw) {
        return preg_replace('/[^a-zA-Z0-9_\- ]/', '', $raw);
    }

    // DO NOT REMOVE
    // function makeStarIcon($centerX, $centerY, $outerRadius, $innerRadius) {
    //
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
    //
    //     return implode(' ', $points);
    // }

    // TODO: edit (pencil), trash, image, phone, mail, chat bubble, profile person, link, tag, cart, sign-in, sign-out
    // TODO: Make paths an order of magnitude smaller so they aren't so big when they are unstyled?
    function icons() {

        if ($this->icons) {
            return $this->icons;
        }

        $this->icons = [

            'arrowLeft'  => '<path d="M25,50H90z"/><polyline points="50,15 20,50 50,85"/>',
            'arrowRight' => '<path d="M10,50H75z"/><polyline points="50,15 80,50 50,85"/>',
            'arrowUp'    => '<path d="M50,25V90z"/><polyline points="15,50 50,20 85,50"/>',
            'arrowDown'  => '<path d="M50,10V75z"/><polyline points="15,50 50,80 85,50"/>',

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
            'plus'         => '<path d="M12,50H88zM50,12V88z"/>',
            'minus'        => '<path d="M12,50H88z"/>',
            'cancel'       => '<path d="M20,20 80,80z M80,20 20,80z"/>',
            'check'        => '<polyline points="15,45 40,70 85,15"/>',

            'home'     => '<path class="svgfill" d="M0,45 50,05 100,45z"/><rect class="svgfill" x="15" y="40" height="50" width="25" /><rect class="svgfill" x="60" y="40" height="50" width="25" /><rect class="svgfill" x="40" y="40" height="20" width="20" />',
            'download' => '<path class="svgfill" d="M12,38 50,75 88,38z"/><rect class="svgfill" x="35" y="2" height="40" width="30" /><rect class="svgfill" x="5" y="88" height="10" width="90" />',
            'upload'   => '<path class="svgfill" d="M12,38 50,0 88,38z"/><rect class="svgfill" x="35" y="33" height="40" width="30" /><rect class="svgfill" x="5" y="88" height="10" width="90" />',
            'search'   => '<circle cx="45" cy="40" r="30"/><path d="M95,95 65,60z"/>',
            'lock'     => '<rect class="svgfill" x="15" y="37" height="48" width="70" rx="5"/><rect style="stroke-width:12" x="31" y="10" height="50" width="38" rx="15" />',
            'heart'    => '<path class="svgfill" d="M89.5,45 50,85 10.5,45z"/><rect class="svgfill" x="35" y="39" height="20" width="30"/><circle class="svgfill" cx="29" cy="31" r="23"/><circle class="svgfill" cx="71" cy="31" r="23"/>',


            // Generated from $this->starIcon(50,50,55,22)
            'star'  => '<path class="svgfill" d="M102.38,33.22 70.89,56.89 82.14,94.63 49.91,72.00 17.49,94.36 29.05,56.71 -2.24,32.79 37.14,32.15 50.23,-5.00 63.01,32.26z"/>',

            // 'gear'  => preg_replace("/\n\s*/", '', '<circle cx="50" cy="50" r="24"/>
            //             <rect class="svgfill" x="42" y="8" height="16" width="16" />
            //             <rect class="svgfill" x="42" y="76" height="16" width="16" />
            //             <rect class="svgfill" x="8" y="42" height="16" width="16" />
            //             <rect class="svgfill" x="76" y="42" height="16" width="16" />
            //             <g transform="rotate(45 50 50)">
            //             <rect class="svgfill" x="42" y="8" height="16" width="16" />
            //             <rect class="svgfill" x="42" y="76" height="16" width="16" />
            //             <rect class="svgfill" x="8" y="42" height="16" width="16" />
            //             <rect class="svgfill" x="76" y="42" height="16" width="16" />
            //             </g>
            // '),

            // Optimized version of above.
            'gear' => '<circle cx="50" cy="50" r="24" /><path class="svgfill" d="M42 8h16v16H42zM42 76h16v16H42zM8 42h16v16H8zM76 42h16v16H76zM74.042 14.645l11.313 11.313-11.313 11.314-11.314-11.314zM25.958 62.728l11.314 11.314-11.314 11.313-11.313-11.313zM25.958 14.645l11.314 11.313-11.314 11.314-11.313-11.314zM74.042 62.728l11.313 11.314-11.313 11.313-11.314-11.313z"/>',

        ];

        return $this->icons;
    }

    // TODO: document
    function u_all_icons() {

        $this->ARGS('', func_get_args());

        return array_keys($this->icons());
    }

    function u_icon($id) {

        $this->ARGS('s', func_get_args());

        $icons = $this->icons();
        if (!isset($icons[$id])) {
            ErrorHandler::setHelpLink('/reference/icons', 'Icons');
            Tht::error("Unknown icon: `$id`");
        }
        if (substr($icons[$id], 0, 4) == '<svg') {
            return new \o\HtmlTypeString($icons[$id]);
        }
        $rawTag = '<svg class="ticon ticon-' . $id . '" viewBox="0 0 100 100">' . $icons[$id] . '</svg>';

        return new \o\HtmlTypeString($rawTag);
    }

    // TODO: UNDOCUMENTED - should be made more accessibility friendly.  maybe via JavaScript.
    function u_mask_email($email) {

        $this->ARGS('s', func_get_args());

        // TODO: show placeholder if user is not logged in

        $atPos = strpos($email, '@');
        if ($atPos === false) {
            $this->error("Invalid email address: `$email`");
        }

        $spanPos = rand(1, $atPos - 1);
        $emailLeft = substr($email, 0, $spanPos);
        $emailRight = $this->encodeAllChars(substr($email, $spanPos));

        $randClass1 = strtolower(Tht::module('String')->u_random_token(rand(6,12)));
        $randClass1 = preg_replace('/[^a-z]/', '', $randClass1);

        // random "content" that will be hidden
        $randContent = Tht::module('String')->u_random_token(rand(10,30));

        $xe = $emailLeft . "<div class=\"$randClass1\">$randContent</div>" . $emailRight;
        $xe .= "<style>.$randClass1{display:none;}</style>";

        return new HtmlTypeString ($xe);
    }

    // TODO: make this a string method?
    function encodeAllChars($str) {

        $str = mb_convert_encoding($str , 'UTF-32', 'UTF-8');
        $t = unpack("N*", $str);
        $t = array_map(function($n) { return "&#$n;"; }, $t);

        return implode("", $t);
    }

    // TODO: Undocumented
    function u_skip_hit_counter($doSkip = true) {

        $this->ARGS('b', func_get_args());

        $this->skipHitCounter = $doSkip;

        return $doSkip;
    }

    function u_htmx($mode, $data) {

        $this->ARGS('sm', func_get_args());

        $data['csrfToken'] = Security::getCsrfToken();
        $data['mode'] = $mode;

        $vals = Tht::module('Json')->u_encode($data)->u_render_string();

        return new HtmlTypeString("hx-post=\"\" hx-vals='$vals'");
    }
}
