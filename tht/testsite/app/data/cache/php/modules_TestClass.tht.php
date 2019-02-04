<?php

namespace tht\modules\TestClass_x;
\o\ModuleManager::registerUserModule('modules/TestClass.tht','tht\\modules\\TestClass_x');

\o\OBare::u_import("subDir/OtherClass");
\o\v(\o\ModuleManager::getModuleFromNamespace(__NAMESPACE__))->u_module_var = 122;
function u_factory ()  {
  return \o\ModuleManager::newObject("TestClass", ["factory", 99]);
 return new \o\ONothing(__METHOD__);
 
}
class u__test_class extends \o\OClass {
function u_new ($u_name, $u_id)  {
  \o\v($this)->u_name = $u_name;
\o\v($this)->u_state = \o\OMap::create([ 'id' => $u_id ]);
\o\v($this)->u_public_field = "abc";
\o\v($this)->u_dep = \o\ModuleManager::newObject("OtherClass", [\o\OMap::create([ 'foo' => 1, 'bar' => 2 ])]);
 return new \o\ONothing(__METHOD__);
 
}
function u_get_mod_var ()  {
  \o\v(\o\ModuleManager::getModuleFromNamespace(__NAMESPACE__))->u_module_var += \o\vn(1, 1);
return \o\v(\o\ModuleManager::getModuleFromNamespace(__NAMESPACE__))->u_module_var;
 return new \o\ONothing(__METHOD__);
 
}
function u_dependency ()  {
  return \o\v(\o\v($this)->u_dep)->u_ok();
 return new \o\ONothing(__METHOD__);
 
}
function u_get_id ()  {
  return \o\v(\o\v($this)->u_state)->u_id;
 return new \o\ONothing(__METHOD__);
 
}
function u_set_id ($u_id)  {
  \o\v(\o\v($this)->u_state)->u_id = $u_id;
 return new \o\ONothing(__METHOD__);
 
}
function u_get_full_name ()  {
  return \o\Runtime::concat(\o\Runtime::concat(\o\v($this)->u_name, ":"), \o\v($this)->u_id);
 return new \o\ONothing(__METHOD__);
 
}
function u_z_to_string ()  {
  return \o\v($this)->u_get_full_name();
 return new \o\ONothing(__METHOD__);
 
}
function u_z_dynamic_get ($u_field)  {
  if (($u_field === "okField")) {
return \o\v(\o\ModuleManager::getModule('Result'))->u_ok(\o\Runtime::concat("dynamic:", $u_field));

}
 else {
return \o\v(\o\ModuleManager::getModule('Result'))->u_fail();

}

 return new \o\ONothing(__METHOD__);
 
}
function u_z_dynamic_call ($u_method_name, $u_args)  {
  if (($u_method_name === "getSecretNumber")) {
return \o\v(\o\ModuleManager::getModule('Result'))->u_ok(42);

}

return \o\v(\o\ModuleManager::getModule('Result'))->u_fail();
 return new \o\ONothing(__METHOD__);
 
}
function u_html ()  {
$t = \o\Runtime::openTemplate("html");
$t->addStatic("<b>Hello</b>");
\o\Runtime::closeTemplate();
return $t->getString();
}

}



/* SOURCE={"file":"modules\/TestClass.tht","6":1,"7":3,"8":5,"9":7,"13":10,"14":12,"15":14,"16":16,"17":19,"18":21,"22":24,"23":25,"24":26,"28":29,"29":30,"33":33,"34":34,"38":37,"39":38,"43":41,"44":42,"48":45,"49":46,"53":49,"54":50,"55":51,"59":54,"66":58,"67":59,"68":60,"72":62,"76":65,"78":69} */

?>