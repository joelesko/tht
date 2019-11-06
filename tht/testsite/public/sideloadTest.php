<?php

require_once('../../main/Tht.php');

//print "sdfsdfsdfsdf";
//print($zzzz);


$mod = Tht::module('TestModule');

print $mod->bareFun('Joe');

print $mod->write('Hey');

print_r($mod->getMap());





//Tht::page('/sideload');


function topLevelFunction() {
    print('Called top-level function!<br />');
}
