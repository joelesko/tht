<?php

namespace tht59f4354d72243;
\o\Runtime::setNameSpace('modules/TestModule.tht','tht59f4354d72243');

function u_bare_fun ($u_name)  {
 return \o\Runtime::concat("bare:", $u_name);
 return \o\Runtime::void(__METHOD__);
}
function u_test_global ()  {
 return \o\Runtime::concat("global:", \o\Runtime::getModule(__NAMESPACE__, 'Global')->u_foo);
 return \o\Runtime::void(__METHOD__);
}
class u_test_module extends \o\OClass {
function u_setup ($u_name)  {
 \o\v($this)->u_name = $u_name;
 return \o\Runtime::void(__METHOD__);
}
function u_test ()  {
 return \o\Runtime::concat("class:", \o\v($this)->u_name);
 return \o\Runtime::void(__METHOD__);
}

}
$u_test_module = __NAMESPACE__ . "\u_test_module";



/* SOURCE={"file":"modules\/TestModule.tht","6":3,"7":4,"10":7,"11":8,"14":12,"15":14,"16":15,"19":18,"20":19} */

?>