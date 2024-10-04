<?php

namespace o;

class Compiler {

    static private $sandboxDepth   = 0;
    static private $filesToProcess = [];
    static private $processedFile  = [];
    static private $processedName  = [];
    static private $didCompile     = false;
    static private $appCompileTime = 0;

    // Compile file from THT to PHP if it is new or modified.  Then execute it.
    static function process($relSourceFile, $isEntry=false) {

        $sourceFile = Tht::getFullPath($relSourceFile);
        $fileBaseName = self::getFileBaseName($relSourceFile);
        $fuzzySourceFile = strtolower($sourceFile);

        // Source already included
        // TODO: validate directory case instead of allowing fuzzy match
        if (isset(self::$processedFile[$fuzzySourceFile])) {
            return;
        }

        // Require .tht extension
        if (substr($sourceFile, -4, 4) !== '.' . Tht::getThtExt()) {
            Tht::error("Source path `$sourceFile` must have extension: `." . Tht::getThtExt() . "`");
        }

        if (!file_exists($sourceFile)) {
            $suggest = ErrorHandler::getFuzzySuggest($fileBaseName, array_keys(self::$processedName));
            Tht::error("Source file not found: `$sourceFile`  $suggest");
        }

        array_unshift(self::$filesToProcess, $sourceFile);
        $sourceModTime = filemtime($sourceFile);

        // Compare last compile time with cached PHP source file
        $phpSourceFile = Tht::getPhpPathForTht($sourceFile);
        $phpModTime = 0;
        if (file_exists($phpSourceFile)) {
            $phpModTime = filemtime($phpSourceFile);
        }

        // File was modified.  Re-compile.
        if (!$phpModTime || $phpModTime < $sourceModTime || Tht::getThtConfig('_coreDevMode')) {
             self::validateSourceFileName($sourceFile, $isEntry);
             self::compile($sourceFile, $phpSourceFile);
        }

        self::$processedFile[$fuzzySourceFile] = true;
        self::$processedName[$fileBaseName] = true;

        self::executePhp($phpSourceFile);

        array_shift(self::$filesToProcess);
    }

    static function getFileBaseName($filePath) {
        return preg_replace('/\.tht$/', '', basename($filePath));
    }

    static function executePhp($phpPath) {

        $perfTask = Tht::module('Perf')->u_start('tht.requireTranspiledFile', basename($phpPath));

        try {
            require($phpPath);
        }
        catch (ThtError $e) {
            ErrorHandler::handleThtRuntimeError($e, $phpPath);
        }

        $perfTask->u_stop();
    }

