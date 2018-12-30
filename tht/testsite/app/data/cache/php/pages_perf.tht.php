<?php

namespace thte31f7b4e4d5bd08e84234e4dc02ec3e1;
\o\Runtime::setNameSpace('pages/perf.tht','thte31f7b4e4d5bd08e84234e4dc02ec3e1');

function u_main ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_get("nothing", "");
$u_rows = \o\OList::create([  ]);
foreach (\o\uv(\o\OBare::u_range(1, 20)) as $u_num) {
$u_rows []= \o\Runtime::concat(\o\Runtime::concat($u_num, "-"), \o\v(\o\Runtime::getModule(__NAMESPACE__, 'String'))->u_random(20));

}
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_send_page(\o\OMap::create([ 'body' => u_html($u_rows), 'title' => "Perf Test Page" ]));
 return new \o\ONothing(__METHOD__);
 
}
function u_html ($u_rows)  {
$t = \o\Runtime::openTemplate("html");
$t->addStatic("<main><h1>Perf Test Page</h1><p>Date: <b>");
$t->addDynamic(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Date'))->u_now());
$t->addStatic("</b></p><ul>");
foreach (\o\uv($u_rows) as $u_row) {
$t->addStatic("<li>");
$t->addDynamic($u_row);
$t->addStatic("</li>");

}
$t->addStatic("</ul></main>");
\o\Runtime::closeTemplate();
return $t->getString();
}



/* SOURCE={"file":"pages\/perf.tht","6":1,"7":3,"8":5,"9":6,"10":7,"13":12,"17":20,"19":25,"20":25,"21":28,"22":28,"23":29,"24":29,"25":30,"28":34} */

?>