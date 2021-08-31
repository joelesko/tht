<?php

namespace o;

require_once('helpers/RequestData.php');

class u_Input extends OStdModule {

    private $processedUpload = [];

    private static $uploadError = '';
    private $allowRemote = false;

    static private $IMAGE_MAX_DIMENSION = 2000;
    static private $IMAGE_JPEG_COMPRESSION = 90;

    static function setUploadError($errorMsg) {
        self::$uploadError = $errorMsg;
    }

    function __construct($allowRemote = false) {
        $this->allowRemote = $allowRemote;
    }

    function getData($method) {
        return new u_RequestData ($method, $this->allowRemote);
    }


    // Meta Getters
    //------------------------------------------------

    function u_x_danger_allow_remote() {

        $this->ARGS('', func_get_args());

        return new u_Input(true);
    }

    function u_route($key, $rule = 'id', $default = null) {

        $this->ARGS('s**', func_get_args());

        $val = WebMode::getWebRouteParam($key, '');

        $result = $this->u_validate_value($key, $val, $rule);

        return $this->getFieldResult($result, $default);
    }

    function u_get($fieldName, $rule='', $default = null) {

        $this->ARGS('ss*', func_get_args());

        $rd = new u_RequestData ('get');

        $result = $rd->getField($fieldName, $rule);

        return $this->getFieldResult($result, $default);
    }

    function u_post($fieldName, $rule='', $default = null) {

        $this->ARGS('ss*', func_get_args());

        $rd = new u_RequestData ('post', $this->allowRemote);

        $result = $rd->getField($fieldName, $rule);

        return $this->getFieldResult($result, $default);
    }

    function getFieldResult($result, $default) {

        if ($result['ok']) {
            return $result['value'];
        }
        else {
            return is_null($default) ? $result['value'] : $default;
        }
    }

    function u_get_all($rules) {

        $this->ARGS('m', func_get_args());

        $fieldConfig = [];
        foreach ($rules as $field => $r) {
            $fieldConfig[$field] = ['rule' => $r];
        }

        $rd = $this->getData('get');
        return $rd->getFields($fieldConfig);
    }

    function u_post_all($rules) {

        $this->ARGS('m', func_get_args());

        $fieldConfig = [];
        foreach ($rules as $field => $r) {
            $fieldConfig[$field] = ['rule' => $r];
        }

        $rd = $this->getData('post', $this->allowRemote);

        return $rd->getFields($fieldConfig);
    }

    // Other Getters
    //------------------------------------------------

    function u_print_all() {

        $this->ARGS('', func_get_args());

        $post = $this->getData('post');
        $get  = $this->getData('get');

        $all = v([
            'get'  => $get->getAllRawFields(),
            'post' => $post->getAllRawFields(),
            'allowRemotePost' => $this->allowRemote,
        ]);

        Security::safePrint($all);

        return ONothing::create('printAll');
    }

    function u_x_danger_raw_data($method) {

        $this->ARGS('s', func_get_args());

        $rd = $this->getData($method);

        $rawData = $rd->getAllRawFields();

        return $rawData;
    }

    function u_field_names($method) {

        $this->ARGS('s', func_get_args());

        $rd = $this->getData($method);

        return $rd->getFieldNames();
    }

    function u_has_field($fieldName, $method) {

        $this->ARGS('ss', func_get_args());

        $rd = $this->getData($method);

        return $rd->hasField($fieldName);
    }


    // Manual Validation
    //------------------------------------------------

    function u_validate_value($name, $val, $rule) {

        // TODO: Map/String
        $this->ARGS('s**', func_get_args());

        $validator = new u_InputValidator ();
        $validated = $validator->validateField($name, $val, $rule);

        unset($validated['field']);

        return $validated;
    }

    // internal
    function validateRawFields($rulesMap, $rawVals) {

        $validator = new u_InputValidator ();
        $validated = $validator->validateFields($rawVals, $rulesMap);

        return v($validated);
    }



    // Uploads
    //------------------------------------------------

    function u_get_uploaded_file($key, $uploadDir, $allowedExts, $maxSizeKb=0) {

        $this->ARGS('ssli', func_get_args());

        Security::validatePostRequest();
        self::$uploadError = '';

        $files = OMap::create(Tht::getPhpGlobal('files', '*'));

        if (!isset($files[$key])) {
            // Invalid key.
            return '';
        }

        $file = $files[$key];

        if (!$this->validateFileSize($file, $maxSizeKb)) {
            // File is too large.
            return '';
        }

        $fileExt = Security::validateUploadedFile($file, $allowedExts);
        if (!$fileExt) {
            // Invalid extension.
            return '';
        }

        // Make sure dir exists.
        $newDir = Tht::path('files', $uploadDir);
        Tht::module('File')->u_make_dir($newDir);

        // Move file to dir with a random name.
        $newName = Tht::module('String')->u_random(30, true) . '.' . $fileExt;
        $newPath = Tht::path('files', $uploadDir, $newName);
        move_uploaded_file($file['tmp_name'], $newPath);

        $relPath = Tht::getRelativePath('files', $newPath);

        return $relPath;
    }

