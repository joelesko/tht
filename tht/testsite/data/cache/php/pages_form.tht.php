<?php

namespace tht5ae4d4b68cf26;
\o\Runtime::setNameSpace('pages/form.tht','tht5ae4d4b68cf26');

function u_main ()  {
 $u_form = \o\Runtime::getModule(__NAMESPACE__, 'Web')->u_form(\o\OMap::create([ 'userName' => \o\OMap::create([ 'label' => "User Name", 'rule' => "min:4" ]), 'email' => \o\OMap::create([ 'label' => "Email", 'rule' => "email", 'value' => "a@b.com", 'help' => "Make sure it is valid!" ]), 'zip' => \o\OMap::create([ 'label' => "Zip Code", 'rule' => "optional|digits" ]), 'password' => \o\OMap::create([ 'label' => "Password", 'type' => "password" ]), 'comment' => \o\OMap::create([ 'label' => "Comment", 'type' => "textarea" ]), 'secret' => \o\OMap::create([ 'type' => "hidden", 'value' => "12345" ]), 'state' => \o\OMap::create([ 'label' => "State", 'type' => "select", 'options' => \o\OMap::create([ 'wi' => "Wisconsin", 'fl' => "Florida", 'ca' => "California" ]) ]), 'color' => \o\OMap::create([ 'label' => "Color", 'type' => "radio", 'options' => \o\OList::create([ "Red", "Blue", "Green" ]) ]), 'toppings' => \o\OMap::create([ 'label' => "Toppings", 'type' => "checkbox", 'options' => \o\OList::create([ "Pepperoni", "Pineapple", "Anchovies" ]) ]) ]));
\o\v($u_form)->u_fill(\o\OMap::create([ 'userName' => "jlesko", 'color' => "Green", 'state' => "wi" ]));
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



/* SOURCE={"file":"pages\/form.tht","6":3,"7":49,"8":77,"9":81,"10":82,"11":83,"15":85,"19":91,"22":95,"23":96,"24":97,"27":100,"29":106,"30":106,"31":109,"35":111,"36":115,"39":120,"41":126,"42":126,"43":128,"45":128,"46":130,"49":130,"50":132,"53":132,"54":134,"57":134,"58":144} */

?>