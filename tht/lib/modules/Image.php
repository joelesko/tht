<?php

namespace o;

/*
    // UNDOCUMENTED -- for future release.

    // TODO: Move optimizer code here.

    getTextBox

    imagelayereffect
    imagesetbrush
    imagesettile

    imagecopymerge - pct
    imagecopymergegray - preserves hue of the source by converting dest to grayscale

*/


class u_Image extends OStdModule {

    // Aliases: ico -> bmp, jpg -> jpeg
    public static $supportedExts = ['bmp', 'jpeg', 'png', 'webp', 'gif'];

    public static $pngCompression = 9;         // 0-10  (lossless. 9 = most/slowest compression)
    public static $jpegQuality = 80;           // 0-100 (lossy. 100 = bigger size, highest quality)
    public static $bmpRleCompression = false;  // true|false  (lossless) doesn't seem to havy any effect.

    var $suggestMethod = [];

    function __construct() {
        if (!extension_loaded('gd')) {
            $this->error("The Image module requires the PHP `GD` extension to be installed.");
        }
    }

    function u_create($sizeMap) {

        $this->ARGS('m', func_get_args());

        $gdi = imagecreatetruecolor($sizeMap['sizeX'], $sizeMap['sizeY']);

        imagesavealpha($gdi, true);

        return new u_Image_Object($gdi);
    }

    function u_load($oFilePath) {

        $this->ARGS('s', func_get_args());

        $filePath = Tht::module('File')->u_app_path($oFilePath);
        $mime = Tht::module('File')->u_get_mime_type($filePath);
        $ext = Tht::module('File')->u_mime_type_to_extension($mime);

        if ($ext == 'jpg') { $ext = 'jpeg'; }
        if ($ext == 'ico') { $ext = 'bmp'; }

        if (!in_array($ext, self::$supportedExts)) {
            $this->error("Unsupported MIME type `$mime` for file: `$filePath`");
        }

        $fnCreate = 'imagecreatefrom' . $ext;

        // todo: scale image w nearest neighbor

        $gdi = $fnCreate($filePath);

        return new u_Image_Object($gdi);
    }

    function u_color_presets() {

        $this->ARGS('', func_get_args());

        $presets = [
            'black' => '#000000',
            'gray'  => '#888888',
            'white' => '#FFFFFF',

            'red'    => '#FF595E',
            'orange' => '#FFA500',
            'yellow' => '#FFCA3A',
            'green'  => '#8AC926',
            'blue'   => '#1982C4',
            'purple' => '#6A4C93',
        ];

        return OMap::create($presets);
    }
}

class u_Image_Object extends OClass {

    private $gdi = null; // GD image handle

    private $sizeX = 0;
    private $sizeY = 0;
    private $pxDensity = 2;

    private $colors = [];

    protected $errorClass = 'Image';
    protected $cleanClass = 'Image';

    function __construct($gdi) {

        $this->updateImage($gdi);

        imagealphablending($this->gdi, false);

        // TODO: lazy init background with custom color?
        $this->u_fill($this->coord(1, 1), OMap::create([ 'color' => 'transparent']));

        imagealphablending($this->gdi, true);
    }

    // public function u_on_print() {

    //     $base64 = $this->u_to_base64();
    //     $tag = '<img style="max-width:1000px; max-height: 600px; box-shadow: 0 0 10px rgba(128,128,128,0.2)" src="' . $base64 . '">';

    //     return new HtmlTypeString($tag);
    // }

    public function u_on_string_token() {

        $this->ARGS('', func_get_args());

        return 'sizeX: ' . $this->sizeX . ', sizeY: ' . $this->sizeY;
    }

    function updateImage($gdi) {

        $this->gdi = $gdi;

        $this->sizeX = imagesx($gdi);
        $this->sizeY = imagesy($gdi);

        $this->u_create_color('transparent', '#000000', 0.0);

        imagesavealpha($this->gdi, true);

        // Change mode from replacing alpha pixels to blending them
        imagealphablending($this->gdi, true);
    }

    function coord($x, $y) {
        return OMap::create(['x' => $x, 'y' => $y]);
    }

