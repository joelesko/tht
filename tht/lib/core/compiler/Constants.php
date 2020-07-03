<?php

namespace o;

const TOKEN_TYPE  = 0;
const TOKEN_POS   = 1;
const TOKEN_SPACE = 2;
const TOKEN_VALUE = 3;

define('TOKEN_SEP', '┃');  // unicode vertical line

// Very short vars to minimize memory overhead
abstract class TokenType {
    const NUMBER   = 'N';    // 123
    const STRING   = 'S';    // 'hello'
    const LSTRING  = 'LS';   // sql'hello'
    const TSTRING  = 'TS';   // (template)
    const RSTRING  = 'RS';   // r'\w+'
    const GLYPH    = 'G';    // +=
    const WORD     = 'W';    // myVar
    const END      = 'END';  // (end of stream)
    const VAR      = 'V';    // $fooBar
    const NEWLINE  = 'NL';   // \n
}

abstract class Glyph {
    const MULTI_GLYPH_PREFIX = '=<>&|+-*:^~!/%#.?$';
    const MULTI_GLYPH_SUFFIX = '=<>&|+-*:^~.?';
    const COMMENT = '/';
    const LINE_COMMENT = '//';
    const BLOCK_COMMENT_START = '/*';
    const BLOCK_COMMENT_END = '*/';
    const TEMPLATE_EXPR_START = '{{';
    const TEMPLATE_EXPR_END = '}}';
    const TEMPLATE_CODE_LINE = '===';
    const STRING_PREFIXES = 'r';
    const QUOTED_LIST_PREFIX = 'q';
    const REGEX_PREFIX = 'r';
    const LAMBDA_PREFIX = 'x';
    const QUOTE = "'";
    const QUOTE_FENCE = "'''";
}

abstract class SymbolType {
    const SEPARATOR     =  'SEPARATOR';  // ;
    const END           =  'END';        // (end of stream)
    const AST_LIST      =  'AST_LIST';   // (list of symbols)

    const NUMBER        =  'NUMBER';     // 123
    const STRING        =  'STRING';     // 'hello'
    const LSTRING       =  'LSTRING';    // sql'hello'
    const TSTRING       =  'TSTRING';    // tem { ... }
    const RSTRING       =  'RSTRING';    // r'...'

    const KEYWORD       =  'KEYWORD';
    const CONSTANT      =  'CONSTANT';   // this
    const FLAG          =  'FLAG';       // true

    const PREFIX        =  'PREFIX';     // ! $a
    const INFIX         =  'INFIX';      // $a + $b
    const BITWISE       =  'BITWISE';    // $a +| $b +~ $c
    const BITSHIFT      =  'BITSHIFT';   // $a +> $b
    const VALGATE       =  'VALGATE';    // $a |: $b &: $c
    const TERNARY       =  'TERNARY';    // $c ? $b1 : $b2
    const ASSIGN        =  'ASSIGN';     // $foo = 123
    const OPERATOR      =  'OPERATOR';   // if (...) {}
    const COMMAND       =  'COMMAND';    // break;

    const TEMPLATE_EXPR =  'TEMPLATE_EXPR';  // (( $foobar ))
    const CALL          =  'CALL';           // foo()
    const TRY_CATCH     =  'TRY_CATCH';      // try {} catch {}
//    const NEW_VAR       =  'NEW_VAR';        // let foo = 1
    const CLASS_PLUGIN  =  'CLASS_PLUGIN';   // plugin SomeClass, OtherClass
    const CLASS_FIELDS  =  'CLASS_FIELDS';   // fields { foo: 1 }
    const NEW_FUN       =  'NEW_FUN';        // function foo () {}
    const NEW_CLASS     =  'NEW_CLASS';      // class Foo {}
    const NEW_OBJECT    =  'NEW_OBJECT';     // new Foo ()
    const BARE_FUN      =  'BARE_FUN';       // print
    const NEW_TEMPLATE  =  'NEW_TEMPLATE';   // template fooHtml() {}
    const FUN_ARG       =  'FUN_ARG';        // function foo ($arg)
    const FUN_ARG_SPLAT =  'FUN_ARG_SPLAT';  // function foo (...$arg)
    const FUN_ARG_TYPE  =  'FUN_ARG_TYPE';   // function foo ($arg:$s)
    const USER_FUN      =  'USER_FUN';       // myFunction
    const USER_VAR      =  'USER_VAR';       // $myVar
    const BARE_WORD     =  'BARE_WORD';      // myVar (illegal)
    const PRE_KEYWORD   =  'PRE_KEYWORD';    // private $myVar = 123;

