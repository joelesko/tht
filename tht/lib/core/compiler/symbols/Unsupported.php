<?php

namespace o;

class S_Unsupported extends Symbol {

    function getCorrect($token) {
        return [
            'switch'   => ['`match { ... }`', 'match', '/language-tour/intermediate-features#match'],
            'for'      => ['`foreach $list as $item { ... }`', 'Loops', '/language-tour/loops'],
            'while'    => ['`loop { ... }`',  'Loops', '/language-tour/loops'],
            'require'  => ['`import`',        'Modules', '/language-tour/modules'],
            'include'  => ['`import`',        'Modules', '/language-tour/modules'],
        ][$token];
    }

    function error($p) {
        $try = $this->getCorrect($this->token[TOKEN_VALUE]);
        ErrorHandler::setErrorDoc($try[2], $try[1]);
        $p->error("Unknown keyword: `" . $this->token[TOKEN_VALUE] . "` Try: " . $try[0]);
    }

    function asStatement ($p) {
        $this->error($p);
    }

    function asLeft ($p) {
        $this->error($p);
    }
}
