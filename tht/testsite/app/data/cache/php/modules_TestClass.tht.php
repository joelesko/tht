<?php

namespace tht\modules\TestClass;
\o\ModuleManager::registerUserModule('modules/TestClass.tht','tht\\modules\\TestClass');

\o\OBare::u_import("subDir/OtherClass");
function u_factory ()  {
  return \o\ModuleManager::newObject("TestClass", ["factory", 99]);
 return new \o\ONothing(__METHOD__);
 
}
class u__test_class extends \o\OClass {
function u_new ($u_name, $u_num)  {
  \o\v($this)->u_name = $u_name;
\o\v($this)->u_num = $u_num;
\o\v(\o\v($this)->u_state)["hiddenChar"] = "x";
\o\v($this)->u_state = \o\OMap::create([ 'hiddenNum' => 0 ]);
\o\v($this)->u_dep = \o\ModuleManager::newObject("OtherClass", []);
return $this;
 return new \o\ONothing(__METHOD__);
 
}
function u_dependency ()  {
  return \o\v(\o\v($this)->u_dep)->u_ok();
 return new \o\ONothing(__METHOD__);
 
}
function u_get_hidden ()  {
  return \o\v(\o\v($this)->u_state)->u_hidden_num;
 return new \o\ONothing(__METHOD__);
 
}
function u_set_hidden ($u_num)  {
  \o\v(\o\v($this)->u_state)->u_hidden_num = $u_num;
 return new \o\ONothing(__METHOD__);
 
}
function u_all_state ()  {
  return \o\v($this)->u_state;
 return new \o\ONothing(__METHOD__);
 
}
function u_get_name ()  {
  \o\OBare::u_print("get name");
return \o\Runtime::concat(\o\v($this)->u_name, "!");
 return new \o\ONothing(__METHOD__);
 
}
function u_test ()  {
  return \o\Runtime::concat(\o\Runtime::concat("class:", \o\v($this)->u_name), \o\v($this)->u_num);
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



/* SOURCE={"file":"modules\/TestClass.tht","6":1,"7":3,"8":5,"12":8,"13":10,"14":12,"15":13,"16":15,"17":17,"18":20,"19":22,"23":25,"24":26,"28":29,"29":30,"33":33,"34":34,"38":37,"39":38,"43":41,"44":42,"45":43,"49":46,"50":47,"54":50,"55":51,"56":52,"60":55,"67":59,"68":60,"69":61,"73":63,"77":66,"79":70} */

?>