    // x, y, toX, toY, sizeX, sizeY,
    // toRelX, toRelY
    // center,
    // fromAngle, toAngle|toRelAngle
    // angle, distance
    function validatePos($pos, $schema) {

        $allowed = preg_split('/\s*,\s*/', $schema);
        foreach ($pos as $k=>$v) {
            if (substr($k, 0, 1) == ':') { continue; }
            if (!in_array($k, $allowed)) {
                $this->error("Unknown position key: `$k`  Try: $schema");
            }
        }

        if (!isset($pos['x'])) { $error = 'Missing position key: `x`'; }
        if (!isset($pos['y'])) { $error = 'Missing position key: `y`'; }

        if (in_array('toRelX', $allowed)) {
            // calculate toX/toY from relX/relY
            // TODO: require both
            if (isset($pos['toRelX'])) {  $pos['toX'] = $pos['x'] + $pos['toRelX']; }
            if (isset($pos['toRelY'])) {  $pos['toY'] = $pos['y'] + $pos['toRelY']; }
        }

        if (in_array('toX', $allowed)) {
            // Require both toX and toY
            if (isset($pos['toX']) && !isset($pos['toY'])) { $this->error('Must set `toY` when `toX` is set.'); }
            if (isset($pos['toY']) && !isset($pos['toX'])) { $this->error('Must set `toX` when `toY` is set.'); }
            if (isset($pos['toX'])) {
                $pos['sizeX'] = $pos['toX'] - $pos['x'];
                $pos['sizeY'] = $pos['toY'] - $pos['y'];
            }
        }

        if (in_array('center', $allowed)) {
            if (!isset($pos['center'])) { $pos['center'] = false; }
            if ($pos['center'] && !isset($pos['sizeX'])) {
                $this->error('Must set `sizeX` or `sizeY` when `center` is true.');
            }
        }

        if (in_array('sizeX', $allowed)) {

            // Default to square aspect ratio
            if (isset($pos['sizeX']) && !isset($pos['sizeY'])) { $pos['sizeY'] = $pos['sizeX']; }
            if (isset($pos['sizeY']) && !isset($pos['sizeX'])) { $pos['sizeX'] = $pos['sizeY']; }

            // Must have either toX/toY or sizeX/sizeY
            if (!isset($pos['sizeX']) && !isset($pos['toX'])) {  $this->error('Missing position key: `sizeX` or `toX`'); }
            if (!isset($pos['sizeY']) && !isset($pos['toY'])) {  $this->error('Missing position key: `sizeY` or `toY`'); }

            // Calculate size based on toX/toY
            if (!isset($pos['sizeX']) && isset($pos['toX'])) {  $pos['sizeX'] = $pos['toX'] - $pos['x']; }
            if (!isset($pos['sizeY']) && isset($pos['toY'])) {  $pos['sizeY'] = $pos['toY'] - $pos['y']; }

            // Calculate toX/toY based on sizeX/sizeY
            if (!isset($pos['toX'])) {  $pos['toX'] = $pos['x'] + $pos['sizeX']; }
            if (!isset($pos['toY'])) {  $pos['toY'] = $pos['y'] + $pos['sizeY']; }
        }

        if (in_array('center', $allowed)) {

            $halfX = floor($pos['sizeX'] / 2);
            $halfY = floor($pos['sizeY'] / 2);

            if ($pos['center']) {
                $pos['x'] += $halfX * $pos[':centerSign'];
                $pos['y'] += $halfY * $pos[':centerSign'];
                $pos['toX'] += $halfX * $pos[':centerSign'];
                $pos['toY'] += $halfY * $pos[':centerSign'];
            }
        }

        return $pos;
    }

    function validateStyle($style, $schema='fill, thick, color') {

        if (is_null($style)) {
            $style = OMap::create([]);
        }

        if (!isset($style['thick'])) {
            $style['thick'] = 1;
        }
        if (!isset($style['fill'])) {
            $style['fill'] = false;
        }
        if (!isset($style['color'])) {
            $style['color'] = 'black';
        }

        $allowed = preg_split('/\s*,\s*/', $schema);
        foreach ($style as $k=>$v) {
            if (!in_array($k, $allowed)) {
                $this->error("Unknown style key: `$k` Try: $schema");
            }
        }

        $style['color'] = $this->resolveColorStyle($style['color']);

        return $style;
    }

    // TODO: get closest palette color
    function resolveColorStyle($color) {

        if (is_string($color)) {
            if (substr($color, 0, 1) == '#') {
                return $this->getColorIntFromHex($color);
            }
            else {
                return $this->getColorIntFromName($color);
            }
        }
        else if (OMap::isa($color)) {
            return $this->getColorIntFromMap($color);
        }

        $this->error("Style color must be a map of `{ r, g, b, opacity }`, hex digits, or color name.");
    }

