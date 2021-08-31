<?php

namespace o;

class u_Image {

    static private $MAX_DIMENSION = 5000;
    static private $PALETTE_NUM_COLORS_THRESH = 250;
    static private $JPEG_QUALITY = 80;   // of 100
    static private $PNG_COMPRESSION = 9; // max
    static private $SMALL_PALETTE_NUM_COLORS = 100;

    private $quantizeStep = 8;

    function u_thumb($oldFile) {
        return $this->optimize($oldFile, 300, 'thumb');
    }

    function optimize ($oldFile, $maxResizeWidth=1024, $fileTag='optimized') {

        $newFile = $this->getNewFileName($oldFile, $fileTag);

        // Large files can take around 40 MB
        ini_set('memory_limit', '100M');

        list($oldSizeX, $oldSizeY) = $this->getImageSize($oldFile);
        list($newSizeX, $newSizeY) = $this->getNewImageSize($oldFile, $oldSizeX, $oldSizeY, $maxResizeWidth);

        $oldType = $this->getImageType($oldFile);
        if (!$oldType) {
            return [ 'error' => 'Image type not supported.' ];
        }

        if ($this->isAnimatedGif($oldType, $oldFile)) {
            return [ 'error' => 'Animated GIF.' ];
        }

        $newImage = $this->createNewImage($oldType, $oldFile, $newSizeX, $newSizeY, $oldSizeX, $oldSizeY);
        $profile = $this->analyze($newImage, $newSizeX, $newSizeY);
        $outType = $this->getOutputType($oldType, $profile);

        $newFile = $this->writeNewImage($outType, $newImage, $newFile, $newSizeX, $newSizeY, $profile);
        $this->compareFileSize($oldFile, $newFile);

        return [ 'error' => '', 'newFile' => $newFile ];
    }

    function getNewFileName($oldFile, $fileTag) {
        if (preg_match('![^a-z]!', $fileTag)) {
            Tht::error("Argument `fileTag` can only be all lowercase.  Got: `$fileTag`");
        }
        $newFile = preg_replace('!\.(.*?)$!', '', $oldFile);
        $newFile .= '_' . $fileTag;

        return $newFile;
    }

    // Calculate new size
    // Don't resize responsive images (e.g. @2x)
    function getNewImageSize($oldFile, $oldSizeX, $oldSizeY, $maxResizeWidth) {
        $newSizeX = $oldSizeX;
        $newSizeY = $oldSizeY;
        if (strpos($oldFile, '@') === false) {
            if ($oldSizeX > $maxResizeWidth) {
                $newSizeX = $maxResizeWidth;
                $newSizeY = round(($oldSizeY / $oldSizeX) * $newSizeX);
            }
        }
        return [$newSizeX, $newSizeY];
    }

    function getImageSize($oldFile) {
        $info = getimagesize($oldFile);
        $oldSizeX = $info[0];
        $oldSizeY = $info[1];
        $max = self::$MAX_DIMENSION;
        if ($oldSizeX >= $max || $oldSizeY >= $max) {
            $fileName = basename($oldFile);
            Tht::error("Can not handle dimension larger than $max. Got: `$oldSizeX x $oldSizeY` for `$fileName`");
        }

        return [$oldSizeX, $oldSizeY];
    }

    // Create new image (resized, if necessary)
    function createNewImage($oldType, $oldFile, $newSizeX, $newSizeY, $oldSizeX, $oldSizeY) {

        // Load original image
        $fnCreateImage = 'imagecreatefrom' . $oldType;
        $oldImage = $fnCreateImage($oldFile);

        // Create new image
        $newImage = imagecreatetruecolor($newSizeX, $newSizeY);
        imagesavealpha($newImage, true);
        imagealphablending($newImage, false);
        imagecopyresampled($newImage, $oldImage, 0, 0, 0, 0, $newSizeX, $newSizeY, $oldSizeX, $oldSizeY);

        return $newImage;
    }

    // This arg list is a little ridiculous
    function writeNewImage($outType, $newImage, $newFile, $newSizeX, $newSizeY, $profile) {

        if ($outType == 'indexedPng') {
            $newImage = $this->palettizeImage($newImage, $newSizeX, $newSizeY, $profile['palette']);
            $newFile .= ".png";
            imagepng($newImage, $newFile, self::$PNG_COMPRESSION);
        }
        else if ($outType == 'quantizedPng') {
            $newImage = $this->quantizeImage($newImage, $newSizeX, $newSizeY, $this->quantizeStep);
            $newFile .= ".png";
            imagepng($newImage, $newFile, self::$PNG_COMPRESSION);
        }
        else {
            $newFile .= ".jpg";
            imagejpeg($newImage, $newFile, self::$JPEG_QUALITY);
        }

        return $newFile;
    }

