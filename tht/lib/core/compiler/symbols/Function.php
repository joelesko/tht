<?php

namespace o;

class S_Function extends S_Statement {

    var $type = SymbolType::NEW_FUN;
    var $isExpression = false;

    // Function as an expression (anonymous)
    // e.g. $funFoo = function () { ... };
    function asLeft($p) {

        $this->isExpression = true;

        $p->anonFunctionDepth += 1;
        $s = $this->asStatement($p);
        $p->anonFunctionDepth -= 1;

        return $s;
    }

    function getAnonSymbol($p) {
        return $p->makeSymbol(
            TokenType::WORD,
            CompilerConstants::$ANON,
            SymbolType::USER_FUN
        );
    }

    function getTemplateTypeSymbol($p, $type) {
        return $p->makeSymbol(
            TokenType::WORD,
            $type,
            SymbolType::STRING
        );
    }

    // function foo() { ... }
    function asStatement ($p) {

        $p->next();
        $this->space('*fnS', true);

        $hasName = false;

        if ($p->symbol->token[TOKEN_TYPE] === TokenType::WORD) {

             // function name
            $hasName = true;
            $sFunName = $p->symbol;
            $sName = $sFunName->token[TOKEN_VALUE];

            if (strlen($sName) < 2) {
                $p->error("Function name `$sName` should be longer than 1 letter.  Try: Be more descriptive.");
            }

            $sTemplateType = '';
            if ($this->type == SymbolType::NEW_TEMPLATE) {
                preg_match('/(' . CompilerConstants::$TEMPLATE_TYPES .')$/i', $sName, $m);
                $sTemplateType = $m[1]; // was already validated in the Tokenizer
            }

            // PHP doesn't allow names in dynamic functions.
            if ($this->isExpression) {
                $sFunName = $this->getAnonSymbol($p);
            }

            $sFunName->updateType(SymbolType::USER_FUN);
            $this->addKid($sFunName);
            $p->registerUserFunction('defined', $sFunName->token);
            $p->space(' name*')->next();

            if ($sTemplateType) {
                $smTemplateType = $this->getTemplateTypeSymbol($p, $sTemplateType);
                $this->addKid($smTemplateType);
            }
        }
        else {
            if (!$this->isExpression) {
                $p->error("Top-level function must have a name.", $p->prevToken);
            }

            if ($this->type == SymbolType::NEW_TEMPLATE) {
                $p->error('Template function must have a name.');
            }

            // anonymous function. e.g. fn () { ... }
            $anon = $this->getAnonSymbol($p);
            $this->addKid($anon);
        }


        $outerScopeVars = $p->validator->getAllInScope();

        $p->validator->newFunctionScope();
        $p->validator->newScope();

        $this->parseArgs($p, $hasName);
        $closureVars = $this->parseClosureVars($p, $outerScopeVars);

        // block. { ... }
        $p->functionDepth += 1;
        if ($this->type == SymbolType::NEW_TEMPLATE) {
            $p->inTemplate = true;
        }
        $this->addKid($p->parseBlock(true));
        $p->inTemplate = false;
        $p->functionDepth -= 1;

        if (!$this->isExpression) {
            $p->space('*}B');
        }

        // Make sure next symbol is out of this scope
        $p->validator->popScope();  // block
        $p->validator->popScope();  // function
        $p->validator->popFunctionScope();

        $p->next();

        if ($closureVars) {
            $this->addKid($p->makeAstList(AstList::ARGS, $closureVars));
        }

        return $this;
    }

    function parseArgs($p, $hasName) {

        // List of args.  function foo (_args_) { ... }
        if ($p->symbol->isValue("(")) {

            $sOpenParen = $p->symbol;

            $space = $hasName ? 'x(x' : ' (x';
            $p->now('(', 'function.args.open')->space($space, true)->next();

            $argSymbols = [];
            $hasOptionalArg = false;
            $seenName = [];

            while (true) {

                if ($p->symbol->isValue(")")) {
                    break;
                }

                // splat
                $isSplat = false;
                if ($p->symbol->token[TOKEN_VALUE] === '...') {
                    $p->space('*...x');
                    $p->next();
                    $isSplat = true;
                }

                if ($p->symbol->token[TOKEN_TYPE] !== TokenType::VAR) {
                    $p->error("Expected an argument name.  Ex: `function myFun (\$argument)`");
                }

                $p->validator->defineVar($p->symbol, true);

                $sArg = $p->symbol;
                $sArg->updateType($isSplat ? SymbolType::FUN_ARG_SPLAT : SymbolType::FUN_ARG);

                if (count($argSymbols) > 0 && !$isSplat) {
                    $p->space('Sarg*');
                }

                $sNext = $p->next();

                // Type declaration
                $sArgType = null;
                if ($sNext->isValue(':')) {
                    $p->space('x:x');
                    $p->next();
                    $sArgType = $p->symbol;
                    if ($sArgType->token[TOKEN_TYPE] !== TokenType::WORD) {
                        $p->error("Expected a type.  Ex: `function myFun (arg:s)`");
                    }
                    $type = $sArgType->token[TOKEN_VALUE];
                    if (!in_array($type, CompilerConstants::$TYPE_DECLARATIONS)) {
                        $types = implode(' ', CompilerConstants::$TYPE_DECLARATIONS);
                        $p->error("Unknown type: `$type`  Try: `$types`");
                    }
                    $sArgType->updateType(SymbolType::FUN_ARG_TYPE);

                    $sArg->addKid($sArgType);

                    $sNext = $p->next();
                }

                // Argument with default
                if ($sNext->isValue('=')) {

                    if ($isSplat) {
                        $p->error("Spread operator `...` can not have a default value.");
                    }

                    $p->space(' = ');

                    $p->next();
                    $sDefault = $p->parseExpression(0);

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
                $p->space('x,S');

                $p->next();
            }

            if (count($argSymbols) > CompilerConstants::$MAX_FUN_ARGS) {
                ErrorHandler::setHelpLink('/reference/format-checker#max-arguments', 'Max Arguments');
                $p->error('Can not have more than ' . CompilerConstants::$MAX_FUN_ARGS . ' arguments to a function. Try: Combine some arguments into a Map', $sOpenParen->token);
            }

            $this->addKid($p->makeAstList(AstList::ARGS, $argSymbols));

            $p->now(')', 'function.args.close')->space('x) ');

            if (!count($argSymbols)) {
                $p->error('Please remove the empty parens: `()`', $sOpenParen->token);
            }

            $p->next();
        }
        else {
            $this->addKid($p->makeAstList(AstList::ARGS, []));
        }
    }

    // Just import everything from outer scope.
    function parseClosureVars($p, $outerScopeVars) {

        if (!$this->isExpression) { return []; }

        $closureVars = [];
        foreach ($outerScopeVars as $varName) {

            $varName = ltrim($varName, '$');

            $sNewVar = $p->makeSymbol(
                TokenType::WORD,
                $varName,
                SymbolType::USER_VAR,
            );

            $p->validator->defineVar($sNewVar, true);

            $closureVars []= $sNewVar;
        }

        return $closureVars;
    }
}
