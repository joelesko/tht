<?php

// Front Controller
// This routes all incoming requests to the THT app.

// $thtRuntime = __DIR__ . '/../system/tht/run/tht.php';

// TODO: CHANGE BACK
$thtRuntime = __DIR__ . '/../../../run/tht.php';

return require_once($thtRuntime);
