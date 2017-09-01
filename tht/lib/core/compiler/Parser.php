<?php

namespace o;

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
    const VALGATE       =  'VALGATE';    // a | b & c
    const TERNARY       =  'TERNARY';    // c ? b1 : b2
    const ASSIGN        =  'ASSIGN';     // foo = 123
    const OPERATOR      =  'OPERATOR';   // if (...) {}
    const COMMAND       =  'COMMAND';    // break;

    const TEMPLATE_EXPR =  'TEMPLATE_EXPR';   // (( fobar ))
    const CALL          =  'CALL';       // foo()
    const TRY_CATCH     =  'TRY_CATCH';  // try {} catch {}
    const NEW_VAR       =  'NEW_VAR';    // let foo = 1
    const NEW_FUN       =  'NEW_FUN';    // function foo () {}
    const NEW_CLASS     =  'NEW_CLASS';  // class Foo {}
    const BARE_FUN      =  'BARE_FUN';   // print
    const TEMPLATE_FUN  =  'TEMPLATE_FUN';   // template fooHtml() {}
    const FUN_ARG       =  'FUN_ARG';    // function foo (arg) {}
    const USER_FUN      =  'USER_FUN';   // myFunction
    const USER_VAR      =  'USER_VAR';   // myVar
    const PACKAGE       =  'PACKAGE';      // MyClass

    const MEMBER        =  'MEMBER';     // foo[...]
    const MEMBER_VAR    =  'MEMBER_VAR'; // foo.bar
    const MAP_KEY       =  'MAP_KEY';    // _foo_: bar
    const PAIR          =  'PAIR';       // foo: 'bar'
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
        'this'  => 'S_Constant',

        // prefix
        '!'  => 'S_Prefix',

        // infix
        '~'   => 'S_Concat',   // + .
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
        '||:' => 'S_ValGate',  // new
        '&&:' => 'S_ValGate',  // new

        // assignment
        '='   => 'S_Assign',
        '+='  => 'S_Assign',
        '-='  => 'S_Assign',
        '*='  => 'S_Assign',
        '/='  => 'S_Assign',
        '%='  => 'S_Assign',
        '**=' => 'S_Assign',
        '~='  => 'S_Assign',  // .=
        '||=' => 'S_Assign',  // new
        '&&=' => 'S_Assign',  // new
        '@='  => 'S_Assign',  // []=

        // delimiters / members
        '.'   => 'S_Dot',
        '['   => 'S_OpenBracket',
        '('   => 'S_OpenParen',
        '{'   => 'S_OpenBrace',
        '{{'  => 'S_TemplateExpr',    // {{ ... }}

        // keywords
        'let'      => 'S_NewVar',       // var
        'function' => 'S_NewFunction',
        'template' => 'S_NewFunction',
    	'class'    => 'S_Class',
        'if'       => 'S_If',
        'for'      => 'S_For',         // foreach
        'try'      => 'S_TryCatch',
        'break'    => 'S_Command',
        'continue' => 'S_Command',
        'return'   => 'S_Return',

        'F' => 'S_NewFunction',
        'T' => 'S_NewFunction',
        'R'   => 'S_Return',

    ];

    static public $RESERVED_NAMES = [
        'if', 'else', 'try', 'catch', 'finally', 'keep', 'in'
    ];

    static public $ALT_TOKENS = [

        // glyphs
        '===' => '==',
        '!==' => '!=',
        '=<'  => '<=',
        '=>'  => ">= (comparison) or colon ':' (map)",
        '<>'  => '!=',
    //    '>>'  => 'Bit.shift()',
    //    '<<'  => 'Bit.shift() or #=',
        '++'  => '+= 1',
        '--'  => '-= 1',
        '**'  => 'Math.exp()',
        '<=>' => 'myVar.compare(otherVar)',
        '->'  => 'dot (.)',
        '$'   => 'remove $ from name',
        '::'  => 'dot (.)',
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
}


//////////////////


class Parser {

    var $symbol = null;
    var $inTernary = false;
    var $expressionDepth = 0;
    var $foreverDepth = 0;
    var $symbolTable = null;
    var $prevToken = null;
    var $validator = null;
    var $foreverBreaks = [];

    private $tokenNum = 0;
    private $tokenStream = null;
    private $numTokens = 0;
    private $undefinedSymbols = [];

    // Main entry function
    function parse ($tokenStream) {

        $this->tokenStream = $tokenStream;
        $this->numTokens = $tokenStream->count();
        $this->symbolTable = new SymbolTable ($this->numTokens, $this);
        $this->validator = new Validator ($this);

        $this->parseMain();
        $this->validator->validate();

        return $this->symbolTable;
    }

