<?php

namespace o;

trait ThtSideload {

    static public function sideloadInit() {

        if (self::$mode['sideload']) {
            return;
        }
        self::$mode['sideload'] = true;

        // THT main
        self::main();
    }

    static public function sideloadPage($pageUrl) {

        self::sideloadInit();

        self::catchPreThtError();
        $fnRun = function() use ($pageUrl) {
            WebMode::runRoute($pageUrl);
        };
        ErrorHandler::catchErrors($fnRun);
        exit(0);
    }

    static public function sideloadModule($mod) {

        self::sideloadInit();

        return \o\ModuleManager::get($mod, true);
    }

}