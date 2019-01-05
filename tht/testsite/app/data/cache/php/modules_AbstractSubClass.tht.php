<?php

namespace tht\modules\AbstractSubClass;
\o\ModuleManager::registerUserModule('modules/AbstractSubClass.tht','tht\\modules\\AbstractSubClass');

\o\v(\o\ModuleManager::getModuleFromNamespace(__NAMESPACE__))->u_foo = 12385858;
function u_hello ()  {
  \o\OBare::u_print(\o\Runtime::concat("HI MODULE", \o\v(\o\ModuleManager::getModuleFromNamespace(__NAMESPACE__))->u_foo));
 return new \o\ONothing(__METHOD__);
 
}
final class u__abstract_sub_class extends \tht\modules\AbstractClass\u__abstract_class {
private $u_prop = "xyz!";
private $u_mapp = [ 'a' => 123, 'b' => [ 1, 2, 3 ] ];
function u_new ()  {
   return new \o\ONothing(__METHOD__);
 
}
function u_please_call_me ()  {
  \o\OBare::u_print(\o\v($this)->u_prop);
\o\OBare::u_print(\o\v($this)->u_mapp);
\o\OBare::u_print(\o\v($this)->u_z_fields());
 return new \o\ONothing(__METHOD__);
 
}
function u_get_prop ()  {
  return \o\Runtime::concat("prop:", \o\v($this)->u_prop);
 return new \o\ONothing(__METHOD__);
 
}
function u_set_prop ($u_val)  {
  \o\v($this)->u_prop = \o\Runtime::concat("SET: ", $u_val);
 return new \o\ONothing(__METHOD__);
 
}

}
class u__extra_class extends \o\OClass {
function u_hey ()  {
  \o\OBare::u_print("hey");
 return new \o\ONothing(__METHOD__);
 
}

}



/* SOURCE={"file":"modules\/AbstractSubClass.tht","6":2,"7":4,"8":5,"12":8,"13":10,"14":11,"15":13,"16":16,"19":18,"20":19,"21":20,"22":21,"26":24,"27":25,"31":28,"32":29,"38":33,"39":35,"40":36} */

?>