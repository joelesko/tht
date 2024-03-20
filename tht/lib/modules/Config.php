<?php

namespace o;

// TODO: support more getters around maps and lists

class u_Config extends OClass {

    // TODO: probably move this to OTypeString and make dynamic
    private $allTypeStrings = 'Html|Url|Css|Js|Cmd|Sql';

    function u_get($key, $default = null) {

        $this->ARGS('s', func_get_args());

        $value = Tht::getAppConfig($key, $default);

        // Auto-convert to TypeString
        if (is_string($value) && preg_match("/(" . $this->allTypeStrings . ")$/", $key, $m)) {
            return OTypeString::create(strtolower($m[1]), $value);
        }

        return $value;
    }

    function checkType($key, $val, $wantType) {
        $gotType = v($val)->u_type();

        if ($gotType !== $wantType) {
            if ($wantType == 'string' && $gotType == 'number') {
                return;
            }
            $this->error("Config key `$key` expected type: `$wantType`  Got: `$gotType`");
        }
    }

    function u_get_type_string($stringType, $key, $default = null) {

        $this->ARGS('sss', func_get_args());

        $val = $this->u_get_string($key, $default);

        return $val !== '' ? OTypeString::create($stringType, $val) : '';
    }

    function u_get_string($key, $default = null) {

        $this->ARGS('ss', func_get_args());

        $val = Tht::getAppConfig($key, $default);

        $this->checkType($key, $val, 'string');

        return '' + $val;
    }

    function u_get_number($key, $default = null) {

        $this->ARGS('sn', func_get_args());

        $val = Tht::getAppConfig($key, $default);

        $this->checkType($key, $val, 'number');

        return 0 + $val;
    }

    function u_get_boolean($key, $default = null) {

        $this->ARGS('sb', func_get_args());

        $val = Tht::getAppConfig($key, $default);

        $this->checkType($key, $val, 'boolean');

        return $val;
    }


}
