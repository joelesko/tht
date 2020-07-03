<?php

namespace o;

class EmitterPHP extends Emitter {

    private $constantContext = '';
    private $constantValues = [];
   // private $isPhpLiteral = false;
    private $inClosureVars = false;
    private $preKeywordDepth = 0;
    private $functionDepth = 0;
    private $classDepth = 0;
    private $importedModules = [];
    private $collectKeys = false;
    private $collectedKeys = [];

    var $astToTarget = [

        'FLAG'            => 'pFlag',
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
        'FUN_ARG_SPLAT'   => 'pFunArgSplat',
        'FUN_ARG_TYPE'    => 'pFunArgType',
        'PACKAGE'         => 'pPackage',
        'FULL_PACKAGE'    => 'pFullPackage',
        'PRE_KEYWORD'     => 'pPreKeyword',

        'PAIR'            => 'pPair',
        'BARE_FUN'        => 'pBareFun',
        'BARE_WORD'       => 'pBareWord',
        'TRY_CATCH'       => 'pTryCatch',
        'CALL'            => 'pCall',
        'INFIX'           => 'pInfix',
        'INFIX|<=>'       => 'pSpaceship',
        'BITSHIFT'        => 'pBitwise',
        'BITWISE'         => 'pBitwise',
        'PREFIX'          => 'pPrefix',
        'PREFIX|...'      => 'pBarePrefix',
        'PREFIX|^^'       => 'pCatPrefix',
        'VALGATE'         => 'pValGate',
        'TERNARY'         => 'pTernary',

        'ASSIGN'          => 'pAssign',
        'ASSIGN|||='      => 'pAssignOr',
        'ASSIGN|&&='      => 'pAssignAnd',
        'ASSIGN|#='       => 'pAssignPush',
        'METHOD_ASSIGN'   => 'pMethodAssign',

        'NEW_VAR'         => 'pNewVar',
        'NEW_FUN'         => 'pFunction',
        'NEW_TEMPLATE'    => 'pTemplate',
        'NEW_CLASS'       => 'pClass',
     //   'NEW_OBJECT'      => 'pNew',
     //   'NEW_OBJECT_VAR'  => 'pNewObjectVar',
        'CLASS_PLUGIN'     => 'pClassPlugin',
        'CLASS_FIELDS'     => 'pClassFields',

        'OPERATOR|~'        => 'pConcat',
        'OPERATOR|if'       => 'pIf',
        'OPERATOR|foreach'  => 'pForEach',
        'OPERATOR|loop'     => 'pLoop',
        'OPERATOR|match'    => 'pMatch',
        'OPERATOR|lambda'   => 'pLambda',

      //  'CONSTANT|this'   => 'pThis',
        'CONSTANT|@'      => 'pThis',
        'CONSTANT|@@'     => 'pThisModule',

        'MEMBER|['        => 'pMemberBracket',
        'MEMBER|.'        => 'pMemberDot',
        'MEMBER_VAR'      => 'pMemberVar',

        'COMMAND'         => 'pCommand',
        'COMMAND|return'  => 'pReturn',
     //   'COMMAND|R'       => 'pReturn',

        'AST_LIST'        => 'pAstList',
        'AST_LIST|{'      => 'pMap',
        'AST_LIST|['      => 'pList',
        'MATCH_PATTERN'   => 'pMatchPattern',
    ];

    private $bitwiseToPhp = [
        '+&' => '&',
        '+|' => '|',
        '+^' => '^',
        '+>' => '>>',
        '+<' => '<<',
    ];

    private $argTypeToPhp = [
        'b'   => 'bool',
        'l'   => '\o\OList',
        'm'   => '\o\OMap',
        'i'   => 'int',
        'f'   => 'float',
        's'   => 'string',
        'fn'  => 'callable',
        'o'   => 'object',
        'any' => '',
    ];