    function error ($msg, $token = null) {
        if (!$token) { $token = $this->symbol->token; }
        return ErrorHandler::handleCompilerError($msg, $token, Source::getCurrentFile());
    }



    ///
    /// Parsing Methods  (Block > Statement(s) > Expression(s))
    ///


    // Main top-level scope (block without braces)
    function parseMain () {
        $sStatements = [];
        $sMain = $this->makeSequence(SequenceType::BLOCK, []);
        $this->next();
        while (true) {
            $s = $this->symbol;
            if ($s->type === SymbolType::END) {
                break;
            }
            $sStatement = $this->parseStatement();
            if ($sStatement) {
                $sStatements []= $sStatement;
            }
        }
        $sMain->setKids($sStatements);
    }

    // A Block is a list of Statements (inside braces)
    function parseBlock () {

        $sStatements = [];

        $this->validator->newScope();

        $this->now('{')->space(' { ', true)->next();

        while (true) {
            $s = $this->symbol;
            if ($s->isValue('}')) {
                $this->space(' }*', true);
                $this->next();
                break;
            }
            if ($s->type === SymbolType::END) {
                $this->error("Reached end of file without a closing brace `}`.");
            }
            $sStatement = $this->parseStatement();
            if ($sStatement) {
                $sStatements []= $sStatement;
            }
        }

        $this->validator->popScope();

        return $this->makeSequence(SequenceType::BLOCK, $sStatements);
    }

    // A Statements is a tree of Expressions.
    function parseStatement () {

        Tht::devPrint("START STATEMENT:\n");

        $this->expressionDepth = 0;

        $s = $this->symbol;
        if ($s instanceof S_Statement) {
            $st = $s->asStatement($this);
        }
        else {
            $st = $this->parseExpression(0);
            // don't allow commas to separate top-level expressions
            // e.g. a = 1, b = 2;
            if ($this->prevToken[TOKEN_VALUE] === ',') {
                $this->error("Unexpected comma `,`");
            }
        }

        return $st;
    }

    // An Expression is an operation that consists of Symbols.
    // Expression starts with a 'left' Symbol, followed by 'inner' Symbols.
    // Symbols are collected into the expression if they have a higher Binding Power.
    // This is how associativity, or precedence, is determined.
    function parseExpression ($baseBindingPower=0) {

        $this->expressionDepth += 1;

        Tht::devPrint("START EXPRESSION bp=$baseBindingPower d=" . $this->expressionDepth . " :\n");

        $left = $this->symbol->asLeft($this);
        while (true) {
            if (!($this->symbol->bindingPower > $baseBindingPower)) { break; }
            $left = $this->symbol->asInner($this, $left);
        }

        $this->expressionDepth -= 1;

        return $left;
    }




    ///
    /// Symbol-Level Methods
    ///


    // Take next Token from input stream and return a Symbol
    function next () {

        // end of stream -- handle off-by-one by returning last symbol (end) again
        if ($this->tokenNum >= $this->numTokens) {
             return $this->symbol;
        }

        if ($this->symbol) {
            $this->prevToken = $this->symbol->token;
        }

        $token = $this->tokenStream->next();
        $this->tokenNum += 1;
        $this->symbol = $this->tokenToSymbol($token);

        if ($token[TOKEN_TYPE] === TokenType::GLYPH) {
            if ($token[TOKEN_VALUE] === ';' || $token[TOKEN_VALUE] === ',') {
                $this->space('x, ');
            }
        }
        return $this->symbol;
    }

    // Assert the current Symbol value
    function now ($expectValue, $context = '') {
        if ($this->symbol->isValue('(end)') && $expectValue === '(newline)') {
            return $this;
        }
        if (!$expectValue) {  return $this;  }
        if (!$this->symbol->isValue($expectValue)) {
            if ($expectValue === ';') {
                $msg = "Missing semicolon `;` at end of statement.";
                $this->error($msg, $this->prevToken);
            } else {
                $msg = "Expected `$expectValue` here instead.";
                if ($context) { $msg .= "  ($context)"; }
                $this->error($msg);
            }

        }
        return $this;
    }

    function space ($mask, $isHard=false) {
        $this->symbol->space($mask, $isHard);
        return $this;
    }

    function checkAltToken ($altValue, $token) {
        $tokenValue = $token[TOKEN_VALUE];
        if (isset(ParserData::$ALT_TOKENS[$tokenValue])) {
            $correct = ParserData::$ALT_TOKENS[$tokenValue];
            $this->error("Unknown token: `$tokenValue`  Try: `$correct`", $token);
        }
    }

