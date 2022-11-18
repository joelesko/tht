<?php

namespace o;

class S_Unsupported extends Symbol {

    static public function getCorrect($token) {

        return [

            'switch'   => ['`match { ... }`', 'match', '/language-tour/intermediate-features#match'],
            'for'      => ['`foreach $list as $item { ... }`', 'Loops', '/language-tour/loops'],
            'while'    => ['`loop { ... }`',  'Loops', '/language-tour/loops'],
            'require'  => ['`load`',        'Modules', '/language-tour/modules'],
            'include'  => ['`load`',        'Modules', '/language-tour/modules'],

            'final'     => ['',                     'Classes & Objects', '/language-tour/oop/classes-and-objects'],
            'protected' => ['',                     'Classes & Objects', '/language-tour/oop/classes-and-objects'],
            'abstract'  => ['',                     'Classes & Objects', '/language-tour/oop/classes-and-objects'],
            'new'       => ['Remove `new` keyword', 'Classes & Objects', '/language-tour/oop/classes-and-objects'],


            'static' => ['Module-level variable or function',  'Modules', '/language-tour/modules'],

            'private/class'  => ['Remove keyword. Methods are private is default.', 'Classes & Objects', '/language-tour/classes-and-objects'],
            'private/module' => ['Make other functions `public`.', 'Modules', '/language-tour/modules'],

        ][$token];
    }

    function error($p) {

        $token = $this->token[TOKEN_VALUE];
        if ($token == 'private') {
            $token = $this->parser->inClass ? 'private/class' : 'private/module';
        }

        $try = self::getCorrect($token);
        ErrorHandler::setHelpLink($try[2], $try[1]);
        $try =  $try[0] ? "Try: " . $try[0] : '';
        $p->error("Unknown keyword: `" . $this->token[TOKEN_VALUE] . "` $try");
    }

    function asStatement ($p) {
        $this->error($p);
    }

    function asLeft ($p) {
        $this->error($p);
    }
}
