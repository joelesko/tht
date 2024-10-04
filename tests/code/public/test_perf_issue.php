<?php

ini_set('memory_limit', '512M');
//shell_exec('php -v');

main();

function main() {
    $t1 = microtime(true);

    for ($i = 0; $i < 5; $i += 1) {
        go();
    }

    $t2 = microtime(true);
    print(($t2 - $t1) . "s\n");
}

function handleStr($s) {
    return strrev($s);
}

function go() {
    $str = '1234567890';
    $list = [];
    for ($i = 0; $i < 1_000_000; $i += 1) {
        $str = handleStr($str);
        $list[] = $str;
    }
}