    function getColorIntFromHex($hex) {

        $hex = ltrim($hex, '#');

        // make sure alpha is set
        if (strlen($hex) == 6) {
            $hex = 'FF' . $hex;
        }

        return Tht::module('Math')->u_hex_to_dec($hex);
    }

    function getColorIntFromName($colorName) {

        $int = $this->getColor($colorName);

        return $int;
    }

    function getColorIntFromMap($map) {

        // TODO: require r,g,b
        if (!isset($map['opacity'])) {
            $map['opacity'] = 1;
        }
        $alpha = floor((1.0 - $map['opacity']) * 127);
        $int = imagecolorallocatealpha($maskedImage, $map['r'], $map['g'], $map['b'], $alpha);

        return $int;
    }

    function colorHexToArray($hex) {

        if (is_string($hex)) {

            $int = $this->getColorIntFromHex($hex);
        }

        $a = ($int >> 24) & 0xFF;
        $r = ($int >> 16) & 0xFF;
        $g = ($int >> 8) & 0xFF;
        $b = $int & 0xFF;

        return [$r, $g, $b, $a];
    }

    function colorHexToMap($hex) {

        $ary = $this->colorHexToArray($hex);

        $rgb = (($ary[0] & 0xFF) << 16) + (($ary[1] & 0xFF) << 8)   + ($ary[2] & 0xFF);

        return OMap::create([
            'r' => $ary[0],
            'g' => $ary[1],
            'b' => $ary[2],
            'rgb' => $rgb,
            'opacity' => (127 - $ary[3]) / 127,
        ]);
    }

    function u_save($oFilePath, $flags=null) {

        $this->ARGS('sm', func_get_args());

        $flags = $this->flags($flags, [
            'quality'         => u_Image::$jpegQuality,
            'pngCompression'  => u_Image::$pngCompression,
        ]);

        $foundExt = preg_match('/\.([a-zA-Z]{3,4})/', $oFilePath, $m);
        if (!$foundExt) {
            $this->error("Missing file extension for file: `$oFilePath`");
        }
        $ext = $m[1];
        if ($ext == 'jpg') { $ext = 'jpeg'; }
        if ($ext == 'ico') { $ext = 'bmp'; }

        if (!in_array($ext, u_Image::$supportedExts)) {
            $this->error("Unsupported extension `$ext` for file: `$filePath`");
        }

        $filePath = Tht::module('File')->u_app_path($oFilePath);

        // PHP BUG: Saving to bmp seems to immediately trigger a timeout error. It seems to append '+2' to the timeout.
        $prevTimeoutLimit = ini_get("max_execution_time");
        set_time_limit(0);

        // TODO: figure out when webp uses lossless and lossy modes
        $result = false;
        switch ($ext) {
            case 'bmp':  $result = imagebmp($this->gdi,  $filePath,  false);    break;
            case 'jpeg': $result = imagejpeg($this->gdi, $filePath,  $flags['quality']);           break;
            case 'webp': $result = imagewebp($this->gdi, $filePath,  $flags['quality']);           break;
            case 'png':  $result = imagepng($this->gdi,  $filePath,  $flags['pngCompression']);    break;
            case 'gif':  $result = imagegif($this->gdi,  $filePath);                               break;
        }
        set_time_limit($prevTimeoutLimit);

        if (!$result) {
            $this->error("Unable to save image: `$filePath`");
        }

        return $this;
    }

    function u_gd_call($fnName, $fargs=[]) {

        $this->ARGS('sl', func_get_args());

        $fnName = strtolower($fnName);
        if (substr($fnName, 0, 5) !== 'image') {
            $this->error("Function name must begin with: `image`  Got: `$fnName`");
        }

        if (!function_exists($fnName)) {
            $this->error("GD image function does not exist: `$fnName`");
        }

        $fargs = unv($fargs);

        // convert color names to color handles
        foreach ($fargs as $i => $f) {
            if (isset($this->colors[$f])) {
                $fargs[$i] = $this->colors[$f];
            }
        }

        array_unshift($fargs, $this->gdi);

        $returnVal = \call_user_func_array($fnName, unv($fargs));

        if (get_class($returnVal) == 'Image') {
            $this->updateImage($returnVal);
            return $this;
        }

        return $returnVal;
    }

    function u_size() {
        $this->ARGS('', func_get_args());
        return $this->size;
    }

