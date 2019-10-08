<?php

namespace o;

class S_Match extends S_Statement {
    var $type = SymbolType::OPERATOR;

    // match { ... }
    function asStatement ($p) {

        $this->space('*matchS');

        $p->next();

        $p->expressionDepth += 1;  // prevent assignment

        if (!$p->symbol->isValue('{')) {
            $sMatchSubject = $p->parseExpression(0);
            $this->addKid($sMatchSubject);
        }
        else {
            // experimental -- no subject turns it into a more concise if/else
            $sTrue = $p->makeSymbol(TokenType::WORD, 'true', SymbolType::FLAG);
            $this->addKid($sTrue);
        }

        $p->now('{', 'match.open')->space(' { ', true)->next();

        // Collect patterns.  "pattern { ... }"
        while (true) {

            if ($p->symbol->isValue("}")) { break; }

            if ($p->symbol->isValue('default')) {
                $sPattern = $p->makeSymbol(TokenType::WORD, 'true', SymbolType::FLAG);
                $p->next();
            }
            else {
                $sPattern = $p->parseExpression(0);
            }

            $sBlock = $p->parseBlock();

            $sPatternPair = $p->makeSymbol(SymbolType::MATCH_PATTERN, 'pattern', SymbolType::MATCH_PATTERN);
            $sPatternPair->addKid($sPattern);
            $sPatternPair->addKid($sBlock);

            $this->addKid($sPatternPair);
        }

        $p->now('}', 'match.close')->space(' } ', true)->next();

        return $this;
    }
}
