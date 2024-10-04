<?php

// Front Controller
// This routes all incoming requests to the THT app.
$thtRuntime = dirname(__FILE__) . '/../php/system/tht/run/tht.php';

return require_once($thtRuntime);
