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

    var $allowAsMapKey = false;
    var $preventYoda = false;
    var $isOuterParen = false;

    static function loadSymbols() {
        $symbols = [
            'S_Literal',
            'S_Separator',
            'S_Prefix',
            'S_InfixSticky',
            'S_Infix',
            'S_Statement',
            'S_If',
            'S_ForEach',
            'S_Loop',
            'S_Function',
            'S_Template',
            'S_OpenParen',
            'S_OpenCurly',
            'S_OpenSquare',
            'S_Dot',
            'S_ClassPlugin',
            'S_ClassFields',
            'S_Class',
            'S_Ternary',
            'S_PreKeyword',
            'S_TryCatch',
            'S_TemplateExpr',
            'S_TemplateString',
            'S_Command',
            'S_Return',
            'S_ShortPrint',
            'S_Match',
            'S_Lambda',
            'S_Unsupported',
        ];

        foreach ($symbols as $s) {
            require_once(__DIR__ . '/Symbols/' . $s . '.php');
        }
    }

    function __construct($token, $parser, $type='') {
        $this->parser = $parser;
        $this->token = $token;
        if ($type) {
            $this->type = $type;
        }
        $this->parser->symbolTable->add($this);
    }

    function dump() {
        $this->kids = $this->parser->symbolTable->getKids($this->symbolId);
        $cl = clone $this;
        unset($cl->parser);
        return $cl;
    }

    function u_z_to_print_string() {
        return json_encode($this->dump());
    }


    // Add child node
    function addKid($kid) {
        $this->parser->validator->catchMissingDollarVar($kid);
        $this->parser->symbolTable->addKid($this->symbolId, $kid);
    }

    // Add child nodes
    function addKids($kids) {
        foreach ($kids as $kid) {
            $this->addKid($kid);
        }
    }

    function symbolError($context) {
        $this->parser->error("Unexpected symbol `" . $this->getValue() . "` $context.", $this->token);
    }

    function isValue($val) {
        return $this->token[TOKEN_VALUE] === $val;
    }

    function isNewline() {
        return $this->isValue('(nl)');
    }

    function getValue() {
        return $this->token[TOKEN_VALUE];
    }

    // (Override) - parse top level expression
    function asStatement($p) {
        $this->symbolError('in statement');
    }

    // (Override) - parse symbols at the beginning of an expression
    function asLeft($p) {
        $this->symbolError('at start of expression');
    }

    // (Override) - parse symbols in the middle of an expression
    function asInner($p, $left) {
        $this->symbolError('within expression');
    }

    function updateType($type) {
        $this->type = $type;
        $this->parser->symbolTable->update($this);
    }

    function hasNewlineAfter() {
        return $this->token[TOKEN_SPACE] & NEWLINE_AFTER_BIT;
    }

    function hasNewlineBefore() {
        return $this->token[TOKEN_SPACE] & NEWLINE_BEFORE_BIT;
    }

    function space($pattern, $formatCheckerRule='') {

        $this->parser->validator->validateSymbolSpacing($this, $pattern, $formatCheckerRule);

        return $this;
    }

}

