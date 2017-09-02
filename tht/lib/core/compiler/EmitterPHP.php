<?php

namespace o;



class EmitterPHP extends Emitter {

    var $astToTarget = [

        'FLAG'            => 'pFlag',
        'CONSTANT|this'   => 'pThis',
        'NUMBER'          => 'pNumber',
        'STRING'          => 'pString',
        'LSTRING'         => 'pLString',
        'TSTRING'         => 'pTString',
        'RSTRING'         => 'pRString',

        'USER_FUN'        => 'pUserFun',
        'USER_VAR'        => 'pUserVar',
        'TEMPLATE_EXPR'   => 'pTemplateExpr',
        'CLASS'           => 'pClassName',
        'FUN_ARG'         => 'pFunArg',

        'PAIR'            => 'pPair',
        'BARE_FUN'        => 'pBareFun',
        'TRY_CATCH'       => 'pTryCatch',
        'CALL'            => 'pCall',
        'INFIX'           => 'pInfix',
        'PREFIX'          => 'pPrefix',
        'VALGATE'         => 'pValGate',
        'TERNARY'         => 'pTernary',

        'ASSIGN'          => 'pAssign',
        'ASSIGN|||='      => 'pAssignOr',
        'ASSIGN|&&='      => 'pAssignAnd',
        'ASSIGN|@='       => 'pAssignPush',
        'METHOD_ASSIGN'   => 'pMethodAssign',

        'NEW_VAR'         => 'pNewVar',
        'NEW_FUN'         => 'pFunction',
        'NEW_TEMPLATE'    => 'pTemplate',
		'NEW_CLASS'       => 'pClass',

        'OPERATOR|~'      => 'pConcat',
        'OPERATOR|if'     => 'pIf',
        'OPERATOR|for'    => 'pFor',

        'MEMBER|['        => 'pMemberBracket',
        'MEMBER|.'        => 'pMemberDot',
        'MEMBER_VAR'      => 'pMemberVar',

        'COMMAND'         => 'pCommand',
        'COMMAND|return'  => 'pReturn',
        'COMMAND|R'       => 'pReturn',

        'SEQUENCE'        => 'pSequence',
        'SEQUENCE|{'      => 'pMap',
        'SEQUENCE|['      => 'pList',
    ];

    function emit ($symbolTable, $filePath) {

        $this->symbolTable = $symbolTable;

        $php = $this->out($symbolTable->getFirst());

        $nameSpace = uniqid('tht');
        $relPath = Tht::getRelativePath('root', $filePath);
        $nameSpacePhp = 'namespace ' . $nameSpace . ";\n\\o\\Runtime::setNameSpace('$relPath','$nameSpace');\n";

        $finalCode = "<?php\n\n$nameSpacePhp\n$php\n\n";
        $finalCode = $this->appendSourceMap($finalCode, $filePath);
        $finalCode .= "\n\n?" . ">";

        // Tht::devPrint($finalCode);

        return $finalCode;
    }

    function toSequence ($value, $kids) {
        $isBlock = $value === SequenceType::BLOCK;
        $targetSrc = [];
        foreach ($kids as $k) {
            $sep = '';
            $dent = '';
            if ($isBlock) {
                $dent = $this->indent();
            }
            $targetSrc []= $dent . $this->out($k, $isBlock);
        }
        $delim = $isBlock ? '' : ', ';
        return implode($targetSrc, $delim);
    }

    // Simple

    function pFlag ($value, $k) {
        return $value;
    }

    function pThis ($value, $k) {
        return '$this';
    }

    function pNumber ($value, $k) {
        return $value;
    }

    function pString ($value, $k) {
        $value = str_replace('$', '\\$', $value);
        $value = str_replace('"', '\\"', $value);
        return $this->format('"###"', $value);
    }

    function pLString ($value, $k) {
        $value = str_replace('$', '\\$', $value);
        $value = str_replace('"', '\\"', $value);
        return $this->format('new \o\OLockString ("###")', $value);
    }

