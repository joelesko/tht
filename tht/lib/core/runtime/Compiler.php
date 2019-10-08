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

        // Source already included
        if (isset(self::$processedFile[$sourceFile])) {
            return;
        }

        Tht::module('Perf')->u_start('tht.execute', Tht::stripAppRoot($relSourceFile));

        if (!file_exists($sourceFile)) {
            Tht::error("Source file not found: `$sourceFile`");
        }

        // Require .tht extension
        if (substr($sourceFile, -4, 4) !== '.' . Tht::getExt()) {
            Tht::error("Source file `$sourceFile` must have `." . Tht::getExt() . "` extension.");
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

        self::$processedFile[$sourceFile] = true;

        Tht::executePhp($phpSourceFile);

        array_shift(self::$filesToProcess);

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
                Tht::module('Response')->u_send_error(404);
            }
        }
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

        ErrorHandler::sendTelemetry($sourceFile);
    }

    static function parseString ($source) {
        $source .= "\n";
        Tht::loadLib('compiler/_index.php');
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
        // touch($sourceFile); // for cache comparison // editors don't play nice with this

        // Lint file
        $lint = shell_exec('php -l ' . escapeshellarg($phpSourceFile));
        if (strpos(strtolower($lint), 'no syntax errors') === false) {
            touch($phpSourceFile, time() - 100);  // make sure re-compile is forced next time
            ErrorHandler::handlePhpParseError($lint);
        }
    }
}
