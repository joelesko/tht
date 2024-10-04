<?php

namespace o;

class u_AppConfig extends OClass {

    function u_get($key, $default = null) {

        $this->ARGS('s*', func_get_args());

        $value = Tht::getAppConfig($key, $default);

        return $value;
    }

    // function u_get_dir($key, $default = null) {

    //     $this->ARGS('s*', func_get_args());

    //     $val = Tht::getAppConfig($key, $default);

    //     return $val !== '' ? new DirTypeString($val) : null;
    // }

    // function u_get_file($key, $default = '') {

    //     $this->ARGS('s*', func_get_args());

    //     $val = Tht::getAppConfig($key, $default);

    //     return $val !== '' ? new FileTypeString($val) : null;
    // }


    // function checkType($key, $val, $wantType) {
    //     $gotType = v($val)->u_type();

    //     if ($gotType !== $wantType) {
    //         if ($wantType == 'string' && $gotType == 'number') {
    //             return;
    //         }
    //         $this->error("Config key `$key` expected type: `$wantType`  Got: `$gotType`");
    //     }
    // }

    // function u_get_type_string($stringType, $key, $default = null) {

    //     $this->ARGS('sss', func_get_args());

    //     $val = $this->u_get_string($key, $default);

    //     return $val !== '' ? OTypeString::create($stringType, $val) : '';
    // }

    // function u_get_string($key, $default = '') {

    //     $this->ARGS('ss', func_get_args());

    //     $val = Tht::getAppConfig($key, $default);

    //     $this->checkType($key, $val, 'string');

    //     return '' + $val;
    // }

    // function u_get_number($key, $default = null) {

    //     $this->ARGS('sn', func_get_args());

    //     $val = Tht::getAppConfig($key, $default);

    //     $this->checkType($key, $val, 'number');

    //     return 0 + $val;
    // }

    // function u_get_boolean($key, $default = false) {

    //     $this->ARGS('sb', func_get_args());

    //     $val = Tht::getAppConfig($key, $default);

    //     $this->checkType($key, $val, 'boolean');

    //     return $val;
    // }


}
