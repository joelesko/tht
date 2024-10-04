<?php

namespace o;

class S_Unsupported extends Symbol {

    var $allowAsMapKey = true;

    static public function getSuggestion($token) {

        return [

            'let'     => 'Remove keyword: `let`',

            'class'   => '(TBD)',
            'switch'  => '`if/else` or Map',
            'while'   => ['`loop { ... }`',  'Loops', '/language-tour/loops#infinite-loops'],
            'for'     => '`foreach $list as $item {`',

            'require'  => ['`load`', 'Modules', '/language-tour/custom-modules'],
            'include'  => ['`load`', 'Modules', '/language-tour/custom-modules'],
            'static' => ['Module-level variable or function',  'Modules', '/language-tour/custom-modules'],

            'final'     => ['Remove `final` keyword.',       'Classes & Objects', '/language-tour/oop/classes-and-objects'],
            'protected' => ['Remove `protected` keyword.',   'Classes & Objects', '/language-tour/oop/classes-and-objects'],
            'abstract'  => ['Remove `abstract` keyword.',    'Classes & Objects', '/language-tour/oop/classes-and-objects'],
            'new'       => ['Remove `new` keyword.',         'Classes & Objects', '/language-tour/oop/classes-and-objects'],

            'private/class'  => ['Remove keyword. Methods are private is default.', 'Classes & Objects', '/language-tour/classes-and-objects'],
            'private/module' => ['Add `public` to other functions.', 'Modules', '/language-tour/custom-modules'],

        ][$token];
    }

    function error($p) {

        $tokenVal = $this->token[TOKEN_VALUE];
        if ($tokenVal == 'private') {
            $tokenVal = $this->parser->inClass ? 'private/class' : 'private/module';
        }

        $try = self::getSuggestion($tokenVal);
        if (is_array($try)) {
            ErrorHandler::setHelpLink($try[2], $try[1]);
            $try =  $try[0] ? "Try: " . $try[0] : '';
        }
        else {
            $try = 'Try: ' . $try;
        }

        $p->error("Unsupported keyword: `" . $tokenVal . "`  $try");
    }

    function asStatement($p) {
        $this->error($p);
    }

    function asLeft($p) {
        $this->error($p);
    }
}