    // If the new file isn't better, just use the old file
    function compareFileSize($oldFile, $newFile) {
        $oldFileSize = filesize($oldFile);
        $newFileSize = filesize($newFile);
        if ($newFileSize >= $oldFileSize) {
            copy($oldFile, $newFile);
        }
    }

    function getOutputType($oldType, $profile) {
        // Output strategy
        $outType = '';
        if ($oldType == 'jpeg') {
            $outType = 'jpeg';
        } else if ($profile['palette']) {
             if (!$profile['hasAlpha'] && count($profile['palette']) > self::$SMALL_PALETTE_NUM_COLORS) {
                $outType = 'jpeg';
            } else {
                $outType = 'indexedPng';
            }
        } else if ($profile['hasAlpha']) {
            $outType = 'quantizedPng';
        } else {
            $outType = 'jpeg';
        }
        return $outType;
    }

    function getImageType($filePath) {
        // Detect MIME type
        $oldMime = Tht::module('*File')->u_get_mime_type($filePath);

        $types = [
            'image/jpeg'      => 'jpeg',
            'image/png'       => 'png',
            'image/gif'       => 'gif',
        ];
        if (!isset($types[$oldMime])) {
            return '';
        }
        return $types[$oldMime];
    }

    function isAnimatedGif($oldType, $filePath) {
        if ($oldType != 'gif') {
            return false;
        }
        $fh = fopen($filePath, 'rb');
        $count = 0;
        while(!feof($fh) && $count < 2) {
            $chunk = fread($fh, 1024 * 100);
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
        }
        fclose($fh);
        return $count > 1;
    }

    function analyze($newImage, $w, $h) {

        $numColors = 0;
        $seenColor = [];
        $hasAlpha = false;
        $canPalettize = true;

        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {

                list($a, $r, $g, $b) = $this->readPixel($newImage, $x, $y);

                if ($a > 0) {
                    $hasAlpha = true;
                }

                // Create a slightly quantized histogram that could be used
                // as a palette if we have < 255 colors
                $qstep = 2;
                $qa = $this->quantizeValue($a, $qstep, 127);
                $qr = $this->quantizeValue($r, $qstep);
                $qg = $this->quantizeValue($g, $qstep);
                $qb = $this->quantizeValue($b, $qstep);
                if ($qa < 4)   { $qa = 0;   }
                if ($qa > 120) { $qa = 127; }

                $key = "$qr,$qg,$qb,$qa";
                if ($canPalettize && !isset($seenColor[$key])) {
                    $seenColor[$key] = 1;
                    $numColors += 1;
                    if ($numColors > self::$PALETTE_NUM_COLORS_THRESH) {
                        $canPalettize = false;
                    }
                }
            }
        }

