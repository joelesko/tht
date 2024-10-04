<?php

namespace o;

// class S_New extends Symbol {

//     var $type = SymbolType::NEW_OBJECT;

//     // e.g. new Foo()
//     function asLeft($p) {

//         $p->space('*newS');

//         $p->next();

//         $sClassName = $p->symbol;
//         if (! $sClassName->token[TOKEN_TYPE] === TokenType::WORD) {
//             $p->error("Expected a class name.  Ex: `new User()`");
//         }
//         $p->space('SclassNamex');
//         $sClassName->updateType(SymbolType::PACKAGE);
//         $this->addKid($sClassName);
//         $p->next();

//         // Argument list
//         $p->now('(', 'new')->space('x(x')->next();
//         $args = [];
//         while (true) {
//             if ($p->symbol->isValue(')')) { break; }
//             $args[]= $p->parseExpression(0);
//             if (!$p->symbol->isValue(",")) { break; }
//             $p->space('x, ')->next();
//         }
//         $argSymbol = $p->makeAstList(AstList::FLAT, $args);
//         $this->addKid($argSymbol);

//         $p->now(')')->space('x)*')->next();


//         return $this;
//     }
// }
