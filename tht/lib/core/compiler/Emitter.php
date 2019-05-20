<?php

namespace o;

class Emitter {

    protected $symbolTable = null;
    public $indentLevel = 0;
    protected $prevLineMarker = 0;

    public function indent () {
        return str_repeat('    ', $this->indentLevel);
    }

    function error ($msg, $token) {
        ErrorHandler::handleCompilerError($msg, $token, Compiler::getCurrentFile());
    }

    function appendSourceMap ($targetSrc, $sourceFile) {
        $lines = explode("\n", $targetSrc);
        $sourceMap = new SourceMap ($sourceFile);
        $fnFind = function ($m) use ($sourceMap) {
            $sourceMap->set(\floor($m[1]));
            return '';
        };
        $out = [];
        foreach ($lines as $line) {
            $out []= preg_replace_callback('/!__L:(\d*)__!/', $fnFind, $line);
            $sourceMap->next();
        }

        $out []= $sourceMap->out();

        return implode("\n", $out);
    }

    function getSourceStats($sourceFile) {
        $sa = new SourceAnalyzer ($sourceFile);
        return $sa->analyze(true);
    }

    function lineMarker ($lineNum) {
        if ($lineNum) {
            $this->prevLineMarker = $lineNum;
            return "!__L:" . $lineNum . "__!";
        }
        return "";
    }

    function format () {
        $args = func_get_args();

        $template = array_shift($args);

        $template = str_replace('\\n', "\n", $template);
        $template = str_replace('}', "\n}\n", $template);
        $template = str_replace('{', "{\n", $template);
        $template = str_replace(';', ";\n", $template);
        $template = preg_replace('/\n{2,}/', "\n", $template);

        foreach ($args as $a) {
            if (is_array($a)) {
                $a = $this->out($a);
            }
            $pos = strpos($template, '###');
            $template = substr_replace($template, $a, $pos, 3);
        }

        return $template;
    }

    function out ($node, $inBlock=false) {

        $key = implode([ $node['type'], $node['value'] ], '|');

        $func = isset($this->astToTarget[$key]) ? $this->astToTarget[$key] : null;
        if (!$func) {
           $func = isset($this->astToTarget[$node['type']]) ? $this->astToTarget[$node['type']] : null;
           if (!$func) {
               $this->error("Emitter:  Unknown node. type=`" . $node['type'] . "` value=`" . $node['value'] . "`", $node);
           }
        }

        $out = $this->lineMarker($node['pos'][0]);
        $out .= $this->$func($node['value'], $this->symbolTable->getKids($node['id']));

        // Call can be either expression or statement.
        if ($inBlock && $node['type'] === SymbolType::CALL) {
            $out .= ";\n";
        }

        return $out;
    }
}
