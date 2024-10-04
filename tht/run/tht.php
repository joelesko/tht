<?php

namespace o;

// Include THT lib
require dirname(__FILE__) . '/../lib/core/main/Tht.php';

// Run app
$thtReturnStatus = Tht::main();

return $thtReturnStatus;

