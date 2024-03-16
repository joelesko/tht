<?php

namespace o;

class ErrorPageHtml {

    function print($components) {

        // Got - data (revisit verbiage on these)

        // Print the error
        http_response_code(500);

        $this->printTemplate($components);
    }

    function getComponentHtml($components) {

        $html = [];
        foreach ($components as $k => $c) {
            $html[$k] = $c->getHtml();
        }

        $html['signature'] = $components['helpLink']->getSignature();

        return $html;
    }

    function printTemplate($components) {

        $c = $this->getComponentHtml($components);

        ?>
        <!doctype html><html>
        <body>

        <div style="<?= $this->panelOuterCss() ?>">

            <?= $this->innerCss() ?>

            <div class='tht-error-content'>

                <div class='tht-error-header'>
                    <?= $c['title'] ?>
                </div>

                <div class='tht-error-message'>

                    <?= $c['message'] ?>

                    <?php if ($c['helpLink']) { ?>
                        <br><br><span class="tht-error-label">See:</span><?= $c['helpLink'] ?>
                        <?php if ($c['signature']) { ?>
                            <span class="tht-error-signature"><?= $c['signature'] ?></span>
                        <?php } ?>
                    <?php } ?>

                </div>

                <?php if ($c['filePath']) { ?>
                <div class='tht-error-file'><?= $c['filePath'] ?></div>
                <?php } ?>

                <?php if ($c['sourceLine']) { ?>
                    <div class='tht-error-srcline'><?= $c['sourceLine'] ?></div>
                <?php } ?>

                <?php if ($c['stackTrace']) { ?>
                    <div class='tht-error-trace'><?= $c['stackTrace'] ?></div>
                <?php } ?>



            </div>
        </div>

        <?php self::printPrintBuffer() ?>

    </body>
    </html>

        <?php
    }

    function printPrintBuffer() {

        if (PrintBuffer::hasItems()) {

            PrintBuffer::flush(true);

            print "<style> .tht-print-panel { color: inherit; width: auto; box-shadow: none; background-color: #282828; position: relative; } </style>";
        }
    }

    function panelOuterCss() {

        $zIndex = 99998;  // one less than print layer

        $css = "
            position: fixed;
            overflow: auto;
            z-index: $zIndex;
            background-color: #333;
            margin: 0;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        ";

        return trim(preg_replace('/\s*\n\s*/', ' ', $css));
    }

    function innerCss() {

        $monospace = Tht::module('Output')->font('monospace');
        $sansSerif = Tht::module('Output')->font('sansSerif');

        $css = <<<CSS

        <style scoped>

            .tht-error-content {
                color: #fff;
                padding: 32px 64px;
                -webkit-font-smoothing: antialiased;
                font: 20px $sansSerif;
                line-height: 1.3;
                z-index: 1;
                position: relative;
                margin: 0 auto;
                max-width: 700px;
            }

            a {
                color: #ffd267;
                text-decoration: none;
            }

            a:hover {
                text-decoration: underline;
            }

            .tht-error-header {
                margin-bottom: 48px;
                font-size: 140%;
                font-weight: 600;
            }

            .tht-error-header:after {
                content: '';
                position: relative;
                top: 12px;
                width: 100%;
                height: 2px;
                z-index: 1;
                display: block;
                background: linear-gradient(90deg, rgba(236,194,95,1) 0%, rgba(236,194,95,1) 85%, rgba(236,194,95,0) 100%);
            }

            .tht-error-header-sub {
                font-size: 50%;
                margin-left: 32px;
                font-weight: normal;
                opacity: 0.5;
            }

            .tht-error-message {
                margin-bottom: 32px;
            }

            .tht-error-hint {
                margin-top: 64px;
                line-height: 2;
                opacity: 0.5;
                font-size: 80%;
            }

            .tht-error-srcline {
                font-size: 90%;
                border-radius: 4px;
                margin-bottom: 32px;
                padding: 24px 24px 24px;
                background-color: #282828;
                white-space: pre;
                font-family: $monospace;
                overflow: auto;
            }

            .tht-src-small {
                font-size: 75%;
            }

            .tht-error-trace {
                font-size: 75%;
                border-radius: 4px;
                margin-bottom: 32px;
                margin-top: 32px;
                padding: 24px 24px;
                background-color: #282828;
                line-height: 180%;
                font-family: $monospace;
            }

            .tht-error-trace-bullet {
                font-family: arial, sans-serif;
                display: inline-block;
                width: 0.8em;
                text-align: center;
            }

            .tht-error-line-pointer {
                color: #eac222;
                font-size: 110%;
                font-family: arial, sans-serif;
            }

            .tht-error-file {
                margin-bottom: 32px;
                border-top: solid 1px rgba(255,255,255,0.1);
                padding-top: 32px;
            }

            .tht-error-file-dir {
                opacity: 0.5;
                margin: 0;
                margin-right: 0.1em;
            }

            .tht-error-file-ext {
                opacity: 0.5;
            }

            .tht-error-file span {
                font-size: 105%;
                color: inherit;
            }

            .tht-error-code {
                display: inline-block;
                margin: 4px 0;
                border-radius: 4px;
                font-size: 90%;
                font-weight: bold;
                font-family: $monospace;
                background-color: rgba(255,255,255,0.075);
                padding: 2px 8px;
            }

            .tht-error-signature {
                font-size: 90%;
                margin-left: 4px;
            }

            .tht-error-args {
                color: #aaa;
                font-size: 80%;
            }

            .tht-error-args-line {
                color: #aaa;
                font-size: 80%;
            }

            .tht-error-args-block {
                line-height: 120%;
                padding-left: 2.75em;
                margin-bottom: 0.75em;
            }

            .tht-error-label {
                margin-right: 10px;
            }

        </style>
CSS;

        return Tht::module('Output')->minifyCss($css);
    }
}