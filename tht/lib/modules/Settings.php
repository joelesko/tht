<?php

namespace o;

class u_Settings extends OClass {

    function u_get($key, $default = '') {
        $this->ARGS('s', func_get_args());
        return Tht::getTopConfig('app', $key);
    }
}