    function u_create_color($colorId, $colorHex, $opacity=1.0) {

        $this->ARGS('ssn', func_get_args());

        $rgba = $this->colorHexToArray($colorHex);

        // Between 0 and 127. 0 indicates completely opaque
        //$rgba[3] = floor((255 - $rgba[3]) / 2);
        $rgba[3] = floor((1.0 - $opacity) * 127);

        $this->colors[$colorId] = imagecolorallocatealpha($this->gdi, $rgba[0], $rgba[1], $rgba[2], $rgba[3]);

        return $this;
    }

    function getColor($colorId) {

        if (!isset($this->colors[$colorId])) {
            $presets = Tht::module('Image')->u_color_presets();
            if ($presets[$colorId]) {
                $this->u_create_color($colorId, $presets[$colorId]);
            }
            else {
                $this->error("Unknown color: `$colorId`");
            }
        }

        return $this->colors[$colorId];
    }

    // handle pixel density
    function coords($coords) {
        return $coords;
    }

    function u_fill($pos, $style=null) {

        $this->ARGS('mm', func_get_args());

        $pos = $this->validatePos($pos, 'x, y');
        $style = $this->validateStyle($style);

        imagefill($this->gdi, $pos['x'], $pos['y'], $style['color']);

        return $this;
    }

    function u_draw_rectangle($pos, $style=null) {

        $this->ARGS('mm', func_get_args());

        $pos[':centerSign'] = -1;
        $pos = $this->validatePos($pos, 'x, y, sizeX, sizeY, toX, toY, center');
        $style = $this->validateStyle($style);

        $fnRect = $style['fill'] ? 'imagefilledrectangle' : 'imagerectangle';

        $fnRect(
            $this->gdi,
            $pos['x'] - ONE_INDEX,
            $pos['y'] - ONE_INDEX,
            $pos['toX'] - ONE_INDEX,
            $pos['toY'] - ONE_INDEX,
            $style['color']
        );

        return $this;
    }

    function u_draw_ellipse($pos, $style=null) {

        $this->ARGS('mm', func_get_args());

        $pos[':centerSign'] = 1;
        $pos = $this->validatePos($pos, 'x, y, sizeX, sizeY, toX, toY, center');
        $style = $this->validateStyle($style);

        $fnEllipse = $style['fill'] ? 'imagefilledellipse' : 'imageellipse';

        $fnEllipse(
            $this->gdi,
            $pos['x'] - ONE_INDEX,
            $pos['y'] - ONE_INDEX,
            floor($pos['sizeX'] / 2),
            floor($pos['sizeY'] / 2),
            $style['color']
        );

        return $this;
    }

    function u_draw_arc($coords, $colorId='black') {

        $this->ARGS('ms', func_get_args());

        $colorHandle = $this->getColor($colorId);

        // TODO: validate points

        $thick = 1;
        if (isset($coords['thick']) && $coords['thick']) {
            $thick = $coords['thick'];
        }
        imagesetthickness($this->gdi, $thick);

        if (isset($coords['relEndAngle']) && $coords['relEndAngle']) {
            $coords['endAngle'] = $coords['startAngle'] + $coords['relEndAngle'];
        }

        $fnArc = isset($coords['fill']) && $coords['fill'] ? 'imagefilledarc' : 'imagearc';

        $args = [
            $this->gdi,
            $coords['centerX'],
            $coords['centerY'],
            $coords['sizeX'],
            $coords['sizeY'],
            $coords['startAngle'] - 90, // start at 12:00, not 3:00
            $coords['endAngle'] - 90,
            $colorHandle
        ];

        if ($fnArc == 'imagefilledarc') {
            $args []= IMG_ARC_PIE;
        }

        call_user_func_array($fnArc, $args);

        return $this;
    }

