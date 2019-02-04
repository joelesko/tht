<?php

namespace tht\modules\TestModule_x;
\o\ModuleManager::registerUserModule('modules/TestModule.tht','tht\\modules\\TestModule_x');

\o\v(\o\ModuleManager::getModule('TestModule'))->u_module_var = "mod";
function u_bare_fun ($u_name)  {
  return \o\Runtime::concat("bare:", $u_name);
 return new \o\ONothing(__METHOD__);
 
}
function u_test_global ()  {
  return \o\Runtime::concat("global:", \o\v(\o\ModuleManager::getModule('Global'))->u_foo);
 return new \o\ONothing(__METHOD__);
 
}
function u_test_module_var ()  {
  return \o\Runtime::concat("moduleVar:", \o\v(\o\ModuleManager::getModule('TestModule'))->u_module_var);
 return new \o\ONothing(__METHOD__);
 
}



/* SOURCE={"file":"modules\/TestModule.tht","6":2,"7":4,"8":5,"12":8,"13":9,"17":12,"18":13} */

?>