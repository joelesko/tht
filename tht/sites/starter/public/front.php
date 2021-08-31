<?php

// Front Controller
// This routes all incoming requests to the THT app.

$thtRuntime = __DIR__ . '/../system/tht/run/tht.php';

return require_once($thtRuntime);
