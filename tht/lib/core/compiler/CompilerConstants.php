<?php

namespace o;

const TOKEN_TYPE  = 0;
const TOKEN_POS   = 1;
const TOKEN_SPACE = 2;
const TOKEN_VALUE = 3;

const SPACE_BEFORE_BIT   = 1;
const SPACE_AFTER_BIT    = 2;
const NEWLINE_BEFORE_BIT = 4;
const NEWLINE_AFTER_BIT  = 8;

const TOKEN_SEP = '┃';  // unicode vertical line


// Very short values to minimize memory overhead
abstract class TokenType {

    const NUMBER      = 'N';    // 123
    const STRING      = 'S';    // 'hello'
    const T_STRING    = 'TS';   // sql'hello'
    const TEM_STRING  = 'TMS';  // (template)
    const RX_STRING   = 'RS';   // r'\w+'
    const FLAG        = 'F';    // -myFlag
    const GLYPH       = 'G';    // +=
    const WORD        = 'W';    // myVar
    const END         = 'END';  // (end of stream)
    const VAR         = 'V';    // $fooBar
    const NEWLINE     = 'NL';   // \n
}

abstract class Glyph {

    const MULTI_GLYPH_PREFIX  = '=<>&|+-*:^~!/%#.?$';
    const MULTI_GLYPH_SUFFIX  = '=<>&|+-*:^~.?';
    const COMMENT             = '/';
    const LINE_COMMENT        = '//';
    const BLOCK_COMMENT_START = '/*';
    const BLOCK_COMMENT_END   = '*/';
    const TEMPLATE_EXPR_START = '{{';
    const TEMPLATE_EXPR_END   = '}}';
    const TEMPLATE_CODE_LINE  = '---';
    const STRING_PREFIXES     = 'r';
    const QUOTED_LIST_PREFIX  = 'q';
    const REGEX_PREFIX        = 'rx';
    const LAMBDA_PREFIX       = 'x';
    const QUOTE               = "'";
    const QUOTE_FENCE         = "'''";
}

// Note: Truncating these values only trims about 1% of transpile-time memory,
//  so leaving them as-is.
abstract class SymbolType {

    const SEPARATOR     =  'SEPARATOR';  // ,
    const END           =  'END';        // (end of stream)
    const AST_LIST      =  'AST_LIST';   // (list of symbols)

    const NUMBER        =  'NUMBER';     // 123
    const STRING        =  'STRING';     // 'hello'
    const T_STRING      =  'T_STRING';   // sql'hello'
    const TEM_STRING    =  'TEM_STRING'; // tem { ... }
    const RX_STRING     =  'RX_STRING';  // rx'...'
    const FLAG          =  'FLAG';       // -myFlag

    const KEYWORD       =  'KEYWORD';
    const CONSTANT      =  'CONSTANT';   // this
    const BOOLEAN       =  'BOOLEAN';    // true
    const NULL          =  'NULL';       // null

    const PREFIX        =  'PREFIX';     // !$a
    const INFIX         =  'INFIX';      // $a + $b
    const BITWISE       =  'BITWISE';    // $a +| $b +~ $c
    const BITSHIFT      =  'BITSHIFT';   // $a +> $b
    const VALGATE       =  'VALGATE';    // $a |: $b &: $c
    const LISTFILTER    =  'LISTFILTER'; // $list #: $a > 100
    const TERNARY       =  'TERNARY';    // $c ? $b1 : $b2
    const ASSIGN        =  'ASSIGN';     // $foo = 123
    const OPERATOR      =  'OPERATOR';   // if (...) {}
    const COMMAND       =  'COMMAND';    // break;

    const TEMPLATE_EXPR =  'TEMPLATE_EXPR';  // {{ $foobar }}
    const CALL          =  'CALL';           // foo()
    const TRY_CATCH     =  'TRY_CATCH';      // try {} catch {}
    const CLASS_PLUGIN  =  'CLASS_PLUGIN';   // plugin SomeClass, OtherClass
    const CLASS_FIELDS  =  'CLASS_FIELDS';   // fields { foo: 1 }
    const NEW_FUN       =  'NEW_FUN';        // function foo() {}
    const NEW_CLASS     =  'NEW_CLASS';      // class Foo {}
    const NEW_OBJECT    =  'NEW_OBJECT';     // new Foo ()
    const BARE_FUN      =  'BARE_FUN';       // print
    const NEW_TEMPLATE  =  'NEW_TEMPLATE';   // tm fooHtml() {}
    const FUN_ARG       =  'FUN_ARG';        // fn foo ($arg)
    const FUN_ARG_SPLAT =  'FUN_ARG_SPLAT';  // fn foo (...$arg)
    const FUN_ARG_TYPE  =  'FUN_ARG_TYPE';   // fn foo ($arg:$s)
    const USER_FUN      =  'USER_FUN';       // myFunction
    const USER_VAR      =  'USER_VAR';       // $myVar
    const BARE_WORD     =  'BARE_WORD';      // myVar (illegal)
    const PRE_KEYWORD   =  'PRE_KEYWORD';    // private $myVar = 123;

