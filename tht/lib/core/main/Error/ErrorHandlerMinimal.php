<?php

namespace o;

class ErrorHandlerMinimal {

    // The only public method
    public static function printError($msg) {

        if (php_sapi_name() == 'cli') {
            self::printErrorText($msg);
        }
        else {
            self::printErrorHtml($msg);
        }
        exit();
    }

    private static function printErrorText($msg) {

        $out = preg_replace('/\s{2,}/', "\n\n", $msg);
        $out = "\n--- Error ---\n\n" . $out;

        print($out . "\n\n");
    }

    private static function printErrorHtml($msg) {

        $style = '<style> body { margin: 0; padding: 0; } .tht-error { font-family: arial, sans-serif; padding: 0px 20px;} </style>';
        $out = $style . '<div class="tht-error">' . "<h2>Error</h2>" . $msg . '</div>';

        $out = preg_replace('/`(.*?)`/', '<code><b>$1</b></code>', $out);
        $out = preg_replace('/\n/', '<br>', $out);
        $out = preg_replace('#\s{2,}|//#', '<br><br>', $out);

        print($out);
    }
}