    function tokenToSymbol ($token) {

        $symbol = null;
        $tokenType = $token[TOKEN_TYPE];
        $tokenValue = $token[TOKEN_VALUE];

        if (isset(ParserData::$LITERAL_TYPES[$tokenType])) {
            $symbol = new S_Literal ($token, $this, $tokenType);
        }
        else if ($tokenType === TokenType::TSTRING) {
            $symbol = new S_TString ($token, $this);
        }
        else if (isset(ParserData::$SYMBOL_CLASS[$tokenValue])) {
            $symbolClass = 'o\\' . ParserData::$SYMBOL_CLASS[$tokenValue];
            $symbol = new $symbolClass ($token, $this);
        }
        else if ($tokenType === TokenType::WORD) {

            $type = '';
            $allowDigits = true;

            // Classes/Modules start with uppercase letter
            if ($tokenValue[0] >= 'A' && $tokenValue[0] <= 'Z') {
                $type = SymbolType::PACKAGE;
            }
            else if (OBare::isa($tokenValue)) {
                $type = SymbolType::BARE_FUN;
            }
            else if (in_array(strtolower($tokenValue), ParserData::$RESERVED_NAMES)) {
                $type = SymbolType::KEYWORD;
            }
            else {
                // Bare word
                // This might get overrided later as user_fun or map key.
                // TODO: figure out a cleaner way to handle this
                $type = SymbolType::USER_VAR;
            }

            $symbol = new S_Name ($token, $this, $type);

            $this->validator->validateNameFormat($tokenValue, $token, $type);
            $this->validator->validateDefined($symbol);
        }
        else {
            $this->checkAltToken($tokenValue, $token);
            $this->error("Unknown token: `$tokenValue`", $token);
        }

        Tht::devPrint($tokenValue . "  ==>  " . get_class($symbol));

        return $symbol;
    }

    function makeSymbol ($tokenType, $tokenValue, $symbolType) {
        $token = [
            $tokenType,
            $this->prevToken[TOKEN_POS],
            0,
            $tokenValue
        ];
        return new Symbol ($token, $this, $symbolType);
    }

    // A Sequence is a list of Symbols
    function makeSequence ($type, $els) {
        $sList = $this->makeSymbol('(SEQ)', $type, SymbolType::SEQUENCE);
        $sList->setKids($els);
        return $sList;
    }

    function registerUserFunction ($context, $token) {
        $this->validator->registerUserFunction($context, $token);
    }
}




//===================================
//              SYMBOLS
//===================================


class SymbolTable {

    private $parser = null;
    private $symbols = null;
    private $kids = null;
    private $i = 0;

    function __construct ($size, $parser) {
        $this->parser = $parser;

        // Extra padding for sequences. +10-15% is realistic
        $paddedSize = floor($size * 1.5);
        $this->symbols = new \SplFixedArray ($paddedSize);
        $this->kids = new \SplFixedArray ($paddedSize);
    }

    function add ($s) {
        $s->symbolId = $this->i;
        $this->update($s);
        $this->i += 1;
    }

    function update ($s) {
        $this->symbols[$s->symbolId] = $this->compress($s);
    }

    function get ($i) {
        return $this->decompress($this->symbols[$i]);
    }

    function setKids ($parentId, $kids) {
        foreach ($kids as $kid) {
            $this->addKid($parentId, $kid);
        }
    }

    function addKid ($parentId, $kid) {
        if (!$kid) {
            $p = $this->get($parentId);
            $token = [];
            $token[TOKEN_POS] = $p['pos'][0] . ',' . $p['pos'][1];
            $this->parser->error("Incomplete expression.", $token);
        }
        $kidId = $kid->symbolId;
        if (isset($this->kids[$parentId])) {
            $this->kids[$parentId] .= ',' . $kidId;
        } else {
            $this->kids[$parentId] = $kidId;
        }
    }

    function getFirst () {
        return $this->get(0);
    }

    function getKids ($parentId) {
        $kids = [];
        if (isset($this->kids[$parentId])) {
            $kidIds = explode(',', $this->kids[$parentId]);
            foreach ($kidIds as $kidId) {
                $kids []= $this->get($kidId);
            }
        }
        return $kids;
    }

    function compress ($sym) {
        $c = implode(TOKEN_SEP, [
            $sym->symbolId,
            $sym->token[TOKEN_POS],
            $sym->token[TOKEN_TYPE],
            $sym->type,
            $sym->token[TOKEN_VALUE]
        ]);
        return $c;
    }

    function decompress ($symbol) {
        $s = explode(TOKEN_SEP, $symbol, 5);
        // Don't need tokenType $s[2]
        return [
            'id' => $s[0],
            'pos' => explode(',', $s[1]),
            'type' => $s[3],
            'value' => $s[4]
        ];
    }
}


