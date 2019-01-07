<?php

namespace o;

const MAX_LINE_LENGTH = 100;

const TOKEN_TYPE  = 0;
const TOKEN_POS   = 1;
const TOKEN_SPACE = 2;
const TOKEN_VALUE = 3;

define('TOKEN_SEP', '┃');  // unicode vertical line

abstract class TokenType {
    const NUMBER   = 'NUMBER';    // 123
    const STRING   = 'STRING';    // 'hello'
    const LSTRING  = 'LSTRING';   // L'hello'
    const TSTRING  = 'TSTRING';   // (template)
    const RSTRING  = 'RSTRING';   // R'\w+'
    const GLYPH    = 'GLYPH';     // +=
    const WORD     = 'WORD';      // myVar
    const NEWLINE  = 'NEWLINE';   // \n
    const SPACE    = 'SPACE';     // ' '
    const END      = 'END';       // (end of stream)
}

abstract class Glyph {
    const MULTI_GLYPH_PREFIX = '=<>&|+-*:!/%~#@';
    const MULTI_GLYPH_SUFFIX = '=<>&|+-*:@^~';
    const COMMENT = '/';
    const LINE_COMMENT = '//';
    const BLOCK_COMMENT_START = '/*';
    const BLOCK_COMMENT_END = '*/';
    const TEMPLATE_EXPR_START = '{{';
    const TEMPLATE_EXPR_END = '}}';
    const TEMPLATE_CODE_LINE = '::';
    const STRING_MODS = 'RL';
    const LIST_MOD = 'Q';
    const REGEX_MOD = 'R';
    const LOCK_MOD = 'L';
    const QUOTE = "'";
    const BACKTICK = '`';
}

abstract class SymbolType {
    const SEPARATOR     =  'SEPARATOR';  // ;
    const SPACE         =  'SPACE';      // ' '
    const NEWLINE       =  'NEWLINE';    // \n
    const END           =  'END';        // (end of stream)
    const SEQUENCE      =  'SEQUENCE';   // (list of symbols)

    const STRING        =  'STRING';     // 'hello'
    const LSTRING       =  'LSTRING';    // L'hello'
    const TSTRING       =  'TSTRING';    // tem { ... }
    const RSTRING       =  'RSTRING';    // R'...'
    const KEYWORD       =  'KEYWORD';
    const NUMBER        =  'NUMBER';     // 123
    const CONSTANT      =  'CONSTANT';   // this
    const FLAG          =  'FLAG';       // true

    const PREFIX        =  'PREFIX';     // ! a
    const INFIX         =  'INFIX';      // a + b
    const BITWISE       =  'BITWISE';    // a +| b +~ c
    const BITSHIFT      =  'BITSHIFT';   // a +> b
    const VALGATE       =  'VALGATE';    // a |: b &: c
    const TERNARY       =  'TERNARY';    // c ? b1 : b2
    const ASSIGN        =  'ASSIGN';     // foo = 123
    const OPERATOR      =  'OPERATOR';   // if (...) {}
    const COMMAND       =  'COMMAND';    // break;

    const TEMPLATE_EXPR =  'TEMPLATE_EXPR';  // (( fobar ))
    const CALL          =  'CALL';           // foo()
    const TRY_CATCH     =  'TRY_CATCH';      // try {} catch {}
    const NEW_VAR       =  'NEW_VAR';        // let foo = 1
    const NEW_FUN       =  'NEW_FUN';        // function foo () {}
    const NEW_CLASS     =  'NEW_CLASS';      // class Foo {}
    const NEW_OBJECT    =  'NEW_OBJECT';     // new Foo ()
    const BARE_FUN      =  'BARE_FUN';       // print
    const NEW_TEMPLATE  =  'NEW_TEMPLATE';   // template fooHtml() {}
    const FUN_ARG       =  'FUN_ARG';        // function foo (arg) {}
    const USER_FUN      =  'USER_FUN';       // myFunction
    const USER_VAR      =  'USER_VAR';       // myVar

    const MEMBER        =  'MEMBER';     // foo[...]
    const MEMBER_VAR    =  'MEMBER_VAR'; // foo.bar
    const MAP_KEY       =  'MAP_KEY';    // _foo_: bar
    const PAIR          =  'PAIR';       // foo: 'bar'

    const PACKAGE           = 'PACKAGE';           // MyClass
    const PACKAGE_QUALIFIER = 'PACKAGE_QUALIFIER'; // abstract final
    const NEW_OBJECT_VAR    = 'NEW_OBJECT_VAR';    // private myVar = 123;
}

abstract class SequenceType {
    const FLAT  = 'FLAT';
    const ARGS  = 'ARGS';
    const BLOCK = 'BLOCK';
    const XLIST = 'LIST';
    const MAP   = 'MAP';
}


class ParserData {

    static public $MAX_WORD_LENGTH = 40;

    static $LITERAL_TYPES = [
        TokenType::NUMBER  => 1,
        TokenType::STRING  => 1,
        TokenType::RSTRING => 1,
        TokenType::LSTRING => 1,
    ];

