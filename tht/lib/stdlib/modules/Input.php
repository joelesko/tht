<?php

namespace o;

require_once('Input/InputValidator.php');


class u_Input extends OStdModule {

    private $processedUpload = [];

    private static $uploadError = '';

    static private $IMAGE_MAX_DIMENSION = 2000;
    static private $IMAGE_JPEG_COMPRESSION = 90;

    static function setUploadError($errorMsg) {
        self::$uploadError = $errorMsg;
    }

    function getData($method) {

        if ($method != 'get' && $method != 'post') {
            $this->error('HTTP method must be `get` or `post` (lowercase).');
        }

        return Tht::getPhpGlobal($method, '*');
    }


    // Getters
    //------------------------------------------------

    function u_route($key, $rule = 'id', $default = null) {

        Tht::module('Meta')->u_fail_if_template_mode();

        $this->ARGS('s**', func_get_args());

        $val = WebMode::getWebRouteParam($key, '');
        $result = $this->u_validate_value($key, $val, $rule);

        if ($result['ok']) {
            return $result['value'];
        }
        return is_null($default) ? $result['value'] : $default;
    }

    function u_get($key, $rule='', $default = null) {

        Tht::module('Meta')->u_fail_if_template_mode();

        $this->ARGS('ss*', func_get_args());

        return $this->getValue('get', $key, $rule, $default);
    }

    function u_post($key, $rule='', $default = null) {

        Tht::module('Meta')->u_fail_if_template_mode();

        $this->ARGS('ss*', func_get_args());

        return $this->getValue('post', $key, $rule, $default);
    }

    function getValue($method, $key, $rule, $default) {

        $data = $this->getData($method);

        $rawVal = isset($data[$key]) ? $data[$key] : '';

        $iv = new u_InputValidator ();
        $result = $iv->validateField($key, $rawVal, $rule);

        if ($result['ok']) {
            return $result['value'];
        }

        return is_null($default) ? $result['value'] : $default;
    }

    function u_get_all($ruleMap) {

        Tht::module('Meta')->u_fail_if_template_mode();

        $this->ARGS('m', func_get_args());

        return $this->getAllValues('get', $ruleMap);
    }

    function u_post_all($ruleMap) {

        Tht::module('Meta')->u_fail_if_template_mode();

        $this->ARGS('m', func_get_args());

        return $this->getAllValues('post', $ruleMap);
    }

    function getAllValues($method, $ruleMap) {

        $data = $this->getData($method);

        $iv = new u_InputValidator ();
        $result = $iv->validateFields($data, $ruleMap);

        return $result;
    }




    // Meta Getters
    //------------------------------------------------

    function u_field_keys($method) {

        Tht::module('Meta')->u_fail_if_template_mode();

        $this->ARGS('s', func_get_args());

        $data = $this->getData($method);

        return OList::create(
            array_keys($data)
        );
    }

    function u_has_field($method, $key) {

        Tht::module('Meta')->u_fail_if_template_mode();

        $this->ARGS('ss', func_get_args());

        $data = $this->getData($method);

        return isset($data[$key]);
    }

    function u_print_all() {

        Tht::module('Meta')->u_fail_if_template_mode();

        $this->ARGS('', func_get_args());

        $all = v([
            'get'  => $this->u_x_danger_raw_data('get'),
            'post' => $this->u_x_danger_raw_data('post'),
        ]);

        Security::safePrint($all);

        return NULL_NORETURN;
    }

    function u_x_danger_raw_data($method) {

        Tht::module('Meta')->u_fail_if_template_mode();

        $this->ARGS('s', func_get_args());

        $data = $this->getData($method);

        return OMap::create($data);
    }



    // Manual Validation
    //------------------------------------------------

    function u_validate_value($name, $val, $rule) {

        Tht::module('Meta')->u_fail_if_template_mode();

        // TODO: Allow $rule to also be a Map
        $this->ARGS('s*s', func_get_args());

        $validator = new u_InputValidator ();
        $validated = $validator->validateField($name, $val, $rule);

        unset($validated['field']);

        return $validated;
    }

    function u_validate_values($rawValues, $rulesMap) {

        Tht::module('Meta')->u_fail_if_template_mode();

        $validator = new u_InputValidator ();
        $validated = $validator->validateFields($rawValues, $rulesMap);

        return $validated;
    }

    // internal
    function u_z_sanitize_raw_input($val) {
        return Security::sanitizeInputString($val);
    }



    // internal
    function getValidator() {
        return new u_InputValidator ();
    }