    function pRString ($value, $k) {
        $value = str_replace('$', '\\$', $value);
        $value = str_replace('"', '\\"', $value);
        return $this->format('new \o\ORegex ("###")', $value);
    }

    function pTString ($value, $k) {
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace('$', '\\$', $value);
        $value = str_replace('"', '\\"', $value);
        return $this->format('$t->addStatic("###");', $value);
    }



    // Templates


    function pTemplateExpr ($value, $k) {
        return $this->format('$t->addDynamic(###);', $k[0]);
    }



    // Arrays

    function pMap ($value, $k) {
        return $this->format('\o\OMap::create([ ### ])', $this->toSequence($value, $k));
    }

    function pList ($value, $k) {
       return $this->format('\o\OList::create([ ### ])', $this->toSequence($value, $k));
    }



    // Words

    function pUserVar ($value, $k) {
        return $this->format('$###', u_($value));
    }

    function pMemberVar ($value, $k) {
        return $value;
    }

    function pUserFun ($value, $k) {
        if ($value === ParserData::$ANON) {  return '';  }
        return $this->format('###', u_($value));
    }

    function pFunArg ($value, $k) {
        if (isset($k[0])) {
            return $this->format('$###=###', u_($value), $k[0]);
        } else {
            return $this->format('$###', u_($value));
        }
    }

    function pClassName ($value, $k) {
        $nameSpace = Runtime::isStdLib($value) ? '\o\\' : '$';
        return $this->format('######', $nameSpace, u_($value));
    }

    function pBareFun ($value, $k) {
        return $this->format('\o\OBare::###', u_($value));
    }





    // Clusters

    function pSequence ($value, $k) {
        return $this->toSequence($value, $k);
    }

    function pPair ($value, $k) {
        return $this->format("'###' => ###", $value, $k[0]);
    }




    // Operators

    function pInfix ($value, $k) {
        if ($value === '==') {
            $value = '===';
        } else if ($value === '!=') {
            $value = '!==';
        }

        $t = '(### ### ###)';
        if (in_array($value, ['+', '-', '/', '*', '%', '**', '>', '<', '>=', '<='])) {
            // Wrap Math expressions in numeric check
            $isAdd = $value === '+' ? '1' : '0';
            $t = "(\\o\\vn(###, $isAdd) ### \\o\\vn(###, $isAdd))";
        }
        return $this->format($t, $k[0], $value, $k[1]);
    }

    function pPrefix ($value, $k) {
        return $this->format('(### ###)', $value, $k[0]);
    }

    function pTernary ($value, $k) {
        return $this->format('(### ? ### : ###)', $k[0], $k[1], $k[2]);
    }

    function pValGate ($value, $k) {
        if ($value === '&&:') {
            return $this->format('(\o\Runtime::andPush(###) ? ### : \o\Runtime::andPop())', $k[0], $k[1]);
        }
        else {
            return $this->format('(### ?: ###)', $k[0], $k[1]);
        }
    }

    function pConcat ($value, $k) {
        return $this->format('\o\Runtime::concat(###, ###)', $k[0], $k[1]);
    }




    // Assignment

    function pNewVar ($value, $k) {
        return $this->format('### = ###;', $k[0], $k[1]);
    }

    function pAssign ($value, $k) {
        if ($value === '~=') {
            $value = '.=';
        }
        $t = '### ### ###;';
        if (in_array($value, ['+=', '-=', '/=', '*=', '%=', '**='])) {
            // TODO: check left-side operand for numeric value
            $isAdd = $value === '+=' ? '1' : '0';
            $t = "### ### \\o\\vn(###, $isAdd);";
        }

        return $this->format($t, $k[0], $value, $k[1]);
    }

    function pAssignAnd ($value, $k) {
        return $this->format('### = ### ? ### : ###;', $k[0], $k[0], $k[1], $k[0]);
    }

