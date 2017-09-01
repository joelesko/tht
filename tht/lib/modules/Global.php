<?php

namespace o;

class u_Global extends OClass {
    function u_constant ($key, $ary) {
        $v = [];
        foreach (uv($ary) as $a) {
            $v[$a] = $a;
        }
        $this->u_field[$key] = $v;
    }

    function u_setting($key) {
        return Tht::getTopConfig('app', $key);
    }
}