    const MATCH         =  'MATCH';          // match $foo { ... }
    const MATCH_PAIR    =  'MATCH_PAIR';     // pattern: value

    const MEMBER        =  'MEMBER';         // $foo[...]
    const MEMBER_VAR    =  'MEMBER_VAR';     // $foo.bar
    const MAP_KEY       =  'MAP_KEY';        // foo:
    const MAP_PAIR      =  'MAP_PAIR';       // foo: 'bar'

    const PACKAGE       = 'PACKAGE';           // MyClass
    const FULL_PACKAGE  = 'FULL_PACKAGE';      // namespace\MyClass
    // const PACKAGE_QUALIFIER = 'PACKAGE_QUALIFIER'; // abstract final
    // const NEW_OBJECT_VAR    = 'NEW_OBJECT_VAR';    // private $myVar = 123;
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

    public const MAX_LINE_LENGTH = 120;
    public const MAX_WORD_LENGTH = 40;
    public const MAX_FUN_ARGS = 4;

    public const INDENT_SPACES = 4;

    public const CLOSING_SEPARATORS = ')]}';
    public const TEMPLATE_TOKEN = 'tem';
    public const ANON = '(ANON)';

    public const TEMPLATE_TYPES_RX = 'html|css|text|js|lm|jcon';

    public const LINE_CONTINUATION_GLYPHS = '+-*/%.';
    // Allow continuation on next line if next glyph is one of these
    public const SKIP_NEWLINE_BEFORE = [
        'else', 'catch', 'finally',
        '+', '-', '/', '*', '~', '%', '**',
        '||', '&&', '||:', '&&:',
        '.',
        '?.', '??:',
        '+|', '+&', '+^', '+<', '+>'
    ];

    public const TYPE_DECLARATIONS = [
        's', 'b', 'n', 'f', 'l', 'm', 'fun', 'o', 'any'
    ];

    public const TOKEN_TYPE_NAMES = [
        'V'  => 'Variable',
        'N'  => 'Number',
        'W'  => 'Word',
        'S'  => 'String',
        'TS' => 'TypeString',
        'RS' => 'Regex',
    ];

    public const LITERAL_TYPES = [
        TokenType::NUMBER     => 'S_Number',
        TokenType::STRING     => 'S_String',
        TokenType::RX_STRING  => 'S_RxString',
        TokenType::T_STRING   => 'S_TString',
        TokenType::TEM_STRING => 'S_TemplateString',
    ];

    public const SYMBOL_CLASS = [

        // meta
        '(end)' => 'S_End',

        // separators / terminators

        '(nl)' => 'S_Separator',
        ','  => 'S_Separator',
        ':'  => 'S_Separator',
        ')'  => 'S_Separator',
        ']'  => 'S_Separator',
        '}'  => 'S_Separator',
        '}}' => 'S_Separator',
        'as' => 'S_Separator',

        // constants
        'true'  => 'S_Boolean',
        'false' => 'S_Boolean',

        'null'     => 'S_Null',
        'pending'  => 'S_Null',

        '@'     => 'S_Constant',
        '@@'    => 'S_Constant',

        // prefix
        '!'      => 'S_Prefix',
        '...'    => 'S_Prefix',

        // infix
        '~'   => 'S_Concat',
        '+'   => 'S_Add',
        '-'   => 'S_Add',
        '*'   => 'S_Multiply',
        '/'   => 'S_Multiply',
        '%'   => 'S_Multiply',
        '**'  => 'S_Power',
        '=='  => 'S_Compare',
        '!='  => 'S_Compare',
        '<'   => 'S_Compare',
        '<='  => 'S_Compare',
        '>'   => 'S_Compare',
        '>='  => 'S_Compare',
        '<=>' => 'S_CompareSpaceship',
        '?'   => 'S_Ternary',
        '||'  => 'S_Logic',
        '&&'  => 'S_Logic',
        '||:' => 'S_ValGate',
        '&&:' => 'S_ValGate',
        '??:' => 'S_ValGate',
      //  '#:'  => 'S_ListFilter',

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
        '??=' => 'S_Assign',
        '#='  => 'S_Assign',
        ':='  => 'S_Assign',
        '??+='=> 'S_Assign',

        // delimiters / members
        '.'   => 'S_Dot',
        '?.'  => 'S_Dot',
        '['   => 'S_OpenSquare',
        '('   => 'S_OpenParen',
        '{'   => 'S_OpenCurly',
        '{{'  => 'S_TemplateExpr',

        // keywords
        'fun'       => 'S_Function',
        'tem'       => 'S_Template',
        'if'        => 'S_If',
        'foreach'   => 'S_ForEach',
        'loop'      => 'S_Loop',
        'try'       => 'S_TryCatch',
        'break'     => 'S_Command',
        'continue'  => 'S_Command',
        '>>'        => 'S_ShortPrint',
        'return'    => 'S_Return',
        'match'     => 'S_Match',
        'lambda'    => 'S_Lambda',

        // oop
        'class'     => 'S_Class',
        'interface' => 'S_Class',
        'trait'     => 'S_Class',

      //  'embed'     => 'S_ClassPlugin',
        'fields'    => 'S_ClassFields',

        'public'    => 'S_PreKeyword',

        // TOOD: would rather not handle these separately, but for now, it provides better error messages
        'class'       => 'S_Unsupported',

        'abstract'  => 'S_Unsupported',
        'final'     => 'S_Unsupported',
        'private'   => 'S_Unsupported',
        'protected' => 'S_Unsupported',
        'static'    => 'S_Unsupported',
        'switch'    => 'S_Unsupported',
        'require'   => 'S_Unsupported',
        'include'   => 'S_Unsupported',
        'while'     => 'S_Unsupported',
        'for'       => 'S_Unsupported',
        'new'       => 'S_Unsupported',
        'let'       => 'S_Unsupported',
    ];

