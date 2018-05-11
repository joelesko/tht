<?php

namespace tht5aeb41cfa5de6;
\o\Runtime::setNameSpace('pages/form.tht','tht5aeb41cfa5de6');

function u_main ()  {
 $u_form = \o\Runtime::getModule(__NAMESPACE__, 'Web')->u_form(\o\OMap::create([ 'userName' => \o\OMap::create([ 'label' => "User Name", 'rule' => "min:4" ]), 'email' => \o\OMap::create([ 'label' => "Email", 'rule' => "email", 'value' => "a@b.com", 'help' => "Make sure it is valid!" ]), 'zip' => \o\OMap::create([ 'label' => "Zip Code", 'rule' => "optional|digits" ]), 'password' => \o\OMap::create([ 'label' => "Password", 'type' => "password" ]), 'comment' => \o\OMap::create([ 'label' => "Comment", 'type' => "textarea" ]), 'secret' => \o\OMap::create([ 'type' => "hidden", 'value' => "12345" ]), 'state' => \o\OMap::create([ 'label' => "State", 'type' => "select", 'options' => \o\OMap::create([ 'wi' => "Wisconsin", 'fl' => "Florida", 'ca' => "California" ]) ]), 'color' => \o\OMap::create([ 'label' => "Color", 'type' => "radio", 'options' => \o\OList::create([ "Red", "Blue", "Green" ]) ]), 'toppings' => \o\OMap::create([ 'label' => "Toppings", 'type' => "checkbox", 'options' => \o\OList::create([ "Pepperoni", "Pineapple", "Anchovies" ]) ]) ]));
\o\v($u_form)->u_set_fields(\o\OMap::create([ 'userName' => "jlesko", 'color' => "Green", 'state' => "wi", 'toppings' => \o\OList::create([ "Pepperoni", "Pineapple" ]) ]));
$u_html = "";
if (\o\v($u_form)->u_ok()) {
$u_html = u_process_form($u_form);

}
 else {
$u_html = u_form_html($u_form);

}

\o\Runtime::getModule(__NAMESPACE__, 'Web')->u_send_page(\o\OMap::create([ 'body' => $u_html, 'title' => "Test Form", 'css' => \o\Runtime::getModule(__NAMESPACE__, 'Css')->u_include("base") ]));
 return \o\Runtime::void(__METHOD__);
}
function u_process_form ($u_form)  {
 \o\OBare::u_print(\o\v($u_form)->u_data());
return u_thanks_html(\o\v($u_form)->u_data());
 return \o\Runtime::void(__METHOD__);
}
function u_thanks_html ($u_d)  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("<div class='row'><div class='col'><h1>Success</h1><p>Thanks <b>");
$t->addDynamic(\o\v($u_d)->u_user_name);
$t->addStatic("!</b></p></div></div>");
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
$t->addDynamic(\o\v($u_f)->u_open());
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



/* SOURCE={"file":"pages\/form.tht","6":3,"7":49,"8":78,"9":82,"10":83,"11":84,"15":86,"19":92,"22":96,"23":97,"24":98,"27":101,"29":107,"30":107,"31":110,"35":112,"36":116,"39":121,"41":127,"42":127,"43":129,"45":129,"46":131,"49":131,"50":133,"53":133,"54":135,"57":135,"58":145} */

?>