    // TODO: validate directory case in addition to filename
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
            }
            else {
                Tht::error("File name mismatch: `$base`  Try: `$baseBad` (exact case)");
            }
        }

        Security::assertIsOutsideDocRoot($filePath);
    }

    static function updateAppCompileTime() {

        touch(Tht::path('appCompileTimeFile'));
    }

    static function getAppCompileTime() {

        if (!self::$appCompileTime) {
            self::$appCompileTime = filemtime(Tht::path('appCompileTimeFile'));
        }

        return self::$appCompileTime;
    }

    static function getDidCompile() {

        return self::$didCompile;
    }

    static function isSandboxMode() {

        return self::$sandboxDepth > 0;
    }

    static function getCurrentFile() {

        return isset(self::$filesToProcess[0]) ? self::$filesToProcess[0] : '';
    }

    static function compile($sourceFile, $phpSourceFile) {

        $perfTask = Tht::module('Perf')->u_start('tht.compile', $sourceFile);

        self::$didCompile = true;
        self::updateAppCompileTime();

        Tht::loadLib('lib/core/compiler/_index.php');

        $rawSource   = self::readSourceFile($sourceFile);

        $tokenStream = self::tokenize($rawSource);
        $ast         = self::parse($tokenStream);
        $phpCode     = self::emit($ast, $sourceFile);

        self::writePhpFile($phpSourceFile, $phpCode, $sourceFile);

        $perfTask->u_stop();
    }

    static function parseString($source) {

        Tht::loadLib('lib/core/compiler/_index.php');

        $source .= "\n";
        $tokens = self::tokenize($source);
        $ast = self::parse($tokens);

        return $ast;
    }

    static function safeParseString($source) {

        self::$sandboxDepth += 1;
        $ast = [];

        try {
            $ast = self::parseString($source);
        }
        catch (\Exception $e) {
            throw new \Exception ($e->getMessage());
        }
        finally {
            self::$sandboxDepth -= 1;
        }

        return $ast;
    }

    static function readSourceFile($sourceFile) {

        $rawSource = file_get_contents($sourceFile);
        $encoding = mb_detect_encoding($rawSource, 'UTF-8', true);

        if ($encoding !== 'UTF-8') {
            ErrorHandler::setFile($sourceFile);
            Tht::error('Source file must be saved in UTF-8 format.');
        }

        // Trim spaces at the end of each line
        $rawSource = preg_replace('/ +$/m', '$1', $rawSource);

        return $rawSource;
    }

    static function tokenize($rawSource) {

        $t = new Tokenizer ($rawSource);
        $tokens = $t->tokenize();

        return $tokens;
    }

    static function parse($tokenStream) {

        $parser = new Parser ();
        $ast = $parser->parse($tokenStream);

        return $ast;
    }

    static function emit($ast, $sourceFile) {

        $emitter = new EmitterPHP ();
        $phpCode = $emitter->emit($ast, $sourceFile);

        return $phpCode;
    }

    static function writePhpFile($phpSourceFile, $phpCode, $sourceFile) {

        file_put_contents($phpSourceFile, $phpCode, LOCK_EX);

        // Force opcache to update. Otherwise it waits 2 seconds to update, which is too long.
        if (Tht::isOpcodeCacheEnabled()) {
            opcache_invalidate($phpSourceFile, true);
        }

        // Lint file - ~ 40ms
        // https://www.php.net/manual/en/features.commandline.options.php
        $lintMsg = shell_exec('php --syntax-check ' . escapeshellarg($phpSourceFile));
        if (strpos(strtolower($lintMsg), 'no syntax errors') === false) {
            // Lint error
            touch($phpSourceFile, time() - 100);  // make sure re-compile is forced next time
            ErrorHandler::handlePhpLintError($lintMsg);
        }
    }

    // Get the THT source position.
    // Default to PHP if not found.
    static function sourceLinePhpToTht($phpFilePath, $phpLineNum, $fnName) {

        $phpFilePath = Tht::normalizeWinPath($phpFilePath);

        $phpCode = file_get_contents($phpFilePath);

        // Reverse to make sure match is from bottom of file.
        $phpLines = array_reverse(
            explode("\n", $phpCode)
        );

        if (!$phpLineNum) { $phpLineNum = 0; }

        // Read the source map
        foreach ($phpLines as $l) {

            if (substr($l, 0, 2) === '/*') {

                $match = [];
                $sourceMapFound = preg_match('/SOURCE=(\{.*})/', $l, $match);

                if ($sourceMapFound) {

                    $sourceMap = Security::jsonDecode($match[1]);

                    if ($fnName && hasu_($fnName)) {
                        $phpLineNum = self::findRealSourceLineForFunctionCall($fnName, $phpLineNum, $phpLines);
                    }

                    // Go up the line numbers and find the nearest one with a mapping to THT
                    $checkLineNum = $phpLineNum;
                    while ($checkLineNum > 0) {
                        if (isset($sourceMap[$checkLineNum])) {

                            return [
                                'lang' => 'tht',
                                'file' => $sourceMap['file'],
                                'lineNum' => $sourceMap[$checkLineNum],
                                'linePos' => null,
                                'lineSource' => '',
                            ];
                        }
                        $checkLineNum -= 1;
                    }

                    break;
                }
            }
        }

        // Fall through to PHP frame
        return [
            'lang' => 'php',
            'lineNum' => $phpLineNum,
            'file' => $phpFilePath,
            'linePos'  => -1,
            'lineSource' => trim($phpLines[$phpLineNum]),
        ];
    }

    // There is a bug in PHP where the call line is wrong if the argument of a
    // function is an multi-line array literal.
    // - If the array values are expressions (map, function call), it gets the line of the LAST key
    // - If the array values are atomic, it gets the line of the FIRST key
    //
    // To work around this, we need to search up the source to match the function name itself.
    // BUG/TODO: Report bug to PHP devs
    static function findRealSourceLineForFunctionCall($fnName, $startLineNum, $phpLines) {

        // Reverse back to original order.
        $phpLines = array_reverse($phpLines);

        if (!hasu_($fnName)) {
            $fnName = u_($fnName);
        }
        $fnName = strtolower($fnName);

        $lineNum = $startLineNum;
        while (true) {
            $line = $phpLines[$lineNum];
            if (str_contains(strtolower($line), $fnName)) {
                return $lineNum + 1;
            }

            $lineNum -= 1;

            // Not found
            if (!$lineNum) {
                return $startLineNum;
            }
        }
    }

}