    function u_draw_line($coords, $colorId='black') {

        $this->ARGS('ms', func_get_args());

        $colorHandle = $this->getColor($colorId);

        $coords = $this->coords($coords);

        // TODO: validate points

        if (isset($coords['toRelX']) && $coords['toRelX']) {
            $coords['toX'] = $coords['x'] + $coords['toRelX'];
            $coords['toY'] = $coords['y'] + $coords['toRelY'];
        }

        $thick = 1;
        if (isset($coords['thick']) && $coords['thick']) {
            $thick = $coords['thick'];
        }
        imagesetthickness($this->gdi, $thick);

        $dashStyle = false;
        if (isset($coords['dashSize']) && $coords['dashSize']) {
            $dashGap = $coords['dashSize'];
            if (isset($coords['dashGap'])) {
                 $dashGap = $coords['dashGap'];
            }
            $dashColor = array_fill(0, $coords['dashSize'] * $thick, $colorHandle);
            $dashClear = array_fill(0, $dashGap * $thick, $this->colors['transparent']);
            $dashes = array_merge($dashColor, $dashClear);
            imagesetstyle($this->gdi, $dashes);
            $dashStyle = true;
        }

        imageline(
            $this->gdi,
            $coords['x'],
            $coords['y'],
            $coords['toX'],
            $coords['toY'],
            $dashStyle ? IMG_COLOR_STYLED : $colorHandle
        );

        return $this;
    }

    function u_draw_star($pts, $colorId='black') {

        $this->ARGS('ls', func_get_args());

        return $this->drawPoly($pts, $colorId, 'closed');
    }

    function u_draw_polygon($pts, $colorId='black') {

        $this->ARGS('ls', func_get_args());

        return $this->drawPoly($pts, $colorId, 'closed');
    }

    function u_draw_shape($pts, $colorId='black') {

        $this->ARGS('ls', func_get_args());

        return $this->drawPoly($pts, $colorId, 'closed');
    }

    function u_draw_lines($pts, $colorId='black') {

        $this->ARGS('ls', func_get_args());

        return $this->drawPoly($pts, $colorId, 'open');
    }

    function drawPoly($pts, $colorId='black', $mode='closed') {

        $colorHandle = $this->getColor($colorId);

        // TODO: validate points

        $fns = [
            'closed' => 'imagepolygon',
            'open'   => 'imageopenpolygon',
            'filled' => 'imagefilledpolygon',
        ];

        if (!isset($fns[$mode])) {
            $this->error("Unknown mode: `$mode` Try: `closed`, `open`, `filled`");
        }

        $fnPoly = $fns[$mode];

        $upts = [];
        $prevX = 0;
        $prevY = 0;
        foreach ($pts as $pt) {
            if (isset($pt['relX'])) { $pt['x'] = $prevX + $pt['relX']; }
            if (isset($pt['relY'])) { $pt['y'] = $prevY + $pt['relY']; }
            $upts []= $pt['x'];
            $upts []= $pt['y'];
            $prevX = $pt['x'];
            $prevY = $pt['y'];
        }

        $fnPoly(
            $this->gdi,
            $upts,
            $colorHandle
        );

        return $this;
    }

/*
function imagelinedotted ($im, $x1, $y1, $x2, $y2, $dist, $col) {
    $transp = imagecolortransparent ($im);

    $style = array ($col);

    for ($i=0; $i<$dist; $i++) {
        array_push($style, $transp);        // Generate style array - loop needed for customisable distance between the dots
    }

    imagesetstyle ($im, $style);
    return (integer) imageline ($im, $x1, $y1, $x2, $y2, IMG_COLOR_STYLED);
    imagesetstyle ($im, array($col));        // Reset style - just in case...
}
*/

    // https://stackoverflow.com/questions/7203160/php-gd-use-one-image-to-mask-another-image-including-transparency
    function u_mask($maskImage) {

        $maskImage = $maskImage->gdi;

        $maskedImage = imagecreatetruecolor($this->sizeX, $this->sizeY);
        imagesavealpha($maskedImage, true);
        $colorClear = imagecolorallocatealpha($maskedImage, 0, 0, 0, 127);
        imagefill($maskedImage, 0, 0, $colorClear);

        for ($x = 0; $x < imagesx($maskImage); $x += 1) {
            for ($y = 0; $y < imagesy($maskImage); $y += 1) {

                $maskPixel = imagecolorsforindex($maskImage, imagecolorat($maskImage, $x, $y));

                // if ($maskPixel['alpha'] == 0) {
                //     imagesetpixel($maskedImage, $x, $y, $colorClear);
                // }
                // else {
                    $hex = imagecolorat($this->gdi, $x, $y);
                    $origAlpha = $this->colorHexToArray($hex)[3];

                    if (($maskPixel['alpha'] == 127)) {
                        imagesetpixel($maskedImage, $x, $y, $colorClear);
                    }
                    else {
                        $mainColor = imagecolorsforindex($this->gdi, imagecolorat($this->gdi, $x, $y));
                        $newAlphaColor = imagecolorallocatealpha($maskedImage, $mainColor['red'], $mainColor['green'], $mainColor['blue'], $maskPixel['alpha']);
                        imagesetpixel($maskedImage, $x, $y,  $newAlphaColor);
                    }
            //    }
            }
        }

        $this->updateImage($maskedImage);

        return $this;
    }