    public const KEYWORDS = [
        'if',
        'else',
        'try',
        'catch',
        'finally',
        'in',
        'loop',
        'as',
        'default',
        'fun',
        'tem',
        'return',
        'match',
        'foreach',
        'public',
        'private',
    ];

    public const SUGGEST_TOKEN = [
        '===' => '`==`',
        '!==' => '`!=`',
        '=<'  => '`<=`',
        '=>'  => "`:` (map key) or `>=` (greater than/equal)",
        '<>'  => '`!=`',
        // '>>'  => '+> (bit-shift right)', // caught in ShortPrint
        '<<'  => '`+<` (bit-shift left)',
        '++'  => '`+= 1`',
        '--'  => '`-= 1`',
        '->'  => '`.` (dot)',
        '::'  => '`.` (dot)',
        '^'   => '`+^` (bitwise xor)',
        '&'   => '`&&` or `+&` (bitwise and)',
        '|'   => '`||` or `+|` (bitwise or)',
        '#'   => '`//` (line comment)',
        '?:'  => '`||:` (default or)',
        '??'  => '`||:` (default or)',
        '"'   => '`\'` (single quote)',
        '`'   => '`\'\'\'` (multi-line quote fence)',
        '.='  => '`~=` (string append)',
        '$'   => 'remove `$` from name',
    ];

    //  TODO: allow unsupported keywords as keys & methods
    public const UNSUPPORTED_KEYWORD = [

        'elseif' => 'else if',
        'elif'   => 'else if',
        'elsif'  => 'else if',

        'function' => 'fun',
        'fn'       => 'fun',
        'func'     => 'fun',

        'template' => 'tem',
        'temp'     => 'tem',
        'tm'       => 'tem',

    //    'switch'   => ['`match { ... }`', 'match', '/language-tour/intermediate-features#match'],
        'switch'   => 'if/else',
        'while'    => ['`loop { ... }`',  'Loops', '/language-tour/loops'],

        'require'  => ['`load`', 'Modules', '/language-tour/modules'],
        'new'      => ['Remove `new` keyword.  Ex: `myClass()`', 'Classes & Objects', '/language-tour/oop/classes-and-objects'],

        'include'  => ['`load`', 'Modules', '/language-tour/modules'],
        'import'   => ['`load`', 'Modules', '/language-tour/modules'],

        'final'     => ['',                     'Classes & Objects', '/language-tour/oop/classes-and-objects'],
        'protected' => ['',                     'Classes & Objects', '/language-tour/oop/classes-and-objects'],
        'abstract'  => ['',                     'Classes & Objects', '/language-tour/oop/classes-and-objects'],

        'static' => ['Module-level function or variable. Ex: `@@.myVar`',  'Modules', '/language-tour/modules'],
        'private' => 'Remove keyword. Methods are private is default.',
        'protected' => 'Use public for now (TODO).',
    ];

    public const CHECK_ADJ_TOKEN_TYPES = [
        'V', 'N', 'W', 'S', 'TS', 'RS'
    ];

    public const OK_ADJ_WORDS = [

        // Words that can precede another word.
        'prev' => [

            'fun',
            'tem',
            'in',
            'as',
            'from',
            'return',
            'foreach',
            'if',
            'match',
            'catch',
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

            // unsupported pass-through (caught in S_Unsupported)
            // 'elsif',
            // 'elif',
            // 'elseif',
            // 'switch',
            // 'while',
        ],

        // Words that can follow another word.
        'next' => [
            'in',
            'as',
            'from',
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
        ]
    ];
}