    const MATCH          =  'MATCH';          // match $foo { ... }
    const MATCH_PATTERN  =  'MATCH_PATTERN';  // range(0, 10) { ... }

    const MEMBER        =  'MEMBER';     // $foo[...]
    const MEMBER_VAR    =  'MEMBER_VAR'; // $foo.bar
    const MAP_KEY       =  'MAP_KEY';    // _foo_: bar
    const PAIR          =  'PAIR';       // foo: 'bar'

    const PACKAGE       = 'PACKAGE';           // MyClass
    const FULL_PACKAGE  = 'FULL_PACKAGE';           // namespace\MyClass
  //  const PACKAGE_QUALIFIER = 'PACKAGE_QUALIFIER'; // abstract final
  //  const NEW_OBJECT_VAR    = 'NEW_OBJECT_VAR';    // private $myVar = 123;
}

abstract class AstList {
    const FLAT  = 'FLAT';
    const ARGS  = 'ARGS';
    const BLOCK = 'BLOCK';
    const XLIST = 'LIST';
    const MAP   = 'MAP';
    const MATCH = 'MATCH';
}


class CompilerConstants {

    static public $MAX_LINE_LENGTH = 100;
    static public $MAX_WORD_LENGTH = 40;
    static public $MAX_FUN_ARGS = 4;

    static $LITERAL_TYPES = [
        TokenType::NUMBER  => SymbolType::NUMBER,
        TokenType::STRING  => SymbolType::STRING,
        TokenType::RSTRING => SymbolType::RSTRING,
        TokenType::LSTRING => SymbolType::LSTRING,
    ];

    static public $TEMPLATE_TOKEN = 'tm';

    static public $SYMBOL_CLASS = [

        // meta
        '(end)' => 'S_End',

        // separators / terminators

        '(nl)' => 'S_Separator',
   //     ';'  => 'S_Separator',
        ','  => 'S_Separator',
        ':'  => 'S_Separator',
        ')'  => 'S_Separator',
        ']'  => 'S_Separator',
        '}'  => 'S_Separator',
        '}}' => 'S_Separator',
        'as' => 'S_Separator',

        // constants
        'true'  => 'S_Flag',
        'false' => 'S_Flag',

        '@'     => 'S_Constant',
        '@@'    => 'S_Constant',

        // prefix
        '!'  => 'S_Prefix',
        '...'  => 'S_Prefix',

        // infix
        '~'  => 'S_Concat',
        '+'  => 'S_Add',
        '-'  => 'S_Add',
        '*'  => 'S_Multiply',
        '/'  => 'S_Multiply',
        '%'  => 'S_Multiply',
        '**' => 'S_Power',
        '==' => 'S_Compare',
        '!=' => 'S_Compare',
        '<'  => 'S_Compare',
        '<=' => 'S_Compare',
        '>'  => 'S_Compare',
        '>=' => 'S_Compare',
        '<=>' => 'S_Compare',
        '?'  => 'S_Ternary',
        '||' => 'S_Logic',
        '&&' => 'S_Logic',
        '||:' => 'S_ValGate',
        '&&:' => 'S_ValGate',

        '+&' => 'S_Bitwise',
        '+|' => 'S_Bitwise',
        '+^' => 'S_Bitwise',
        '+>' => 'S_BitShift',
        '+<' => 'S_BitShift',
        '+~' => 'S_Prefix',

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
        'fn'        => 'S_Function',
        'tm'        => 'S_Template',
     //   'new'       => 'S_New',
        'if'        => 'S_If',
        'foreach'   => 'S_ForEach',
        'loop'      => 'S_Loop',
        'try'       => 'S_TryCatch',
        'break'     => 'S_Command',
        'continue'  => 'S_Command',
        'return'    => 'S_Return',
        'match'     => 'S_Match',
        'lambda'    => 'S_Lambda',

        'switch'    => 'S_Unsupported',
        'require'   => 'S_Unsupported',
        'include'   => 'S_Unsupported',
        'while'     => 'S_Unsupported',
        'for'       => 'S_Unsupported',
        'new'       => 'S_Unsupported',

        // oop
        'class'     => 'S_Class',
        'interface' => 'S_Class',
        'trait'     => 'S_Class',

        'outer'     => 'S_ClassPlugin',
        'inner'     => 'S_ClassPlugin',
        'fields'    => 'S_ClassFields',

        'public'    => 'S_PreKeyword',

        'abstract'  => 'S_Unsupported',
        'final'     => 'S_Unsupported',
        'private'   => 'S_Unsupported',
        'protected' => 'S_Unsupported',
        'static'    => 'S_Unsupported',
    ];