class Symbol {

    var $type = '';
    var $kids = [];
    var $bindingPower = 0;
    var $parser = null;
    var $token = null;
    var $symbolId = 0;
    var $isDefined = false;

    function __construct ($token, $parser, $type='') {
        $this->parser = $parser;
        $this->token = $token;
        if ($type) {
            $this->type = $type;
        }
        $this->parser->symbolTable->add($this);
    }

    function addKid ($kid) {
        $this->parser->symbolTable->addKid($this->symbolId, $kid);
    }

    function setKids ($kids) {
        $this->parser->symbolTable->setKids($this->symbolId, $kids);
    }

    function symbolError ($context) {
        $this->parser->error("Unexpected symbol `" . $this->getValue() . "` $context.", $this->token);
    }

    function isValue ($val) {
        return $this->token[TOKEN_VALUE] === $val;
    }

    function getValue () {
        return $this->token[TOKEN_VALUE];
    }

    function getDefined () {
        return $this->isDefined;
    }

    function setDefined () {
        $this->isDefined = true;
    }

    // (Override) - parse top level expression
    function asStatement ($p) {
        $this->symbolError('in statement');
    }

    // (Override) - parse symbols at the beginning of an expression
    function asLeft($p) {
        $this->symbolError('at start of expression');
    }

    // (Override) - parse symbols in the middle of an expression
    function asInner ($p, $left) {
        $this->symbolError('within expression');
    }

    function updateType ($type) {
        $this->type = $type;
        $this->parser->symbolTable->update($this);
    }

    // Whitespace rule for this token (before and after).
    // The middle symbol(s) are arbitrary.  Only the left and right have meaning.
    // Examples:
    // ' | ' = space required before & after
    // 'x| ' = space not allowed before, required after
    // '*| ' = anything before, space required after
    // '*|n' = anything before, newline or non-space after
    function space ($pattern, $isHard=false) {

        if (Tht::getConfig('disableFormatChecker') && !$isHard) {
            return $this;
        }

        $this->spacePos($isHard, 'L', $pattern[0]);
        $this->spacePos($isHard, 'R', $pattern[strlen($pattern) - 1]);

        return $this;
    }

    // Validate whitespace rules for this token.
    // E.g. space required before or after the token.
    // TODO: refactor. log is a little hairy
    function spacePos ($isHard, $pos, $require) {

        if ($require == '*') { return; }

        $p = $this->parser;
        $t = $this->token;

        $isRequired = ($require === ' ' || $require === 'S');
        $allowNewline = ($require === 'N');

        $lrBit = $pos === 'L' ? 1 : 2;
        $cSpace = $t[TOKEN_SPACE];
        $hasNewline = $cSpace & 4;
        $hasSpace = ($cSpace & $lrBit);
        if ($hasNewline && $pos === 'R' && $require !== 'S') {
            $hasSpace = true;
        }

        if ($hasNewline && $allowNewline) {
            return;
        }

        $msg = '';
        $what = 'space';
        if ($require === 'S' && $pos === 'R' && $hasNewline) {
            $msg = 'remove the';
            $what = 'newline';
        } else if ($hasSpace && !$isRequired) {
            $msg = 'remove the';
        }
        else if (!$hasSpace && $isRequired) {
            $msg = 'add a';
            if ($pos === 'R') {
                $nextToken = $p->next()->token;
                if ($nextToken[TOKEN_VALUE] === ';') {
                    $p->error('Unexpected semicolon `;`', $nextToken);
                }
                else if ($nextToken[TOKEN_VALUE] === ',') {
                    $p->error('Unexpected comma `,`', $nextToken);
                }
            }
        }

        if ($msg) {
            $sPos = $pos === 'L' ? 'before' : 'after';
            $aPos = explode(',', $t[TOKEN_POS]);
            $posDelta = $pos === 'L' ? -1 : strlen($t[TOKEN_VALUE]);
            $t[TOKEN_POS] = $aPos[0] . ',' . ($aPos[1] + $posDelta);

            $fullMsg = 'Please ' . $msg . ' ' . $what . ' ' . $sPos . " '" . $t[TOKEN_VALUE] . "'.";
            if (!$isHard) { $fullMsg = '(Format Checker) ' . $fullMsg; }

            $p->error($fullMsg, $t);
        }

        return;
    }
}



//===================================
//              SIMPLE
//===================================


class S_Literal extends Symbol {
    var $kids = 0;
    function asLeft($p) {
        $p->next();
        return $this;
    }
}

class S_Name extends S_Literal {
}

class S_Constant extends S_Name {
    var $type = SymbolType::CONSTANT;
}

