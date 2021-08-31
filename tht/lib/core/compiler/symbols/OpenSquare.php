<?php

namespace o;

class S_OpenSquare extends S_Infix {

    // Dynamic member.  foo[...]
    function asInner ($p, $left) {

        $p->next();
        $this->updateType(SymbolType::MEMBER);
        $this->space('x[x');

        $this->setKids([$left, $p->parseExpression(0)]);
        $p->now(']', 'index.close')->space('x]*')->next();

        return $this;
    }

    // List literal.  [ ... ]
    function asLeft($p) {

        $p->space('*[N');
        $p->next();
        $this->updateType(SymbolType::AST_LIST);
        $els = [];

        $hasNewline = false;
        while (true) {

            $hasNewline = $hasNewline | $p->skipNewline();
            if ($p->symbol->isValue("]")) {
                break;
            }
            $hasNewline = $hasNewline | $p->skipNewline();

            $els []= $p->parseExpression(0);

            // comma
            if (!$p->symbol->isValue(',')) {

                if ($p->symbol->isValue(':')) {
                    ErrorHandler::addSubOrigin('formatChecker');
                    $p->error('Unexpected `:` inside of List. Try: Create a Map `{...}` instead?');
                }

                if ($hasNewline && count($els) > 0) {
                    ErrorHandler::addSubOrigin('formatChecker');
                    $p->error('Please add a comma `,` after the multi-line list item.', $p->prevToken);
                }

                $p->now(']', 'list.close', 'Forgot comma `,`?');
                break;
            }

            $p->space('x, ');
            $p->next();

            if (!$hasNewline && $p->symbol->isValue(']')) {
                ErrorHandler::addSubOrigin('formatChecker');
                $p->error('Please remove the trailing comma `,`.', $p->prevToken);
            }

            //$p->parseElementSeparator();
        }

        $p->space('N]*')->next();

        $this->setKids($els);

        return $this;
    }
}
