<?php

namespace tht5b0884d101106;
\o\Runtime::setNameSpace('pages/form.tht','tht5b0884d101106');

function u_main ()  {
 $u_form = u_get_form();
\o\v($u_form)->u_set_fields(\o\OMap::create([ 'userName' => "jlesko", 'color' => "Green", 'state' => "wi", 'toppings' => \o\OList::create([ "Pepperoni", "Pineapple" ]) ]));
$u_html = u_form_html($u_form);
\o\Runtime::getModule(__NAMESPACE__, 'Web')->u_send_page(\o\OMap::create([ 'body' => $u_html, 'title' => "Test Form", 'css' => \o\Runtime::getModule(__NAMESPACE__, 'Css')->u_include("base") ]));
 return \o\Runtime::void(__METHOD__);
}
function u_ajax ()  {
 \o\v(u_get_form())->u_validate("/yay");
 return \o\Runtime::void(__METHOD__);
}
function u_get_form ()  {
 $u_form = \o\Runtime::getModule(__NAMESPACE__, 'Web')->u_form(\o\OMap::create([ 'userName' => \o\OMap::create([ 'label' => "User Name", 'rule' => "min:4" ]), 'email' => \o\OMap::create([ 'label' => "Email", 'rule' => "email", 'value' => "a@b.com", 'help' => "Make sure it is valid!" ]), 'zip' => \o\OMap::create([ 'label' => "Zip Code", 'rule' => "optional|digits" ]), 'password' => \o\OMap::create([ 'label' => "Password", 'type' => "password" ]), 'comment' => \o\OMap::create([ 'label' => "Comment", 'type' => "textarea" ]), 'secret' => \o\OMap::create([ 'type' => "hidden", 'value' => "12345" ]), 'state' => \o\OMap::create([ 'label' => "State", 'type' => "select", 'options' => \o\OMap::create([ 'wi' => "Wisconsin", 'fl' => "Florida", 'ca' => "California" ]) ]), 'color' => \o\OMap::create([ 'label' => "Color", 'type' => "radio", 'options' => \o\OList::create([ "Red", "Blue", "Green" ]) ]), 'toppings' => \o\OMap::create([ 'label' => "Toppings", 'type' => "checkbox", 'options' => \o\OList::create([ "Pepperoni", "Pineapple", "Anchovies" ]) ]) ]));
return $u_form;
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



/* SOURCE={"file":"pages\/form.tht","6":3,"7":5,"8":11,"9":15,"10":20,"13":24,"14":25,"17":28,"18":74,"19":100,"22":103,"24":109,"25":109,"26":112,"30":114,"31":118,"34":123,"36":129,"37":129,"38":131,"40":131,"41":133,"44":133,"45":135,"48":135,"49":137,"52":137,"53":143} */

?>