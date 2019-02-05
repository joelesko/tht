<?php

namespace o;

// Include THT lib
require dirname(__FILE__) . '/../lib/core/Tht.php';

// Run app
$thtReturnStatus = Tht::start();

return $thtReturnStatus;