class S_Flag extends S_Literal {
    var $type = SymbolType::FLAG;
}

class S_Sep extends Symbol {
    var $kids = 0;
    var $type = SymbolType::SEPARATOR;
    function asLeft($p) {
        $p->next();
        return null;
    }
}

class S_End extends S_Sep {
    var $type = SymbolType::END;
}





//===================================
//              PREFIX
//===================================


class S_Prefix extends Symbol {
    var $type = SymbolType::PREFIX;
    function asLeft($p) {
        $p->next();
        $this->space('*!x', true);
        $this->setKids([$p->parseExpression(70)]);
        return $this;
    }
}



//===================================
//              INFIX
//===================================


class S_Infix extends Symbol {
    var $bindingPower = 80;
    var $type = SymbolType::INFIX;
    function asInner ($p, $left) {
        $this->space(' + ');
        $p->next();

        $right = $p->parseExpression($this->bindingPower);
        $this->setKids([$left, $right]);

        return $this;
    }
}

class S_Add extends S_Infix {
    var $bindingPower = 51;

    // Unary + and -
    function asLeft($p) {
        $p->next();
        $this->updateType(SymbolType::PREFIX);
        $this->setKids([$p->parseExpression(70)]);
        return $this;
    }
}

class S_Multiply extends S_Infix {
    var $bindingPower = 52;
}

class S_Concat extends S_Infix {
    var $bindingPower = 50;
    var $type = SymbolType::OPERATOR;
}

class S_OpenBracket extends S_Infix {

    // Dynamic member.  foo[...]
    function asInner ($p, $left) {
        $p->next();
        $this->updateType(SymbolType::MEMBER);
        $this->space('x[x', true);
        $this->setKids([$left, $p->parseExpression(0)]);
        $p->now(']')->space('x]*')->next();
        return $this;
    }

    // List literal.  [ ... ]
    function asLeft($p) {
        $p->space('*[N')->next();
        $this->updateType(SymbolType::SEQUENCE);
        $els = [];
        while (true) {
            if ($p->symbol->isValue("]")) {
                break;
            }
            $els []= $p->parseExpression(0);
            if (!$p->symbol->isValue(',')) {
                break;
            }
            $p->space('x, ');
            $p->next();
        }

        $p->now(']', 'Missed a comma?')->next();
        $this->setKids($els);
        return $this;
    }
}

class S_Dot extends S_Infix {
    var $type = SymbolType::MEMBER;

    // Dot member.  foo.bar
    function asInner ($p, $objName) {
        $p->next();
        $this->space('x.x', true);
        $sMember = $p->symbol;
        if ($sMember->token[TOKEN_TYPE] !== TokenType::WORD) {
            $p->error('Expected a field name.  Ex: `user.name`');
        }
        $sMember->updateType(SymbolType::MEMBER_VAR);
        $name = $sMember->token[TOKEN_VALUE];
        if (!($name >= 'a' && $name <= 'z')) {
            $p->error("Member `$name` must be lowerCamelCase.");
        }
        $this->setKids([ $objName, $sMember ]);
        $p->next();

        return $this;
    }
}


//===================================
//           INFIX RIGHT
//===================================


// Infix with lower binding power
class S_InfixRight extends Symbol {
    var $type = SymbolType::INFIX;
    var $isAssignment = false;

    function asInner ($p, $left) {
        $p->next();
        if ($this->isAssignment && $p->expressionDepth >= 2) {
            $tip = $this->token[TOKEN_VALUE] == '=' ? "Did you mean `==`?" : '';
            $p->error("Assignment can not be used as an expression.  $tip", $this->token);
        }
        $this->space(' = ');
        $this->setKids([$left, $p->parseExpression($this->bindingPower - 1)]);
        return $this;
    }
}

class S_Assign extends S_InfixRight {
    // =, +=, etc.
    var $type = SymbolType::ASSIGN;
    var $bindingPower = 10;
    var $isAssignment = true;
}

class S_Logic extends S_InfixRight {
    // e.g. ||, &&
    var $bindingPower = 30;
}

class S_Compare extends S_InfixRight {
    // e.g. !=, ==
    var $bindingPower = 40;
}

class S_ValGate extends S_InfixRight {
    // e.g. &&:, ||:
    var $type = SymbolType::VALGATE;
    var $bindingPower = 41;
}




//===================================
//              MISC
//===================================



class S_OpenParen extends Symbol {

    var $bindingPower = 90;

    // Grouping (...)
    function asLeft($p) {
        $this->space('*(N');
        $p->next();
        $this->updateType(SymbolType::OPERATOR);
        $e = $p->parseExpression(0);
        $p->now(')')->next();
        return $e;
    }

