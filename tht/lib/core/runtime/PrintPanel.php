<?php

namespace o;

class PrintPanel {

    static private $buffer = [];

    static public function add($s) {
        if (!Tht::getThtConfig('showPrintPanel')) {
            return;
        }
        self::$buffer []= $s;
    }

    static public function hasItems() {
        return count(self::$buffer) > 0;
    }

    static function flushJsonMode() {
        echo "\n\n// THT PRINT:\n";
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
            #tht-print-panel { box-sizing: border-box; position: fixed; bottom: 0; left: 0; z-index: 100000; width: 100vw; max-height: calc(100% - 64px); min-height: 150px; font-size: <?php echo $fontSize ?>px; background-color: #fff; -webkit-font-smoothing: antialiased; color: #222; border-top: solid 1px #60adff; box-shadow: 0 0px 8px rgba(0,0,0,0.15); $height; overflow-y: scroll;  }
            #tht-print-lines { box-sizing: border-box; position: absolute; top: 0; left: 0; width: 100%; height: 100%; padding-top: 24px; overflow-y: scroll; overflow-x: auto;  }
            .tht-print { white-space: pre; border: 0; border-left: solid 3px #60adff; padding: 0px 32px; margin: 4px 0 0 24px;  font-family: <?php echo Tht::module('Output')->font('monospace') ?>; }
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

            let lsKey = 'tht.printPanelY.' + window.location.href;
            let prevY = localStorage.getItem(lsKey);
            if (prevY) { resizePrintPanel(prevY); }

            function onResizePrintPanel(e) {
                let sizeY = window.innerHeight - e.y;
                resizePrintPanel(sizeY);
            }

            function resizePrintPanel(sizeY) {
                localStorage.setItem(lsKey, sizeY);
                document.getElementById("tht-print-panel").style.height = sizeY + "px";
            }

            $tpr.addEventListener("mousedown", (e)=>{
                tprPos = e.y;
                document.addEventListener("mousemove", onResizePrintPanel, false);
            }, false);

            document.addEventListener("mouseup", (e)=>{
                document.removeEventListener("mousemove", onResizePrintPanel, false);
            }, false);

            $tpr.addEventListener("dblclick", (e)=>{
                resizePrintPanel(window.innerHeight - 64);
            }, false);

            document.getElementById('tht-print-close').addEventListener('click', () => {
                document.getElementById('tht-print-panel').remove();
            });

        </script>
        <?php
    }
}