    function emit ($symbolTable, $filePath) {

        $this->symbolTable = $symbolTable;

        $php = $this->out($symbolTable->getFirst());

        $relPath = Tht::getRelativePath('app', $filePath);
        $nameSpace = ModuleManager::getNamespace($relPath);
        $escNamespace = str_replace('\\', '\\\\', $nameSpace);
        $nameSpacePhp = 'namespace ' . $nameSpace . ";\n\\o\\ModuleManager::registerUserModule('$relPath','$escNamespace');\n";

        $finalCode = "<?php\n\n";

        if (PHP_VERSION_ID >= 70100) {
            $finalCode .= "declare(strict_types=1);\n";
        }

        $finalCode .= "$nameSpacePhp\n";
        $finalCode .= "$php\n\n";
        $finalCode = $this->appendSourceMap($finalCode, $filePath);
        $finalCode .= $this->getSourceStats($filePath);
        $finalCode .= "\n";

        return $finalCode;
    }

    function toAstList ($value, $kids, $multiline=false) {
        $isBlock = $value === AstList::BLOCK;
        $targetSrc = [];
        foreach ($kids as $k) {
            $dent = '';
            if ($isBlock) {
                $dent = $this->indent();
            }
            $out = $this->out($k, $isBlock);
            // if ($isBlock && !preg_match('/;\s*$/', $out)) {
            //     // need to force this to correctly report errors with malformed statements that don't get a semicolon
            //     $out .= ";\n";
            // }
            $targetSrc []= $dent . $out;
        }

        $delim = $isBlock ? '' : ",";
        $pre = '';
        $post = '';
        if ($multiline) {
            // adding newlines to preserve line number mapping
            $delim .= "\n";
            $pre = "\n";
            $post = "\n";
        }

        return $pre . implode($delim, $targetSrc) . $post;
    }

    // Simple

    function pBareWord ($value, $k) {
        return $value;
    }

    function pFlag ($value, $k) {
        return $value;
    }

    function pNumber ($value, $k) {
        return $value;
    }

    function pString ($value, $k) {
        $value = $this->escapeString($value);
        return $this->format('"###"', $value);
    }

    function pLString ($value, $k) {
        $value = $this->escapeString($value);
        list($type, $str) = explode('::', $value, 2);
        return $this->format('\\o\\OTypeString::create("###", "###")', $type, $str);
    }

    function pRString ($value, $k) {
        $value = $this->escapeString($value);
        return $this->format('new \o\ORegex("###")', $value);
    }

    function pTString ($value, $k) {
        $value = str_replace('\\', '\\\\', $value);
        $value = $this->escapeString($value);
        return $this->format('$t->addStatic("###");', $value);
    }

    function escapeString($s) {
        $s = str_replace('$', '\\$', $s);
        $s = str_replace('"', '\\"', $s);
        return $s;
    }



    // Templates

    function pTemplateExpr ($value, $k) {
        return $this->format('$t->addDynamic(###, ###);', $k[0], $k[1]);
    }



    // Arrays

    function pMap ($value, $k) {
        $template = '\o\OMap::create([ ### ])';
        if ($this->constantContext) {
            $template = '[ ### ]';
            $this->constantValues []= [
                '___type' => 'map',
                'name' => $this->constantContext
            ];
        }
        // else if ($this->isPhpLiteral) {
        //     $template = '[ ### ]';
        // }
        return $this->format($template, $this->toAstList($value, $k, true));
    }

    function pList ($value, $k) {

        $template = '\o\OList::create([ ### ])';
        if ($this->constantContext) {
            $template = '[ ### ]';
            $this->constantValues []= [
                '___type' => 'list',
                'name' => $this->constantContext
            ];
        }
        // else if ($this->isPhpLiteral) {
        //     $template = '[ ### ]';
        // }

       return $this->format($template, $this->toAstList($value, $k, true));
    }



    // Words

    function pThis ($value, $k) {
        return '$this';
    }

    function pThisModule ($value, $k) {
       return '\o\ModuleManager::getFromLocalPath(__FILE__)';
    }

