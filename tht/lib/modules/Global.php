<?php

namespace o;

class u_Global extends OClass {
    function u_constant ($key, $ary) {
        $this->ARGS('sl', func_get_args());

        $v = [];
        foreach (uv($ary) as $a) {
            $v[$a] = $a;
        }
        $this->u_field[$key] = $v;
    }
}
