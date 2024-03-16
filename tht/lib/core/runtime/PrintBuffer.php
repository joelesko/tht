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

    static function flushJsonMode() {
        echo "\n\n// PRINT PANEL:\n";
        foreach (self::$buffer as $b) {
            echo $b . "\n";
        }
    }

    // Send the output of all print() statements
    static public function flush() {

        if (!self::hasItems()) { return; }

        $sentType = Tht::module('Output')->sentResponseType;

        if ($sentType == 'json') {
            self::flushJsonMode();
            return;
        }
        else if ($sentType !== '' && $sentType !== 'html') {
            return;
        }

        $zIndex = 100000;

        $numLines = count(self::$buffer);
        foreach (self::$buffer as $b) {
            $numLines += substr_count($b, "\n");
        }

        $height = '';
        $fontSize = 18;
        if ($numLines >= 10) {
            $fontSize = 16;
            $height = 'height: 400px';
        }

        echo "<div id='tht-print-panel'>\n";

        echo "<style scoped>\n";
        echo ".tht-print { white-space: pre; border: 0; border-left: solid 3px #60adff; padding: 4px 32px; margin: 4px 0 0;  font-family: " . Tht::module('Output')->font('monospace') . "; }\n";
        echo "#tht-print-panel { position: fixed; bottom: 0; left: 0; z-index: $zIndex; width: 100%; max-height: calc(100% - 64px); padding: 24px 32px 24px; font-size: ${fontSize}px; background-color: rgba(255,255,255,0.98);  -webkit-font-smoothing: antialiased; color: #222; box-shadow: 0 0px 8px rgba(0,0,0,0.15); $height; overflow-y: scroll; overflow-x: auto;  }\n";
        echo "#tht-print-close { user-select: none; background-color: #fff; position: absolute; top: 5px; right: 16px; color: #aaa; font-size: 30px; z-index: 1; cursor: pointer; }";
        echo "#tht-print-resize { user-select: none; background-color: #fff; position: absolute; top: 0; left: 0; width: 100%; height: 8px; cursor: row-resize;  }";
        echo "</style>\n";

        echo '<div id="tht-print-close" aria-label="Close"><b>&times;</b></div>';
        echo '<div id="tht-print-resize"></div>';

        foreach (self::$buffer as $b) {
            echo "<div class='tht-print'>" . $b . "</div>\n";
        }
        echo "</div>";

        echo "<script nonce=" . Tht::module('Web')->u_nonce() . ">document.getElementById('tht-print-close').addEventListener('click', () => { document.getElementById('tht-print-panel').remove(); });";

        echo '
            var tprPos;
            var $tpr = document.getElementById("tht-print-resize");
            function resizePrintPanel(e){
                var parent = $tpr.parentNode;
                var dy = tprPos - e.y;
                tprPos = e.y;
                parent.style.height = (parseInt(getComputedStyle(parent, "").height) + dy) + "px";
            }

            $tpr.addEventListener("mousedown", function(e){
                tprPos = e.y;
                document.addEventListener("mousemove", resizePrintPanel, false);
            }, false);

            document.addEventListener("mouseup", function(){
                document.removeEventListener("mousemove", resizePrintPanel, false);
            }, false);
        ';
        echo "</script>";

    }


}
