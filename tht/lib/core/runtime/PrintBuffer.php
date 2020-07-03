<?php

namespace o;

class PrintBuffer {

    static private $buffer = [];

    static public function add($s) {
        if (!Tht::getConfig('showPrintPanel')) {
            return;
        }
        self::$buffer []= $s;
    }

    static public function hasItems() {
        return count(self::$buffer) > 0;
    }

    // Send the output of all print() statements
    static public function flush() {
        if (!self::hasItems()) { return; }

        $zIndex = 100000;

        $numLines = 0;
        foreach (self::$buffer as $b) {
            $numLines += substr_count($b, "\n");
        }

        $height = 'max-height: 400px';
        $fontSize = 18;
        if ($numLines >= 10) {
            $height = 'height: 400px'; // allow resize larger
            $fontSize = 16;
        }


        echo "<div class='tht-print-panel'>\n";

        echo "<style scoped>\n";
        echo ".tht-print { white-space: pre; border: 0; border-left: solid 8px #60adff; padding: 4px 32px; margin: 4px 0 0;  font-family: " . Tht::module('Css')->u_font('monospace') ."; }\n";
        echo ".tht-print-panel { resize: vertical; position: fixed; top: 0; left: 0; z-index: $zIndex; width: calc(100% - 32px); padding: 24px 32px 24px; font-size: ${fontSize}px; background-color: rgba(255,255,255,0.98);  -webkit-font-smoothing: antialiased; color: #222; box-shadow: 0 4px 4px rgba(0,0,0,0.15); $height; overflow: auto;  }\n";
        echo "</style>\n";

        foreach (self::$buffer as $b) {
            echo "<div class='tht-print'>" . $b . "</div>\n";
        }
        echo "</div>";

    }
}
