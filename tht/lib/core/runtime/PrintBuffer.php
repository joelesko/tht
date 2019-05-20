<?php

namespace o;

class PrintBuffer {

    static private $buffer = [];

    static public function add($s) {
        self::$buffer []= $s;
    }

    static public function hasItems() {
        return count(self::$buffer) > 0;
    }

    // Send the output of all print() statements
    static public function flush() {
        if (!self::hasItems()) { return; }

        $zIndex = 100000;

        echo "<style>\n";
        echo ".tht-print { white-space: pre; border: 0; border-left: solid 8px #60adff; padding: 4px 32px; margin: 4px 0 0;  font-family: " . Tht::module('Css')->u_font('monospace') ."; }\n";
        echo ".tht-print-panel { position: fixed; top: 0; left: 0; z-index: $zIndex; width: 100%; padding: 24px 32px 24px; font-size: 18px; background-color: rgba(255,255,255,0.98);  -webkit-font-smoothing: antialiased; color: #222; box-shadow: 0 4px 4px rgba(0,0,0,0.15); max-height: 400px; overflow: auto;  }\n";
        echo "</style>\n";

        echo "<div class='tht-print-panel'>\n";
        foreach (self::$buffer as $b) {
            echo "<div class='tht-print'>" . $b . "</div>\n";
        }
        echo "</div>";
    }
}