    // Function call. foo()
    function asInner ($p, $left) {

        $this->space('x(N', true);

        $p->next();
        $this->updateType(SymbolType::CALL);

        // Check for bare function like "print"
        if ($left->token[TOKEN_TYPE] === TokenType::WORD) {
            $type = OBare::isa($left->getValue()) ? SymbolType::BARE_FUN : SymbolType::USER_FUN;
            $left->updateType($type);
            if ($type === SymbolType::USER_FUN) {
                $p->registerUserFunction('called', $left->token);
            }
        }
        $this->setKids([ $left ]);

        // Argument list
        $args = [];
        while (true) {
            if ($p->symbol->isValue(')')) { break; }
            $args[]= $p->parseExpression(0);
            if (!$p->symbol->isValue(",")) { break; }
            $p->space('x, ')->next();
        }
        $argSymbol = $p->makeSequence(SequenceType::FLAT, $args);
        $this->addKid($argSymbol);

        $p->now(')')->space('x)*')->next();

        return $this;
    }
}

class S_OpenBrace extends Symbol {

    var $type = SymbolType::SEQUENCE;

    // Map Literal { ... }
    function asLeft($p) {

        $p->next();

        $pairs = [];
        $hasKey = [];
        $sep = ',';

        if ($p->symbol->isValue("}")) {
            $this->space('*{N');
        }
        else {
            $this->space('*{ ');
        }

        // Collect "key: value" pairs
        while (true) {

            if ($p->symbol->isValue("}")) { break; }

            // key
            $key = $p->symbol;
            $sKey = $key->getValue();
            if (isset($hasKey[$sKey])) {
                $p->error("Duplicate key: `$sKey`");
            }
            $key->updateType(SymbolType::MAP_KEY);
            $hasKey[$sKey] = true;
            $p->next();

            // colon
            $p->now(':', 'Map key')->space('x: ', true)->next();

            // value
            $val = $p->parseExpression(0);
            $pair = $p->makeSymbol(SymbolType::PAIR, $key->getValue(), SymbolType::PAIR);
            $pair->addKid($val);
            $pairs []= $pair;

            // comma
            if (!$p->symbol->isValue($sep)) { break; }
            $p->space('x, ');
            $sSep = $p->symbol;
            $p->next();
        }

        if (count($pairs) > 0) {  $p->space(' }*');  }

        $p->now('}', 'Map - Missed a comma?')->next();
        $this->setKids($pairs);
        $this->value = SequenceType::MAP;
        return $this;
    }
}

class S_Ternary extends Symbol {
    var $type = SymbolType::TERNARY;
    var $bindingPower = 20;

    // e.g. test ? result1 : result2
    function asInner ($p, $left) {
        $p->next();

        if ($p->inTernary) {
            $p->error("Nested ternary operator `a ? b : c`. Try an `if/else` instead.");
        }
        $p->inTernary = true;

        $this->addKid($left);
        $this->space(' ? ');
        $this->addKid($p->parseExpression(0));
        $p->now(':')->space(' : ')->next();
        $this->addKid($p->parseExpression(0));

        $p->inTernary = false;

        return $this;
    }
}


//===================================
//          STATEMENTS
//===================================


class S_Statement extends Symbol {
}

class S_NewVar extends S_Statement {
    var $type = SymbolType::NEW_VAR;

    // e.g. let a = 1;
    function asStatement ($p) {

        // var name
        $p->validator->setPaused(true);
        $p->next();
        $sNewVarName = $p->symbol;
        $this->addKid($sNewVarName);
        $p->validator->setPaused(false);

        $p->next();
        $p->now('=')->space(' = ')->next();

        $p->expressionDepth += 1;
        $this->addKid($p->parseExpression(0));
        $p->expressionDepth -= 1;

        // define after statement, to prevent e.g. 'let a = a + 1;'
        $p->validator->define($sNewVarName);

        $p->now(';')->next();

        return $this;
    }
}

class S_If extends S_Statement {
    var $type = SymbolType::OPERATOR;

    // if / else
    function asStatement ($p) {

        $this->space('*if ');

        $p->next();

        $p->expressionDepth += 1;  // prevent assignment

        // conditional. if (...)
        $p->now('(', 'if')->space(' (x', true)->next();
        $this->addKid($p->parseExpression(0));
        $p->now(')', 'if')->space('x) ', true)->next();

        // block. { ... }
        $this->addKid($p->parseBlock());

        // else/if
        if ($p->symbol->isValue('else')) {
            $p->space(' else ', true)->next();
            if ($p->symbol->isValue('if')) {
                $p->space(' if ', true);
                $this->addKid($p->parseStatement());
            } else {
                $this->addKid($p->parseBlock());
            }
        }
        return $this;
    }
}

