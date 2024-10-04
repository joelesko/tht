<?php

    // preg_match('#/benchmark/test/(.*)#', $_GET['route'], $m);
    // $id = intval($m[1]);

    $id = intval($_GET['route']);

?>
<!doctype html>
<html>
    <head>
        <title>Route Param Test</title>
    </head>
    <body>
        Route Param: <?= $id ?>
    </body>
</html>