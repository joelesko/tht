<?php

namespace o;

/*

    // TODO: Move optimizer code here (use webp!).  rename to Image.

    - name: scale or resize?

    imagecopymerge - pct?!
    imagecopymergegray - preserves hue of the source by converting dest to grayscale

    imagettfbbox

    replacecolor

    imagelayereffect
    imagesetbrush
    imagesettile (pass in image as fill argument)
    imagesetinterpolation (carry over to new images)

*/


class u_Gd extends OStdModule {

    // Aliases: ico -> bmp, jpg -> jpeg
    public static $supportedExts = ['bmp', 'jpeg', 'png', 'webp', 'gif'];

    public static $pngCompression = 9;         // 0-10  (lossless. 9 = most/slowest compression)
    public static $jpegQuality = 80;           // 0-100 (lossy. 100 = bigger size, highest quality)
    public static $bmpRleCompression = false;  // true|false  (lossless) doesn't seem to havy any effect.

    var $suggestMethod = [];

    function u_create_image($sizeX, $sizeY) {

        $this->ARGS('nn', func_get_args());

        // TODO: add a 'maybe' perf mode bc we don't know how or if this will end.
        Tht::module('Perf')->u_start('gd.image');

        $gdi = imagecreatetruecolor($sizeX, $sizeY);

        imagesavealpha($gdi, true);

        return new u_Gd_Image($gdi);
    }