    static public $SYMBOL_CLASS = [

        // meta
        '(end)' => 'S_End',

        // separators / terminators
        ';'  => 'S_Sep',
        ':'  => 'S_Sep',
        ')'  => 'S_Sep',
        ']'  => 'S_Sep',
        '}'  => 'S_Sep',
        ','  => 'S_Sep',
        '}}' => 'S_Sep',

        // constants
        'true'  => 'S_Flag',
        'false' => 'S_Flag',
        'super' => 'S_Constant',
        'self'  => 'S_Constant',
        'this'  => 'S_Constant',
        '@'     => 'S_Constant',
        '@@'    => 'S_Constant',

        // prefix
        '!'  => 'S_Prefix',
        '+~' => 'S_Prefix',

        // infix
        '~'  => 'S_Concat',   // + .
        '+'  => 'S_Add',
        '-'  => 'S_Add',
        '*'  => 'S_Multiply',
        '**' => 'S_Multiply',
        '/'  => 'S_Multiply',
        '%'  => 'S_Multiply',
        '==' => 'S_Compare',
        '!=' => 'S_Compare',
        '<'  => 'S_Compare',
        '<=' => 'S_Compare',
        '>'  => 'S_Compare',
        '>=' => 'S_Compare',
        '?'  => 'S_Ternary',
        '||' => 'S_Logic',
        '&&' => 'S_Logic',
        '||:' => 'S_ValGate',
        '&&:' => 'S_ValGate',
        '+&'  => 'S_Bitwise',
        '+|'  => 'S_Bitwise',
        '+^'  => 'S_Bitwise',
        '+>' => 'S_BitShift',
        '+<' => 'S_BitShift',

        // assignment
        '='   => 'S_Assign',
        '+='  => 'S_Assign',
        '-='  => 'S_Assign',
        '*='  => 'S_Assign',
        '/='  => 'S_Assign',
        '%='  => 'S_Assign',
        '**=' => 'S_Assign',
        '~='  => 'S_Assign',
        '||=' => 'S_Assign',
        '&&=' => 'S_Assign',
        '#='  => 'S_Assign',

        // delimiters / members
        '.'   => 'S_Dot',
        '['   => 'S_OpenBracket',
        '('   => 'S_OpenParen',
        '{'   => 'S_OpenBrace',
        '{{'  => 'S_TemplateExpr',

        // keywords
        'let'       => 'S_NewVar',
        'function'  => 'S_NewFunction',
        'F'         => 'S_NewFunction',
        'template'  => 'S_NewTemplate',
        'T'         => 'S_NewTemplate',
        'new'       => 'S_New',
        'if'        => 'S_If',
        'for'       => 'S_For',
        'try'       => 'S_TryCatch',
        'break'     => 'S_Command',
        'continue'  => 'S_Command',
        'return'    => 'S_Return',
        'R'         => 'S_Return',

        // oop
        'class'     => 'S_Class',
        'interface' => 'S_Class',
        'trait'     => 'S_Class',
        'abstract'  => 'S_Class',
        'final'     => 'S_Class',
        'public'    => 'S_Class',
        'private'   => 'S_Class',
        'protected' => 'S_Class',
        'static'    => 'S_Class',

    ];

    static public $RESERVED_NAMES = [
        'if', 'else', 'try', 'catch', 'finally', 'keep', 'in', 'extends'
    ];

    static public $ALT_TOKENS = [

        // glyphs
        '===' => '==',
        '!==' => '!=',
        '=<'  => '<=',
        '=>'  => ">= (comparison) or colon ':' (map)",
        '<>'  => '!=',
        '>>'  => '+> (bit shift)',
        '<<'  => '+< (bit shift) or #=',
        '++'  => '+= 1',
        '--'  => '-= 1',
        '**'  => 'Math.exp()',
        '<=>' => 'myVar.compare(otherVar)',
        '->'  => 'dot (.)',
        '$'   => 'remove $ from name',
        '::'  => 'dot (.)',
        '^'   => '+^ (bitwise xor)',
        '&'   => '&& or +& (bitwise and)',
        '|'   => '|| or +| (bitwise or)',

     //   '[]=' => '#=',
        '"'   => 'single quote (\')',

        // other langs
        'elseif'   => 'else if',
        'elsif'    => 'else if',
        'elif'     => 'else if',
        'require'  => 'import',
        'include'  => 'import',
        'var'      => 'let',
        'const'    => 'let',

        // removed
        'switch'   => 'if/else, or a Map',
        'while'    => 'for { ... }',

        // renamed
        'foreach'  => 'for (list as foo) { ... }'
    ];

    static $ANON = '(ANON)';

    static $TEMPLATE_TYPES = 'html|css|text|js|lite|jcon';

    static $CLOSING_BRACE = [
        '{' => '}',
        '[' => ']',
        '(' => ')'
    ];

    static $QUALIFIER_KEYWORDS = [
        'abstract',
        'final',
        'public',
        'private',
        'protected',
        'static',
        'class',
        'trait',
        'interface',
    ];
}

