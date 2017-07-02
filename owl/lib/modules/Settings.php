<?php

namespace o;

class u_Settings extends StdModule {

    function u_get($key) {
        return Owl::getTopConfig('app', $key);
    }

}