    function pUserVar ($value, $k) {

        // if ($value == '') {
        //     return '$this';
        // }
        // if ($value == '$') {
        //     return '\o\ModuleManager::getFromNamespace(__NAMESPACE__)';
        // }

        $t = '$###';
        if ($this->inClosureVars) {
            // closure vars need to be a reference
            $t = '&$###';
        }
        return $this->format($t, u_($value));
    }

    function pMemberVar ($value, $k) {
        return $value;
    }

    function pUserFun ($value, $k) {
        if ($value === CompilerConstants::$ANON) {  return '';  }
        return $this->format('###', u_($value));
    }

    function pFunArg ($value, $k) {

        // Type declaration
        $typeDec = '';
        if (isset($k[0]) && $k[0]['type'] == 'FUN_ARG_TYPE') {
            if (PHP_VERSION_ID >= 70100) {
                $typeDec = $this->format('### ', $k[0]);
            }
            array_shift($k);
        }

        // Default value
        if (isset($k[0])) {
            // mark defaults as constants, so maps and lists can be wrapped inside the function body
            $this->constantContext = $value;
            $out = $this->format('$###=###', u_($value), $k[0]);
            $this->constantContext = '';
            return $typeDec . $out;
        }

        return $typeDec . $this->format('$###', u_($value));
    }

    function pFunArgSplat ($value, $k) {
        return $this->format('...$###', u_($value));
    }

    function pFunArgType ($value, $k) {
        return $this->format('###', $this->argTypeToPhp[$value]);
    }

    function pClassName ($value, $k) {
        $nameSpace = ModuleManager::isStdLib($value) ? '\o\\' : '$';
        return $this->format('######', $nameSpace, u_($value));
    }

    function pBareFun ($value, $k) {
        return $this->format("\o\ModuleManager::get('*Bare')->###", u_($value));
    }

    function pPreKeyword ($value, $k) {
        $this->preKeywordDepth += 1;
        if ($k[0]['type'] == SymbolType::CLASS_FIELDS) {
            $out = $this->format('###', $k[0]);
        }
        else {
            if ($value == 'private') {
                $value = 'protected';
            }
            if (!$this->classDepth) {
                // module level: don't output anything
                $value = '';
            }
            $out = $this->format('### ###', $value, $k[0]);
        }
        $this->preKeywordDepth -= 1;
        return $out;
    }



    // Clusters

    function pAstList ($value, $k) {
        return $this->toAstList($value, $k);
    }