class S_For extends S_Statement {
    var $type = SymbolType::OPERATOR;

    // for (...) { ... }
    function asStatement ($p) {

        $p->expressionDepth += 1; // prevent assignment

        $sFor = $p->symbol;
        $p->next();

        // Forever block. for { ... }
        if ($p->symbol->isValue('{')) {
            $p->foreverBreaks []= false;
            $this->addKid($p->parseBlock());
            $hasBreak = array_pop($p->foreverBreaks);
            if (!$hasBreak) {
                $p->error("Infinite `for` loop needs a `break` or `return` statement.", $sFor->token);
            }
            return $this;
        }

        $p->validator->newScope();

        // Optional Parens (disabled)
        //  $inParens = false;
        //  if ($p->symbol->isValue('(')) {
        //    $inParens = true;
              $p->now('(')->space(' (x', true)->next();
        //  }

        if ($p->symbol->isValue('let')) {
            $p->error("Unexpected `let`.  Try: `for (item in items) { ... }`");
        }

        // Temp variable. for (_temp_ in list) { ... }
        if ($p->symbol->type !== SymbolType::USER_VAR) {
            $p->error('Expected a list variable.  Ex: `for (item in items) { ... }`');
        }
        $p->validator->define($p->symbol);
        $this->addKid($p->symbol);
        $p->next();

        // key:value alias.  for (_k:v_ in map) { ... }
        if ($p->symbol->isValue(':')) {
            $p->space('x:x', true)->next();
            if ($p->symbol->type !== SymbolType::USER_VAR) {
                $p->error('Expected a key:value pair.  Ex: `for (userName:age in users) { ... }`');
            }
            $p->validator->define($p->symbol);
            $this->addKid($p->symbol);
            $p->next();
        }

        $p->now('in', 'for/in')->next();


        // Iterator.  for (a in _iterator_) { ... }
        $this->addKid($p->parseExpression(0));

        // if ($inParens) {
            $p->now(')')->space('x) ', true)->next();
        // }

        $this->addKid($p->parseBlock());

        $p->validator->popScope();

        return $this;
    }
}

class S_NewFunction extends S_Statement {
    var $type = SymbolType::NEW_FUN;

    // Function as an expression (anonymous)
    // e.g. let funFoo = function () { ... };
    function asLeft($p) {
        return $this->asStatement($p);
    }

    // function foo() { ... }
    function asStatement ($p) {

        $p->next();
        $this->space('*function ', true);

        $hasName = false;

        if ($p->symbol->token[TOKEN_TYPE] === TokenType::WORD) {
            // function name
            $hasName = true;
            $sFunName = $p->symbol;
            $sName = $sFunName->token[TOKEN_VALUE];
            if (strlen($sName) < 2) {
                $p->error("Function name `$sName` should be longer than 1 letter.  Tip: Be more descriptive.");
            }
            $p->validator->define($p->symbol);
            $sFunName->updateType(SymbolType::USER_FUN);
            $this->addKid($sFunName);
            $p->registerUserFunction('defined', $sFunName->token);
            $p->space(' name*')->next();
        }
        else {
            // anonymous function. e.g. function () { ... }
            $anon = $p->makeSymbol(
                TokenType::WORD,
                ParserData::$ANON,
                SymbolType::USER_FUN
            );
            $this->addKid($anon);
        }

        $p->validator->newScope();

        // List of args.  function foo (_args_) { ... }
        if ($p->symbol->isValue("(")) {

            $space = $hasName ? 'x(x' : ' (x';
            $p->now('(', 'function')->space($space, true)->next();
            $argSymbols = [];
            $hasOptionalArg = false;
            while (true) {

                if ($p->symbol->isValue(")")) {
                    break;
                }

                if ($p->symbol->token[TOKEN_TYPE] !== TokenType::WORD) {
                    $p->error("Expected an argument name.  Ex: `fun myFun (argument) { ... }`");
                }

                $p->validator->define($p->symbol);

                $sArg = $p->symbol;
                $sArg->updateType(SymbolType::FUN_ARG);

                $sNext = $p->next();

                if ($sNext->isValue('=')) {
                    $p->space(' = ');

                    // argument with default.
                    // e.g. function foo (a = 1) { ... }
                    $sDefault = $p->next();
                    $types = [ SymbolType::STRING, SymbolType::NUMBER, SymbolType::FLAG ];
                    if (!in_array($sDefault->type, $types)) {
                        $p->error("Argument defaults need to be a String, Number, or Flag.", $sDefault->token);
                    }
                    $sArg->addKid($sDefault);
                    $p->next();
                    $hasOptionalArg = true;
                }
                else if ($hasOptionalArg) {
                    $p->error("Required arguments should appear before optional arguments.", $sArg->token);
                }

                $argSymbols []= $sArg;

                if (!$p->symbol->isValue(",")) {
                    break;
                }
                $p->next();
            }

            $this->addKid($p->makeSequence(SequenceType::ARGS, $argSymbols));

            $p->now(')')->space('x) ')->next();
        }
        else {
            $this->addKid($p->makeSequence(SequenceType::ARGS, []));
        }

        // closure vars. e.g. function foo() keep (varName) { ... }
        $closureVars = [];
        if ($p->symbol->isValue('keep')) {
            $p->next();
            $p->now('(', 'keep')->next();
            while (true) {
                if ($p->symbol->token[TOKEN_TYPE] !== TokenType::WORD) {
                    $p->error("Expected an outer variable inside `keep`.  Ex: `fun () keep (name) { ... }`");
                }

                $sArg = $p->symbol;
                $sArg->updateType(SymbolType::USER_VAR);
                $closureVars []= $sArg;

                $s = $p->next();
                if (!$s->isValue(',')) {
                    break;
                }
                $p->now(',')->next();
            }
            $p->now(')')->space('x) ')->next();
        }

        // block. { ... }
        $this->addKid($p->parseBlock());

        $p->validator->popScope();

        if ($closureVars) {
            $this->addKid($p->makeSequence(SequenceType::ARGS, $closureVars));
        }

        return $this;
    }
}

