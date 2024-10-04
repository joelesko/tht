<?php

namespace o;

class EmitterPHP extends Emitter {

    private $defaultArgName = '';
    private $defaultArgTypes = [];
    private $currentFunArgs = [];
    private $currentNumArgs = 0;
    private $inClosureVars = false;
    private $preKeywordDepth = 0;
    private $functionDepth = 0;
    private $classDepth = 0;
    private $numMatchPatterns = 0;
    private $importedModules = [];
    private $collectKeys = false;
    private $collectedKeys = [];
    private $currentMatchHasDefault = false;

    var $astToTarget = [

        'BOOLEAN'         => 'pBoolean',
        'NULL'            => 'pNull',
        'NUMBER'          => 'pNumber',
        'STRING'          => 'pString',
        'T_STRING'        => 'pTypeString',
        'TEM_STRING'      => 'pTemString',
        'RX_STRING'       => 'pRxString',
        'FLAG'            => 'pFlag',

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

        'KEYWORD'         => 'pKeyword',

        'MAP_PAIR'        => 'pMapPair',
        'BARE_FUN'        => 'pBareFun',
        'BARE_WORD'       => 'pBareWord',
        'TRY_CATCH'       => 'pTryCatch',
        'CALL'            => 'pCall',
        'INFIX'           => 'pInfix',
        'INFIX|=='        => 'pInfixEquals',
        'INFIX|!='        => 'pInfixEquals',
        'BITSHIFT'        => 'pBitwise',
        'BITWISE'         => 'pBitwise',
        'PREFIX'          => 'pPrefix',
        'PREFIX|...'      => 'pBarePrefix',
        'PREFIX|^^'       => 'pCatPrefix',
        'VALGATE'         => 'pValGate',
        'TERNARY'         => 'pTernary',

        'ASSIGN'          => 'pAssign',
        'ASSIGN|~='       => 'pAssignConcat',
        'ASSIGN|||='      => 'pAssignOr',
        'ASSIGN|&&='      => 'pAssignAnd',
        'ASSIGN|??='      => 'pAssignNullOr',
        'ASSIGN|#='       => 'pAssignPush',
        'ASSIGN|:='       => 'pIfAssign',
        'ASSIGN|??+='     => 'pNullAssignInc',

        'LISTFILTER'      => 'pListFilter',

        'NEW_VAR'         => 'pNewVar',
        'NEW_FUN'         => 'pFunction',
        'NEW_TEMPLATE'    => 'pTemplate',
        'NEW_CLASS'       => 'pClass',
        // 'NEW_OBJECT'      => 'pNew',
        'CLASS_PLUGIN'     => 'pClassPlugin',
        'CLASS_FIELDS'     => 'pClassFields',

        'OPERATOR|~'        => 'pConcat',
        'OPERATOR|if'       => 'pIf',
        'OPERATOR|foreach'  => 'pForEach',
        'OPERATOR|loop'     => 'pLoop',
        'OPERATOR|match'    => 'pMatch',
        'OPERATOR|lambda'   => 'pLambda',

        'CONSTANT|@'       => 'pThis',
        'CONSTANT|@@'      => 'pThisModule',

        'MEMBER|['        => 'pMemberBracket',
        'MEMBER|.'        => 'pMemberDot',
        'MEMBER|?.'       => 'pMemberNullDot',
        'MEMBER_VAR'      => 'pMemberVar',

        'COMMAND'         => 'pCommand',
        'COMMAND|return'  => 'pReturn',
        'COMMAND|>>'      => 'pShortPrint',

        'AST_LIST'        => 'pAstList',
        'AST_LIST|{'      => 'pMap',
        'AST_LIST|['      => 'pList',
        'AST_LIST|MATCH'  => 'pMatchPatternList',

        'MATCH_PAIR'      => 'pMatchPair',
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
        'n'   => 'float',
        's'   => 'string',
        'fun' => 'callable',
        'o'   => 'object',
        'any' => 'object|array|string|float|bool',
    ];

