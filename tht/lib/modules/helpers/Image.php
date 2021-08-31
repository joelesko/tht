<?php

namespace o;

/*
    Algorithm:

    if thumb
       - convert to jpeg
       - resize

    if png
       - if flat image, index with smallest palette possible
       - if colors < 256, then palettize
       - if colors > 256
            - if has alpha: quantize
            - if no alpha: convert to jpeg encoding, but keep png extension
              (this isn't ideal, but simplifies cache checking)

    if jpeg
       - just resize down to 1200

*/


class u_Image {

    static private $MAX_DIMENSION = 5000;
    static private $MAX_NUM_PALETTE_COLORS = 256;
    static private $JPEG_QUALITY = 80;   // of 100
    static private $PNG_COMPRESSION = 9; // max

    private $oldSize = [0, 0];
    private $oldFileName = '';
    private $newFileName = '';
    private $maxResizeWidth = 0;
    private $fileTag = '';
    private $oldImageType = '';
    private $newImageType = '';

    function error($msg) {
        ErrorHandler::setHelpLink('/reference/performance-optimization#images', 'Image Optimization');
        Tht::error($msg);
    }

    // Main method
    public function optimize ($oldFileName, $maxResizeWidth=1200, $fileTag='optimized') {

        Tht::module('Perf')->u_start('Image.optimize', $this->oldFileName);

        $this->oldFileName = $oldFileName;
        $this->maxResizeWidth = $maxResizeWidth;
        $this->fileTag = $fileTag;

        $error = $this->init();
        if ($error) {
            Tht::module('Perf')->u_stop();
            return $error;
        }

        $this->optimizeImage();

        Tht::module('Perf')->u_stop();

        return [ 'error' => '', 'newFile' => $this->newFileName ];
    }

    // Do a bunch of validation on the filename, etc.
    function init() {

        // Skip "as is" files (filename has "_asis")
        if (preg_match('/([^a-zA-Z]?)asis([^a-z]?)/', $this->oldFileName)) {
            $newFileName = preg_replace('/_asis/', '', $this->oldFileName);
            return [ 'error' => 'Filename has `asis` tag.', 'newFile' => $newFileName ];
        }

        // Skip file if URL points directly to the optimized version.
        if (strpos($this->oldFileName, '_' . $this->fileTag) !== false) {
            return [ 'error' => 'Filename already has optimization tag.', 'newFile' => $this->oldFileName ];
        }

        // Image type - Just checking the filename, since we assume the file is trusted at this point.
        // Uploaded images are already validated.
        $validType = preg_match('/\.(png|jpeg|jpg)/i', $this->oldFileName, $m);
        if (!$validType) {
            return [ 'error' => 'Image type not supported.' ];
        }

        $ext = $m[1];
        $this->oldImageType = $ext;
        $this->newImageType = $ext;

        // Handle thumbnails  (e.g. filename has "_thumb200")
        if (preg_match('/_thumb(\d+)/', $this->oldFileName, $m)) {
            $this->oldFileName = preg_replace('/_thumb(\d+)/', '', $this->oldFileName);
            $this->maxResizeWidth = $m[1];
            $this->fileTag = 'thumb' . $m[1];
            $this->newImageType = 'jpg';
        }

        // Has to come after thumb check
        if (!file_exists($this->oldFileName)) {
            return [ 'error' => 'File does not exist.' ];
        }

        $this->newFileName = $this->getNewFileName();

        if ($this->isAlreadyOptimized()) {
            return [ 'error' => 'Image is already optimized.', 'newFile' => $this->newFileName ];
        }

        // Validate fileTag
        if (preg_match('![^a-z0-9]!', $this->fileTag)) {
            $this->error("Argument `fileTag` can only be all lowercase.  Got: `" . $this->fileTag . "`");
        }

        // Prevent cache stampede
        copy($this->oldFileName, $this->newFileName);

        $this->oldSize = $this->getImageSize($this->oldFileName);

        return false;
    }

