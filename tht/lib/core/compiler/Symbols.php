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
    var $isDefined = false;

    static function loadSymbols() {
        $symbols = [
            'Literal',
            'Sep',
            'Prefix',
            'Infix',
            'InfixWeak',
            'Statement',
            'If',
            'For',
            'Function',
            'Template',
            'OpenParen',
            'OpenBrace',
            'OpenBracket',
            'Dot',
            'New',
            'Class',
            'Ternary',
            'Var',
            'TryCatch',
            'TemplateExpr',
            'TemplateString',
            'Command',
            'Return',
            'Unsupported',
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

    function addKid ($kid) {
        $this->parser->symbolTable->addKid($this->symbolId, $kid);
    }

    function setKids ($kids) {
        $this->parser->symbolTable->setKids($this->symbolId, $kids);
    }

    function symbolError ($context) {
        $this->parser->error("Unexpected symbol `" . $this->getValue() . "` $context.", $this->token);
    }

    function isValue ($val) {
        return $this->token[TOKEN_VALUE] === $val;
    }

    function getValue () {
        return $this->token[TOKEN_VALUE];
    }

    function getDefined () {
        return $this->isDefined;
    }

    function setDefined () {
        $this->isDefined = true;
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

    // Whitespace rule for this token (before and after).
    // The middle symbol(s) are arbitrary.  Only the left and right have meaning.
    // Examples:
    // ' | ' = whitespace required before & after
    // 'x| ' = space not allowed before, whitespace required after
    // '*| ' = anything before, whitespace required after
    // '*|N' = anything before, newline or non-space after
    // '*|B' = anything before, newline required (hard break) after
    // '*|S' = anything before, space (not newline) required after
    function space ($pattern, $isHard=false) {

        // if (Tht::getConfig('disableFormatChecker') && !$isHard) {
        //     return $this;
        // }

        $this->spacePos('L', $pattern[0]);
        $this->spacePos('R', $pattern[strlen($pattern) - 1]);

        return $this;
    }

    // Validate whitespace rules for this token.
    // E.g. space required before or after the token.
    function spacePos ($pos, $require) {

        if ($require == '*') { return; }

        $p = $this->parser;
        $t = $this->token;

        $isRequired = ($require === ' ' || $require === 'S');
        $allowNewline = ($require === 'N' || $require === 'B');

        $cSpace = $t[TOKEN_SPACE];

        $bitHasSpace = $pos === 'L' ? 1 : 4;
        $hasSpace = ($cSpace & $bitHasSpace);

        $bitHasNewline = $pos === 'L' ? 2 : 8;
        $hasNewline = ($cSpace & $bitHasNewline);

        if ($hasNewline && $require !== 'S') {
            $hasSpace = true;
        }

        if ($hasNewline && $allowNewline) {
            return;
        }

        $msg = '';
        $what = 'space';
        if ($require === 'S' && $hasNewline) {
            $msg = 'remove the';
            $what = 'newline';
        } else if ($require === 'B' && !$hasNewline) {
            $msg = 'add a';
            $what = 'newline';
        } else if ($hasSpace && !$isRequired) {
            $msg = 'remove the';
        }
        else if (!$hasSpace && $isRequired) {
            $msg = 'add a';
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

        if ($msg) {
            $sPos = $pos === 'L' ? 'before' : 'after';
            $aPos = explode(',', $t[TOKEN_POS]);
            $posDelta = $pos === 'L' ? -1 : strlen($t[TOKEN_VALUE]);
            $t[TOKEN_POS] = $aPos[0] . ',' . ($aPos[1] + $posDelta);

            $fullMsg = 'Please ' . $msg . ' ' . $what . ' ' . $sPos . " `" . $t[TOKEN_VALUE] . "`.";
            $fullMsg = '(Format Checker) ' . $fullMsg;

            $p->error($fullMsg, $t);
        }

        return;
    }
}