        return [
            'hasAlpha' => $hasAlpha,
            'palette' => $canPalettize ? array_keys($seenColor) : [],
        ];
    }

    function readPixel($image, $x, $y) {
        $rgba = imagecolorat($image, $x, $y);
        $a = ($rgba >> 24) & 0xFF;
        $r = ($rgba >> 16) & 0xFF;
        $g = ($rgba >> 8) & 0xFF;
        $b = $rgba & 0xFF;

        return [$a, $r, $g, $b];
    }

    // Create an indexed PNG.  Best case scenario for PNGs.
    // Since the original image had < 255 colors, this should be lossless.
    function palettizeImage($newImage, $w, $h, $palette) {

        $pImage = imagecreate($w, $h);
        imagesavealpha($pImage, true);
        imagealphablending($pImage, false);

        // Convert histogram colors to palette
        foreach ($palette as $c) {
            list($r, $g, $b, $a) = explode(',', $c);
            imagecolorallocatealpha($pImage, $r, $g, $b, $a);
        }

        $empty = imagecolorclosestalpha($pImage, 0,0,0, 127);
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                list($a, $r, $g, $b) = $this->readPixel($newImage, $x, $y);
                if ($a > 120) {
                    $color = $empty;
                } else {
                    $color = imagecolorclosestalpha($pImage, $r, $g, $b, $a);
                }
                imagesetpixel($pImage, $x, $y, $color);
            }
        }

        return $pImage;
    }

    // Peg each color channel to the given step, in order to improve compression.
    // Step = 8 is just barely perceptible.
    function quantizeImage($newImage, $w, $h, $step=8) {

        $alphaStep = ceil($step / 2);
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {

                list($a, $r, $g, $b) = $this->readPixel($newImage, $x, $y);

                $na = 0;
                if ($a > 120) {
                    $na = 127;
                }
                else if ($a > 0) {
                    // suitable-ish for gradients
                    $na = $this->quantizeValue($a, $alphaStep, 127);
                }
                $nr = $this->quantizeValue($r, $step);
                $ng = $this->quantizeValue($g, $step);
                $nb = $this->quantizeValue($b, $step);

                $color = imagecolorallocatealpha($newImage, $nr, $ng, $nb, $na);
                imagesetpixel($newImage, $x, $y, $color);
            }
        }

        return $newImage;
    }

    function quantizeValue($v, $step, $max=255) {
        $qv = round($v / $step) * $step;
        $qv = min($qv, $max);
        $qv = max($qv, 0);
        return $qv;
    }

    static function canOptimize($f) {
        if (!in_array($f['fileExt'], ['png', 'gif', 'jpeg', 'jpg', 'bmp'])) {
            return false;
        }

        if (strpos($f['dirPath'], '_originals') !== false) {
            return false;
        }

        $origDir = $f['dirPath'] . '/_originals';
        $origPath = $origDir . '/' . $f['fileName'];
        if (file_exists($origPath)) {
            return false;
        }

        return true;
    }

    function auditImages($dir) {

        $numImages = 0;
        $oldSizeTotal = 0;
        $newSizeTotal = 0;

        $images = Tht::module('*File')->u_loop_dir($dir, function($f) use (&$numImages, &$oldSizeTotal, &$newSizeTotal) {

            if (self::canOptimize($f)) {

                $oldSize = round(filesize($f['fullPath']) / 1024, 1);

                $estPercent = 0.6;
                if ($oldSize > 500) {
                    $estPercent = 0.3;
                }
                else if ($f['fileExt'] == 'png' && $oldSize > 200) {
                    $estPercent = 0.4;
                }

                $newSizeTotal += round($oldSize * $estPercent, 1);
                $oldSizeTotal += $oldSize;
                $numImages += 1;

                return $f;
            }
        }, true);

        $pc = 0;
        $delta = 0;
        if ($numImages) {
            $pc = 100 - round($newSizeTotal / $oldSizeTotal, 2) * 100;
            $delta = round($oldSizeTotal - $newSizeTotal);
        }

        $audit = [
            'images' => $images,
            'numImages' => $numImages,
            'savingsPercent' => $pc,
            'savingsKb' => $delta,
        ];

        return $audit;
    }

    // Entry point from CliMode
    function optimizeImages($dir=null, $maxSize=1024) {

        CliMode::printHeaderBox('Optimize Images');

        if (!$dir) {
            Tht::error("Need a directory.");
        }

        $dir = Tht::realpath($dir);
        if (!is_dir($dir)) {
            Tht::error("Not a directory:\n  $dir");
        }

        $audit = $this->auditImages($dir);

        if (!$audit['numImages']) {
            echo "No images to optimize.\n\n";
            return;
        }

        $msg = "Optimize (" . $audit['numImages'] . ") images under:\n  $dir\n\n";
        $msg .= "The original images will be backed up to sub-folders named `_originals`.\n\n";
        $msg .= "Continue?";
        if (!Tht::module('System')->u_confirm($msg)) {
            Tht::exitScript(0);
        }

        echo "\n\nOptimizing images...\n\n";
        usleep(500000);

        $numImages = 0;
        $numSkipped = 0;
        $oldSizeTotal = 0;
        $newSizeTotal = 0;

        foreach ($audit['images'] as $f) {

            $origDir = $f['dirPath'] . '/_originals';
            $origPath = $origDir . '/' . $f['fileName'];
            $relPath = str_replace($dir, '', $f['fullPath']);

            if (!is_dir($origDir)) {
                Tht::module('*File')->u_make_dir($origDir);
            }

            echo $relPath . "\n";

            try {
                $result = Tht::module('Image')->optimize($f['fullPath'], $maxSize);
            } catch (\Exception $e) {
                $result = [ 'error' => $e->getMessage() ];
            }

            if ($result['error']) {
                echo "  SKIP: " . $result['error'] . "\n\n";
                copy($f['fullPath'], $origPath);
                $numSkipped += 1;
                continue;
            }

            $optPath = $result['newFile'];

            $oldSize = round(filesize($f['fullPath']) / 1024, 1);
            $newSize = round(filesize($optPath) / 1024, 1);
            $oldSizeTotal += $oldSize;
            $newSizeTotal += $newSize;
            $numImages += 1;

            rename($f['fullPath'], $origPath);
            rename($optPath, $f['fullPath']);

            $pc = 100 - (round($newSize / $oldSize, 2) * 100);
            echo "  " . $oldSize . "k -> " . $newSize . "k = " . $pc . "%\n\n";
        }

        $pc = 0;
        $delta = 0;
        if ($numImages) {
            $pc = 100 - round($newSizeTotal / $oldSizeTotal, 2) * 100;
            $delta = round($oldSizeTotal - $newSizeTotal);
        }

        echo "\n-------------------------\n\n";
        echo "Images Optimized: $numImages\n";
        echo "Images Skipped: $numSkipped\n\n";
        echo "Bytes Cut: $delta kb\n";
        echo "Total: " . $pc . "%\n\n";
        echo "NOTE: File extensions were retained, but the underlying types might have changed.\n\n";
    }
}