    // Uploads
    //------------------------------------------------

    function u_uploaded_file($key, $uploadDir, $allowedExts, $maxSizeMb=0) {

        Tht::module('Meta')->u_fail_if_template_mode();

        $this->ARGS('s*sn', func_get_args());
        OtypeString::assertType($uploadDir, 'dir');

       // self::$uploadError = '';
        $validationResult = Security::validateUploadedFile($key, $allowedExts, $maxSizeMb, $uploadDir);

        return $validationResult;
    }

    function u_uploaded_image($key, $uploadDir, $size='500x500', $flags=null) {

        Tht::module('Meta')->u_fail_if_template_mode();

        $this->ARGS('s*sm', func_get_args());
        OtypeString::assertType($uploadDir, 'dir');

        $flags = $this->flags($flags, [
            'exactSize' => false,
        ]);

        $exts = OList::create(['png', 'jpg', 'jpeg', 'gif', 'webp']);
        $uploadResult = $this->u_get_uploaded_file($key, $uploadDir, $exts);

        if (!$uploadResult->u_is_ok()) {
            return $uploadResult;
        }
        else {
            // Optimize image
            if (!preg_match('#\d+x\d+#', $size)) {
                $this->error("Size argument must be in the format of: `width x height` (ex: `500x500`)  Got: `$size`");
            }
            list($sizeX, $sizeY) = explode('x', $size);

            $origPath = $uploadResult->u_get();
            $newPath = $origPath->u_with_ext('webp');
            $ok = $this->resizeImage($origPath->u_render_string(), $newPath->u_render_string(), $sizeX, $sizeY, $flags['exactSize']);
            $newRelPath = $ok ? $newRelPath : '';
        }

        $fNewRelPath = new FileTypeString($newRelPath);

        return Tht::module('Result')->u_ok($fNewRelPath);
    }

    // TODO: Move to Image helper?
    // TODO: Can break this up into smaller functions, probably.
    // Always output as jpeg for now.  No risk of being re-optimized by Image.optimize().
    function resizeImage($origPath, $newPath, $maxX, $maxY, $exactSize) {

        // Large files can take around 40 MB
        ini_set('memory_limit', '100M');

        list($origX, $origY) = getimagesize($origPath);

        // if ($origX > self::$IMAGE_MAX_DIMENSION || $origY > self::$IMAGE_MAX_DIMENSION) {

        //     unlink($origPath);
        //     $currDim = $origX . 'x' . $origY;
        //     self::$uploadError = "File dimensions ($currDim) are too large.  Max: "
        //          . self::$IMAGE_MAX_DIMENSION . ' x ' . self::$IMAGE_MAX_DIMENSION;

        //     return false;
        // }

        if ($exactSize) {

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
        else {

            // Scale along the largest dimension

            $shiftX = 0;
            $shiftY = 0;

            if ($origX > $origY) {
                // Horizontal (landscape)
                $newX = $maxX;
                $ratioX = $maxX / $origX;
                $newY = floor($ratioX * $origY);
            }
            else {
                // Vertical (portrait)
                $newY = $maxY;
                $ratioY = $maxY / $origY;
                $newX = floor($ratioY * $origX);
            }

            $canvasX = $newX;
            $canvasY = $newY;
        }

        $type = $this->getImageTypeForFile($origPath);
        if (!$type) {
            self::$uploadError = 'Unsupported image type.  Try: `.jpg`, `.png`, `.gif`, `.webp`';
            return '';
        }

        $fn = 'imagecreatefrom' . $type;
        $src = $fn($origPath);
        $dst = imagecreatetruecolor($canvasX, $canvasY);

        imagecopyresampled($dst, $src, $shiftX, $shiftY, 0, 0, $newX, $newY, $origX, $origY);

        $ok = imagewebp($dst, $newPath, self::$IMAGE_JPEG_COMPRESSION);

        unlink($origPath);

        return $ok;
    }

    function u_get_upload_error() {

        Tht::module('Meta')->u_fail_if_template_mode();

        $this->ARGS('', func_get_args());

        return self::$uploadError;
    }

    function getImageTypeForFile($filePath) {

        $mime = PathTypeString::create($filePath)->u_get_mime_type();

        $types = [
            'image/jpeg'  => 'jpeg',
            'image/png'   => 'png',
            'image/gif'   => 'gif',
            'image/webp'  => 'webp',
        ];

        if (!isset($types[$mime])) {
            return '';
        }

        return $types[$mime];
    }
}
