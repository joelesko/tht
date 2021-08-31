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
        $e = $p->parseExpression(0);

        $p->ignoreNewlines = false;

        $p->now(')', 'group.close')->space('N)*')->next();

        return $e;
    }

    // Function call. foo()
    function asInner ($p, $left) {

        $this->space('x(N', true);

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
        $this->setKids([ $left ]);

        // Argument list
        $args = [];
        while (true) {
            $p->skipNewline();
            if ($p->symbol->isValue(')')) { break; }
            $arg = $p->parseExpression(0);
            if ($arg->type == SymbolType::BOOLEAN) {
                ErrorHandler::setHelpLink('/language-tour/flags', 'Flags');
                $p->error('Can not use a Boolean as a function argument.  Try: Use a -flag instead.', $arg->token);
            }
            $args[]= $arg;
            if (!$p->symbol->isValue(",")) { break; }
            $p->space('x, ')->next();
        }
        $p->skipNewline();

        if (!$p->symbol->isValue(')')) {
            $p->error('Expected closing paren `)`');
        }
        $p->space('N)*')->next();

        $sArgs = $p->makeAstList(AstList::FLAT, $args);
        $this->addKid($sArgs);

        return $this;
    }
}
