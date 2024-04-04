<?php

namespace o;

class S_Match extends S_Statement {
    var $type = SymbolType::OPERATOR;

    function asLeft($p) {

     //   $p->matchDepth += 1;
        $s = $this->asStatement($p);
    //    $p->matchDepth -= 1;

        return $s;
    }

    // match { ... }
    function asStatement ($p) {

        $this->space('*matchS');

        $p->next();

        $p->expressionDepth += 1;  // prevent assignment

        if (!$p->symbol->isValue('{')) {
            $sMatchSubject = $p->parseExpression(0, 'noOuterParen');
            $this->addKid($sMatchSubject);
        }
        else {
            // implicit true if no subject
            $sTrue = $p->makeSymbol(TokenType::WORD, 'true', SymbolType::BOOLEAN);
            $this->addKid($sTrue);
        }

        $p->now('{', 'match.open')->space(' {B')->next();

        // Collect patterns.  "pattern: ..."
        $pos = 0;
        while (true) {

            $p->parseElementSeparator($pos, true, $this);
            $pos += 1;

            if ($p->symbol->isValue("}")) { break; }

            // Match Pattern
            if ($p->symbol->isValue('default')) {
                $sMatchPattern = $p->makeSymbol(TokenType::WORD, 'default', SymbolType::CONSTANT);
                $p->next();
            }
            else {
                $sMatchPattern = $p->parseExpression(0);
            }

            // Colon ':'
            $p->now(':', 'match.colon')->space('x:S')->next();

            // Match Value
            $sMatchValue = $p->parseExpression(0);

            $sMatchPair = $p->makeSymbol(SymbolType::MATCH_PAIR, '(pair)', SymbolType::MATCH_PAIR);
            $sMatchPair->addKid($sMatchPattern);
            $sMatchPair->addKid($sMatchValue);

            $this->addKid($sMatchPair);

            // newline separator
            if ($p->symbol->isNewline()) {
                $p->next();
            }
        }

        $p->now('}', 'match.close')->space(' } ')->next();

        return $this;
    }
}
