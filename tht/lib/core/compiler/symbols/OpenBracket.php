<?php

namespace o;

class S_OpenBracket extends S_Infix {

    // Dynamic member.  foo[...]
    function asStatement ($p) {
        $p->next();

        $this->updateType(SymbolType::MEMBER);
        $this->space('*[x', true);
        $this->setKids([$left, $p->parseExpression(0)]);
        $p->now(']', 'index.close')->space('x]*')->next();
        return $this;
    }

    // Dynamic member.  foo[...]
    function asInner ($p, $left) {
        $p->next();
        $this->updateType(SymbolType::MEMBER);
        $this->space('x[x', true);
        $this->setKids([$left, $p->parseExpression(0)]);
        $p->now(']', 'index.close')->space('x]*')->next();
        return $this;
    }

    // List literal.  [ ... ]
    function asLeft($p) {
        $p->space('*[N')->next();
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

            // Old comma-delimited behavior
            // if (!$p->symbol->isValue(',')) {
            //     if ($hasNewline && count($els) > 1) {
            //         ErrorHandler::addSubOrigin('formatChecker');
            //         $p->error('Please add a comma `,` after the list item.');
            //     }

            //     $p->skipNewline();

            //     $p->now(']', 'list.close');
            //     break;
            // }
            // $p->space('x, ');
            // $p->next();

            $p->parseElementSeparator();
        }

        $p->space('N]*')->next();

        $this->setKids($els);

        return $this;
    }
}
