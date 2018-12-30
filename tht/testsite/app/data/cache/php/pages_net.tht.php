<?php

namespace tht998ec27c6dcf6a931462dfbbbc2957d3;
\o\Runtime::setNameSpace('pages/net.tht','tht998ec27c6dcf6a931462dfbbbc2957d3');

if ((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_request())->u_method === "get")) {
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_send_json(\o\OMap::create([ 'method' => \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_request() ]));

}
 else if ((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_request())->u_method === "post")) {
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_send_json(\o\OMap::create([ 'foo' => \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_temp_get_input("dangerDangerRemote", "foo") ]));

}





/* SOURCE={"file":"pages\/net.tht","6":3,"7":4,"10":6,"11":7} */

?>