<?php

namespace o;

class S_Unsupported extends Symbol {

    function error($p) {
        $val = $this->token[TOKEN_VALUE];
        $try = CompilerConstants::$ALT_TOKENS[$val];
        $p->error("Unsupported token: `" . $this->token[TOKEN_VALUE] . "` Try: $try");
    }

    function asStatement ($p) {
        $this->error($p);
    }

    function asLeft ($p) {
        $this->error($p);
    }
}