    function pAssignOr ($value, $k) {
        return $this->format('### = ### ?: ###;', $k[0], $k[0], $k[1]);
    }

    function pAssignPush ($value, $k) {
        return $this->format('### []= ###;', $k[0], $k[1]);
    }



    // Member access

    function expand ($part) {
        return explode("\t", $part);
    }

    function pCall ($value, $k) {
        if ($k[0]['value'] === 'import') {
            return $this->format('\o\OBare::u_import(__NAMESPACE__, ###)', $k[1]);
        }
        else if (substr($k[0]['value'], 0, 3) === 'fun') {
            return $this->format('$###(###)', $k[0], $k[1]);
        }
        return $this->format('###(###)', $k[0], $k[1]);
    }

    function pMemberBracket ($value, $k) {
        return $this->format('\o\v(###)[###]', $k[0], $k[1]);
    }

    function pMemberDot ($value, $k) {
        if ($k[0]['type'] === SymbolType::PACKAGE) {
            return $this->format('\o\Runtime::getModule(__NAMESPACE__, \'###\')->###', $k[0]['value'], u_($k[1]['value']));
        }
        else {
            return $this->format('\o\v(###)->###', $k[0], u_($k[1]['value']));
        }
    }


    // Functions


    function pFunction ($value, $k) {
        $closure = '';
        if (isset($k[3])) {
            $closure = $this->format('use (###)', $k[3]);
        }

        return $this->format('function ### (###) ### { ### return \o\Runtime::void(__METHOD__);}',
          $k[0], $k[1], $closure, $this->out($k[2], true) );
    }

    function pTemplate ($value, $k) {
        $closure = '';
        if (isset($k[3])) {
            $closure = $this->format('use (###)', $k[3]);
        }

        // get template type
        // TODO: move this upstream and include it in AST
        preg_match('/(' . ParserData::$TEMPLATE_TYPES .')$/i', $k[0]['value'], $m);
        $templateType = $m[1];

        return $this->format('function ### (###) ### {' .
                              '$t = \o\Runtime::openTemplate("###");###' .
                              '\o\Runtime::closeTemplate();return $t->getString();}',
                               $k[0], $k[1], $closure, $templateType, $this->out($k[2], true));
    }



    // Statements

    function pFor ($value, $k) {
        if (count($k) === 1) {
            return $this->format('while (true) {###}', $this->out($k[0], true));
        } else if (count($k) === 4) {
            return $this->format('foreach (\o\uv(###) as ### => ###) {###}', $k[2], $k[0], $k[1], $this->out($k[3], true));
        }
        else {
            return $this->format('foreach (\o\uv(###) as ###) {###}', $k[1], $k[0], $this->out($k[2], true));
        }
    }

    function pIf ($value, $k) {
        $out = $this->format('if (###) {###}', $k[0], $this->out($k[1], true));
        if (isset($k[2])) {
            $out .= ' else ';
            if ($k[2]['value'] === 'if') {
                $out .= $this->out($k[2], true);
            } else {
                $out .= $this->format('{###}', $this->out($k[2], true));
            }
        }
        return $out . "\n";
    }

	function pClass ($value, $k) {
        $className = $k[0]['value'];
		$c = $this->format('class ### extends \o\OClass {###}', u_($className), $this->out($k[1], true));
        $c .= $this->format('$### = __NAMESPACE__ . "\###";', u_($className), u_($className));
        return $c;
	}

	function pTryCatch ($value, $k) {
		$s = $this->format('try {###} catch (\Exception ###) {###}', $k[0], $k[1], $k[2]);
        if (isset($k[3])) {
            $s .= $this->format(" finally {###}", $k[3]);
        }
        return $s . "\n";
	}


    // Commands

    function pCommand ($value, $k) {
        return $this->format('###;\n', $value);
    }

    function pReturn ($value, $k) {
        if (!isset($k[0])) {
            $k[0] = '\o\Runtime::void(__METHOD__)';
        }
        return $this->format('return ###;', $k[0]);
    }
}