    function optimizeImage() {

        // Large files can take around 40 MB
        ini_set('memory_limit', '200M');
        ini_set('max_execution_time', '0');

        $img = null;

        // Analyze image
        if ($this->newImageType == 'png') {

            $img = $this->loadImage();

            list($hasAlpha, $palette) = $this->createPalette($img);

            if (count($palette) <= self::$MAX_NUM_PALETTE_COLORS) {
                // Small palette -> index it (best case)
                $img = $this->palettizeImage($img, $palette);
            }
            else if ($hasAlpha) {
                // Large palette + alpha -> resize & quantize
                $img = $this->resizeImage();
                $img = $this->quantizeImage($img);
            }
            else {
                // Large palette + no alpha -> Convert to jpeg internally,
                // but keep 'png' file extension.
                $this->newImageType = 'jpg';
            }
        }

        // Write file
        if ($this->newImageType == 'png') {
            imagepng($img, $this->newFileName, self::$PNG_COMPRESSION);
        }
        else if ($this->newImageType == 'jpg' || $this->newImageType == 'jpeg') {
            $img = $this->resizeImage();
            imagejpeg($img, $this->newFileName, self::$JPEG_QUALITY);
        }
    }

    // Check if the file has already been optimized and hasn't been updated.
    // About 0.1ms per file.
    function isAlreadyOptimized() {

        // Comment this out to regenerate images during debugging.
        // return false;

        // Trap error if file doesn't exist.
        ErrorHandler::startTrapErrors();
        $isUpdated = filemtime($this->oldFileName) > filemtime($this->newFileName);
        $fileMissing = ErrorHandler::endTrapErrors();

        return !($fileMissing || $isUpdated);
    }

    function resizeImage() {

        $newSize = $this->getNewImageSize();
        $img = $this->loadImage($newSize);

        return $img;
    }

    // Remove extension and append tag
    // e.g. 'myImage.png' -> 'myImage_thumb300.jpg'
    function getNewFileName() {

        $newBaseName = preg_replace('!\.(png|jpg|jpeg)$!i', '', $this->oldFileName);
        $newBaseName .= '_' . $this->fileTag;

        return $newBaseName . '.' . $this->newImageType;
    }

    // Calculate new size
    function getNewImageSize() {

        $oldSize = $this->oldSize;
        $newSize = $oldSize;

        // Don't resize responsive images (e.g. @2x)
        if (strpos($this->oldFileName, '@') !== false) {
            return $oldSize;
        }

        if ($oldSize[0] > $this->maxResizeWidth) {
            $newSize[0] = $this->maxResizeWidth;
            $newSize[1] = round(($oldSize[1] / $oldSize[0]) * $newSize[0]);
        }

        return $newSize;
    }

    function getImageSize($fileName) {

        $info = getimagesize($fileName);
        $oldSizeX = $info[0];
        $oldSizeY = $info[1];
        $max = self::$MAX_DIMENSION;

        if ($oldSizeX >= $max || $oldSizeY >= $max) {
            $fileName = basename($fileName);
            $this->error("Can not handle dimension larger than $max. Got: `$oldSizeX x $oldSizeY` for `$fileName`");
        }

        return [$oldSizeX, $oldSizeY];
    }

    // Create new image (resized, if necessary)
    function loadImage($newSize = 0) {

        $oldSize = $this->oldSize;
        if (!$newSize) { $newSize = $oldSize; }

        // Load original image
        $fnType = $this->oldImageType == 'jpg' ? 'jpeg' : $this->oldImageType;
        $fnCreateImage = 'imagecreatefrom' . $fnType;
        $oldImage = $fnCreateImage($this->oldFileName);

        // Create new image
        $newImage = imagecreatetruecolor($newSize[0], $newSize[1]);
        imagesavealpha($newImage, true);
        imagealphablending($newImage, false);
        imagecopyresampled($newImage, $oldImage, 0, 0, 0, 0, $newSize[0], $newSize[1], $oldSize[0], $oldSize[1]);

        return $newImage;
    }

    // Can be around 30 MB for large images
    function createPalette($img) {

        list($w, $h) = [imagesx($img), imagesy($img)];

        $sampleStep = ceil($w / 1000);
        $totalPixelSampled = floor(($w * $h) / ($sampleStep * $sampleStep));

        $numPixelsByColor = [];

        $hasAlpha = false;

        // Create a slightly quantized palette of sampled pixels
        $qstep = 2;
        for ($x = 0; $x < $w; $x += $sampleStep) {
            for ($y = 0; $y < $h; $y += $sampleStep) {

                $rgba = $this->readPixel($img, $x, $y);

                if (!$hasAlpha && $rgba[3] > 0) {
                    $hasAlpha = true;
                }

                $qval = [];
                foreach (range(0, 3) as $i) {
                    $max = $i == 3 ? 127 : 255;
                    $qval []= $this->quantizeValue($rgba[$i], $qstep, $max);
                }

                $key = implode(',', $qval);

                if (!isset($numPixelsByColor[$key])) {
                    $numPixelsByColor[$key] = 1;
                }
                else {
                    $numPixelsByColor[$key] += 1;
                }
            }
        }

        $palette = $this->getWeightedPalette($numPixelsByColor);

        $palette = $this->getSmallestPalette($palette, $numPixelsByColor, $totalPixelSampled);

        return [$hasAlpha, $palette];
    }