    static public $KEYWORDS = [
        'if', 'else', 'try', 'catch', 'finally', 'keep', 'in',
        'default', 'fn', 'tm', 'return', 'match', 'foreach', 'public', 'plugin', 'outerplugin'
    ];

    static public $SKIP_NEWLINE_BEFORE = [
        'else', 'catch', 'finally',
        '+', '-', '/', '*', '~', '%', '**', '||', '&&',
        '.', '{',
        '+|', '+&', '+^', '+<', '+>'
    ];

    static public $SUGGEST_TOKEN = [
        '===' => '==',
        '!==' => '!=',
        '=<'  => '<=',
        '=>'  => ">= (comparison) or colon ':' (map key)",
        '<>'  => '!=',
        '>>'  => '+> (bit shift)',
        '<<'  => '+< (bit shift) or #=',
        '++'  => '+= 1',
        '--'  => '-= 1',
        '->'  => 'dot (.)',
        '$'   => 'remove $ from name',
        '::'  => 'dot (.)',
        '^'   => '+^ (bitwise xor)',
        '&'   => '&& or +& (bitwise and)',
        '|'   => '|| or +| (bitwise or)',
        '#'   => '// line comment',
        '?:'  => '||: (default or)',
        '??'  => '||: (default or)',
        '??=' => '||= (or assign)',
        '"'   => 'single quote (\')',
        '`'   => 'multi-line quote fence (\'\'\')',
    ];

    static $ANON = '(ANON)';

    static $TEMPLATE_TYPES = 'html|css|text|js|lite|jcon';

    static $CLOSING_BRACE = [
        '{' => '}',
        '[' => ']',
        '(' => ')'
    ];

    static $TYPE_DECLARATIONS = [
        's', 'b', 'i', 'f', 'l', 'm', 'fn', 'o', 'any'
    ];


    static $OK_NEXT_ADJ_TOKENS = [
        'in',
        'as',
        'if',
        'abstract',
        'class',
        'trait',
        'interface',
        'final',
        'public',
        'private',
        'protected',
        'static',
        'extends',
    ];

    static $OK_PREV_ADJ_TOKENS = [
        'match',
        'catch',
        'fn',
        'tm',
        'in',
        'as',
        'return' ,
     //   'new',
        'foreach',
        'if',
        'class',
        'trait',
        'interface',
        'final',
        'public',
        'private',
        'protected',
        'static',
        'extends',
        'abstract',
        'outer',
        'inner',
    ];

}