    function pPair ($value, $k) {
        if ($this->collectKeys) {
            $this->collectedKeys []= $value;
        }
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

    function pSpaceship ($value, $k) {
        return $this->format('\o\Runtime::spaceship(###, ###)', $k[0], $k[1]);
    }

    function pBitwise ($value, $k) {
        $phpOp = $this->bitwiseToPhp[$value];
        return $this->pInfix($phpOp, $k);
    }

    function pPrefix ($value, $k) {
        if ($value == '+~') {
            $value = '~';
        }
        return $this->format('(### ###)', $value, $k[0]);
    }

    function pBarePrefix ($value, $k) {
        return $this->format('######', $value, $k[0]);
    }

    function pCatPrefix ($value, $k) {
        return '"MEOW"';
    }

    function pQualifier ($value) {
        return $this->format('###', $value);
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

    function pMatch ($value, $k) {
        $subject = array_shift($k);
        $out = $this->format('$_match = ###;', $subject);
        $this->numMatchPatterns = 0;
        foreach ($k as $kid) {
            $this->numMatchPatterns += 1;
            $out .= $this->format('###', $kid);
        }
        return $out;
    }

    function pMatchPattern ($value, $k) {
        $else = $this->numMatchPatterns > 1 ? 'else ' : '';
        if ($k[0]['value'] == 'true' || $k[0]['value'] == 'false') {
            return $this->format($else . 'if (###) { ###; }', $k[0], $k[1]);
        }
        return $this->format($else . 'if (\o\Runtime::match($_match, ###)) { ###; }', $k[0], $k[1]);
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
            $isAdd = $value === '+=' ? 'true' : 'false';
            $t = "### ### \\o\\vn(###, $isAdd);";
        }

        // fields default to private
        if ($this->classDepth == 1 && $this->functionDepth == 0 && $this->preKeywordDepth == 0) {
            $t = 'protected ' . $t;
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

        if (preg_match('/^[A-Z]/', $k[0]['value'])) {
            return $this->pNew($value, $k);
        }

        if ($k[0]['value'] === 'import') {
            $k[0] = $this->pBareFun('import', $k[1]);
            $path = $this->format('###(###)', $k[0], $k[1]);
        }
        return $this->format('###(###)', $k[0], $k[1]);
    }

    function pMemberBracket ($value, $k) {
        return $this->format('\o\v(###)[###]', $k[0], $k[1]);
    }

    function pMemberDot ($value, $k) {
        return $this->format('\o\v(###)->###', $k[0], u_($k[1]['value']));
    }

    function pPackage($value, $k) {
        return $this->format('\o\ModuleManager::get(\'###\')', $value);
    }

    function pFullPackage($value, $k) {
        return $this->format('###', $value);
    }


    // Functions


    function pFunction ($value, $k) {

        $closure = '';
        if (isset($k[3])) {
            $this->inClosureVars = true;
            $closure = $this->format('use (###)', $k[3]);
            $this->inClosureVars = false;
        }

       // $this->isPhpLiteral = false;
        $this->functionDepth += 1;

        $t = 'function ### (###) ### { %!WRAP% %!IMPLICIT% ### return \o\ONothing::create(__METHOD__); }';

        // If class method, default to 'private'
        if ($this->classDepth == 1 && $this->functionDepth == 1 && $this->preKeywordDepth == 0 && $k[0]['value'] !== 'new') {
            $t = 'private ' . $t;
        }

        $out = $this->format($t, $k[0], $k[1], $closure, $this->out($k[2], true));

        $out .= $this->exportFunction($k[0]);

        $this->functionDepth -= 1;

        // wrap any lists or maps that come in as an argument
        $objectWrappers = '';
        foreach ($this->constantValues as $cv) {
            $varName = '$' . u_($cv['name']);
            if ($cv['___type'] == 'map') {
                $objectWrappers .= "$varName = is_object($varName) ? $varName : \o\OMap::create($varName);\n";
            }
            else {
                $objectWrappers .= "$varName = is_object($varName) ? $varName : \o\OList::create($varName);\n";
            }
        }
        $this->constantValues = [];
        $out = preg_replace('/%!WRAP%/', $objectWrappers, $out, 1);

        // Create implicit $a, $b, $c for anon functions
        $implicitArgs = '';
        if ($k[0]['value'] == '(ANON)') {
            $implicitArgs = '$_all = func_get_args(); if (isset($_all[0])) { $u_a = func_get_arg(0);  if (isset($_all[1])) { $u_b = func_get_arg(1); if (isset($_all[2])) { $u_c = func_get_arg(2); }}}';
        }
        $out = preg_replace('/%!IMPLICIT%/', $implicitArgs, $out, 1);

        return $out;
    }

    function pTemplate ($value, $k) {
        $closure = '';
        if (isset($k[3])) {
            $closure = $this->format('use (###)', $k[3]);
        }

        // get template type
        // TODO: move this upstream and include it in AST
        preg_match('/(' . CompilerConstants::$TEMPLATE_TYPES .')$/i', $k[0]['value'], $m);
        $templateType = $m[1];

        $template = 'function ### (###) ### {' .
                    '$t = \o\Runtime::openTemplate("###");###' .
                    '\o\Runtime::closeTemplate();return $t->getString();}';

        $out = $this->format($template, $k[0], $k[1], $closure, $templateType, $this->out($k[2], true));

        $out .= $this->exportFunction($k[0]);

        return $out;
    }

    function exportFunction($fnName) {
        if ($this->preKeywordDepth && !$this->classDepth) {
            $export = $this->format("->exportFunction('###')", $fnName);
            return $this->pThisModule('', '') . $export . ";";
        }
        return '';
    }



    // Statements

    function pForEach ($value, $k) {
        if (count($k) === 4) {
            // key/value iterator
            return $this->format('foreach (### as ### => ###) {###}', $k[0], $k[1], $k[2], $this->out($k[3], true));
        }
        else {
            // Perf: covert range() loop into C-style 'for' loop
            // (4x faster than generator, 25x faster than array)
            // Only willing to do this because loops are a common bottleneck
            if ($k[0]['value'] == '(') {
                $iterKids = $this->getKidsForNode($k[0]);
                if ($iterKids[0]['value'] == 'range') {

                    $argList = $this->getKidsForNode($iterKids[1]);
                    $alias = $k[1];
                    $initial = $this->out($argList[0]);
                    $max = $this->out($argList[1]);
                    $step = isset($argList[2]) ? $this->out($argList[2]) : 1;
                    $block = $this->out($k[2], true);

                    return $this->format('for (### = ###; ### <= ###; ### += ###) {###}',
                         $alias, $initial, $alias, $max, $alias, $step, $block);
                }
            }

            return $this->format('foreach (### as ###) {###}', $k[0], $k[1], $this->out($k[2], true));
        }
    }

    function pLoop ($value, $k) {
        return $this->format('while (true) {###}', $this->out($k[0], true));
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
        $extends = $k[1]['value'];
        $implements = $k[2]['value'];
        $block = $k[3];

        if (!$extends) {
            $extends = '\o\OClass';
        }
        else {
            $extends = '\tht\modules\ParentClass_x\\' . u_($extends);
        }

       // $this->isPhpLiteral = true;
        $this->classDepth += 1;
        $c = $this->format("### ### extends ### {###}", $value, u_($className), $extends, $this->out($block, true));
        $this->classDepth -= 1;

        return $c;
    }

    function pClassPlugin ($value, $k) {
        $method = $value == 'outer' ? '___init_outer_objects' : '___init_inner_objects';
        $innerMethod = $value == 'outer' ? '$this->addOuterObjects' : '$this->addInnerObjects';
        $s = $this->format('protected function ' . $method . '() { ' . $innerMethod . '(###); }', $k[0]);
        return $s;
    }

    function pClassFields ($value, $k) {
        $fnName = '';
        if ($this->preKeywordDepth > 0) {
            $fnName = "___init_public_fields";
        }
        else {
            $fnName = "___init_fields";
        }
        $this->collectKeys = true;
        $this->collectedKeys = [];
        $s = $this->format("protected function $fnName() { return ###; }", $k[0]);

      //  $visibility = $this->preKeywordDepth > 0 ? 'public' : 'protected';
        foreach ($this->collectedKeys as $k) {
            $s .= "protected $" . u_($k) . " = 0;\n";
        }

        return $s;
    }

    function pTryCatch ($value, $k) {
        $s = $this->format('try {###} catch (\Exception ###) {###}', $k[0], $k[1], $k[2]);
        if (isset($k[3])) {
            $s .= $this->format(" finally {###}", $k[3]);
        }
        return $s . "\n";
    }

    function pNew ($value, $k) {
        $className = $k[0]['value'];
        $c = $this->format('\o\ModuleManager::newObject("###", [###])', $className, $this->out($k[1], true));
        return $c;
    }

    // Commands

    function pCommand ($value, $k) {
        return $this->format('###;\n', $value);
    }

    function pReturn ($value, $k) {
        if (!isset($k[0])) {
            $k[0] = '\o\ONothing::create(__METHOD__)';
        }
        return $this->format('return ###;', $k[0]);
    }

    function pLambda ($value, $k) {
        return $this->format("function (\$u_a='',\$u_b='',\$u_c='') { return ###; }", $k[0]);
    }
}