    // If it's a mostly flat graphic image (e.g. mostly text) find the smallest palette. (64, 128, or 256 colors)
    function getSmallestPalette($palette, $numPixelsByColor, $totalPixelSampled) {

        $paletteNumPixels = ['64' => 0, '128' => 0, '256' => 0];

        $i = 0;
        foreach ($palette as $palColor) {
            $num = $numPixelsByColor[$palColor];
            foreach ($paletteNumPixels as $pSize => $n) {
                if ($i < $pSize) {
                    $paletteNumPixels[$pSize] += $num;
                }
            }
            $i += 1;
        }

        foreach ($paletteNumPixels as $pSize => $n) {
            $ratio = round($paletteNumPixels[$pSize] / $totalPixelSampled, 2);
            if ($ratio >= 0.99) {
                return array_slice($palette, 0, $pSize);
            }
        }

        return $palette;
    }

    // Get palette in descending order of number of pixels per color.
    // Most common color is on top.
    function getWeightedPalette($numPixelsByColor) {

        arsort($numPixelsByColor);

        $palette = [];
        $keys = array_keys($numPixelsByColor);

        // Stopping at 260 colors so we know if we're going over 256.
        $maxColors = min(260, count($keys));

        for ($i = 0; $i < $maxColors; $i += 1) {
            $palette []= $keys[$i];
        }

        return $palette;
    }

    function readPixel($image, $x, $y) {

        $rgba = imagecolorat($image, $x, $y);

        $a = ($rgba >> 24) & 0xFF;
        $r = ($rgba >> 16) & 0xFF;
        $g = ($rgba >> 8) & 0xFF;
        $b = $rgba & 0xFF;

        return [$r, $g, $b, $a];
    }

    // Create an indexed PNG.  Best optimization for PNGs.
    function palettizeImage($img, $palette) {

        $w = imagesx($img);
        $h = imagesy($img);

        $pImage = imagecreatetruecolor($w, $h);
        imagesavealpha($pImage, true);
        imagealphablending($pImage, false);
        imagetruecolortopalette($pImage, false, count($palette));

        // Allocate colors from palette
        foreach ($palette as $c) {
            list($r, $g, $b, $a) = explode(',', $c);
            imagecolorallocatealpha($pImage, $r, $g, $b, $a);
        }

        for ($x = 0; $x < $w; $x += 1) {
            for ($y = 0; $y < $h; $y += 1) {
                list($r, $g, $b, $a) = $this->readPixel($img, $x, $y);
                $color = imagecolorclosestalpha($pImage, $r, $g, $b, $a);
                imagesetpixel($pImage, $x, $y, $color);
            }
        }

        return $pImage;
    }

    // Naive quantization.
    // TODO: I tried applying Floyd-Steinberg dithering but it was WAY too slow.
    //       Would be good to find a fast way to do decent-quality dithering.
    //       However, this is only applied to rich PNGs with alpha, so it might not be a common case.
    function quantizeImage($img) {

        $w = imagesx($img);
        $h = imagesy($img);

        $qImage = imagecreatetruecolor($w, $h);
        imagesavealpha($qImage, true);
        imagealphablending($qImage, false);

        for ($x = 0; $x < $w; $x += 1) {
            for ($y = 0; $y < $h; $y += 1) {

                $rgba = $this->readPixel($img, $x, $y);

                $qval = [];
                foreach (range(0, 3) as $ch) {
                    $max = $ch == 3 ? 127 : 255;
                    $qstep = $ch == 3 ? 2 : 6;
                    $qval []= $this->quantizeValue($rgba[$ch], $qstep, $max);
                }

                $color = imagecolorallocatealpha($qImage, $qval[0], $qval[1], $qval[2], $qval[3]);
                imagesetpixel($qImage, $x, $y, $color);
            }
        }

        return $qImage;
    }

    function quantizeValue($v, $step, $max=255) {

        $qv = round($v / $step) * $step;
        $qv = min($qv, $max);

        return $qv;
    }
}
