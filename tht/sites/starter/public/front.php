<?php

// Front Controller
// This routes all incoming requests to the THT app.
$thtRuntime = dirname(__FILE__) . '/../system/tht/run/tht.php';

return require_once($thtRuntime);
