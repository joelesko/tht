<?php

namespace o;

trait ThtErrors {

    static private function catchPhpCompileErrors() {

        // These will be overridden later
        error_reporting(E_ALL & ~E_DEPRECATED);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
    }

    static public function errorLog ($msg) {

        $msg = trim($msg);
        if (!$msg) { return; }

        $msg = preg_replace("/\n{3,}/", "\n\n", $msg);
        if (strpos($msg, "\n") !== false) {
            $msg = ltrim(v($msg)->u_indent(2));
        }

        $line = '[' . date('Y-m-d H:i:s') . "]  " . $msg . "\n";

        file_put_contents(self::path('logFile'), $line, FILE_APPEND);
    }

    static public function error ($msg) {

        throw new ThtError ($msg);
    }

    static public function configError ($msg) {

        ErrorHandler::handleConfigError($msg);
    }

    static public function startupError ($msg) {
        throw new StartupError ($msg);
    }

    static public function strictFormatError($msg) {

        if (!Tht::isStrictFormat()) {
            return;
        }

        ErrorHandler::addSubOrigin('formatChecker');

        throw new ThtError ($msg);
    }

    static public function catchPreThtError() {

        $ob = ob_get_clean();
        if (headers_sent($atFile, $atLine) || $ob) {

            if (!$atFile) { $atFile = ''; }
            if (!$atLine) { $atLine = 0; }
            $e = error_get_last();

            if ($e) {
                ErrorHandler::handlePhpRuntimeError($e['type'], $e['message'], $e['file'], $e['line']);
            }
            else if ($ob) {
                $ob = substr($ob, 0, 200);
                ErrorHandler::handlePhpRuntimeError(0, "Unexpected output sent before THT page started: `$ob...`", $atFile, $atLine);
            }
            else {
                ErrorHandler::handlePhpRuntimeError(0, 'Unexpected output sent before THT page started.', $atFile, $atLine);
            }
        }
    }

}