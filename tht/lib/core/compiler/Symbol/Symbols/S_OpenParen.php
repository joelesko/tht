<?php

namespace o;

class S_OpenParen extends Symbol {

    var $bindingPower = 90;

    // Grouping (...)
    function asLeft($p) {

        $this->space('*(N');

        $p->ignoreNewlines = true;

        $p->next();
        $this->updateType(SymbolType::OPERATOR);

        $exp = $p->parseExpression(0);

        $p->ignoreNewlines = false;

        $p->now(')')->space('N)*');
        $p->symbol->isOuterParen = $this->isOuterParen;
        $p->next();

        return $exp;
    }

    // Function call. foo()
    function asInner($p, $left) {

        $this->space('x(N');

        $sOpenParen = $p->symbol;

        $p->next();
        $this->updateType(SymbolType::CALL);

        // Check for bare function like "print"
        if ($left->token[TOKEN_TYPE] === TokenType::WORD) {
            $type = u_Bare::isa($left->getValue()) ? SymbolType::BARE_FUN : SymbolType::USER_FUN;
            $left->updateType($type);
            if ($type === SymbolType::USER_FUN) {
                $p->registerUserFunction('called', $left->token);
            }
        }
        $this->addKids([ $left ]);

        // Argument list
        $args = [];
        $isMultiline = false;
        $pos = 0;
        while (true) {

            $isMultiline = $p->parseElementSeparator($pos, $isMultiline, ')');
            $pos += 1;

            if ($p->symbol->isValue(')')) {
                break;
            }

            $arg = $p->parseExpression(0);

            if ($arg === null) {
                $p->error('Reached end of file without closing paren: `)`');
            }
            if ($arg->type == SymbolType::BOOLEAN) {
                ErrorHandler::setHelpLink('/language-tour/option-maps', 'Option Maps');
                $p->error('Can\'t use a Boolean as a function argument.  Try: Use an option map instead.  Ex: `{ flag: true }` or `-flag`', $arg->token);
            }
            $args[]= $arg;
        }

        if (!$p->symbol->isValue(')')) {
            $p->error('Expected closing paren `)`');
        }

        if (!$args) {
            // empty parens: ()
            $sOpenParen->space('x(x');
            $p->space('x)*');
        }
        else {
            $sOpenParen->space('x(N');
            $p->space('N)*');
        }

        $p->next();

        $sArgs = $p->makeAstList(AstList::FLAT, $args);
        $this->addKid($sArgs);

        return $this;
    }
}
