<?php

namespace o;

class S_Function extends S_Statement {
    var $type = SymbolType::NEW_FUN;

    // Function as an expression (anonymous)
    // e.g. let funFoo = function () { ... };
    function asLeft($p) {
        return $this->asStatement($p);
    }

    // function foo() { ... }
    function asStatement ($p) {

        $p->next();
        $this->space('*functionS', true);

        $hasName = false;

        if ($p->symbol->token[TOKEN_TYPE] === TokenType::WORD) {
            // function name
            $hasName = true;
            $sFunName = $p->symbol;
            $sName = $sFunName->token[TOKEN_VALUE];
            if (strlen($sName) < 2) {
                $p->error("Function name `$sName` should be longer than 1 letter.  Try: Be more descriptive.");
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

        $this->parseArgs($p, $hasName);

        $closureVars = $this->parseClosureVars($p);

        // block. { ... }
        $this->addKid($p->parseBlock());

        $p->validator->popScope();


        if ($closureVars) {
            $this->addKid($p->makeSequence(SequenceType::ARGS, $closureVars));
        }

        return $this;
    }

    function parseArgs($p, $hasName) {

        // List of args.  function foo (_args_) { ... }
        if ($p->symbol->isValue("(")) {

            $space = $hasName ? 'x(x' : ' (x';
            $p->now('(', 'function')->space($space, true)->next();
            $argSymbols = [];
            $hasOptionalArg = false;
            $seenName = [];
            while (true) {

                if ($p->symbol->isValue(")")) {
                    break;
                }

                $isSplat = false;
                if ($p->symbol->token[TOKEN_VALUE] === '...') {
                    $p->space('*...x');
                    $p->next();
                    $isSplat = true;
                }

                if ($p->symbol->token[TOKEN_TYPE] !== TokenType::WORD) {
                    $p->error("Expected an argument name.  Ex: `fun myFun (argument) { ... }`");
                }

                $p->validator->define($p->symbol, true);

                $sArg = $p->symbol;
                $sArg->updateType($isSplat ? SymbolType::FUN_ARG_SPLAT : SymbolType::FUN_ARG);

                if (count($argSymbols) > 0 && !$isSplat) {
                    $p->space('Sarg*');
                }

                $sNext = $p->next();

                if ($sNext->isValue('=')) {

                    if ($isSplat) {
                        $p->error("Spread operator `...` can not have a default value.");
                    }

                    $p->space(' = ');

                    // argument with default.
                    // e.g. function foo (a = 1) { ... }
                    $p->next();
                    $sDefault = $p->parseExpression(0);

                    $sArg->addKid($sDefault);
                    $hasOptionalArg = true;
                }
                else if ($hasOptionalArg) {
                    $p->error("Required arguments should appear before optional arguments.", $sArg->token);
                }

                // Prevent duplicate arguments
                $argName = $sArg->token[TOKEN_VALUE];
                if (isset($seenName[$argName])) {
                    $p->error("Duplicate argument `$argName`", $sArg->token);
                }
                $seenName[$argName] = true;

                $argSymbols []= $sArg;

                if (!$p->symbol->isValue(",")) {
                    break;
                }
                $p->space('x,S');
                $p->next();
            }

            // $maxArgs = ParserData::$MAX_FUN_ARGS;
            // if (count($argSymbols) > ParserData::$MAX_FUN_ARGS) {
            //     $p->error("Too many arguments in function (Max: $maxArgs). Try: Take a Map of options as one argument.", [], true);
            // }

            $this->addKid($p->makeSequence(SequenceType::ARGS, $argSymbols));

            $p->now(')')->space('x) ')->next();
        }
        else {
            $this->addKid($p->makeSequence(SequenceType::ARGS, []));
        }
    }

    // closure vars. e.g. function foo() keep (varName) { ... }
    function parseClosureVars($p) {

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

        return $closureVars;
    }
}
