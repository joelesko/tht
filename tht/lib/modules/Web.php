<?php

namespace o;

class u_Web extends OStdModule {

    private $icons = null;
    private $assetUrlCache = [];

    function u_nonce () {

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

        OTypeString::getUntyped($url, 'url');

        $tag = OTypeString::create(
            'html', '<script src="{}" nonce="{}"></script>'
        );

        $tag->u_fill(OList::create([
            $url,
            $this->u_nonce(),
        ]));

        return $tag;
    }

    function u_css_tag($url) {

        $this->ARGS('*', func_get_args());

        OTypeString::getUntyped($url, 'url');

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
    function u_table ($rows, $keys, $headings=[], $params=[]) {

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

    function u_anchor_link($label) {

        $this->ARGS('s', func_get_args());

        $slug = v($label)->u_slug();
        $html = "<a href=\"#$slug\" class=\"anchor-link\">#</a><a name=\"$slug\"></a>";

        return OTypeString::create('html', $html);
    }

    function u_post_link($label, $actionUrl, $data, $klass='') {

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

    // TODO: mail, cart
    // TODO: Make paths an order of magnitude smaller so they aren't so big when they are unstyled?
    function icons() {

        if ($this->icons) {
            return $this->icons;
        }

        $this->icons = [

            'arrowLeft'  => '<path d="M30,50H90z"/><polyline points="50,15 20,50 50,85"/>',
            'arrowRight' => '<path d="M10,50H70z"/><polyline points="50,15 80,50 50,85"/>',
            'arrowUp'    => '<path d="M50,30V90z"/><polyline points="15,50 50,20 85,50"/>',
            'arrowDown'  => '<path d="M50,10V70z"/><polyline points="15,50 50,80 85,50"/>',

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

            'home'   => '<path class="svgfill" d="M0,45 50,05 100,45z"/><rect class="svgfill" x="15" y="40" height="50" width="25" /><rect class="svgfill" x="60" y="40" height="50" width="25" /><rect class="svgfill" x="40" y="40" height="20" width="20" />',
            'download' => '<path class="svgfill" d="M12,38 50,75 88,38z"/><rect class="svgfill" x="35" y="2" height="40" width="30" /><rect class="svgfill" x="5" y="88" height="10" width="90" />',
            'upload'   => '<path class="svgfill" d="M12,38 50,0 88,38z"/><rect class="svgfill" x="35" y="33" height="40" width="30" /><rect class="svgfill" x="5" y="88" height="10" width="90" />',
            'search'    => '<circle cx="45" cy="40" r="30"/><path d="M95,95 65,60z"/>',
            'lock'      => '<rect class="svgfill" x="15" y="37" height="48" width="70" rx="5" rx="5" /><rect style="stroke-width:12" x="31" y="10" height="50" width="38" rx="15" rx="15" />',
            'heart'     => '<path class="svgfill" d="M90,45 50,85 10,45z"/><rect class="svgfill" x="48" y="43" height="4" width="4"/><circle class="svgfill" cx="29" cy="31" r="23"/><circle class="svgfill" cx="71" cy="31" r="23"/>',

            // generated from $this->starIcon(50,50,55,22)
            'star'     => '<path class="svgfill" d="M102.38,33.22 70.89,56.89 82.14,94.63 49.91,72.00 17.49,94.36 29.05,56.71 -2.24,32.79 37.14,32.15 50.23,-5.00 63.01,32.26z"/>',

            'twitter' => '<svg class="ticonx" viewBox="0 0 33 33"><g><path d="M 32,6.076c-1.177,0.522-2.443,0.875-3.771,1.034c 1.355-0.813, 2.396-2.099, 2.887-3.632 c-1.269,0.752-2.674,1.299-4.169,1.593c-1.198-1.276-2.904-2.073-4.792-2.073c-3.626,0-6.565,2.939-6.565,6.565 c0,0.515, 0.058,1.016, 0.17,1.496c-5.456-0.274-10.294-2.888-13.532-6.86c-0.565,0.97-0.889,2.097-0.889,3.301 c0,2.278, 1.159,4.287, 2.921,5.465c-1.076-0.034-2.088-0.329-2.974-0.821c-0.001,0.027-0.001,0.055-0.001,0.083 c0,3.181, 2.263,5.834, 5.266,6.438c-0.551,0.15-1.131,0.23-1.73,0.23c-0.423,0-0.834-0.041-1.235-0.118 c 0.836,2.608, 3.26,4.506, 6.133,4.559c-2.247,1.761-5.078,2.81-8.154,2.81c-0.53,0-1.052-0.031-1.566-0.092 c 2.905,1.863, 6.356,2.95, 10.064,2.95c 12.076,0, 18.679-10.004, 18.679-18.68c0-0.285-0.006-0.568-0.019-0.849 C 30.007,8.548, 31.12,7.392, 32,6.076z"></path></g></svg>',

            'facebook' => '<svg class="ticonx" viewBox="0 0 33 33"><g><path d="M 17.996,32L 12,32 L 12,16 l-4,0 l0-5.514 l 4-0.002l-0.006-3.248C 11.993,2.737, 13.213,0, 18.512,0l 4.412,0 l0,5.515 l-2.757,0 c-2.063,0-2.163,0.77-2.163,2.209l-0.008,2.76l 4.959,0 l-0.585,5.514L 18,16L 17.996,32z"></path></g></svg>',

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

        $randClass1 = strtolower(Tht::module('String')->u_random(rand(6,12)));
        $randClass1 = preg_replace('/[^a-z]/', '', $randClass1);

        // random "content" that will be hidden
        $randContent = Tht::module('String')->u_random(rand(10,30));

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

        HitCounter::$skipThisPage = $doSkip;

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
