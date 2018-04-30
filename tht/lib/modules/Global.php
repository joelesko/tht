<?php

namespace o;

class u_Global extends OClass {
    function u_constant ($key, $ary) {
        ARGS('sl', func_get_args());

        $v = [];
        foreach (uv($ary) as $a) {
            $v[$a] = $a;
        }
        $this->u_field[$key] = $v;
    }

    function u_setting($key) {
        ARGS('s', func_get_args());
        return Tht::getTopConfig('app', $key);
    }
}

