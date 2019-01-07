<?php

namespace o;

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
    // '*|N' = anything before, newline or non-space after
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
    // TODO: isHard not currently used
    function spacePos ($isHard, $pos, $require) {

        if ($require == '*') { return; }

        $p = $this->parser;
        $t = $this->token;

        $isRequired = ($require === ' ' || $require === 'S');
        $allowNewline = ($require === 'N');

        $cSpace = $t[TOKEN_SPACE];

        $bitHasSpace = $pos === 'L' ? 1 : 4;
        $hasSpace = ($cSpace & $bitHasSpace);

        $bitHasNewline = $pos === 'L' ? 2 : 8;
        $hasNewline = ($cSpace & $bitHasNewline);
        
        if ($hasNewline && $require !== 'S') {
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

            $fullMsg = 'Please ' . $msg . ' ' . $what . ' ' . $sPos . " `" . $t[TOKEN_VALUE] . "`.";
            $fullMsg = '(Format Checker) ' . $fullMsg; 

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
        $p->space('*[*')->next();
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
        $this->space('N.x', true);
        $sMember = $p->symbol;
        if ($sMember->token[TOKEN_TYPE] !== TokenType::WORD) {
            $p->error('Expected a field name.  Ex: `user.name`');
        }
        $sMember->updateType(SymbolType::MEMBER_VAR);
        $name = $sMember->token[TOKEN_VALUE];
        if (!($name[0] >= 'a' && $name[0] <= 'z')) {
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


// Infix, but with a lower binding power
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

// Precendence
// 50  infix 
// 45  bitshift
// 40  compare
// 30  Bbitwise
// 20  logic
// 10  assignment


class S_BitShift extends S_InfixRight {
    // e.g. +>, +<
    var $type = SymbolType::BITSHIFT;
    var $bindingPower = 45;
}

class S_ValGate extends S_InfixRight {
    // e.g. &&:, ||:
    var $type = SymbolType::VALGATE;
    var $bindingPower = 41;
}

class S_Compare extends S_InfixRight {
    // e.g. !=, ==
    var $bindingPower = 40;
}

class S_Bitwise extends S_InfixRight {
    // e.g. +&, +|
    var $type = SymbolType::BITWISE;
    var $bindingPower = 30;
}

class S_Logic extends S_InfixRight {
    // e.g. ||, &&
    var $bindingPower = 20;
}

class S_Assign extends S_InfixRight {
    // =, +=, etc.
    var $type = SymbolType::ASSIGN;
    var $bindingPower = 10;
    var $isAssignment = true;
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

class S_New extends Symbol {

    var $type = SymbolType::NEW_OBJECT;

    // e.g. new Foo()
    function asLeft ($p) {

        $p->space('*new ', true);

        $p->next();

        $sClassName = $p->symbol;
        if (! $sClassName->token[TOKEN_TYPE] === TokenType::WORD) {
            $p->error("Expected a class name.  Ex: `new User()`");
        }
        $p->space(' classNamex', true);
        $sClassName->updateType(SymbolType::PACKAGE);
        $this->addKid($sClassName);
        $p->next();

        // Argument list
        $p->now('(', 'new')->space('x(x', true)->next();
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

        if ($p->inClass && $p->blockDepth == 1) {
            $this->updateType(SymbolType::NEW_OBJECT_VAR);
        }

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

// class S_Qualifier extends S_Statement {
//     function asStatement($p) {
        
//         $quals = [];
//         while (true) {
//             $this->space(' keyword ', true);
//             $s = $p->symbol;
//             $keyword = $s->token[TOKEN_VALUE];
//             $p->next();
//             if (in_array($keyword, ParserData::$QUALIFIER_KEYWORDS)) {
//                 $quals []= $keyword;
//             }
//             else {
//                 break;
//             }
//         }
        
//       //  $this->addKid(implode(' ', $quals));
//         $this->setKids([$p->parseExpression(0)]);

//         return $this;
//     }
// }

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

class S_NewTemplate extends S_NewFunction {
    var $type = SymbolType::NEW_TEMPLATE;
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
                    $p->next();
                    $sDefault = $p->parseExpression(0);
                    // $types = [ SymbolType::STRING, SymbolType::NUMBER, SymbolType::FLAG ];
                    // if (!in_array($sDefault->type, $types)) {
                    //     $p->error("Argument defaults need to be a String, Number, or Flag.", $sDefault->token);
                    // }

                    $sArg->addKid($sDefault);
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

class S_Class extends S_Statement {
    var $type = SymbolType::NEW_CLASS;

    // e.g. class Foo { ... }
    function asStatement ($p) {

        // qualifiers and class keyword
        $quals = [];
        while (true) {
            $this->space(' keyword ', true);
            $s = $p->symbol;
            $keyword = $s->token[TOKEN_VALUE];
            if (in_array($keyword, ParserData::$QUALIFIER_KEYWORDS)) {
                $quals []= $keyword;
                $p->next();
            }
            else {
                break;
            }
        }
        $sQuals = $p->makeSymbol(
            TokenType::WORD,
            implode(' ', $quals),
            SymbolType::PACKAGE_QUALIFIER
        );
        $this->addKid($sQuals);
        

        // Class name
        $sName = $p->symbol;
        if ($sName->token[TOKEN_TYPE] == TokenType::WORD) {
                $sName->updateType(SymbolType::PACKAGE);
                $this->addKid($sName);
        }
        else {
          //  print_r($sName); exit();
            $p->error("Expected a class name.  Ex: `class User { ... }`");
        }



        // read in class qualifiers (e.g. abstract) and the package name
        // while (true) {

        //     $p->next();
        //     $s = $p->symbol;

        //     if ($s->token[TOKEN_VALUE] == 'abstract' || $s->token[TOKEN_VALUE] == 'final') {
        //       //  $s->updateType(SymbolType::PACKAGE_QUALIFIER);
        //       //  $this->addKid($s);
        //     }
        //     else if ($s->token[TOKEN_TYPE] == TokenType::WORD) {
        //         $s->updateType(SymbolType::PACKAGE);
        //         $this->addKid($s);
        //         break;
        //     }
        //     else {
        //         $p->error("Expected a class name.  Ex: `class User { ... }`");
        //     }
        // }
        
        $p->next();


        if ($p->symbol->isValue('extends')) {

            $p->next();
            $sParentClassName = $p->symbol;
            if ($sParentClassName->token[TOKEN_TYPE] !== TokenType::WORD) {
                $p->error("Expected a parent class name.  Ex: `class MyClass extends MyParentClass { ... }`");
            }
            $sParentClassName->updateType(SymbolType::PACKAGE);
            $this->addKid($sParentClassName);

            $p->next();
        }
        else {
            $sNull = $p->makeSymbol(
                TokenType::WORD,
                '',
                SymbolType::PACKAGE
            );
            $this->addKid($sNull);
        }

        // // TODO: duplicated code
        // if ($p->symbol->isValue('implements')) {

        //     $p->next();
        //     $sParentClassName = $p->symbol;
        //     if ($sParentClassName->token[TOKEN_TYPE] !== TokenType::WORD) {
        //         $p->error("Expected an interface name.  Ex: `class MyClass implements MyInterface { ... }`");
        //     }
        //     $sParentClassName->updateType(SymbolType::PACKAGE);
        //     $this->addKid($sParentClassName);
        // }


        $p->inClass = true;
        $this->addKid($p->parseBlock());
        $p->inClass = false;


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