// Undocumented
class S_Class extends S_Statement {
    var $type = SymbolType::NEW_CLASS;

    // e.g. class Foo { ... }
    function asStatement ($p) {

        $p->space(' class ', true);

        $p->next();

        $sClassName = $p->symbol;
        if (! $sClassName->token[TOKEN_TYPE] === TokenType::WORD) {
			$p->error("Expected a class name.  Ex: `class User { ... }`");
		}

        $sClassName->updateType(SymbolType::PACKAGE);
        $this->addKid($sClassName);
        $p->next();

        $this->addKid($p->parseBlock());

        return $this;
    }
}

class S_TryCatch extends S_Statement {
    var $type = SymbolType::TRY_CATCH;

    // try { ... } catch (e) { ... }
    function asStatement ($p) {

        $p->space(' try ', true);

        $p->next();

        // try
        $this->addKid($p->parseBlock());

        // catch
        $p->now('catch')->space(' catch ', true)->next();

        // exception var
        $p->now('(', 'try/catch')->next();
        $p->validator->define($p->symbol);
        $this->addKid($p->symbol);
        $p->next();
        $p->now(')')->next();

        $this->addKid($p->parseBlock());

        // finally
        if ($p->symbol->isValue('finally')) {
            $p->space(' finally ', true);
            $p->next();
            $this->addKid($p->parseBlock());
        }

        return $this;
    }
}

class S_TemplateExpr extends S_Statement {
    var $type = SymbolType::TEMPLATE_EXPR;

    // {{ expr }}
    function asStatement ($p) {
        $p->space('*{{ ')->next();
        $this->addKid($p->parseExpression(0));
        $p->space(' }}*');
        $p->now(Glyph::TEMPLATE_EXPR_END)->next();
        return $this;
    }
}

class S_TString extends S_Statement {

    // Default text in template function.
    var $type = SymbolType::TSTRING;
    function asStatement ($p) {
        $p->next();
        return $this;
    }
}


//===================================
//       COMMAND STATEMENTS
//===================================

class S_Command extends S_Statement {
    var $type = SymbolType::COMMAND;

    // e.g. continue, break
    function asStatement ($p) {
        $sCommand = $p->symbol;
        $p->next();
        $this->checkForOrphan($p);

        if ($sCommand->isValue('break') || $sCommand->isValue('return')) {
            $p->foreverBreaks[count($p->foreverBreaks) - 1] = true;
        }

        return $this;
    }

    function checkForOrphan ($p) {
         $p->now(';')->next();
         if ($p->symbol->isValue("}")) {
             return;
         }
         if (!$p->symbol->isValue("}")) {
             $p->error("Unreachable statement after `" . $this->getValue() . "`.");
         }
    }
}

class S_Return extends S_Command {

    // e.g. return 1;
    function asStatement ($p) {
        $p->next();
        if (!$p->symbol->isValue(';')) {
            $this->space('*| ', true);
            $p->expressionDepth += 1; // prevent assignment
            $this->addKid($p->parseExpression(0));
        }

        // don't check for orphan, to support a common debugging pattern of returning early

        return $this;
    }
}