    // TODO: to get exact width, need to render it in a buffer and scan the pixels
    function getTextBox($msg, $flags) {

        $gdBox = imagettfbbox($flags['fontSize'], $flags['angle'], $flags['fontPath'], $msg);

        $box = [
            'll-x' => $gdBox[0],
            'll-y' => $gdBox[1],
            'lr-x' => $gdBox[2],
            'lr-y' => $gdBox[3],
            'ur-x' => $gdBox[4],
            'ur-y' => $gdBox[5],
            'ul-x' => $gdBox[6],
            'ul-y' => $gdBox[7],
        ];

        return OMap::create([
            'x' => $flags['x'] + $box['ul-x'],
            'y' => $flags['y'] + $box['ul-y'],

            'toX' => $flags['x'] + $box['lr-x'],
            'toY' => $flags['y'] + $box['lr-y'],

            'sizeX' => $box['ur-x'] - $box['ul-x'],
            'sizeY' => $box['ll-y'] - $box['ul-y'],
        ]);
    }

    // TODO: separate position & style?
    function u_draw_text($msg, $flags=null) {

        $this->ARGS('sm', func_get_args());

        // TODO: hint at alignX if user provides 'center'

        $flags = $this->flags($flags, [
            'x' => 0,
            'y' => 0,
            'sizeX' => 0,
            'color' => 'black',
            'font' => 'default',
            'fontSize' => 16,
            'lineSpacing' => 1,
            'angle' => 0,
            'alignX' => 'left|center|right',
            'alignY' => 'top|middle|bottom',
            'debug' => false,
        ]);

        // px to pts
        $flags['fontSize'] = floor(0.75 * $flags['fontSize']);

        $colorHandle = $this->getColor($flags['color']);

        if ($flags['font'] == 'default') {
            $flags['fontPath'] = __DIR__ . '/helpers/resources/ibm_plex_condensed_min.ttf';
        }
        else {
            $flags['fontPath'] = Tht::path('files', $flags['font']);
            if (!Tht::module('File')->u_exists($flags['fontPath'])) {
                $this->error("Unable to load font file: `" . $flags['fontPath'] . "`");
            }
        }

        $flags['x'] -= ONE_INDEX;
        $flags['y'] -= ONE_INDEX;

        $alignBox = OMap::create([
            'x' => $flags['x'],
            'y' => $flags['y'],
            'sizeX' => $flags['sizeX'],
            'sizeY' => $flags['fontSize'],
        ]);

        // Position relative to top, not baseline
        $flags['y'] += $flags['fontSize'];

        $drawnTextBox = $this->getTextBox($msg, $flags);

        if ($flags['alignX'] == 'center') {
            $marginX = floor(($alignBox['sizeX'] - $drawnTextBox['sizeX']) / 2);
            $flags['x'] += $marginX;
            $drawnTextBox = $this->getTextBox($msg, $flags);
        }

        // else if ($flags['alignX'] == 'right') {
        //     $marginX = ($flags['sizeX'] - $drawnTextBox['sizeX']);
        //     $flags['x'] += $marginX;
        //     $drawnTextBox = $this->getTextBox($msg, $flags);
        // }

        // if ($flags['angle'] != 0) {
        //     if ($flags['alignY'] == 'middle') {
        //         $marginY = floor(($drawnTextBox['sizeY'] - $flags['fontSize']) / 2);
        //         $flags['y'] += $marginY;
        //         $drawnTextBox = $this->getTextBox($msg, $flags);
        //     }
        //     else if ($flags['alignY'] == 'top') {
        //         $marginY = floor(($drawnTextBox['sizeY'] - $flags['fontSize']));
        //         $flags['y'] += $marginY;
        //         $drawnTextBox = $this->getTextBox($msg, $flags);
        //     }
        // }

        if ($flags['debug']) {
            $this->u_draw_rectangle($drawnTextBox, OMap::create(['color' => 'red']));
            $this->u_draw_rectangle($alignBox,  OMap::create(['color' => 'green']));
        }

        $textBox = imagefttext(
            $this->gdi,
            $flags['fontSize'],
            $flags['angle'],
            $flags['x'],
            $flags['y'],
            $colorHandle,
            $flags['fontPath'],
            $msg,
            [ 'linespacing' => $flags['lineSpacing'] ],
        );

        if ($textBox === false) {
            $this->error("Unable to draw text.");
        }

        return $drawnTextBox;
    }

