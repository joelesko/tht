<?php

namespace o;

require_once('helpers/RequestData.php');

class u_Input extends OStdModule {

    private $forms = [];
    private $processedUpload = [];

    public static $lastUploadError = '';

    static private $IMAGE_MAX_DIMENSION = 2000;
    static private $IMAGE_JPEG_COMPRESSION = 90;

    // Misc Getters

    function u_is_post() {
        return Tht::module('Request')->u_method() == 'post';
    }

    function u_route ($key) {
        $this->ARGS('s', func_get_args());
        return WebMode::getWebRouteParam($key);
    }

    function u_form ($formId, $schema=null) {
        $this->ARGS('sm', func_get_args());
        if (is_null($schema)) {
            if (isset($this->forms[$formId])) {
                return $this->forms[$formId];
            }
            else {
                Tht::error("Unknown formId `$formId`");
            }
        }

        require_once('Form.php');
        $f = new u_Form ($formId, $schema);
        $this->forms[$formId] = $f;
        return $f;
    }


    // Get Single Field

    function u_get($name, $sRules='') {
        $this->ARGS('ss', func_get_args());
        $getter = new u_RequestData ('get');
        return $getter->field($name, $sRules)['value'];
    }

    function u_post($name, $sRules='') {
        $this->ARGS('ss', func_get_args());
        $getter = new u_RequestData ('post');
        return $getter->field($name, $sRules)['value'];
    }

    function u_remote($method, $fieldName, $sRules='') {
        $this->ARGS('sss', func_get_args());
        $getter = new u_RequestData ($method);
        return $getter->field($fieldName, $sRules)['value'];
    }


    // Get Map of Fields

    function u_get_all($rules) {
        $this->ARGS('m', func_get_args());
        $getter = new u_RequestData ('get');
        return $getter->fields($rules);
    }

    function u_post_all($rules) {
        $this->ARGS('m', func_get_args());
        $getter = new u_RequestData ('post');
        return $getter->fields($rules);
    }

    function u_remote_all($method, $rules) {
        $this->ARGS('sm', func_get_args());
        $getter = new u_RequestData ($method, true);
        return $getter->fields($rules);
    }

    function u_print_all($method, $printToLog = false) {
        $this->ARGS('sf', func_get_args());
        $all = OMap::create(Tht::getPhpGlobal($method, '*'));
        if ($printToLog) {
            Tht::module('File')->u_log($all);
        } else {
            Tht::module('*Bare')->u_print($all);
        }
    }


    // Meta-Getters

    function u_fields($method) {
        $this->ARGS('s', func_get_args());
        $getter = new u_RequestData ($method);
        return OList::create($getter->fieldNames());
    }

    function u_has_field($method, $fieldName) {
        $this->ARGS('ss', func_get_args());
        $getter = new u_RequestData ($method);
        return $getter->hasField($fieldName);
    }

    function u_validate($fieldName, $val, $rules) {
        $this->ARGS('s*s', func_get_args());
        $validator = new u_InputValidator ();
        $validated = $validator->validateField($fieldName, $val, $rules);

        return OMap::create($validated);
    }

    function u_uploaded_file($key, $uploadDir, $allowedExts) {

        $this->ARGS('ssl', func_get_args());

        Security::validatePostRequest();
        self::$lastUploadError = '';

        $files = OMap::create(Tht::getPhpGlobal('files', '*'));

        if (isset($files[$key])) {
            $file = $files[$key];
            $fileExt = Security::validateUploadedFile($file, $allowedExts);
            if ($fileExt) {
                $newName = Tht::module('String')->u_random(20, true) . '.' . $fileExt;
                $newDir = Tht::path('files', $uploadDir);
                Tht::module('File')->u_make_dir($newDir);
                $newPath = Tht::path('files', $uploadDir, $newName);
                move_uploaded_file($file['tmp_name'], $newPath);

                $relPath = Tht::getRelativePath('files', $newPath);

                return $relPath;
            }
        }

        return '';
    }

    function u_uploaded_image($key, $uploadDir, $maxX, $maxY) {

        $this->ARGS('ssnn', func_get_args());

        Security::validatePostRequest();
        self::$lastUploadError = '';

        if (isset($this->processedUpload[$key])) {
            return $this->processedUpload[$key];
        }

        // Large files can take around 40 MB
        ini_set('memory_limit', '100M');

        $tempRelPath = $this->u_uploaded_file($key, $uploadDir, OList::create(['png', 'jpg', 'jpeg', 'gif']));
        if (!$tempRelPath) {
            $newRelPath = '';
        }
        else {
            $newRelPath = preg_replace('/\\.\\w+$/', '.jpg', $tempRelPath);
            $ok = $this->resizeImage($tempRelPath, $newRelPath, $maxX, $maxY);
            $newRelPath = $ok ? $newRelPath : '';
        }

        $this->processedUpload[$key] = $newRelPath;

        return $newRelPath;
    }

    function resizeImage($origRelPath, $newRelPath, $maxX, $maxY) {

        $this->ARGS('ssnn', func_get_args());

        $origPath = Tht::path('files', $origRelPath);
        $newPath  = Tht::path('files', $newRelPath);

        list($origX, $origY) = getimagesize($origPath);

        if ($origX > self::$IMAGE_MAX_DIMENSION || $origY > self::$IMAGE_MAX_DIMENSION) {
            unlink($origPath);
            self::$lastUploadError = 'File dimensions too large.  Maximum is ' . self::$IMAGE_MAX_DIMENSION . ' x ' . self::$IMAGE_MAX_DIMENSION;
            return false;
        }

        $ratioX = $origX / $maxX;
        $ratioY = $origY / $maxY;

        // expand the dimension that is furthest from the max,
        // and allow other dimension to be cropped
        if ($ratioX < $ratioY) {
            $newX = $maxX;
            $newY = $origY + floor((($newX - $origX) / $origX) * $origY);
            $shiftY = floor(($maxY - $newY) / 2);
            $shiftX = 0;
        } else {
            $newY = $maxY;
            $newX = $origX + floor((($newY - $origY) / $origY) * $origX);
            $shiftX = floor(($maxX - $newX) / 2);
            $shiftY = 0;
        }

        // Scale only
        // if ($origX > $origY) {
        //     // horizontal
        //     $ratio = $maxX / $origX;
        //     $newX = $maxX;
        //     $newY = $ratio * $origY;
        // }
        // else {
        //     // vertical
        //     $ratio = $maxY / $origY;
        //     $newY = $maxY;
        //     $newX = $ratio * $origX;
        // }

        $type = $this->getImageType($origPath);
        if (!$type) {
            self::$lastUploadError = 'Unsupported image type.  Only `.jpg`, `.png`, `.gif` are allowed.';
            return '';
        }

        $fn = 'imagecreatefrom' . $type;
        $src = $fn($origPath);
        $dst = imagecreatetruecolor($maxX, $maxY);

        imagecopyresampled($dst, $src, $shiftX, $shiftY, 0, 0, $newX, $newY, $origX, $origY);

        $ok = imagejpeg($dst, $newPath, self::$IMAGE_JPEG_COMPRESSION);

        unlink($origPath);

        return $ok;
    }

    function u_last_upload_error() {
        $this->ARGS('', func_get_args());
        return self::$lastUploadError;
    }

    function getImageType($filePath) {
        $mime = Tht::module('*File')->u_get_mime_type($filePath);
        $types = [
            'image/jpeg' => 'jpeg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
        ];
        if (!isset($types[$mime])) {
            return '';
        }
        return $types[$mime];
    }
}
