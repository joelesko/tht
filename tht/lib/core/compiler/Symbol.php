<?php

namespace o;

Symbol::loadSymbols();

// Precendence
// 50  infix
// 45  bitshift
// 40  compare
// 30  bitwise
// 20  logic
// 10  assignment

class Symbol {

    var $type = '';
    var $kids = [];
    var $bindingPower = 0;
    var $parser = null;
    var $token = null;
    var $symbolId = 0;

    static function loadSymbols() {
        $symbols = [
            'Literal',
            'Separator',
            'Prefix',
            'Infix',
            'InfixWeak',
            'Statement',
            'If',
            'ForEach',
            'Loop',
            'Function',
            'Template',
            'OpenParen',
            'OpenCurly',
            'OpenSquare',
            'Dot',
            'ClassPlugin',
            'ClassFields',
            'Class',
            'Ternary',
            'PreKeyword',
            'TryCatch',
            'TemplateExpr',
            'TemplateString',
            'Command',
            'Return',
            'ShortPrint',
            'Match',
            'Unsupported',
            'Lambda',
        ];

        foreach ($symbols as $s) {
            include_once('symbols/' . $s . '.php');
        }
    }

    function __construct ($token, $parser, $type='') {
        $this->parser = $parser;
        $this->token = $token;
        if ($type) {
            $this->type = $type;
        }
        $this->parser->symbolTable->add($this);
    }

    // Add child node
    function addKid ($kid) {
        $this->parser->catchMissingDollarVar($kid);
        $this->parser->symbolTable->addKid($this->symbolId, $kid);
    }

    // Set child nodes
    function setKids ($kids) {
        foreach ($kids as $kid) {
            $this->parser->catchMissingDollarVar($kid);
        }
        $this->parser->symbolTable->setKids($this->symbolId, $kids);
    }

    function symbolError ($context) {
        $this->parser->error("Unexpected symbol `" . $this->getValue() . "` $context.", $this->token);
    }

    function isValue ($val) {
        return $this->token[TOKEN_VALUE] === $val;
    }

    function isSeparator ($val) {
        return $this->token[TOKEN_VALUE] === $val && $this instanceof S_Separator;
    }

    function isNewline () {
        return $this->isSeparator('(nl)');
    }

    function getValue () {
        return $this->token[TOKEN_VALUE];
    }

    // (Override) - parse top level expression
    function asStatement ($p) {
        $this->symbolError('in statement');
    }

    // (Override) - parse symbols at the beginning of an expression
    function asLeft($p) {
        $this->symbolError('at start of expression');
    }

    // (Override) - parse symbols in the middle of an expression
    function asInner ($p, $left) {
        $this->symbolError('within expression');
    }

    function updateType ($type) {
        $this->type = $type;
        $this->parser->symbolTable->update($this);
    }

    function hasNewlineAfter() {
        return $this->token[TOKEN_SPACE] & NEWLINE_AFTER_BIT;
    }

    function hasNewlineBefore() {
        return $this->token[TOKEN_SPACE] & NEWLINE_BEFORE_BIT;
    }

    // Whitespace rule for this token (before and after).
    // The middle symbol(s) are arbitrary.  Only the left and right have meaning.
    // Examples:
    // ' | ' = whitespace required before & after
    // 'x| ' = space not allowed before, whitespace required after
    // '*| ' = anything before, whitespace required after
    // '*|N' = anything before, newline or non-space after
    // '*|B' = anything before, newline required (hard break) after
    // '*|S' = anything before, space (not newline) required after
    function space ($pattern) {

        $this->validateSpacePos('L', $pattern[0]);
        $this->validateSpacePos('R', $pattern[strlen($pattern) - 1]);

        return $this;
    }

    // Validate whitespace rules for this token.
    // E.g. space required before or after the token.
    function validateSpacePos ($pos, $require) {

        if ($require == '*') { return; }

        $p = $this->parser;
        $t = $this->token;

        $isRequired = ($require === ' ' || $require === 'S');
        $allowNewline = ($require === 'N' || $require === 'B');

        $cSpace = $t[TOKEN_SPACE];

        $bitHasSpace = $pos === 'L' ? SPACE_BEFORE_BIT : SPACE_AFTER_BIT;
        $hasSpace = ($cSpace & $bitHasSpace);

        $bitHasNewline = $pos === 'L' ? NEWLINE_BEFORE_BIT : NEWLINE_AFTER_BIT;
        $hasNewline = ($cSpace & $bitHasNewline);

        if ($hasNewline && $require !== 'S') {
            $hasSpace = true;
        }

        if ($hasNewline && $allowNewline) {
            return;
        }

        $verb = '';
        $what = 'space';
        if ($require === 'S' && $hasNewline) {
            $verb = 'remove the';
            $what = 'newline';
        } else if ($require === 'B' && !$hasNewline) {
            $verb = 'add a';
            $what = 'newline';
        } else if ($hasSpace && !$isRequired) {
            $verb = 'remove the';
        }
        else if (!$hasSpace && $isRequired) {
            $verb = 'add a';
            if ($pos === 'R') {
                $nextToken = $p->next()->token;
                if ($nextToken[TOKEN_VALUE] === ';') {
                    $p->error('Unexpected semicolon `;`', $nextToken);
                }
                else if ($nextToken[TOKEN_VALUE] === ',') {
                    $p->error('Unexpected comma `,`', $nextToken);
                }
            }
        }

        if ($verb) {

            $sPos = $pos === 'L' ? 'before' : 'after';
            $aPos = explode(',', $t[TOKEN_POS]);

            $posDelta = 0;
            if ($verb == 'remove the') {
                $posDelta = $pos === 'L' ? -1 : strlen($t[TOKEN_VALUE]);
            }

            $fullMsg = 'Please ' . $verb . ' ' . $what . ' ' . $sPos . ": `" . $t[TOKEN_VALUE] . "`";

            $t[TOKEN_POS] = $aPos[0] . ',' . ($aPos[1] + $posDelta);

            ErrorHandler::addSubOrigin('formatChecker');
            $p->error($fullMsg, $t);
        }

        return;
    }
}