    function validateFileSize($file, $maxSizeKb) {

        if (!$maxSizeKb) { return true; }

        $sizeKb = ceil($file['size'] / 1024);
        if ($sizeKb > $maxSizeKb) {
            self::$uploadError = "File size ($sizeKb KB) can not be larger than $maxSizeKb KB.";
            return false;
        }

        return true;
    }

    function u_get_uploaded_image($key, $uploadDir, $maxX=0, $maxY=0, $keepAspectRatio=false) {

        $this->ARGS('ssnnb', func_get_args());

        if (!$maxX) { $maxX = 500; }
        if (!$maxY) { $maxY = 500; }

        Security::validatePostRequest();
        self::$uploadError = '';

        if (isset($this->processedUpload[$key])) {
            return $this->processedUpload[$key];
        }

        $exts = OList::create(['png', 'jpg', 'jpeg', 'gif']);
        $tempRelPath = $this->u_get_uploaded_file($key, $uploadDir, $exts);

        if (!$tempRelPath) {
            $newRelPath = '';
        }
        else {
            $newRelPath = preg_replace('/\\.\\w+$/', '.jpg', $tempRelPath);
            $ok = $this->resizeImage($tempRelPath, $newRelPath, $maxX, $maxY, $keepAspectRatio);
            $newRelPath = $ok ? $newRelPath : '';
        }

        $this->processedUpload[$key] = $newRelPath;

        return $newRelPath;
    }

    // TODO: Retain png output if it results in better quality and good compression.
    // TODO: Can break this up into smaller functions, probably.
    function resizeImage($origRelPath, $newRelPath, $maxX, $maxY, $keepAspectRatio) {

        // Large files can take around 40 MB
        ini_set('memory_limit', '100M');

        $origPath = Tht::path('files', $origRelPath);
        $newPath  = Tht::path('files', $newRelPath);

        list($origX, $origY) = getimagesize($origPath);

        if ($origX > self::$IMAGE_MAX_DIMENSION || $origY > self::$IMAGE_MAX_DIMENSION) {

            unlink($origPath);
            self::$uploadError = 'File dimensions too large.  Maximum is '
                 . self::$IMAGE_MAX_DIMENSION . ' x ' . self::$IMAGE_MAX_DIMENSION;

            return false;
        }

        if ($keepAspectRatio) {

            $shiftX = 0;
            $shiftY = 0;

            if ($origX > $origY) {
                // Horizontal (landscape)
                $newX = $maxX;
                $ratioX = $maxX / $origX;
                $newY = $ratioX * $origY;
            }
            else {
                // Vertical (portrait)
                $newY = $maxY;
                $ratioY = $maxY / $origY;
                $newX = $ratioY * $origX;
            }

            $canvasX = $newX;
            $canvasY = $newY;
        }
        else {
            // Expand the dimension that is furthest from the max,
            // and crop the other dimension.

            $ratioX = $origX / $maxX;
            $ratioY = $origY / $maxY;

            if ($ratioX < $ratioY) {
                $newX = $maxX;
                $newY = $origY + floor((($newX - $origX) / $origX) * $origY);
                $shiftY = floor(($maxY - $newY) / 2);
                $shiftX = 0;
            }
            else {
                $newY = $maxY;
                $newX = $origX + floor((($newY - $origY) / $origY) * $origX);
                $shiftX = floor(($maxX - $newX) / 2);
                $shiftY = 0;
            }

            $canvasX = $maxX;
            $canvasY = $maxY;
        }

        $type = $this->getImageType($origPath);
        if (!$type) {
            self::$uploadError = 'Unsupported image type.  Only `.jpg`, `.png`, `.gif` are allowed.';
            return '';
        }

        $fn = 'imagecreatefrom' . $type;
        $src = $fn($origPath);
        $dst = imagecreatetruecolor($canvasX, $canvasY);

        imagecopyresampled($dst, $src, $shiftX, $shiftY, 0, 0, $newX, $newY, $origX, $origY);

        $ok = imagejpeg($dst, $newPath, self::$IMAGE_JPEG_COMPRESSION);

        unlink($origPath);

        return $ok;
    }

    function u_get_upload_error() {

        $this->ARGS('', func_get_args());

        return self::$uploadError;
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
