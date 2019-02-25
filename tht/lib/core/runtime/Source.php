<?php

namespace o;

class Source {

    static private $sandboxDepth = 0;
    static private $currentFile = [];
    static private $processedFile = [];
    static private $didCompile = false;
    static private $appCompileTime = 0;

    static function process ($relSourceFile, $isEntry=false) {

        $sourceFile = Tht::getFullPath($relSourceFile);

        // Source already included
        if (isset(Source::$processedFile[$sourceFile])) {
            return;
        }

        Tht::module('Perf')->u_start('tht.execute', basename($relSourceFile));

        if (!file_exists($sourceFile)) {
            Tht::error("Source file not found: `$sourceFile`");
        }

        // Require .tht extension
        if (substr($sourceFile, -4, 4) !== '.' . Tht::getExt()) {
            Tht::error("Source file `$sourceFile` must have `." . Tht::getExt() . "` extension.");
        }

        array_unshift(Source::$currentFile, $sourceFile);
        $sourceModTime = filemtime($sourceFile);

        // Compare last compile time with cached PHP source file
        $phpSourceFile = Tht::getPhpPathForTht($sourceFile);
        $phpModTime = 0;
        if (file_exists($phpSourceFile)) {
            $phpModTime = filemtime($phpSourceFile);
        }

        // File was modified.  Re-compile.
        if (!$phpModTime || $phpModTime < $sourceModTime || Tht::getConfig('_disablePhpCache')) {
            Source::validateSourceFileName($sourceFile, $isEntry);
            Source::compile($sourceFile, $phpSourceFile);
        }

        Source::$processedFile[$sourceFile] = true;

        Tht::executePhp($phpSourceFile);

        array_shift(Source::$currentFile);

        Tht::module('Perf')->u_stop();
    }

    static function validateSourceFileName($filePath, $isEntry) {

        // Seems the only way to get the true filename on case-insensitive file systems
        // is to traverse the directory. :/
        $files = scandir(dirname($filePath));
        $base = basename($filePath);
        $fuzzyBase = strtolower($base);
        $badFile = false;
        foreach ($files as $f) {
            if (strtolower($f) === $fuzzyBase) {
                $badFile = ($f !== $base) ? $f : '';
                break;
            }
        }
        if ($badFile === false) {
            Tht::error("Unable to validate file name `$fileName` from directory listing.");
        }

        if ($badFile) {
            $baseBad = basename($badFile);
            if ($baseBad == strtolower($baseBad)) {
                $case = $isEntry ? 'lowerCamelCase' : 'UpperCamelCase';
                Tht::error("File name `$baseBad` must be $case.");
            } else {
                Tht::errorLog("Url `$base` is missing hyphens. Ex: file-name = fileName.tht");
                Tht::module('Web')->u_send_error(404);
            }
        }

        // Make sure 'modules' is in the path for modules, and not in the path for entry points
        $dirParts = explode('/', $filePath);
        $inModules = in_array('modules', $dirParts);
        if ($inModules && $isEntry) {
            Tht::errorLog("Entry route `$filePath` can not point to `modules` directory.");
            Tht::module('Web')->u_send_error(404);
        }
        if (!$isEntry && !$inModules) {
            Tht::error("Module `$base` must be located under a `modules` directory.");
        }

    }

    static function updateAppCompileTime () {
        touch(Tht::path('appCompileTimeFile'));
    }

    static function getAppCompileTime () {
        if (!Source::$appCompileTime) {
            Source::$appCompileTime = filemtime(Tht::path('appCompileTimeFile'));
        }
        return Source::$appCompileTime;
    }

    static function getDidCompile () {
        return Source::$didCompile;
    }

    static function isSandboxMode () {
        return Source::$sandboxDepth > 0;
    }

    static function getCurrentFile () {
        return isset(Source::$currentFile[0]) ? Source::$currentFile[0] : '';
    }

    static function compile ($sourceFile, $phpSourceFile) {

        Tht::module('Perf')->u_start('tht.compile', $sourceFile);

        Source::$didCompile = true;
        Source::updateAppCompileTime();

        Tht::loadLib('compiler/_index.php');

        $rawSource   = Source::readSourceFile($sourceFile);
        $tokenStream = Source::tokenize($rawSource);
        $ast         = Source::parse($tokenStream);
        $phpCode     = Source::emit($ast, $sourceFile);

        Source::writePhpFile($phpSourceFile, $phpCode, $sourceFile);

        Tht::module('Perf')->u_stop();
    }

    static function parseString ($source) {
        $source .= "\n";
        Tht::loadLib('compiler/_index.php');
        $tokens = Source::tokenize($source);
        $ast = Source::parse($tokens);
        return $ast;
    }

    static function safeParseString ($source) {
        Source::$sandboxDepth += 1;
        $ast = [];
        try {
            $ast = Source::parseString($source);
        }
        catch (\Exception $e) {
            throw new \Exception ($e->getMessage());
        }
        finally {
            Source::$sandboxDepth -= 1;
        }
        return $ast;
    }

    static function readSourceFile ($sourceFile) {
        $rawSource = file_get_contents($sourceFile);
        $encoding = mb_detect_encoding($rawSource, 'UTF-8', true);

        if ($encoding !== 'UTF-8') {
            Tht::error('Source file must be saved in UTF-8 format.', [ 'sourceFile' => $sourceFile ]);
        }

        return $rawSource;
    }

    static function tokenize ($rawSource) {
        $t = new Tokenizer ($rawSource);
        $tokens = $t->tokenize();
        return $tokens;
    }

    static function parse ($tokenStream) {
        $parser = new Parser ();
        $ast = $parser->parse($tokenStream);
        return $ast;
    }

    static function emit ($ast, $sourceFile) {
        $emitter = new EmitterPHP ();
        $phpCode = $emitter->emit($ast, $sourceFile);
        return $phpCode;
    }

    static function writePhpFile ($phpSourceFile, $phpCode, $sourceFile) {
        file_put_contents($phpSourceFile, $phpCode);
        touch($sourceFile); // for cache comparison

        if (Tht::getConfig('_lintPhp')) {
            $lint = shell_exec('php -l ' . $phpSourceFile);
            if (strpos(strtolower($lint), 'no syntax errors') === false) {
                touch($phpSourceFile, time() - 100);  // make sure re-compile is forced next time
                ErrorHandler::handlePhpParseError($lint);
            }
        }
    }
}
