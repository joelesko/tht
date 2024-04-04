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
    ?>
    <div id="tht-print-panel">

        <style scoped>
            #tht-print-panel { box-sizing: border-box; position: fixed; bottom: 0; left: 0; z-index: 10000; width: 100vw; max-height: calc(100% - 64px); min-height: 150px; font-size: <?php echo $fontSize ?>px; background-color: #fff; -webkit-font-smoothing: antialiased; color: #222; border-top: solid 1px #60adff; box-shadow: 0 0px 8px rgba(0,0,0,0.15); $height;  }
            #tht-print-lines { box-sizing: border-box; position: absolute; top: 0; left: 0; width: 100%; height: 100%; padding-top: 24px; overflow-y: scroll; overflow-x: auto;  }
            .tht-print { white-space: pre; border: 0; border-left: solid 3px #60adff; padding: 4px 32px; margin: 4px 0 0 24px;  font-family: <?php echo Tht::module('Output')->font('monospace') ?>; }
            .tht-print:last-child { margin-bottom: 24px; }
            #tht-print-close { user-select: none; background-color: #fff; position: absolute; top: 5px; right: 28px; color: #aaa; font-size: 30px; z-index: 1; cursor: pointer; }
            #tht-print-resize { user-select: none; background-color: transparent; position: absolute; top: -5px; left: 0; width: 100%; height: 10px; z-index: 1; cursor: row-resize;  }
        </style>

        <div id="tht-print-close" aria-label="Close"><b>&times;</b></div>
        <div id="tht-print-resize"></div>

        <div id="tht-print-lines">
        <?php
            foreach (self::$buffer as $b) {
                echo "<div class='tht-print'>" . $b . "</div>\n";
            }
        ?>
        </div>
        </div>

        <script nonce="<?php echo Tht::module('Web')->u_nonce() ?>">

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

            $tpr.addEventListener("dblclick", function(e){
                console.log(window.innerHeight);
                document.getElementById("tht-print-panel").style.height = (window.innerHeight - 64) + "px";
            }, false);

            document.getElementById('tht-print-close').addEventListener('click', () => {
                document.getElementById('tht-print-panel').remove();
            });

        </script>
        <?php
    }
}