    function u_load_image($oFilePath) {

        $this->ARGS('s', func_get_args());

        Tht::module('Perf')->u_start('gd.image', $oFilePath);

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

        return new u_Gd_Image($gdi);
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

class u_Gd_Image extends OClass {

    private $gdi = null; // GD image handle

    private $sizeX = 0;
    private $sizeY = 0;
    private $pxDensity = 2;

    private $colors = [];

    // protected $errorClass = 'GdImage';
    // protected $cleanClass = 'GdImage';

    function __construct($gdi) {

        $this->updateImage($gdi);

        imagealphablending($this->gdi, false);

        // TODO: lazy init background with custom color?
        $this->u_fill($this->coord(0, 0), 'transparent');

        imagealphablending($this->gdi, true);
    }

    public function u_on_print() {

        $base64 = $this->u_to_base64();
        $tag = '<img style="max-width:1000px; max-height: 600px; box-shadow: 0 0 10px rgba(128,128,128,0.2)" src="' . $base64 . '">';

        Tht::module('Perf')->u_stop();

        return new HtmlTypeString($tag);

    }

    function updateImage($gdi) {

        $this->gdi = $gdi;

        $this->sizeX = imagesx($gdi);
        $this->sizeY = imagesy($gdi);

        $this->u_create_color('transparent', '#000000', 0.0);

        imagesavealpha($this->gdi, true);

      //  imagecolortransparent($this->gdi, $this->getColor('transparent'));

        //$this->size = $this->vec2($this->sizeX, $this->sizeY);

        // Change mode from replacing alpha pixels to blending them
        imagealphablending($this->gdi, true);
    }

    function coord($x, $y) {
        return OMap::create(['x' => $x, 'y' => $y]);
    }

    function colorHexToArray($hex) {

        if (is_string($hex)) {

            $hex = ltrim($hex, '#');

            // make sure alpha is set
            if (strlen($hex) == 6) {
                $hex = 'FF' . $hex;
            }
            $hex = Tht::module('Math')->u_hex_to_dec($hex);
        }

        $a = ($hex >> 24) & 0xFF;
        $r = ($hex >> 16) & 0xFF;
        $g = ($hex >> 8) & 0xFF;
        $b = $hex & 0xFF;

        return [$r, $g, $b, $a];
    }

    function u_save_image($oFilePath, $flags=null) {

        $this->ARGS('sm', func_get_args());

        $flags = $this->flags($flags, [
            'quality'         => u_Gd::$jpegQuality,
            'pngCompression'  => u_Gd::$pngCompression,
            'bmpCompression'  => u_Gd::$bmpRleCompression,
        ]);

        $foundExt = preg_match('/\.([a-zA-Z]{3,4})/', $oFilePath, $m);
        if (!$foundExt) {
            $this->error("Missing file extension for file: `$oFilePath`");
        }
        $ext = $m[1];
        if ($ext == 'jpg') { $ext = 'jpeg'; }
        if ($ext == 'ico') { $ext = 'bmp'; }

        if (!in_array($ext, u_Gd::$supportedExts)) {
            $this->error("Unsupported extension `$ext` for file: `$filePath`");
        }

        $filePath = Tht::module('File')->u_app_path($oFilePath);

        // PHP BUG: Saving to bmp seems to immediately trigger a timeout error. It seems to append '+2' to the timeout.
        $prevTimeoutLimit = ini_get("max_execution_time");
        set_time_limit(0);

        // TODO: figure out when webp uses lossless and lossy modes
        $result = false;
        switch ($ext) {
            case 'bmp':  $result = imagebmp($this->gdi,  $filePath,  $flags['bmpCompression']);    break;
            case 'jpeg': $result = imagejpeg($this->gdi, $filePath,  $flags['quality']);           break;
            case 'webp': $result = imagewebp($this->gdi, $filePath,  $flags['quality']);           break;
            case 'png':  $result = imagepng($this->gdi,  $filePath,  $flags['pngCompression']);    break;
            case 'gif':  $result = imagegif($this->gdi,  $filePath);                               break;
        }
        set_time_limit($prevTimeoutLimit);

        if (!$result) {
            $this->error("Unable to save image: `$filePath`");
        }

        Tht::module('Perf')->u_stop();

        return $this;
    }

    function u_call($fnName, $fargs=[]) {

        $this->ARGS('sl', func_get_args());

        $fnName = strtolower($fnName);
        if (substr($fnName, 0, 5) !== 'image') {
            $this->error("Function name must begin with `image`.");
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

        if (get_class($returnVal) == 'GdImage') {
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
            $presets = Tht::module('Gd')->u_color_presets();
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

    function u_fill($coords, $colorId) {

        $this->ARGS('ms', func_get_args());

        $colorHandle = $this->getColor($colorId);

        imagefill($this->gdi, $coords['x'], $coords['y'], $colorHandle);

        return $this;
    }

    // TODO: implement cornerX or toX
    function u_rect($coords, $colorId) {

        $this->ARGS('ms', func_get_args());

        $colorHandle = $this->getColor($colorId);

        // TODO: validate points

        $fnRect = isset($coords['fill']) && $coords['fill'] ? 'imagefilledrectangle' : 'imagerectangle';

        if (isset($coords['center']) && $coords['center']) {
            $coords['x'] -= floor($coords['sizeX'] / 2);
            $coords['y'] -= floor($coords['sizeY'] / 2);
        }

        $fnRect(
            $this->gdi,
            $coords['x'],
            $coords['y'],
            $coords['x'] + $coords['sizeX'],
            $coords['y'] + $coords['sizeY'],
            $colorHandle
        );

        return $this;
    }

    function u_arc($coords, $colorId) {

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

    function u_line($coords, $colorId) {

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

    function u_poly($pts, $colorId, $mode='closed') {

        $this->ARGS('lss', func_get_args());

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

    function u_ellipse($coords, $colorId) {

        $this->ARGS('ms', func_get_args());

        $colorHandle = $this->getColor($colorId);

        $fnEllipse = isset($coords['fill']) && $coords['fill'] ? 'imagefilledellipse' : 'imageellipse';

        if (isset($coords['size']) && $coords['size']) {
            $coords['sizeX'] = $coords['size'];
            $coords['sizeY'] = $coords['size'];
        }

        if (!isset($coords['center']) || !$coords['center']) {
            $coords['x'] += floor($coords['sizeX'] / 2);
            $coords['y'] += floor($coords['sizeY'] / 2);
        }

        $fnEllipse(
            $this->gdi,
            $coords['x'],
            $coords['y'],
            $coords['sizeX'],
            $coords['sizeY'],
            $colorHandle
        );

        return $this;
    }

    function u_text($msg, $flags=null) {

        $this->ARGS('sm', func_get_args());

        $flags = $this->flags($flags, [
            'x' => 0,
            'y' => 0,
            'color' => 'black',
            'font' => 'default',
            'angle' => 0,
            'size' => 10,
            'lineSpacing' => 1,
        ]);

        $colorHandle = $this->getColor($flags['color']);

        $fontPath = __DIR__ . '/helpers/resources/ibm_plex_condensed_min.ttf'; // $flags['font'] == 'default' ? '' : '';

        $textBox = imagefttext(
            $this->gdi,
            $flags['size'],
            $flags['angle'],
            $flags['x'],
            $flags['y'],
            $colorHandle,
            $fontPath,
            $msg,
            [ 'linespacing' => $flags['lineSpacing'] ],
        );

        if ($textBox === false) {
            $this->error("Unable to draw text.");
        }


        return $this;
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

    function u_resize($flags=null) {

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

        if (isset($flags['scale'])) {
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
    // TODO: foreach coordinates
    function u_get_pixel($coord) {

        $this->ARGS('m', func_get_args());

        $hex = imagecolorat($this->gdi, $coord['x'], $coord['y']);

        $ary = $this->colorHexToArray($hex);

        return OMap::create([
            'r' => $ary[0],
            'g' => $ary[1],
            'b' => $ary[2],
            'opacity' => (127 - $ary[3]) / 127,
        ]);
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

        // imagecopy - copy but no resize
        // imagecopyresampled - copy and resize with resampling
        // imagecopyresized - copy and resize with no resampling (pixellated)


    // TODO: mimic rect coords
    function u_get_image($coord) {

        $this->ARGS('m', func_get_args());

        $subImage = Tht::module('Gd')->u_create_image($coord['sizeX'], $coord['sizeY']);

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


     //    $text = strtoupper(substr($appName, 0, 1));

     //    $image = imagecreate(128, 128);
     //    imagecolorallocate($image, 0,0,0);
     //    $textColor = imagecolorallocate($image, 255,255,255);

     //    imagefilledrectangle($image, 0, 128 - 12, 128, 128 - 10, $textColor);

     //    $size = 80;
     //    $angle = 0;
     //    $font = Tht::path('localTht', 'lib/modules/helpers/resources/opensans_min.ttf');

     //    // origin is bottom left of baseline
     //    list($blX, $blY, $brX, $brY, $trX, $trY, $tlX, $tlY) = imagettfbbox($size, $angle, $font, $text);

     //    print("--- $text ---\n");
     //    print_r([
     //        [$blX, $blY], [$brX, $brY], [$trX, $trY], [$tlX, $tlY]
     //    ]);

     //    $textSizeX = $trX - $tlX;
     //    $textSizeY = $blY - $tlY;

     // //   print($text . ' = ' . $textSizeY . " sizeY \n");

     //    $x = 20;
     //    $baselineY = 100;
     //    imagettftext($image, $size, $angle, $x, $baselineY, $textColor, $font, $text);

     //    imagerectangle($image, $x + $blX, $baselineY - $textSizeY, $x + $textSizeX, $baselineY, $textColor);

     //    imagepng($image, Tht::path('images', 'favicon_' . $text . '_128.png'));
     //    imagepng($image, Tht::path('images', 'favicon_128.png'));
     //    imagedestroy($image);