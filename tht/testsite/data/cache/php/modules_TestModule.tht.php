<?php

namespace tht25ca020412a05b2c5cdc411674c2d40a;
\o\Runtime::setNameSpace('modules/TestModule.tht','tht25ca020412a05b2c5cdc411674c2d40a');

\o\v(\o\Runtime::getModule(__NAMESPACE__, 'TestModule'))->u_module_var = "mod";
function u_bare_fun ($u_name)  {
 return \o\Runtime::concat("bare:", $u_name);
 return \o\Runtime::void(__METHOD__);
}
function u_test_global ()  {
 return \o\Runtime::concat("global:", \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global'))->u_foo);
 return \o\Runtime::void(__METHOD__);
}
function u_test_module_var ()  {
 return \o\Runtime::concat("moduleVar:", \o\v(\o\Runtime::getModule(__NAMESPACE__, 'TestModule'))->u_module_var);
 return \o\Runtime::void(__METHOD__);
}



/* SOURCE={"file":"modules\/TestModule.tht","6":2,"7":4,"8":5,"11":8,"12":9,"15":12,"16":13} */

?>