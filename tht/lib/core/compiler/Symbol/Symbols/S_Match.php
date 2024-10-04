<?php

namespace o;

class S_Match extends S_Statement {

    var $type = SymbolType::OPERATOR;
    var $allowAsMapKey = true;

    function asLeft($p) {

        $s = $this->asStatement($p);

        return $s;
    }

    // match { ... }
    function asStatement($p) {

        $this->space('*matchS');

        $p->next();

        $p->expressionDepth += 1;  // prevent assignment

        if (!$p->symbol->isValue('{')) {
            $sMatchSubject = $p->parseExpression(0, 'noOuterParen');
            if ($sMatchSubject->type == SymbolType::BOOLEAN) {
                $bool = $sMatchSubject->token[TOKEN_VALUE];
                $p->error("Please remove literal boolean: `$bool`  Try: `match { ... }`", $sMatchSubject->token);
            }
            $this->addKid($sMatchSubject);
        }
        else {
            // implicit true if no subject
            $sTrue = $p->makeSymbol(TokenType::WORD, 'true', SymbolType::BOOLEAN);
            $this->addKid($sTrue);
        }

        $p->now('{', 'match.open')->space(' {B');

        $p->next();

        // Collect pattern pairs.  "pattern: value"
        $pos = 0;
        $hasDefaultCase = false;
        while (true) {

            $p->parseElementSeparator($pos, true, '}');
            $pos += 1;

            if ($p->symbol->isValue("}")) { break; }

            // Pattern(s)
            $sMatchPatternList = $this->getMatchPatternList($p);

            // Colon ':'
            $p->now(':', 'match.colon')->space('x:S')->next();

            // Match Value
            $sMatchValue = $p->parseExpression(0);

            $sMatchPair = $p->makeSymbol(SymbolType::MATCH_PAIR, '(pair)', SymbolType::MATCH_PAIR);

            $sMatchPair->addKid($sMatchPatternList);
            $sMatchPair->addKid($sMatchValue);

            $this->addKid($sMatchPair);
        }

        $p->now('}', 'match.close')->space(' } ')->next();

        return $this;
    }

    function getMatchPatternList($p) {

            // Match Patterns (comma delimited)
            $matchPatterns = [];

            if ($p->symbol->isValue('default')) {
                $sMatchPattern = $p->makeSymbol(TokenType::WORD, 'default', SymbolType::CONSTANT);
                $matchPatterns []= $sMatchPattern;
                $p->next();
            }
            else {
                while (true) {
                    $sMatchPattern = $p->parseExpression(0);
                    $matchPatterns []= $sMatchPattern;
                    if (!$p->symbol->isValue(",")) { break; }
                    $p->next();
                }
            }

            $sMatchPatternList = $p->makeAstList(AstList::MATCH, $matchPatterns);

            return $sMatchPatternList;
    }
}