    function u_rotate($deg, $flags=null) {

        $this->ARGS('nm', func_get_args());

        $flags = $this->flags($flags, [
            'resize'  => false,
            'bgColor' => 'transparent',
        ]);

        $bgColorId = $this->getColor($flags['bgColor']);
        $rotated = imagerotate($this->gdi, -1 * $deg, $bgColorId);

        if ($rotated === false) {
            $this->error("Unable to rotate image.");
        }

        if (!$flags['resize']) {
            $cropX = floor((imagesx($rotated) - $this->sizeX) / 2);
            $cropY = floor((imagesy($rotated) - $this->sizeY) / 2);

            $cropRect = ['x' => $cropX, 'y' => $cropY, 'width' => $this->sizeX, 'height' => $this->sizeY];
            $cropped = imagecrop($rotated, $cropRect);

            $this->updateImage($cropped);
        }
        else {
            $this->updateImage($rotated);
        }


        return $this;
    }

    // TODO: imagecropauto
    function u_crop($rect) {

        $this->ARGS('m', func_get_args());

        $aRect = [
            'x' => $rect['x'],
            'y' => $rect['y'],
            'width'  => $rect['sizeX'],
            'height' => $rect['sizeY']
        ];

        $cropped = imagecrop($this->gdi, $aRect);

        if ($cropped  === false) {
            $this->error("Unable to crop image.");
        }

        $this->updateImage($cropped);

        return $this;
    }

    function u_flip($dir='x') {

        $this->ARGS('s', func_get_args());

        // TODO:validate $dir

        // Doc Note: flip x and y is same as rotate 180.

        $const = [
            'x'  => IMG_FLIP_HORIZONTAL,
            'y'  => IMG_FLIP_VERTICAL,
            'xy' => IMG_FLIP_BOTH,
        ];

        $didFlip = imageflip($this->gdi, $const[$dir]);

        if (!$didFlip) {
            $this->error("Unable to flip image.");
        }

        return $this;
    }

    function u_scale($flags=null) {

        $this->ARGS('m', func_get_args());

        // by type: pixelart, photo, logo, graphics (detect?)
        // TODO: require positive nums
        $flags = $this->flags($flags, [
            'sizeX' => 0,
            'sizeY' => 0,
            'scale' => 0,
            'mode' => 'smooth',
        ]);

        if ($flags['mode'] == 'smooth') { $flags['mode'] = 'quadratic'; }
        if ($flags['mode'] == 'crisp')  { $flags['mode'] = 'nearestNeighbor'; }

        // PHP BUG: bicubic and bicubicFixed are temporarily broken.
        // https://github.com/php/php-src/issues/12012

        $mode = strtoupper('img_' . v($flags['mode'])->u_to_token_case('_'));
        if (defined($mode)) {
            $mode = constant($mode);
        }
        else {
            $this->error("Unsupported interpolation mode: `$mode`");
        }

        if (isset($flags['scale']) && $flags['scale'] > 0) {
            $flags['sizeX'] = floor($this->sizeX * $flags['scale']);
            $flags['sizeY'] = floor($this->sizeY * $flags['scale']);
        }
        else {

            if ($flags['sizeX'] == 0 && $flags['sizeY'] == 0) {
                $this->error("Option `sizeX` or `sizeY` must be greater than zero.");
            }

            if ($flags['sizeX'] == 0) {
                $ratio = $this->sizeX / $this->sizeY;
                $flags['sizeX'] = floor($ratio * $flags['sizeY']);
            }
            else if ($flags['sizeY'] == 0) {
                // GD already handles sizeY = -1
                $flags['sizeY'] = -1;

            }
        }

        $scaled = imagescale($this->gdi, $flags['sizeX'], $flags['sizeY'], $mode);

        if ($scaled === false) {
            $this->error("Unable to resize image.");
        }

        $this->updateImage($scaled);

        return $this;
    }

    // TODO: return ok: false if outside bounds?
    // TODO: function isInBounds (check x and y range)
    // TODO: function forEachPixel
    function u_get_pixel($coord) {

        $this->ARGS('m', func_get_args());

        $hex = imagecolorat($this->gdi, $coord['x'], $coord['y']);

        return $this->colorHexToMap($hex);
    }

