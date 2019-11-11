<?php

require_once('../../main/Tht.php');

$mod = Tht::module('TestModule');

print $mod->bareFun('foobar');

print $mod->writeTemplate('Joe');

print_r($mod->getMap());

function topLevelFunction() {
    print('Called top-level function!<br />');
}


//Tht::page('/sideload');


