<?php

namespace thtf13d648fa2b70ebc0ede87fdd1244154;
\o\Runtime::setNameSpace('pages/form.tht','thtf13d648fa2b70ebc0ede87fdd1244154');

function u_main ()  {
  $u_html = "";
if (\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_temp_get_input("get", "done")) {
$u_html = new \o\OLockString ("Thanks!");

}
 else {
$u_form = u_get_form();
$u_html = u_form_html($u_form);

}

\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_send_page(\o\OMap::create([ 'body' => $u_html, 'title' => "Test Form", 'css' => \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Css'))->u_include("base") ]));
 return \o\Runtime::void(__METHOD__);
}
function u_ajax ()  {
  $u_form = u_get_form();
if ((! \o\v($u_form)->u_validate())) {
\o\v($u_form)->u_go_fail();

}

\o\v($u_form)->u_go_next("?done=1");
 return \o\Runtime::void(__METHOD__);
}
function u_get_form ()  {
  $u_form = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_form(\o\OMap::create([ 'name' => \o\OMap::create([ 'label' => "Name", 'type' => "text", 'rule' => "text" ]) ]));
return $u_form;
 return \o\Runtime::void(__METHOD__);
}
function u_thanks_html ($u_d)  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("<div class='row'><div class='col'><h1>Success</h1><p>Thanks!</p></div></div>");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_get_options ()  {
  return \o\OMap::create([ '1' => "Option 1", '2' => "Option 2", '3' => "Option 3" ]);
 return \o\Runtime::void(__METHOD__);
}
function u_form_html ($u_form)  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("<div class='row'><div class='col'><h1>Test Form</h1>");
$u_f = $u_form;
$t->addStatic("
        ");
$t->addDynamic(\o\v($u_f)->u_open("", \o\OMap::create([ 'jsValidation' => false ])));
$t->addStatic("

            ");
$t->addDynamic(\o\v($u_f)->u_tags());
$t->addStatic("

            ");
$t->addDynamic(\o\v($u_f)->u_submit("Submit", \o\OMap::create([ 'class' => "button-large button-primary" ])));
$t->addStatic("

        ");
$t->addDynamic(\o\v($u_f)->u_close());
$t->addStatic("</div></div>");
\o\Runtime::closeTemplate();
return $t->getString();
}



/* SOURCE={"file":"pages\/form.tht","6":3,"7":5,"8":7,"9":8,"13":12,"14":14,"18":20,"21":24,"22":26,"23":27,"24":28,"28":31,"31":34,"32":40,"33":44,"36":47,"38":56,"42":58,"43":62,"46":67,"48":73,"49":73,"50":75,"52":75,"53":77,"56":77,"57":79,"60":79,"61":81,"64":81,"65":87} */

?>