    function u_set_pixel($coord, $color='black') {

        $this->ARGS('ms', func_get_args());

        $colorId = $this->getColor($color);

        $ret = imagesetpixel($this->gdi, $coord['x'], $coord['y'], $colorId);

        if (!$ret) {
            $this->error("Unable to set pixel at x=" . $coord['x'] . ', y=' . $coord['y']);
        }

        return $this;
    }

    function u_replace_color ($fromColorMap, $toColorMap) {

        $this->ARGS('**', func_get_args());

        if (is_string($fromColorMap)) {
            $fromColorMap = $this->colorHexToMap($fromColorMap);
        }
        if (is_string($toColorMap)) {
            $toColorMap = $this->colorHexToMap($toColorMap);
        }

        // TODO: validate string or colormap (hex, name or map?)

        $fromColorRgb = (($fromColorMap['r'] & 0xFF) << 16) + (($fromColorMap['g'] & 0xFF) << 8) + ($fromColorMap['b'] & 0xFF);

        for ($x = 0; $x < $this->sizeX; $x += 1) {
            for ($y = 0; $y < $this->sizeY; $y += 1) {

                $currentColor = $this->u_get_pixel(OMap::create([ 'x' => $x, 'y' => $y ]));

                if ($currentColor['rgb'] == $fromColorRgb) {
                    $alpha = floor((1.0 - $currentColor['opacity']) * 127);
                    $alphaPixel = imagecolorallocatealpha($this->gdi, $toColorMap['r'], $toColorMap['g'], $toColorMap['b'], $alpha);
                    imagesetpixel($this->gdi, $x, $y, $alphaPixel);
                }
            }
        }

        return $this;
    }

    function u_in_bounds($x, $y) {

        $this->ARGS('nn', func_get_args());

        return ($x >= 1 && $y >= 1 && $x <= $this->sizeX && $y <= $this->sizeY);
    }

    function u_for_each_pixel($fn) {

        $this->ARGS('c', func_get_args());

        for ($y = 0; $y < $this->sizeY; $y += 1) {
            for ($x = 0; $x < $this->sizeX; $x += 1) {
                $pix = $this->u_get_pixel(OMap::create([ 'x' => $x, 'y' => $y ]));
                $pix['x'] = $x;
                $pix['y'] = $y;
                $retColor = $fn($pix);
                if (is_string($retColor)) {
                    $this->u_set_pixel($pix, $retColor);
                }
            }
        }

        return $this;
    }

    // TODO: mimic rect coords
    function u_get_image($coord) {

        $this->ARGS('m', func_get_args());

        $subImage = Tht::module('Image')->u_create($coord['sizeX'], $coord['sizeY']);

        $copied = imagecopy(
            $subImage->gdi,
            $this->gdi,
            0,
            0,
            $coord['x'],
            $coord['y'],
            $coord['sizeX'],
            $coord['sizeY']
        );
        if ($copied === false) {
            // TODO: error with coords.
            $this->error("Unable to get image at xy");
        }

        return $subImage;
    }

    function u_draw_image($subImage, $coord) {

        $this->ARGS('*m', func_get_args());

        if (!isset($coord['sizeX'])) {
            $coord['sizeX'] = $subImage->sizeX;
        }
        if (!isset($coord['sizeY'])) {
            $coord['sizeY'] = $subImage->sizeY;
        }

        $fnCopy = 'imagecopyresampled';
        if (isset($coord['pixelate']) && $coord['pixelate']) {
            $fnCopy = 'imagecopyresized';
        }

         $copied = $fnCopy(
            $this->gdi,
            $subImage->gdi,
            $coord['x'],
            $coord['y'],
            0,
            0,
            $coord['sizeX'],
            $coord['sizeY'],
            $subImage->sizeX,
            $subImage->sizeY
        );

        if ($copied === false) {
            // TODO: error with coords.
            $this->error("Unable to draw image at xy");
        }

        return $this;
    }

    function u_to_base64 ($flags=null) {

        $this->ARGS('m', func_get_args());

        $flags = $this->flags($flags, [
            'plain' => false,
        ]);

        // pixel density correct
       // $this->u_resize(OMap::create([ 'scale' => 0.5, 'mode' => 'quadratic' ]));

        ob_start();
        imagepng($this->gdi, null, 9);
        $base64 = base64_encode(ob_get_clean());

        $out = $flags['plain'] ? $base64 : 'data:image/png;base64,' . $base64;

        return $out;
    }
}