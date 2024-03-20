<?php

namespace o;

class Compiler {

    static private $sandboxDepth = 0;
    static private $filesToProcess = [];
    static private $processedFile = [];
    static private $didCompile = false;
    static private $appCompileTime = 0;

    static function process ($relSourceFile, $isEntry=false) {

        $sourceFile = Tht::getFullPath($relSourceFile);
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
            Tht::error("Source file not found: `$sourceFile`");
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
        if (!$phpModTime || $phpModTime < $sourceModTime || Tht::getConfig('_coreDevMode')) {
            self::validateSourceFileName($sourceFile, $isEntry);
            self::compile($sourceFile, $phpSourceFile);
        }

        self::$processedFile[$fuzzySourceFile] = true;

        self::executePhp($phpSourceFile);

        array_shift(self::$filesToProcess);
    }

    static function executePhp ($phpPath) {

        Tht::module('Perf')->u_start('tht.requireTranspiledFile', basename($phpPath));

        try {
            require($phpPath);
        }
        catch (ThtError $e) {
            ErrorHandler::handleThtRuntimeError($e, $phpPath);
        }

        Tht::module('Perf')->u_stop();
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
            else if ($isEntry) {
                Tht::errorLog("Url `$base` is missing hyphens. Ex: file-name = fileName.tht");
                Tht::module('Output')->u_send_error(404);
            }
            else {
                Tht::error("File name mismatch: `$base`  Try: `$baseBad` (exact case)");
            }
        }

        Security::assertIsOutsideDocRoot($filePath);
    }

    static function updateAppCompileTime () {

        touch(Tht::path('appCompileTimeFile'));
    }

    static function getAppCompileTime () {

        if (!self::$appCompileTime) {
            self::$appCompileTime = filemtime(Tht::path('appCompileTimeFile'));
        }

        return self::$appCompileTime;
    }

    static function getDidCompile () {

        return self::$didCompile;
    }

    static function isSandboxMode () {

        return self::$sandboxDepth > 0;
    }

    static function getCurrentFile () {

        return isset(self::$filesToProcess[0]) ? self::$filesToProcess[0] : '';
    }

    static function compile ($sourceFile, $phpSourceFile) {

        Tht::module('Perf')->u_start('tht.compile', $sourceFile);

        self::$didCompile = true;
        self::updateAppCompileTime();

        Tht::loadLib('compiler/_index.php');

        $rawSource   = self::readSourceFile($sourceFile);
        $tokenStream = self::tokenize($rawSource);
        $ast         = self::parse($tokenStream);
        $phpCode     = self::emit($ast, $sourceFile);

        self::writePhpFile($phpSourceFile, $phpCode, $sourceFile);

        Tht::module('Perf')->u_stop();

        ErrorTelemetry::send($sourceFile);
    }

    static function parseString ($source) {

        Tht::loadLib('compiler/_index.php');

        $source .= "\n";
        $tokens = self::tokenize($source);
        $ast = self::parse($tokens);

        return $ast;
    }

    static function safeParseString ($source) {

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

    static function readSourceFile ($sourceFile) {

        $rawSource = file_get_contents($sourceFile);
        $encoding = mb_detect_encoding($rawSource, 'UTF-8', true);

        if ($encoding !== 'UTF-8') {
            ErrorHandler::setFile($sourceFile);
            Tht::error('Source file must be saved in UTF-8 format.');
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

        file_put_contents($phpSourceFile, $phpCode, LOCK_EX);

        // Prevent delay introduced by opcode cache, which defaults to 2 second lag
        if (Tht::isOpcodeCacheEnabled()) {
            opcache_invalidate($phpSourceFile, true);
        }

        // Lint file
        $lint = shell_exec('php -l ' . escapeshellarg($phpSourceFile));
        if (strpos(strtolower($lint), 'no syntax errors') === false) {
            touch($phpSourceFile, time() - 100);  // make sure re-compile is forced next time
            ErrorHandler::handlePhpParseError($lint);
        }
    }

    // Get the THT source position.
    // Default to PHP if not found.
    static function sourceLinePhpToTht ($phpFilePath, $phpLineNum, $fnName) {

        $phpFilePath = Tht::normalizeWinPath($phpFilePath);

        $phpCode = file_get_contents($phpFilePath);

        // Reverse to make sure match is from bottom of file.
        $phpLines = array_reverse(
            explode("\n", $phpCode)
        );

        // Read the source map
        foreach ($phpLines as $l) {

            if (substr($l, 0, 2) === '/*') {

                $match = [];
                $sourceMapFound = preg_match('/SOURCE=(\{.*})/', $l, $match);

                if ($sourceMapFound) {

                    $sourceMap = Security::jsonDecode($match[1]);

                    if ($fnName) {
                        $phpLineNum = self::findRealSourceLineForFunctionCall($fnName, $phpLineNum, $phpLines);
                    }

                    // Go up the line numbers and find the nearest one with a mapping to THT
                    $checkLineNum = $phpLineNum;
                    while (true) {
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
    // function is an array literal.
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

            if (strpos(strtolower($line), $fnName) !== false) {
                return $lineNum;
            }

            $lineNum -= 1;
            if (!$lineNum) {
                return $startLineNum;
            }
        }
    }

}
