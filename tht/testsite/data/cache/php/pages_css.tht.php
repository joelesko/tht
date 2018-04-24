<?php

namespace tht59e278682272e;
\o\Runtime::setNameSpace('pages/css.tht','tht59e278682272e');

\o\Runtime::getModule(__NAMESPACE__, 'Web')->u_send_css(u_css());
function u_css ()  {
$t = \o\Runtime::openTemplate("css");
$t->addStatic("");
$t->addDynamic(\o\Runtime::getModule(__NAMESPACE__, 'Css')->u_include("base", 700));
$t->addStatic("body{font-size:2rem;color:#29296f}.subline{font-size:2.5rem;color:#394;margin-bottom:4rem;margin-top:-3rem;border-bottom:solid 1px #d6d6e6;padding-bottom:2rem}code{font-weight:bold}");
\o\Runtime::closeTemplate();
return $t->getString();
}



/* SOURCE={"file":"pages\/css.tht","6":1,"7":3,"9":5,"10":5,"11":23} */

?>