<?php

namespace o;

class ThtError extends \Exception {
    function u_message() {
        return $this->getMessage();
    }
}

// Global functions for triggering errors.
// Errors are handled in ErrorHandler.
trait ThtErrors {

    static public function error($msg) {
        // Use custom exception to track extra caller info.
        throw new ThtError ($msg);
    }

    static public function configError($msg) {
        ErrorHandler::handleConfigError($msg);
    }

    // For startup errors, we assume nothing else is loaded and have to give a minimal error message.
    static public function startupError($msg) {
        ErrorHandlerMinimal::printError($msg);
    }

    static public function phpIniError($msg) {

        $iniPath = php_ini_loaded_file();
        $msg .= "  Try the following: // 1) Edit `$iniPath` // 2) Restart the web server";

        self::startupError($msg);
    }

    static public function phpLibError($lib, $altHelpMsg='') {

        $iniPath = php_ini_loaded_file();
        $msg = "PHP extension `$lib` must be installed and enabled. // Try the following: // 1) Edit `$iniPath` // 2) Remove the semicolon in front of this line: // `;extension=$lib` // 3) Restart the web server";
        $msg .= ' // ' . $altHelpMsg;

        self::startupError($msg);
    }

    // Report error for the most recent userland function call
    static public function callerError($msg, $skipClass = '') {

        $callerFun = Tht::getUserlandCaller($skipClass)['function'];

        Tht::error("Function `$callerFun()` " . $msg);
    }

    static public function getUserlandCaller($skipClass = '') {

        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $callerFrame = false;

        foreach ($frames as $f) {
            if (hasu_($f['function'])) {
                if ($f['class'] !== 'o\\u_' . $skipClass) {
                    $callerFrame = $f;
                    break;
                }
            }
        }

        if (!$callerFrame) {
            $callerFrame = $frames[2]; // TODO: why?
        }

        $fun = $callerFrame['function'];
        $fun = ModuleManager::cleanNamespacedFunction($fun);

        // TODO: ugly.  This should be consolidated somewhere
        $class = $callerFrame['class'] ?? '';
        $class = str_replace('o\\', '', $class);

       // if ($class) { $class .= '.'; }

        return [
            'class'    => $class,
            'function' => $fun,
            'file'     => $callerFrame['file'],
        ];
    }

}
