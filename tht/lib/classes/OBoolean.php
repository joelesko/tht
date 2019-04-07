<?php

namespace o;

class OBoolean extends OVar {

    function __toString () {
        return $this->u_to_string();
    }

    // Casting

    function u_to_number () {
        return $this->val ? 1 : 0;
    }

    function u_to_boolean () {
        return $this->val;
    }

    function u_to_string () {
        return $this->val ? 'true' : 'false';
    }
}

