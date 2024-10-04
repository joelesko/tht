<?php

namespace o;

class TokenStream {

    var $tokens = [];

    function add($t) {
        $this->tokens []= implode(TOKEN_SEP, $t);
    }

    function done() {
        if (count($this->tokens) <= 1) {
            // add a noop token to prevent error if there are no tokens (e.g. all comments)
            $this->add([TokenType::WORD, '1,1', 0, 'false']);
        }
        // array_pop is much faster than array_shift, so reverse it
        $this->tokens = array_reverse($this->tokens);
    }

    function count() {
        return count($this->tokens);
    }

    function next() {
        $t = array_pop($this->tokens);
        return explode(TOKEN_SEP, $t, 4);
    }

    function lookahead() {
        $t = $this->tokens[count($this->tokens) - 1];
        return explode(TOKEN_SEP, $t, 4);
    }
}