    function emit($symbolTable, $filePath) {

        $this->symbolTable = $symbolTable;

        $php = $this->out($symbolTable->getFirst());

        $relPath = Tht::getRelativePath('app', $filePath);
        $nameSpace = ModuleManager::getNamespace($relPath);
        $escNamespace = str_replace('\\', '\\\\', $nameSpace);
        $nameSpacePhp = 'namespace ' . $nameSpace
            . ";\n\\o\\ModuleManager::registerUserModule('$relPath','$escNamespace');\n";

        $finalCode = "<?php\n\n";
        $finalCode .= "declare(strict_types=1);\n";

        $finalCode .= "$nameSpacePhp\n";
        $finalCode .= "$php\n\n";

        $finalCode = $this->appendSourceMap($finalCode, $filePath);

        $finalCode .= $this->getSourceStats($filePath);
        $finalCode .= "\n";

        return $finalCode;
    }

    function toAstList($value, $kids, $multiline=false) {

        $isBlock = $value === AstList::BLOCK;
        $isMatchPattern = $value === AstList::MATCH;
        $targetSrc = [];

        foreach ($kids as $kids) {
            $dent = '';
            $out = '';
            if ($isBlock) {
                $dent = $this->indent();
            }

            if ($isMatchPattern) {
                $out = $this->pMatchPattern($kids);
            }
            else {
                $out = $this->out($kids, $isBlock);
            }

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

    function pBareWord($value, $kids) {
        return $value;
    }

    function pBoolean($value, $kids) {
        return $value;
    }

    function pNull($value, $kids) {
        return 'null';
    }

    function pNumber($value, $kids) {
        return $value;
    }

    function pString($value, $kids) {

        return $this->format("'###'", $value);
    }

    function pTypeString($value, $kids) {

        list($type, $str) = explode('::', $value, 2);

        if ($this->defaultArgName) {
            $this->defaultArgTypes []= [
                'type' => 'typestring',
                'subtype' => $type,
                'name' => $this->defaultArgName,
            ];
            return "'$str'";
        }
        else {
            return $this->format(
                '\\o\\OTypeString::create(\'###\', \'###\')',
                $type,
                $str
            );
        }
    }

    function pRxString($value, $kids) {

        list($mods, $str) = explode('::', $value, 2);

        return $this->format(
            'new \o\ORegex(\'###\', \'' . $mods . '\')',
            $str
        );
    }

    function pFlag($value, $kids) {

        $fl = ltrim($value, '-');
        $sMap = "'$fl' => true";

        if ($this->defaultArgName) {
            $this->defaultArgTypes []= [
                'type' => 'map',
                'name' => $this->defaultArgName,
            ];
            return "[$sMap]";
        }
        else {
            return "\o\OMap::create([$sMap])";
        }
    }

    function pTemString($value, $kids) {

        // Get rid of escapes for special template characters,
        // which were already applied in the Tokenizer.
        $value = str_replace('\\-', '-', $value);
        $value = str_replace('\\{', '{', $value);

        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace("'", "\\'", $value);

        return $this->format('$t->addStatic(\'###\');', $value);
    }


    // Templates

    function pTemplateExpr($value, $kids) {
        return $this->format('$t->addDynamic(###, ###, ###);', $kids[0], $kids[1], $kids[2]);
    }



    // Arrays

    function pMap($value, $kids) {

        $template = '\o\OMap::create([ ### ])';
        if ($this->defaultArgName) {
            $template = '[ ### ]';
            $this->defaultArgTypes []= [
                'type' => 'map',
                'name' => $this->defaultArgName,
            ];
        }

        return $this->format($template, $this->toAstList($value, $kids, true));
    }

    function pList($value, $kids) {

        $template = '\o\OList::create([ ### ])';
        if ($this->defaultArgName) {
            $template = '[ ### ]';
            $this->defaultArgTypes []= [
                'type' => 'list',
                'name' => $this->defaultArgName,
            ];
        }

        return $this->format($template, $this->toAstList($value, $kids, true));
    }



    // Words

    function pThis($value, $kids) {
        return '$this';
    }

    function pThisModule($value, $kids) {
       return '\o\ModuleManager::getFromLocalPath(__FILE__)';
    }

    function pUserVar($value, $kids) {

        $t = '$###';
        if ($this->inClosureVars) {
            // closure vars need to be a reference
            $t = '&$###';
        }

        return $this->format($t, u_($value));
    }

    function pMemberVar($value, $kids) {
        return $value;
    }

    function pUserFun($value, $kids) {

        if ($value === CompilerConstants::ANON) {  return '';  }

        return $this->format('###', u_($value));
    }

    function pFunArg($value, $kids) {

        $this->currentNumArgs += 1;

        // Type declaration
        $typeDecl = $this->argTypeToPhp['any'] . ' ';
        if (isset($kids[0]) && $kids[0]['type'] == 'FUN_ARG_TYPE') {
            $typeDecl = $this->format('### ', $kids[0]);
            array_shift($kids);
        }

        if (v($value)->u_ends_with('OrNull')) {
            $typeDecl .= '|null';
        }

        // Default value
        // TODO: include argument number for error message in OMap::createFromArg, etc.
        if (isset($kids[0])) {
            // mark defaults as constants, so maps and lists can
            // be wrapped inside the function body
            $this->defaultArgName = $value;
            $out = $this->format('$###=###', u_($value), $kids[0]);
            $this->defaultArgName = '';
            return $typeDecl . $out;
        }

        $this->currentFunArgs []= u_($value);

        return $typeDecl . $this->format('$###', u_($value));
    }

    function pFunArgSplat($value, $kids) {
        return $this->format('...$###', u_($value));
    }

    function pFunArgType($value, $kids) {
        $argType = $this->argTypeToPhp[$value] ?? $value;
        return $this->format('###', $argType);
    }

    function pClassName($value, $kids) {

        $nameSpace = ModuleManager::isStdLib($value) ? '\o\\' : '$';

        return $this->format('######', $nameSpace, u_($value));
    }

    function pBareFun($value, $kids) {

        return $this->format(
            "\o\ModuleManager::get('*Bare')->###",
            u_($value)
        );
    }

    // Pass through so that it's caught by the linter as a syntax error.
    function pKeyword($value, $kids) {
        return $value;
    }

    function pPreKeyword($value, $kids) {

        $this->preKeywordDepth += 1;
        if ($kids[0]['type'] == SymbolType::CLASS_FIELDS) {
            $out = $this->format('###', $kids[0]);
        }
        else {
            if ($value == 'private') {
                $value = 'protected';
            }
            if (!$this->classDepth) {
                // module level: don't output anything
                $value = '';
            }
            $out = $this->format('### ###', $value, $kids[0]);
        }
        $this->preKeywordDepth -= 1;

        return $out;
    }



    // Clusters

    function pAstList($value, $kids) {
        return $this->toAstList($value, $kids);
    }

    function pMapPair($value, $kids) {

        if ($this->collectKeys) {
            $this->collectedKeys []= $value;
        }

        return $this->format("'###' => ###", $value, $kids[0]);
    }




    // Operators

    function pInfix($value, $kids) {

        $t = '(### ### ###)';

        // if (in_array($value, ['+', '-', '/', '*', '%', '**'])) {
        //     $t = '\o\Runtime::infixMath(###, \'###\', ###)';
        // }
        // else if (in_array($value, ['>', '<', '>=', '<=', '<=>'])) {
        //     $t = '\o\Runtime::infixCompare(###, \'###\', ###)';
        // }
        if (in_array($value, ['&&', '||'])) {
            $t = '(\o\Runtime::truthy(###) ### \o\Runtime::truthy(###))';
        }

        return $this->format($t, $kids[0], $value, $kids[1]);
    }

    function pInfixEquals($value, $kids) {

        // Perf: If we're comparing against certain literals, just inline triple-equals.
        if ($this->isStrictLiteralToken($kids[0]) || $this->isStrictLiteralToken($kids[1])) {
            if ($value == '==') {
                $t = '(### === ###)';
            }
            else if ($value == '!=') {
                $t = '(### !== ###)';
            }
        }
        else {
            if ($value == '==') {
                $t = '\o\Runtime::infixEquals(###, ###)';
            }
            else if ($value == '!=') {
                $t = '!\o\Runtime::infixEquals(###, ###)';
            }
        }

        return $this->format($t, $kids[0], $kids[1]);
    }

    // Numbers don't count because we need '==' to compare floats with ints.
    function isStrictLiteralToken($token) {
        if ($token['type'] == 'STRING') { return true; }
        return $token['type'] == 'WORD' && in_array($token['value'], ['null', 'true', 'false', 'pending']);
    }

    function pBitwise($value, $kids) {

        $phpOp = $this->bitwiseToPhp[$value];
        return $this->pInfix($phpOp, $kids);
    }

    function pPrefix($value, $kids) {

        if ($value == '+~') {
            $value = '~';
        }

        if ($value == '!') {
            return $this->format('(!\o\Runtime::truthy(###))', $kids[0]);
        }

        return $this->format('(### ###)', $value, $kids[0]);
    }

    function pBarePrefix($value, $kids) {
        return $this->format('######', $value, $kids[0]);
    }

    function pCatPrefix($value, $kids) {
        return '"MEOW"';
    }

    function pQualifier($value) {
        return $this->format('###', $value);
    }

    function pTernary($value, $kids) {
        return $this->format('(\o\Runtime::truthy(###) ? ### : ###)', $kids[0], $kids[1], $kids[2]);
    }

    function pValGate($value, $kids) {

        if ($value === '&&:') {
            return $this->format(
                '(\o\Runtime::andPush(###) ? ### : \o\Runtime::andPop())',
                $kids[0],
                $kids[1]
            );
        }
        else if ($value === '??:') {
            return $this->format(
                '(### ?? ###)',
                $kids[0],
                $kids[1]
            );
        }
        else {
            // ||:
            return $this->format(
                '(\o\Runtime::truthy(###) ?: ###)',
                $kids[0],
                $kids[1])
            ;
        }
    }

    // function pListFilter($value, $kids) {
    //     return $this->format('\o\Runtime::listFilter(###, function ($u_a, $u_i) { return ###; })', $kids[0], $kids[1]);
    // }

    function pConcat($value, $kids) {

        //return $this->format('(\o\v(###)->u_to_string() . \o\v(###)->u_to_string())', $kids[0], $kids[1]);
        //return $this->format("(### . ###)", $kids[0], $kids[1]);
        return $this->format('\o\Runtime::concat(###, ###)', $kids[0], $kids[1]);
    }

    function pMatch($value, $kids) {

        $this->currentMatchHasDefault = false;

        $subject = array_shift($kids);
        $out = $this->format('match (($__matchSubject = ###) ? true : true) {', $subject);

        // TODO: errors still point to inner pattern instead of `match` keyword
        $topLm = $this->lineMarker(-1);

        foreach ($kids as $kid) {
            $out .= $this->format('###', $kid);
        }

        if (!$this->currentMatchHasDefault) {
            $out .= "default => \o\Runtime::matchDie(\$__matchSubject),";
        }

        $out .= '};';
        $out .= $topLm;

        return $out;
    }

    function pMatchPair($value, $kids) {

        $patternList = $kids[0]; // ast list
        $value = $kids[1];

        return $this->format('### => ###,', $patternList, $value);
    }

    function pMatchPatternList($value, $kids) {
        return $this->toAstList($value, $kids);
    }

    function pMatchPattern($pattern) {

        if ($pattern['value'] == 'default' && $pattern['type'] == 'CONSTANT') {
            $this->currentMatchHasDefault = true;
            return 'default';
        }
        else {
            return $this->format('($__matchSubject === ###)', $pattern);
        }
    }



    // Assignment

    function pNewVar($value, $kids) {
        return $this->format('### = ###;', $kids[0], $kids[1]);
    }

    function pAssign($value, $kids) {

        $t = '### ### ###;';

        // TODO: assert strings
        if ($value === '~=') {
            $value = '.=';
        }

        // if (in_array($value, ['+=', '-=', '/=', '*=', '%=', '**='])) {
        //     $t = '### = \o\Runtime::infixMath(###, \'###\', ###);';
        //     return $this->format($t, $kids[0], $kids[0], $value, $kids[1]);
        // }

        // fields default to private
        if ($this->classDepth == 1 && $this->functionDepth == 0 &&
            $this->preKeywordDepth == 0) {

            $t = 'protected ' . $t;
        }

        return $this->format($t, $kids[0], $value, $kids[1]);
    }

    function pAssignAnd($value, $kids) {
        return $this->format('### = \o\Runtime::truthy(###) ? ### : ###;', $kids[0], $kids[0], $kids[1], $kids[0]);
    }

    function pAssignOr($value, $kids) {
        return $this->format('### = \o\Runtime::truthy(###) ? ### : ###;', $kids[0], $kids[0], $kids[0], $kids[1]);
    }

    function pAssignNullor($value, $kids) {
        return $this->format('### = ### ?? ###;', $kids[0], $kids[0], $kids[1]);
    }

    function pAssignPush($value, $kids) {
        return $this->format('### []= ###;', $kids[0], $kids[1]);
    }

    function pAssignConcat($value, $kids) {
        return $this->format('### = ### . ###;', $kids[0], $kids[0], $kids[1]);
       // return $this->format('### = \o\Runtime::concat(###, ###);', $kids[0], $kids[0], $kids[1]);
    }

    function pIfAssign($value, $kids) {
        return $this->format('(### = ###)', $kids[0], $kids[1]);
    }

    function pNullAssignInc($value, $kids) {
        return $this->format('### ??= 0; ### += ###;', $kids[0], $kids[0], $kids[1]);
    }



    // Member access

    function pCall($value, $kids) {

        // Object initializer: e.g. $user = User()
        if (preg_match('/^[A-Z]/', $kids[0]['value'])) {
            return $this->pNew($value, $kids);
        }

        return $this->format('###(###)', $kids[0], $kids[1]);
    }

    function pMemberBracket($value, $kids) {
        return $this->format('\o\v(###)[###]', $kids[0], $kids[1]);
    }

    function pMemberDot($value, $kids) {

        // Have to insert newlines and line markers to enable sourcemapping
        // across chained method calls.  This isn't great, but it works.
        // Not sure why the line marker needs to come before newline.
        // TODO: Would be nice to have a universal solution for line mapping for all
        //   statements/expressions that span multiple lines.
        $lm = $this->lineMarker(-1);

        return $this->format('\o\v(###)' . $lm . '\n->###', $kids[0], u_($kids[1]['value']));
    }

    function pMemberNullDot($value, $kids) {
        $lm = $this->lineMarker(-1);
        return $this->format('\o\vnullsafe(###)' . $lm . '\n?->###', $kids[0], u_($kids[1]['value']));
    }

    function pPackage($value, $kids) {
        return $this->format('\o\ModuleManager::get(\'###\')', $value);
    }

    function pFullPackage($value, $kids) {
        return $this->format('###', $value);
    }


    // Functions


    function pFunction($value, $kids) {

        $closure = '';
        if (isset($kids[3])) {
            $this->inClosureVars = true;
            $closure = $this->format('use (###)', $kids[3]);
            $this->inClosureVars = false;
        }

        $this->currentNumArgs = 0;
        $this->functionDepth += 1;

        $t = "function ### (###) ### {\n %!ARG_CHECK% %!WRAP% %!CLONE% %!LAMBDA_ABC% ### return NULL_NORETURN; }";

        // If class method, default to 'private'
        if ($this->classDepth == 1 && $this->functionDepth == 1 &&
            $this->preKeywordDepth == 0 && $kids[0]['value'] !== 'new') {
            $t = 'private ' . $t;
        }

        $out = $this->format($t, $kids[0], $kids[1], $closure, $this->out($kids[2], true));

        $out .= $this->exportFunction($kids[0]);

        $this->functionDepth -= 1;

        $numArgs = $this->currentNumArgs;
        //if ($numArgs) {
            $funName = $kids[0]['value'];
            $out = preg_replace('/%!ARG_CHECK%/', '\o\Runtime::checkNumArgs("' . $funName . '", ' . $numArgs . ', func_num_args());', $out, 1);
        // }
        // else {
        //     // Allow variable args if sig is empty
        //     $out = preg_replace('/%!ARG_CHECK%/', '', $out);
        // }

        // wrap any lists or maps that come in as an argument
        $objectWrappers = '';
        foreach ($this->defaultArgTypes as $defaultArg) {
            $varName = '$' . u_($defaultArg['name']);
            $fnName = $kids[0]['value'];
            if ($defaultArg['type'] == 'map') {
                $objectWrappers .= "$varName = \o\OMap::createFromArg('$fnName', $varName);\n";
            }
            else if ($defaultArg['type'] == 'list') {
                $objectWrappers .= "$varName = \o\OList::createFromArg('$fnName', $varName);\n";
            }
            else if ($defaultArg['type'] == 'typestring') {
                $subtype = $defaultArg['subtype'];
                $objectWrappers .= "$varName = \o\OTypeString::create('$subtype', $varName);\n";
            }
        }
        $this->defaultArgTypes = [];
        $out = preg_replace('/%!WRAP%/', $objectWrappers, $out, 1);


        // Clone objects coming in for pass-by-copy
        $cloneWrappers = $this->getPassByCopy();
        $out = preg_replace('/%!CLONE%/', $cloneWrappers, $out, 1);

        // Create implicit $a, $b, $c for anon functions
        $implicitArgs = '';
        if ($kids[0]['value'] == CompilerConstants::ANON) {
            $implicitArgs = '$_all = func_get_args(); if (isset($_all[0])) { $u_a = func_get_arg(0);  if (isset($_all[1])) { $u_b = func_get_arg(1); if (isset($_all[2])) { $u_c = func_get_arg(2); }}}';
        }
        $out = preg_replace('/%!LAMBDA_ABC%/', $implicitArgs, $out, 1);

        $out = $this->getFnMarker('fun', $kids[0]) . $out;

        return $out;
    }

    function getFnMarker($keyword, $tName) {

        if (!$tName || $tName['value'] == CompilerConstants::ANON) {
            return '';
        }

        return "\n\n\n// $keyword " . $tName['value']
            . "\n//-------------------------------------------\n";
    }

    function pTemplate($value, $kids) {

        $closure = '';
        if (isset($kids[4])) {
            $closure = $this->format('use (###)', $kids[4]);
        }

        $name = $kids[0];
        $type = $kids[1];
        $args = $kids[2];
        $block = $kids[3];

        $template = 'function ### (###) ### { %!CLONE% ' .
                    '$t = \o\Runtime::openTemplate(###); ### ' .
                    '\o\Runtime::closeTemplate(); return $t->getString(); }';

        $out = $this->format(
            $template,
            $name,
            $args,
            $closure,
            $type,
            $this->out($block, true)
        );

        // Clone objects coming in for pass-by-copy
        $cloneWrappers = $this->getPassByCopy();
        $out = preg_replace('/%!CLONE%/', $cloneWrappers, $out, 1);

        $out .= $this->exportFunction($kids[0]);

        $out = $this->getFnMarker('tem', $kids[0]) . $out;

        return $out;
    }

    function getPassByCopy() {

        $cloneWrappers = '';
        foreach ($this->currentFunArgs as $arg) {
            $cloneWrappers .= '$' . $arg . ' = \o\v($' . $arg . ')->u_z_clone();' . "\n";
        }
        $this->currentFunArgs = [];

        return $cloneWrappers;
    }

    function exportFunction($fnName) {

        if ($fnName['value'] != CompilerConstants::ANON && $this->preKeywordDepth && !$this->classDepth) {
            $export = $this->format("->exportFunction('###')", $fnName);
            return $this->pThisModule('', '') . $export . ";";
        }

        return '';
    }




    // Statements

    function pForEach($value, $kids) {

        if (count($kids) === 4) {
            // key/value iterator
            return $this->format(
                'foreach (### as ### => ###) {###}',
                $kids[0],
                $kids[1],
                $kids[2],
                $this->out($kids[3], true)
            );
        }
        else {

            return $this->format(
                'foreach (### as ###) {###}',
                $kids[0],
                $kids[1],
                $this->out($kids[2], true)
            );
        }
    }

    function pLoop($value, $kids) {
        return $this->format('while (true) {###}', $this->out($kids[0], true));
    }

    function pIf($value, $kids) {

        $elseIfIndex = 2;
        if ($kids[1]['type'] == 'USER_VAR') {
            // handle if-assignment via "as"
            // ex: if getThing() as $thing { ...
            $elseIfIndex += 1;
            $out = $this->format('if (\o\Runtime::truthy(### = (###))) {###}', $kids[1], $kids[0], $this->out($kids[2], true));
        }
        else {
            $out = $this->format('if (\o\Runtime::truthy(###)) {###}', $kids[0], $this->out($kids[1], true));
        }

        // else / else if
        if (isset($kids[$elseIfIndex])) {
            $out .= ' else ';
            if ($kids[$elseIfIndex]['value'] === 'if') {
                $out .= $this->out($kids[$elseIfIndex], true);
            } else {
                $out .= $this->format('{###}', $this->out($kids[$elseIfIndex], true));
            }
        }

        return $out . "\n";
    }

    function pClass($value, $kids) {

        $className = $kids[0]['value'];
       // $extends = $kids[1]['value'];
        $implements = $kids[2]['value'];
        $block = $kids[3];

        $extends = '\o\OClass';

        $this->classDepth += 1;

        $c = $this->format(
            "### ### extends ### {###}",
            $value,
            u_($className),
            $extends,
            $this->out($block, true)
        );

        $this->classDepth -= 1;

        return $c;
    }

    function pClassPlugin($value, $kids) {

        $method = '___init_embedded_objects';
        $innerMethod = '$this->addEmbeddedObjects';
        $s = $this->format(
            'protected function ' . $method . '() { ' . $innerMethod . '(###); }'
            , $kids[0]
        );

        return $s;
    }

    function pClassFields($value, $kids) {

        $fnName = '';
        if ($this->preKeywordDepth > 0) {
            $fnName = "___init_public_fields";
        }
        else {
            $fnName = "___init_fields";
        }
        $this->collectKeys = true;
        $this->collectedKeys = [];
        $s = $this->format("protected function $fnName() { return ###; }", $kids[0]);

        foreach ($this->collectedKeys as $kids) {
            $s .= "protected $" . u_($kids) . " = 0;\n";
        }

        return $s;
    }

    function pTryCatch($value, $kids) {

        $s = $this->format(
            'try {###} catch (\Exception ###) {###}',
            $kids[0],
            $kids[1],
            $kids[2]
        );

        if (isset($kids[3])) {
            $s .= $this->format(" finally {###}", $kids[3]);
        }

        return $s . "\n";
    }

    function pNew($value, $kids) {

        $className = $kids[0]['value'];
        $c = $this->format(
            '\o\ModuleManager::newObject("###", [###])',
            $className,
            $this->out($kids[1], true)
        );

        return $c;
    }

    // Commands

    function pCommand($value, $kids) {
        return $this->format('###;\n', $value);
    }

    function pReturn($value, $kids) {

        if (!isset($kids[0])) {
            $kids[0] = 'NULL_NORETURN';
        }

        return $this->format('return ###;', $kids[0]);
    }

    function pShortPrint($value, $kids) {
        return $this->format(
            "\o\ModuleManager::get('*Bare')->u_print(###);",
            $kids[0]
        );
    }

    function pLambda($value, $kids) {

        // $closureVars = '';
        // if (isset($kids[1])) {
        //     $this->inClosureVars = true;
        //     $closureVars = $this->format('use (###)', $kids[1]);
        //     $this->inClosureVars = false;
        // }

        return $this->format(
            "fn (\$u_a=null, \$u_b=null, \$u_c=null) => ###",
            $kids[0]
        );


        // Note: this overwrites any outer vars named $a, $b, or $c
        // TODO: only declare the arguments used inside the expression
        // return $this->format(
        //     "function () $closureVars { \$la = func_get_args(); \$u_a = array_shift(\$la); \$u_b = array_shift(\$la); \$u_c = array_shift(\$la); return ###; }",
        //     $kids[0]
        // );
    }
}

