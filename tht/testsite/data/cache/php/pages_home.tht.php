<?php

namespace tht56c96bb25769e47a6a07ef140e39e79f;
\o\Runtime::setNameSpace('pages/home.tht','tht56c96bb25769e47a6a07ef140e39e79f');

function u_main ()  {
  $u_test = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Test'))->u_new();
u_run($u_test);
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_send_html(u_html(\o\v($u_test)->u_results_html()));
 return new \o\ONothing(__METHOD__);
 
}
function u_html ($u_results)  {
$t = \o\Runtime::openTemplate("html");
$t->addStatic("<!-- this is a comment --><html><head><title>THT Unit Tests</title>");
$t->addDynamic(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Css'))->u_include("base"));
$t->addStatic("</head><body><main><h1>THT Unit Tests</h1><a href=\"#test-results\">Skip to Results</a>");
$t->addDynamic($u_results);
$t->addStatic("</main></body></html>");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_run ($u_t)  {
  u_test_math_and_logic($u_t);
u_test_strings($u_t);
u_test_control_flow($u_t);
u_test_lists($u_t);
u_test_maps($u_t);
u_test_functions($u_t);
u_test_types($u_t);
u_test_misc($u_t);
u_test_templates($u_t);
u_runtime_errors($u_t);
u_compile_errors($u_t);
u_lib_file($u_t);
u_lib_date($u_t);
u_lib_db($u_t);
u_lib_jcon_test($u_t);
u_lib_js($u_t);
u_lib_json($u_t);
u_lib_litemark($u_t);
u_lib_math($u_t);
u_lib_perf($u_t);
u_lib_php($u_t);
u_lib_web($u_t);
u_lib_global($u_t);
u_lib_settings($u_t);
u_lib_session($u_t);
u_lib_cache($u_t);
u_lib_net($u_t);
 return new \o\ONothing(__METHOD__);
 
}
function u_runtime_errors ($u_t)  {
  \o\v($u_t)->u_section("Runtime Errors");
\o\v($u_t)->u_dies(function  ()  {
  \o\v("abc")->u_sdf();
 return new \o\ONothing(__METHOD__);
 
}
, "non-existent method");
\o\v($u_t)->u_dies(function  ()  {
  \o\v("abc {1}")->u_fill(\o\OList::create([ "foo" ]));
 return new \o\ONothing(__METHOD__);
 
}
, "bad fill value");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\OMap::create([ 'a' => 1 ]))->u_sdfsdf();
 return new \o\ONothing(__METHOD__);
 
}
, "invalid method");
\o\v($u_t)->u_dies(function  ()  {
  $u_a = \o\v("sdf")->u_reverse;
 return new \o\ONothing(__METHOD__);
 
}
, "missing parens in method call");
$u_fun_for = function  ()  {
  foreach (\o\uv(2) as $u_foo) {

}
 return new \o\ONothing(__METHOD__);
 
}
;
\o\v($u_t)->u_dies($u_fun_for, "Invalid argument");
\o\v($u_t)->u_dies(function  ()  {
  return \o\v("abc")->u_length;
 return new \o\ONothing(__METHOD__);
 
}
, "length()");
 return new \o\ONothing(__METHOD__);
 
}
function u_compile_errors ($u_t)  {
  \o\v($u_t)->u_section("Parser");
$u_code = "// test comments

/*
    this is a block comment
*/

let commented = 2; // line-end comment";
\o\v($u_t)->u_parser_ok($u_code, "comments");
$u_long_comment = \o\Runtime::concat(\o\Runtime::concat("// ", \o\v(\o\Runtime::getModule(__NAMESPACE__, 'String'))->u_repeat("a", 102)), "\n");
\o\v($u_t)->u_parser_ok($u_long_comment, "line comment over 100 chars");
$u_long_block_comment = \o\Runtime::concat(\o\Runtime::concat("/*\n", \o\v(\o\Runtime::getModule(__NAMESPACE__, 'String'))->u_repeat("a", 102)), "\n*/");
\o\v($u_t)->u_parser_ok($u_long_block_comment, "block comment over 100 chars");
\o\v($u_t)->u_section("Parser Errors - Names");
\o\v($u_t)->u_parser_error("let FOO = 3;", "camelCase");
\o\v($u_t)->u_parser_error("let fOO = 3;", "camelCase");
\o\v($u_t)->u_parser_error("let XMLreader = {};", "camelCase");
\o\v($u_t)->u_parser_error("let a_b = 3;", "camelCase");
\o\v($u_t)->u_parser_error("function FOO() {}", "camelCase");
\o\v($u_t)->u_parser_error("function a () {}", "longer than 1");
$u_long_name = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'String'))->u_repeat("a", 41);
\o\v($u_t)->u_parser_error(\o\Runtime::concat(\o\Runtime::concat("let ", $u_long_name), " = 1;"), "40 characters or less");
\o\v($u_t)->u_parser_error(\o\Runtime::concat(\o\Runtime::concat("function ", $u_long_name), " () {}"), "40 characters or less");
\o\v($u_t)->u_section("Parser Errors - Aliases");
\o\v($u_t)->u_parser_error("var a = 3;", "try: `let`");
\o\v($u_t)->u_parser_error("foreach (ary as a) { }", "try: `for`");
\o\v($u_t)->u_parser_error("let ary = [];\nfor (ary as a) { }", "item in list");
\o\v($u_t)->u_parser_error("\$foo = 123", "remove \$ from name");
\o\v($u_t)->u_section("Parser Errors - Misc");
\o\v($u_t)->u_parser_error("asdasd", "unknown variable", "");
\o\v($u_t)->u_parser_error("if (a = 3) { }", "assignment", "if, missing paren");
\o\v($u_t)->u_parser_error("break;\nlet a = 3;", "unreachable");
\o\v($u_t)->u_parser_ok("return;\nlet a = 3;", "may return early");
\o\v($u_t)->u_parser_ok("if (true) { break; }", "newline not needed for one-line if");
\o\v($u_t)->u_parser_ok("function foo() { return 1; }", "newline not needed for one-line fun");
\o\v($u_t)->u_parser_error("let a = 'hello", "unexpected newline");
\o\v($u_t)->u_parser_error("for (a) {}", "expected 'in'");
\o\v($u_t)->u_parser_ok("\t let a\t=\t1;", "tabs");
\o\v($u_t)->u_parser_error("for (let i = 0; i < 10; i++) {}", "unexpected 'let'");
\o\v($u_t)->u_parser_error("1 ? 2 ? 3 : 4 : 5", "nested ternary");
\o\v($u_t)->u_parser_error("let a = E'foo';", "string modifier");
\o\v($u_t)->u_parser_error("let a = l'foo';", "uppercase");
\o\v($u_t)->u_parser_error("let a == 123;", "expected '='");
\o\v($u_t)->u_parser_error("let a;", "expected '='");
\o\v($u_t)->u_parser_error("if (2 => 1) { }", ">=");
\o\v($u_t)->u_parser_error("if (1 =< 2) { }", "<=");
\o\v($u_t)->u_parser_error("let a = 1 <> 2;", "!=");
\o\v($u_t)->u_parser_error("function foo();", "Unexpected semicolon");
\o\v($u_t)->u_parser_error("if (a == 3) .", "Expected '{'");
\o\v($u_t)->u_parser_error("function foo(),", "Unexpected comma");
\o\v($u_t)->u_parser_error("let a = { FOO: 'bar' };", "camelCase");
\o\v($u_t)->u_parser_error("let a = { foo: 'bar', foo: 1 };", "duplicate key");
\o\v($u_t)->u_parser_error("if (true) return;", "Expected '{'");
\o\v($u_t)->u_parser_error("print('a'), print('b');", "comma");
\o\v($u_t)->u_parser_error("let a = 1, b = 2;", "Missing semicolon", "");
\o\v($u_t)->u_parser_error("let a = (1 + );", "incomplete");
\o\v($u_t)->u_parser_error("let a = 2 + (1 * ) + 1;", "incomplete");
\o\v($u_t)->u_parser_error("<?", "Unexpected symbol '<'");
\o\v($u_t)->u_parser_error("?>", "Unexpected symbol '?'");
\o\v($u_t)->u_parser_error("'hello'[] = 'a';", "Assignment can not");
\o\v($u_t)->u_section("Parser Errors - Adjacent Tokens");
\o\v($u_t)->u_parser_error("let a = foo foo;", "unexpected word");
\o\v($u_t)->u_parser_error("let a = 'foo' foo;", "unexpected word");
\o\v($u_t)->u_parser_error("let a = 123 foo;", "unexpected word");
\o\v($u_t)->u_parser_error("let a = foo 'foo';", "unexpected string");
\o\v($u_t)->u_parser_error("let a = 123 'foo';", "unexpected string");
\o\v($u_t)->u_parser_error("let a = foo 123;", "unexpected number");
\o\v($u_t)->u_parser_error("let a = 'foo' 123;", "unexpected number");
\o\v($u_t)->u_parser_error("let a = [1, 2 3]", "unexpected number");
\o\v($u_t)->u_parser_error("let a = { k: a, b c }", "unexpected word");
\o\v($u_t)->u_section("Parser Errors - Newlines");
\o\v($u_t)->u_parser_error("let a = ''' sdf", "newline");
\o\v($u_t)->u_parser_error("let a = '''\ndfg ''';", "triple-quote");
\o\v($u_t)->u_parser_error("let a = 1; /*\n", "separate line");
\o\v($u_t)->u_parser_error("/*\nsdf\n*/ d", "missing newline");
\o\v($u_t)->u_parser_error("/*\nsdf", "unclosed comment");
\o\v($u_t)->u_parser_error("template fooText() {\n};", "missing newline");
\o\v($u_t)->u_section("Parser Errors - Spaces");
\o\v($u_t)->u_parser_error("function(){}", "space after 'function'");
\o\v($u_t)->u_parser_error("function foo () {}", "space before '('");
\o\v($u_t)->u_parser_error("function foo(){}", "space after ')'");
\o\v($u_t)->u_parser_error("function foo{}", "space before '{'");
\o\v($u_t)->u_parser_error("function (){}", "space after ')'");
\o\v($u_t)->u_parser_error("a = function() {};", "space after 'function'");
\o\v($u_t)->u_parser_error("F foo() {a = 1 }", "space after '{'");
\o\v($u_t)->u_parser_error("( a + 1)", "space after '('");
\o\v($u_t)->u_parser_ok("let a = (\n1 +\n2\n);", "space after '('");
\o\v($u_t)->u_parser_error("foo( );", "space after '('");
\o\v($u_t)->u_parser_error("let a = [ ]", "space after '['");
\o\v($u_t)->u_parser_error("let a = { }", "space after '{'");
\o\v($u_t)->u_parser_ok("let a = [\n];", "space after '['");
\o\v($u_t)->u_parser_ok("let a = {\n};", "space after '{'");
\o\v($u_t)->u_parser_error("let a = b[ 0]", "space after '['");
\o\v($u_t)->u_parser_error("let a = b[0 ]", "space before ']'");
\o\v($u_t)->u_parser_error("let a= 1+2;", "space before '='");
\o\v($u_t)->u_parser_error("let a =1+2;", "space after '='");
\o\v($u_t)->u_parser_error("let a = 1+ 2;", "space before '+'");
\o\v($u_t)->u_parser_error("let a = 1 +2;", "space after '+'");
\o\v($u_t)->u_parser_error("let a = {a:'b'}", "space after '{'");
\o\v($u_t)->u_parser_error("let a = { a:'b'}", "space after ':'");
\o\v($u_t)->u_parser_error("let a = { a : 'b'}", "space before ':'");
\o\v($u_t)->u_parser_error("let a = [a,b,c]", "space after ','");
\o\v($u_t)->u_parser_error("if(true) {}", "space after 'if'");
\o\v($u_t)->u_parser_error("if ( true) {}", "space after '('");
\o\v($u_t)->u_parser_error("if (true){}", "space after ')'");
\o\v($u_t)->u_parser_error("return(a);", "space after 'return'");
\o\v($u_t)->u_parser_error("a,b,c", "space after ','");
\o\v($u_t)->u_parser_error("return a ;", "space before ';'");
\o\v($u_t)->u_parser_error("a? 1 : 2;", "space before '?'");
\o\v($u_t)->u_parser_error("a ?1 : 2;", "space after '?'");
\o\v($u_t)->u_parser_error("a ? 1: 2;", "space before ':'");
\o\v($u_t)->u_parser_error("a ? 1 :2;", "space after ':'");
\o\v($u_t)->u_parser_error("let a = 1;let a = 2;", "space after ';'");
\o\v($u_t)->u_parser_ok("if (true)\n{ }", "newline after ')'");
\o\v($u_t)->u_parser_ok("else if (true)\n{ }", "newline after ')'");
\o\v($u_t)->u_parser_ok("if (true) {\n}\nelse\n{ }", "newline after 'else'");
\o\v($u_t)->u_parser_ok("for (a in ['a'])\n{ }", "newline after ')'");
\o\v($u_t)->u_parser_ok("function fn()\n{ }", "newline after ')'");
\o\v($u_t)->u_section("Parser Errors - Templates");
\o\v($u_t)->u_parser_error("template fHtml() {<", "newline");
\o\v($u_t)->u_parser_error("template fHtml() {\n  ::for", "space after '::'");
\o\v($u_t)->u_parser_error("template fHtml() {\n  :: for", "must end");
\o\v($u_t)->u_parser_error("template fHtml() {\n  {{5 }}\n}\n", "space after '{{'");
\o\v($u_t)->u_parser_error("template fHtml() {\n  {{ 5}}\n}\n", "space before '}}'");
\o\v($u_t)->u_parser_error("template fHtml() {\n  {{ \n5 }}", "unexpected newline");
\o\v($u_t)->u_parser_error("template fHtml() {\n  <hr>", "self-closing");
\o\v($u_t)->u_parser_error("template fHtml() {\n  <b>Hi</div>", "expected '</b>'");
\o\v($u_t)->u_parser_error("template fHtml() {\n  <b>Hi</b></b>", "extra closing tag");
\o\v($u_t)->u_parser_error("function fHtml() {\n  <", "unexpected '<'", "");
\o\v($u_t)->u_parser_error("template foo() {\n", "missing type");
\o\v($u_t)->u_section("Parser Errors - Assignment as Expression");
\o\v($u_t)->u_parser_error("let b = 1;\nlet a = b = 3;", "assignment can not");
\o\v($u_t)->u_parser_error("let b = 1;\nlet a = b += 3;", "assignment can not");
\o\v($u_t)->u_parser_error("let a = { b: c = 1 }", "assignment can not");
\o\v($u_t)->u_parser_error("print(a = 3);", "assignment can not");
\o\v($u_t)->u_parser_error("a[b = 3]", "assignment can not");
\o\v($u_t)->u_parser_error("for (b in a = 3) {}", "assignment can not");
\o\v($u_t)->u_parser_error("if (a = 3) {}", "assignment can not");
\o\v($u_t)->u_parser_error("F foo() { return a = 3;\n }", "assignment can not");
\o\v($u_t)->u_section("Parser Errors - Scope");
\o\v($u_t)->u_parser_error("a = 3;", "unknown variable");
\o\v($u_t)->u_parser_error("let a = 1;\nlet a = 2;", "already defined");
\o\v($u_t)->u_parser_error("let fOo = 1;\nlet foO = 2;", "already defined");
\o\v($u_t)->u_parser_error("let a = 1;\nif (a == 1) {\n let a = 2;\n}", "already defined");
\o\v($u_t)->u_parser_error("if (true) {\n let a = 1;\n let a = 2;\n}", "already defined");
\o\v($u_t)->u_parser_ok("if (true) {\n let a = 1; }\nif (true) { let a = 2;\n }", "already defined");
\o\v($u_t)->u_parser_error("let a = 1;\nfunction foo(a) {}", "already defined");
\o\v($u_t)->u_parser_error("function foo(a, a) {}", "already defined");
\o\v($u_t)->u_parser_error("function foo(a = 1, a) {}", "already defined");
\o\v($u_t)->u_parser_error("function foo() { }\nfunction foo() { }", "already defined");
\o\v($u_t)->u_parser_error("function foo() { }\nfunction fOo() { }", "already defined");
\o\v($u_t)->u_parser_error("let a = 1;\nfor (a in ary) {}", "already defined");
\o\v($u_t)->u_parser_error("let print = 123;", "core function");
\o\v($u_t)->u_parser_error("let finally = 123;", "reserved");
\o\v($u_t)->u_parser_error("function foo() keep (a) { }", "unknown variable");
\o\v($u_t)->u_parser_error("b = 4;", "unknown variable");
\o\v($u_t)->u_parser_error("let fOo = 1;\nfoO = 2;", "unknown variable");
\o\v($u_t)->u_parser_error("let a = a + 1;", "unknown variable");
\o\v($u_t)->u_parser_error("function foo() { }\nfOo();", "case mismatch", "");
 return new \o\ONothing(__METHOD__);
 
}
function u_test_misc ($u_t)  {
  \o\v($u_t)->u_section("Performance");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Perf'))->u_start("Large Array");
$u_now = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Date'))->u_now(true);
$u_num_els = 1000;
$u_nums = \o\OBare::u_range(1, $u_num_els);
$u_ii = 0;
foreach (\o\uv($u_nums) as $u_nn) {
$u_b = \o\v($u_nums)[$u_ii];
$u_ii += \o\vn(1, 1);

}
\o\v($u_t)->u_ok(($u_ii === $u_num_els), "large loop done");
$u_elapsed = (\o\vn(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Date'))->u_now(true), 0) - \o\vn($u_now, 0));
\o\v($u_t)->u_ok((\o\vn($u_elapsed, 0) < \o\vn(3, 0)), \o\v("ArrayAccess loop ({0} elements) took {1} ms")->u_fill($u_num_els, $u_elapsed));
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Perf'))->u_stop();
\o\v($u_t)->u_section("Functional Methods");
\o\v($u_t)->u_section("Result Objects");
$u_st = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Result'))->u_ok(123);
\o\v($u_t)->u_ok(\o\v($u_st)->u_ok(), "not ok");
\o\v($u_t)->u_ok((\o\v($u_st)->u_get() === 123), "ok value");
$u_st = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Result'))->u_fail(66);
\o\v($u_t)->u_ok((! \o\v($u_st)->u_ok()), "not ok");
\o\v($u_t)->u_ok((\o\v($u_st)->u_fail_code() === 66), "failCode");
\o\v($u_t)->u_section("Modules");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'TestModule'))->u_bare_fun("Joe") === "bare:Joe"), "module call - autoloaded");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global'))->u_foo = "BAR";
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'TestModule'))->u_test_global() === "global:BAR"), "module global");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'TestModule'))->u_test_module_var() === "moduleVar:mod"), "module var - inside access");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'TestModule'))->u_module_var === "mod"), "module var - outside access");
\o\OBare::u_import(__NAMESPACE__, "subDir/OtherModule");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'OtherModule'))->u_ok("Joe") === "ok:Joe"), "import from subfolder");
\o\v($u_t)->u_section("Classes (OOP)");
$u_tc = \o\Runtime::newObject(__NAMESPACE__, "TestClass", ["green", 123]);
\o\v($u_t)->u_ok((\o\v($u_tc)->u_test() === "class:green123"), "new object");
\o\v($u_t)->u_ok((\o\v($u_tc)->u_name === "green"), "get property");
\o\v($u_t)->u_ok((\o\v(\o\v($u_tc)->u_html())->u_unlocked() === "<b>Hello</b>\n"), "object template");
\o\v($u_t)->u_ok((\o\v($u_tc)->u_get_hidden() === 0), "get state");
\o\v($u_tc)->u_set_hidden(345);
\o\v($u_t)->u_ok((\o\v($u_tc)->u_get_hidden() === 345), "set state");
\o\v($u_t)->u_ok((\o\v(\o\v($u_tc)->u_all_state())["hiddenNum"] === 345), "all state");
\o\v($u_t)->u_ok((\o\v($u_tc)->u_dependency() === "other"), "dependency");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\v($u_tc)->u_state)["abc"] = 123;
 return new \o\ONothing(__METHOD__);
 
}
, "private state");
\o\v($u_t)->u_dies(function  ()  {
  \o\v($u_tc)->u_foo = 123;
 return new \o\ONothing(__METHOD__);
 
}
, "Fields locked after construction");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'TestClass'))->u_factory())->u_name === "factory"), "module factory");
\o\v($u_t)->u_ok(\o\v(\o\v($u_tc)->u_z_methods())->u_contains("getHidden"), "zMethods");
\o\v($u_t)->u_ok((\o\v(\o\v($u_tc)->u_z_fields())->u_name === "green"), "zFields");
\o\v($u_t)->u_ok((\o\v($u_tc)->u_z_get_field("num") === 123), "zGetField");
\o\v($u_tc)->u_z_set_field("name", "blue");
\o\v($u_t)->u_ok((\o\v($u_tc)->u_name === "blue"), "zSetField");
\o\v($u_tc)->u_z_call_method("setHidden", \o\OList::create([ 789 ]));
\o\v($u_t)->u_ok((\o\v($u_tc)->u_z_call_method("getHidden") === 789), "zCallMethod");
\o\v($u_t)->u_ok((\o\v($u_tc)->u_ok_field === "dynamic:okField"), "zDynamicGet ok");
\o\v($u_t)->u_dies(function  ()  {
  \o\v($u_tc)->u_bad_field = 1;
 return new \o\ONothing(__METHOD__);
 
}
, "zDynamicGet fail");
\o\v($u_t)->u_ok((\o\v($u_tc)->u_get_secret_number() === 42), "zDynamicCall");
\o\v($u_t)->u_ok(\o\v($u_tc)->u_z_has_method("setHidden"), "zHasMethod true");
\o\v($u_t)->u_ok((! \o\v($u_tc)->u_z_has_method("sethidden")), "zHasMethod false");
\o\v($u_t)->u_ok(\o\v($u_tc)->u_z_has_field("num"), "zHasField true");
\o\v($u_t)->u_ok((! \o\v($u_tc)->u_z_has_field("Num")), "zHasField false");
\o\OBare::u_import(__NAMESPACE__, "subDir/OtherClass");
$u_oc = \o\Runtime::newObject(__NAMESPACE__, "OtherClass", []);
\o\v($u_t)->u_ok((\o\v($u_oc)->u_ok() === "other"), "OtherClass");
 return new \o\ONothing(__METHOD__);
 
}
function u_test_types ($u_t)  {
  \o\v($u_t)->u_section("Types");
\o\v($u_t)->u_ok(\o\v(\o\OList::create([  ]))->u_is_list(), "list");
\o\v($u_t)->u_ok(\o\v(\o\OMap::create([  ]))->u_is_map(), "map");
\o\v($u_t)->u_ok(\o\v("foo")->u_is_string(), "string");
$u_n = 123;
\o\v($u_t)->u_ok(\o\v($u_n)->u_is_number(), "number");
$u_f = true;
\o\v($u_t)->u_ok(\o\v($u_f)->u_is_flag(), "flag");
$u_fn = function  ()  {
   return new \o\ONothing(__METHOD__);
 
}
;
\o\v($u_t)->u_ok(\o\v($u_fn)->u_is_function(), "function");
\o\v($u_t)->u_section("Empty Values");
\o\v($u_t)->u_ok(\o\v(\o\OList::create([  ]))->u_is_empty(), "empty list");
\o\v($u_t)->u_ok(\o\v(\o\OMap::create([  ]))->u_is_empty(), "empty map");
\o\v($u_t)->u_ok(\o\v("")->u_is_empty(), "empty string");
$u_n = 0;
\o\v($u_t)->u_ok(\o\v($u_n)->u_is_empty(), "empty num");
$u_f = false;
\o\v($u_t)->u_ok(\o\v($u_f)->u_is_empty(), "empty flag");
\o\v($u_t)->u_ok((! \o\v(\o\OList::create([ 1, 2, 3 ]))->u_is_empty()), "non-empty list");
\o\v($u_t)->u_ok((! \o\v(\o\OMap::create([ 'foo' => 0 ]))->u_is_empty()), "non-empty map");
\o\v($u_t)->u_ok((! \o\v("abc")->u_is_empty()), "non-empty string");
$u_n = 0.1;
\o\v($u_t)->u_ok((! \o\v($u_n)->u_is_empty()), "non-empty num");
$u_f = true;
\o\v($u_t)->u_ok((! \o\v($u_f)->u_is_empty()), "non-empty flag");
 return new \o\ONothing(__METHOD__);
 
}
function u_test_functions ($u_t)  {
  \o\v($u_t)->u_section("Functions");
function u_test ()  {
  return "yay";
 return new \o\ONothing(__METHOD__);
 
}
\o\v($u_t)->u_ok((u_test() === "yay"), "no args");
function u_test_a ($u_arg)  {
  return \o\Runtime::concat($u_arg, "!");
 return new \o\ONothing(__METHOD__);
 
}
\o\v($u_t)->u_ok((u_test_a("hey") === "hey!"), "with arg");
function u_test_b ($u_arg="default")  {
  return \o\Runtime::concat($u_arg, "!");
 return new \o\ONothing(__METHOD__);
 
}
\o\v($u_t)->u_ok((u_test_b() === "default!"), "default");
function u_test_sum ()  {
  $u_asum = 0;
foreach (\o\uv(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_arguments()) as $u_arg) {
$u_asum += \o\vn($u_arg, 1);

}
return $u_asum;
 return new \o\ONothing(__METHOD__);
 
}
$u_sum = u_test_sum(1, 2, 3, 4);
\o\v($u_t)->u_ok(($u_sum === 10), "variable args");
function u_with_op ($u_foo, $u_bar="default")  {
  return $u_bar;
 return new \o\ONothing(__METHOD__);
 
}
$u_r = u_with_op("hello", "world");
\o\v($u_t)->u_ok(($u_r === "world"), "default, supplied");
$u_r = u_with_op("hello");
\o\v($u_t)->u_ok(($u_r === "default"), "default, fallback");
$u_outer = "OUT";
$u_fun_closure = function  ($u_a) use ($u_outer) {
  return \o\Runtime::concat(\o\Runtime::concat($u_a, "/"), $u_outer);
 return new \o\ONothing(__METHOD__);
 
}
;
\o\v($u_t)->u_ok(($u_fun_closure("IN") === "IN/OUT"), "closure");
function u_add_to_list ($u_l)  {
  $u_l []= 4;
 return new \o\ONothing(__METHOD__);
 
}
$u_ref_list = \o\OList::create([ 1, 2, 3 ]);
u_add_to_list($u_ref_list);
\o\v($u_t)->u_ok((\o\v($u_ref_list)->u_length() === 4), "list (object) - pass by ref - changed");
\o\v($u_ref_list)->u_reverse();
\o\v($u_t)->u_ok((\o\v($u_ref_list)[0] === 1), "list.reverse - not changed in place");
function u_add_to_string ($u_s)  {
  $u_s .= "4";
 return new \o\ONothing(__METHOD__);
 
}
$u_ref_str = "123";
u_add_to_string($u_ref_str);
\o\v($u_t)->u_ok((\o\v($u_ref_str)->u_length() === 3), "string - pass by ref - unchanged");
$u_fn_no_return = function  ()  {
  $u_v = u_no_return();
\o\v($u_v)->u_reverse();
 return new \o\ONothing(__METHOD__);
 
}
;
\o\v($u_t)->u_dies($u_fn_no_return, "returned Nothing");
function u_missing_args ($u_arg1, $u_arg2)  {
   return new \o\ONothing(__METHOD__);
 
}
\o\v($u_t)->u_dies(function  ()  {
  u_missing_args(1);
 return new \o\ONothing(__METHOD__);
 
}
, "Missing argument - user function");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_read();
 return new \o\ONothing(__METHOD__);
 
}
, "Missing argument - module");
\o\v($u_t)->u_ok((\o\v(u_test_default_map())->u_a === 123), "map as default arg");
\o\v($u_t)->u_ok((\o\v(u_test_default_map(\o\OMap::create([ 'a' => 345 ])))->u_a === 345), "map as default arg - with passed arg");
\o\v($u_t)->u_ok((\o\v(u_test_default_list())[1] === "b"), "list as default arg");
\o\v($u_t)->u_ok((\o\v(u_test_default_list(\o\OList::create([ "x", "y", "z" ])))[1] === "y"), "list as default arg - with passed arg");
\o\v($u_t)->u_ok((\o\v(u_test_default_maps())->u_a === "aa"), "multiple default args as maps");
\o\v($u_t)->u_ok((\o\v(\o\v(u_test_default_maps())->u_m2)->u_b === "bb"), "multiple default args as maps");
\o\v($u_t)->u_section("Function - Argument Checking");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_string(""), "string");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_number(123), "number");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_list(\o\OList::create([  ])), "list");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_flag(false), "flag");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_map(\o\OMap::create([  ])), "map");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_multi("", 0, \o\OList::create([  ])), "multi: string, number, list");
\o\v($u_t)->u_dies(function  ()  {
  \o\v($u_t)->u_check_args_map(true, true);
 return new \o\ONothing(__METHOD__);
 
}
, "Too many args");
\o\v($u_t)->u_dies(function  ()  {
  \o\v($u_t)->u_check_args_map(\o\OList::create([  ]));
 return new \o\ONothing(__METHOD__);
 
}
, "Expect map.  Got List.");
\o\v($u_t)->u_dies(function  ()  {
  \o\v($u_t)->u_check_args_map("x");
 return new \o\ONothing(__METHOD__);
 
}
, "Expect map. Got String");
\o\v($u_t)->u_dies(function  ()  {
  \o\v($u_t)->u_check_args_map(123);
 return new \o\ONothing(__METHOD__);
 
}
, "Expect map. Got Number");
\o\v($u_t)->u_dies(function  ()  {
  \o\v($u_t)->u_check_args_map(true);
 return new \o\ONothing(__METHOD__);
 
}
, "Expect map. Got Flag");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_string(123), "Number as string");
\o\v($u_t)->u_dies(function  ()  {
  \o\v($u_t)->u_check_args_number("123");
 return new \o\ONothing(__METHOD__);
 
}
, "String as number");
\o\v($u_t)->u_dies(function  ()  {
  \o\v($u_t)->u_check_args_multi(true, 123, \o\OList::create([  ]));
 return new \o\ONothing(__METHOD__);
 
}
, "Multi (snl): bad #1");
\o\v($u_t)->u_dies(function  ()  {
  \o\v($u_t)->u_check_args_multi("", "123", \o\OList::create([  ]));
 return new \o\ONothing(__METHOD__);
 
}
, "Multi (snl): bad #2");
\o\v($u_t)->u_dies(function  ()  {
  \o\v($u_t)->u_check_args_multi("", 123, "x");
 return new \o\ONothing(__METHOD__);
 
}
, "Multi (snl): bad #3");
 return new \o\ONothing(__METHOD__);
 
}
function u_test_maps ($u_t)  {
  \o\v($u_t)->u_section("Maps");
$u_user = \o\OMap::create([ 'name' => "Drac", 'age' => 500, 'friends' => \o\OList::create([ \o\OMap::create([ 'name' => "Igor" ]) ]) ]);
\o\v($u_t)->u_ok((\o\v($u_user)["name"] === "Drac"), "bracket");
\o\v($u_t)->u_ok((\o\v($u_user)->u_get("name") === "Drac"), "get");
\o\v($u_t)->u_ok((\o\v($u_user)->u_length() === 3), "length");
\o\v($u_t)->u_ok((\o\v($u_user)->u_get("foo", "bar") === "bar"), "default");
\o\v($u_t)->u_ok((\o\v($u_user)->u_get(\o\OList::create([ "friends", 0, "name" ])) === "Igor"), "chained");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v($u_user)["friends"])[0])["name"] === "Igor"), "chained brackets");
\o\v($u_t)->u_ok((\o\v($u_user)->u_get(\o\OList::create([ "friends", 1, "name" ]), false) === false), "chained fail");
\o\v($u_user)["height"] = "6ft";
\o\v($u_t)->u_ok((\o\v($u_user)->u_get("height") === "6ft"), "put");
$u_map_num = \o\OMap::create([ 'name' => "Frank", 'age' => 8 ]);
\o\v($u_t)->u_ok((\o\v($u_map_num)->u_get("age") === 8), "numeric val");
$u_mlmap = \o\OMap::create([ 'name' => "Joe", 'id' => 12345 ]);
\o\v($u_t)->u_ok((\o\v($u_mlmap)->u_id === 12345), "multiline map");
\o\v($u_mlmap)["foo"] = \o\v($u_mlmap)["foo"] ?: 33;
\o\v($u_t)->u_ok((\o\v($u_mlmap)->u_foo === 33), "default assign");
\o\v($u_t)->u_section("Maps - dot access");
\o\v($u_t)->u_ok((\o\v($u_user)->u_name === "Drac"), "dot access");
\o\v($u_t)->u_dies(function  () use ($u_user) {
  \o\OBare::u_print(\o\v($u_user)->u_name_x);
 return new \o\ONothing(__METHOD__);
 
}
, "dot access - missing field dies");
\o\v($u_t)->u_section("Maps - Missing values");
$u_empty = \o\OMap::create([ 'foo' => 1 ]);
\o\v($u_t)->u_ok((\o\Runtime::concat(\o\v($u_empty)["missing"], "yo") === "yo"), "empty concat");
\o\v($u_t)->u_ok((\o\v($u_empty)["missing"] === ""), "empty string");
\o\v($u_t)->u_ok(((! \o\v($u_empty)["missing"]) === true), "empty bool");
\o\v($u_empty)["def"] = \o\v($u_empty)["def"] ?: "default";
\o\v($u_t)->u_ok((\o\v($u_empty)["def"] === "default"), "empty or assign");
\o\v($u_t)->u_section("Maps - Explicit default");
$u_dmap = \o\v(\o\OMap::create([ 'foo' => 1 ]))->u_default("HAY");
\o\v($u_t)->u_ok((\o\v($u_dmap)["missing"] === "HAY"), "Map default - not found");
\o\v($u_t)->u_ok((\o\v($u_dmap)["foo"] === 1), "Map default - found");
$u_count_map = \o\v(\o\OMap::create([  ]))->u_default(0);
\o\v($u_count_map)["a"] += \o\vn(100, 1);
\o\v($u_t)->u_ok((\o\v($u_count_map)->u_a === 100), "numeric default");
\o\v($u_t)->u_section("Maps - Methods");
$u_map = \o\OMap::create([ 'a' => 1, 'b' => 2 ]);
\o\v($u_t)->u_ok(\o\v($u_map)->u_has_key("b"), "hasKey true");
\o\v($u_t)->u_ok((! \o\v($u_map)->u_has_key("z")), "hasKey false");
\o\v($u_t)->u_ok(\o\v($u_map)->u_has_value(2), "hasValue true");
\o\v($u_t)->u_ok((! \o\v($u_map)->u_has_value(99)), "hasValue false");
\o\v($u_t)->u_ok((\o\v(\o\v($u_map)->u_keys())->u_join("|") === "a|b"), "keys");
\o\v($u_t)->u_ok((\o\v(\o\v($u_map)->u_values())->u_join("|") === "1|2"), "values");
$u_map2 = \o\v($u_map)->u_copy();
\o\v($u_map2)["b"] = 3;
\o\v($u_t)->u_ok((\o\v($u_map)->u_b === 2), "copy");
\o\v($u_t)->u_ok((\o\v($u_map2)->u_b === 3), "copy");
\o\v($u_t)->u_ok((! \o\v($u_map2)->u_is_empty()), "not isEmpty");
\o\v($u_map2)->u_clear();
\o\v($u_t)->u_ok(\o\v($u_map2)->u_is_empty(), "clear / isEmpty");
\o\v($u_t)->u_ok((\o\v($u_map)->u_remove("b") === 2), "delete - key exists");
\o\v($u_t)->u_dies(function  () use ($u_map) {
  \o\v($u_map)->u_remove("Z");
 return new \o\ONothing(__METHOD__);
 
}
, "delete - key nonexistent");
\o\v($u_t)->u_ok((\o\v(\o\v($u_map)->u_keys())->u_length() === 1), "delete - modified map");
$u_map = \o\OMap::create([ 'a' => 1, 'b' => 2, 'c' => 1 ]);
$u_flipped = \o\v($u_map)->u_reverse();
\o\v($u_t)->u_ok((\o\v($u_flipped)["1"] === "c"), "reverse");
\o\v($u_t)->u_ok((\o\v($u_flipped)["2"] === "b"), "reverse");
\o\v($u_t)->u_ok((\o\v($u_flipped)->u_length() === 2), "reverse length");
\o\v($u_t)->u_section("Maps - Size Errors");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\OMap::create([  ]))->u_remove("Z");
 return new \o\ONothing(__METHOD__);
 
}
, "Map key not found");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\OMap::create([  ]))->u_get_key("VAL");
 return new \o\ONothing(__METHOD__);
 
}
, "Map value not found");
 return new \o\ONothing(__METHOD__);
 
}
function u_test_math_and_logic ($u_t)  {
  \o\v($u_t)->u_section("Math operators");
$u_a = 2;
$u_b = (\o\vn((\o\vn($u_a, 1) + \o\vn(1, 1)), 1) + \o\vn(2, 1));
$u_c = (\o\vn((\o\vn($u_a, 0) * \o\vn(3, 0)), 1) + \o\vn(1, 1));
$u_d = (\o\vn($u_a, 0) / \o\vn(2, 0));
$u_e = (\o\vn(5, 0) % \o\vn(2, 0));
$u_f = (\o\vn(3, 1) + \o\vn((- 1), 1));
$u_g = (\o\vn((- 1), 1) + \o\vn((- 1), 1));
$u_h = (\o\vn(2, 0) ** \o\vn(4, 0));
\o\v($u_t)->u_ok(($u_a === 2), "assign");
\o\v($u_t)->u_ok(($u_b === 5), "add");
\o\v($u_t)->u_ok(($u_c === 7), "mult, add");
\o\v($u_t)->u_ok(($u_d === 1), "divide");
\o\v($u_t)->u_ok(($u_e === 1), "modulo");
\o\v($u_t)->u_ok(($u_f === 2), "plus negative");
\o\v($u_t)->u_ok(($u_h === 16), "exponent");
$u_fp = (\o\vn(1.1, 1) + \o\vn(2.2, 1));
\o\v($u_t)->u_ok(((\o\vn($u_fp, 0) > \o\vn(3.2, 0)) && (\o\vn($u_fp, 0) < \o\vn(3.4, 0))), "floating point");
\o\v($u_t)->u_ok(((\o\vn(1000000, 1) + \o\vn(2000, 1)) === 1002000), "_ separator");
\o\v($u_t)->u_section("Strict Math");
\o\v($u_t)->u_dies(function  ()  {
  return (\o\vn("a", 1) + \o\vn(2, 1));
 return new \o\ONothing(__METHOD__);
 
}
, "Add string to number");
\o\v($u_t)->u_dies(function  ()  {
  return (\o\vn(2, 1) + \o\vn("b", 1));
 return new \o\ONothing(__METHOD__);
 
}
, "Add number to string");
\o\v($u_t)->u_dies(function  ()  {
  return (\o\vn("a", 0) * \o\vn(2, 0));
 return new \o\ONothing(__METHOD__);
 
}
, "Multiply string");
\o\v($u_t)->u_dies(function  ()  {
  return (\o\vn("a", 0) % \o\vn(2, 0));
 return new \o\ONothing(__METHOD__);
 
}
, "Modulo string");
\o\v($u_t)->u_dies(function  ()  {
  return (\o\vn(true, 1) + \o\vn(2, 1));
 return new \o\ONothing(__METHOD__);
 
}
, "Add flag to number");
\o\v($u_t)->u_dies(function  ()  {
  return (\o\vn(\o\OMap::create([  ]), 1) + \o\vn(2, 1));
 return new \o\ONothing(__METHOD__);
 
}
, "Add Map to number");
\o\v($u_t)->u_dies(function  ()  {
  $u_aa = 1;
$u_aa += \o\vn("v", 1);
 return new \o\ONothing(__METHOD__);
 
}
, "+= string");
\o\v($u_t)->u_dies(function  ()  {
  return (\o\vn(1, 0) > \o\vn("a", 0));
 return new \o\ONothing(__METHOD__);
 
}
, "number > string");
\o\v($u_t)->u_dies(function  ()  {
  return (\o\vn(1, 0) >= \o\vn("a", 0));
 return new \o\ONothing(__METHOD__);
 
}
, "number >= string");
\o\v($u_t)->u_dies(function  ()  {
  return (\o\vn(1, 0) < \o\vn("a", 0));
 return new \o\ONothing(__METHOD__);
 
}
, "number < string");
\o\v($u_t)->u_dies(function  ()  {
  return (\o\vn(1, 0) <= \o\vn("a", 0));
 return new \o\ONothing(__METHOD__);
 
}
, "number <= string");
\o\v($u_t)->u_dies(function  ()  {
  return (\o\vn(2, 0) ** \o\vn("a", 0));
 return new \o\ONothing(__METHOD__);
 
}
, "number ** string");
\o\v($u_t)->u_dies(function  ()  {
  return (\o\vn(2, 0) / \o\vn(0, 0));
 return new \o\ONothing(__METHOD__);
 
}
, "divide by zero");
\o\v($u_t)->u_section("Truth");
\o\v($u_t)->u_ok((! false), "! false");
\o\v($u_t)->u_ok(true, "true");
\o\v($u_t)->u_ok((true || false), "||");
\o\v($u_t)->u_ok((true && true), "&&");
\o\v($u_t)->u_ok((! (true && false)), "! &&");
\o\v($u_t)->u_ok((! (false || false)), "! ||");
\o\v($u_t)->u_section("Positive/Negative");
\o\v($u_t)->u_ok((\o\vn((- 1), 0) < \o\vn(1, 0)), "< negative");
\o\v($u_t)->u_ok((\o\vn(1, 0) > \o\vn((- 1), 0)), "> negative");
\o\v($u_t)->u_ok(((\o\vn(2, 0) * \o\vn((- 1), 0)) === (- 2)), "times negative");
\o\v($u_t)->u_ok(((\o\vn((+ 2), 1) + \o\vn((+ 2), 1)) === 4), "unary plus");
\o\v($u_t)->u_section("Comparison");
\o\v($u_t)->u_ok((1 === 1), "==");
\o\v($u_t)->u_ok((1 !== 2), "!=");
\o\v($u_t)->u_ok((\o\vn(1, 0) < \o\vn(2, 0)), "<");
\o\v($u_t)->u_ok((\o\vn(2, 0) > \o\vn(1, 0)), ">");
\o\v($u_t)->u_ok((\o\vn(4, 0) >= \o\vn(3, 0)), ">= gt");
\o\v($u_t)->u_ok((\o\vn(2, 0) <= \o\vn(3, 0)), "<= lt");
\o\v($u_t)->u_ok((\o\vn(3, 0) >= \o\vn(3, 0)), ">= eq");
\o\v($u_t)->u_ok((\o\vn(3, 0) <= \o\vn(3, 0)), "<= eq");
$u_num = 5;
\o\v($u_t)->u_ok((\o\v($u_num)->u_compare_to(10) === (- 1)), "compare num -");
\o\v($u_t)->u_ok((\o\v($u_num)->u_compare_to((- 5)) === 1), "compare num +");
\o\v($u_t)->u_ok((\o\v($u_num)->u_compare_to(5) === 0), "compare num =");
$u_str = "moo";
\o\v($u_t)->u_ok((\o\v($u_str)->u_compare_to("zoo") === (- 1)), "compare string -");
\o\v($u_t)->u_ok((\o\v($u_str)->u_compare_to("abcdef") === 1), "compare string +");
\o\v($u_t)->u_ok((\o\v($u_str)->u_compare_to("moo") === 0), "compare string =");
\o\v($u_t)->u_section("Math Assignment");
$u_aa = 10;
$u_aa += \o\vn(10, 1);
\o\v($u_t)->u_ok(($u_aa === 20), "+=");
$u_aa *= \o\vn(2, 0);
\o\v($u_t)->u_ok(($u_aa === 40), "*=");
$u_aa -= \o\vn(30, 0);
\o\v($u_t)->u_ok(($u_aa === 10), "-=");
$u_aa /= \o\vn(2, 0);
\o\v($u_t)->u_ok(($u_aa === 5), "/=");
$u_aa **= \o\vn(2, 0);
\o\v($u_t)->u_ok(($u_aa === 25), "**=");
\o\v($u_t)->u_section("Number Methods");
$u_num = 1234.56;
\o\v($u_t)->u_ok((\o\v($u_num)->u_format() === "1,235"), "format");
\o\v($u_t)->u_ok((\o\v($u_num)->u_format(1) === "1,234.6"), "format - numDecimals");
\o\v($u_t)->u_ok((\o\v($u_num)->u_format(2, "") === "1234.56"), "format - blank sep");
\o\v($u_t)->u_ok((\o\v($u_num)->u_format(2, " ", ",") === "1 234,56"), "format - sep & dec");
\o\v($u_t)->u_ok((\o\v($u_num)->u_to_string() === "1234.56"), "toString");
\o\v($u_t)->u_ok((\o\v($u_num)->u_to_flag() === true), "toFlag");
\o\v($u_t)->u_ok((\o\v(0)->u_to_flag() === false), "toFlag - false");
\o\v($u_t)->u_ok((\o\v((- 1))->u_to_flag() === true), "toFlag - negative");
\o\v($u_t)->u_ok((\o\v(0.1)->u_to_flag() === true), "toFlag - float");
 return new \o\ONothing(__METHOD__);
 
}
function u_test_control_flow ($u_t)  {
  \o\v($u_t)->u_section("Loops");
$u_s = "";
foreach (\o\uv(\o\OBare::u_range(1, 3)) as $u_i) {
$u_s .= $u_i;

}
\o\v($u_t)->u_ok(($u_s === "123"), "for, range");
$u_nums = \o\OList::create([ 4, 5, 6 ]);
foreach (\o\uv($u_nums) as $u_n) {
$u_s .= $u_n;

}
\o\v($u_t)->u_ok(($u_s === "123456"), "for, list");
$u_pairs = \o\OMap::create([ 'a' => 1, 'b' => 2, 'c' => 3 ]);
$u_s = "";
foreach (\o\uv($u_pairs) as $u_letter => $u_number) {
$u_s .= \o\Runtime::concat($u_number, $u_letter);

}
\o\v($u_t)->u_ok(($u_s === "1a2b3c"), "for, map");
$u_i = 0;
$u_s = "";
while (true) {
$u_i += \o\vn(1, 1);
$u_s .= $u_i;
if (($u_i === 3)) {
break;

}


}
\o\v($u_t)->u_ok(($u_s === "123"), "break");
$u_i = 0;
$u_s = "";
while (true) {
$u_i += \o\vn(1, 1);
if (($u_i === 4)) {
continue;

}

$u_s .= $u_i;
if (($u_i === 5)) {
break;

}


}
\o\v($u_t)->u_ok(($u_s === "1235"), "continue");
\o\v($u_t)->u_parser_error("for {\nlet a = 1;\n}\n", "needs a 'break'");
\o\v($u_t)->u_parser_error("for {\nlet a = 1;\nreturn;\n}\n", "needs a 'break'");
\o\v($u_t)->u_section("Logic Assignment");
$u_a = (0 ?: 5);
\o\v($u_t)->u_ok(($u_a === 5), "||: false");
$u_a = (2 ?: 5);
\o\v($u_t)->u_ok(($u_a === 2), "||: true");
$u_a = (\o\Runtime::andPush(0) ? 5 : \o\Runtime::andPop());
\o\v($u_t)->u_ok(($u_a === 0), "&&: false");
$u_a = (\o\Runtime::andPush(2) ? 5 : \o\Runtime::andPop());
\o\v($u_t)->u_ok(($u_a === 5), "&&: true");
$u_a = (0 ?: (\o\Runtime::andPush(2) ? 4 : \o\Runtime::andPop()));
\o\v($u_t)->u_ok(($u_a === 4), "||: &&:");
$u_a = (\o\Runtime::andPush(1) ? (0 ?: 5) : \o\Runtime::andPop());
\o\v($u_t)->u_ok(($u_a === 5), "&&: ||:");
$u_a = (0 ?: ("" ?: 6));
\o\v($u_t)->u_ok(($u_a === 6), "||: ||:");
$u_a = (\o\Runtime::andPush(1) ? (\o\Runtime::andPush(2) ? 3 : \o\Runtime::andPop()) : \o\Runtime::andPop());
\o\v($u_t)->u_ok(($u_a === 3), "&&: &&:");
$u_a = 1;
$u_a = $u_a ? 5 : $u_a;
\o\v($u_t)->u_ok(($u_a === 5), "&&= true");
$u_a = 0;
$u_a = $u_a ? 3 : $u_a;
\o\v($u_t)->u_ok(($u_a === 0), "&&= false");
$u_a = 0;
$u_a = $u_a ?: 2;
\o\v($u_t)->u_ok(($u_a === 2), "||= true");
$u_a = $u_a ?: 3;
\o\v($u_t)->u_ok(($u_a === 2), "||= false");
\o\v($u_t)->u_section("if/else");
$u_a = 1;
if (true) {
$u_a = 2;

}

\o\v($u_t)->u_ok(($u_a === 2), "if true");
if (false) {
$u_a = 3;

}

\o\v($u_t)->u_ok(($u_a === 2), "if false");
if (false) {
$u_a = 3;

}
 else {
$u_a = 4;

}

\o\v($u_t)->u_ok(($u_a === 4), "else");
if (false) {
$u_a = 3;

}
 else if (true) {
$u_a = 5;

}


\o\v($u_t)->u_ok(($u_a === 5), "else if");
if (false) {
$u_a = 3;

}
 else if (false) {
$u_a = 5;

}
 else if (false) {
$u_a = 9;

}
 else {
$u_a = 6;

}



\o\v($u_t)->u_ok(($u_a === 6), "if, else if, else");
\o\v($u_t)->u_section("Misc");
$u_ex = false;
$u_fin = false;
try {
\o\OBare::u_die("ERROR!");

}
 catch (\Exception $u_err) {
$u_ex = $u_err;

}
 finally {
$u_fin = true;

}

\o\v($u_t)->u_ok((\o\v($u_err)->u_message() === "ERROR!"), "try/catch thrown");
\o\v($u_t)->u_ok($u_fin, "try/catch - finally");
$u_file_ex = false;
try {
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_read("sdfsdfsdf");

}
 catch (\Exception $u_e) {
$u_file_ex = \o\v($u_e)->u_message();

}

\o\v($u_t)->u_ok(\o\v($u_file_ex)->u_contains("File does not exist"), "catch File exception");
\o\v($u_t)->u_section("Ternary");
\o\v($u_t)->u_ok(((\o\vn(2, 0) > \o\vn(1, 0)) ? true : false), "true");
\o\v($u_t)->u_ok(((\o\vn(1, 0) > \o\vn(2, 0)) ? false : true), "false");
 return new \o\ONothing(__METHOD__);
 
}
function u_test_strings ($u_t)  {
  \o\v($u_t)->u_section("Strings");
$u_stra = "456789";
\o\v($u_t)->u_ok((\o\v($u_stra)[(- 1)] === "9"), "substring index");
$u_ml = "this is a
multiline
string.";
\o\v($u_t)->u_ok(\o\v($u_ml)->u_contains("multiline\nstring"), "multiline with indent");
\o\v($u_t)->u_section("String Concatenation");
\o\v($u_t)->u_ok((\o\Runtime::concat("a", "b") === "ab"), "a ~ b");
\o\v($u_t)->u_ok((\o\Runtime::concat("a", 1) === "a1"), "a ~ 1");
\o\v($u_t)->u_ok((\o\Runtime::concat(1.2, "b") === "1.2b"), "1.2 ~ b");
\o\v($u_t)->u_ok((\o\Runtime::concat(true, "!") === "true!"), "true ~ !");
\o\v($u_t)->u_ok((\o\Runtime::concat(false, "!") === "false!"), "false ~ !");
$u_s = "a";
$u_s .= "bc";
\o\v($u_t)->u_ok(($u_s === "abc"), "~=");
\o\v($u_t)->u_section("String Methods");
$u_hi = "Hello World!";
\o\v($u_t)->u_ok((\o\v("abcdef")->u_reverse() === "fedcba"), "direct string method");
\o\v($u_t)->u_ok((\o\v($u_hi)->u_length() === 12), "length()");
\o\v($u_t)->u_ok((\o\v($u_hi)->u_char_at(1) === "e"), "get()");
\o\v($u_t)->u_ok((\o\v($u_hi)->u_char_at((- 1)) === "!"), "get() negative");
\o\v($u_t)->u_ok(\o\v($u_hi)->u_contains("Hello"), "has()");
\o\v($u_t)->u_ok((! \o\v($u_hi)->u_contains("missing")), "! has()");
\o\v($u_t)->u_ok((\o\v(\o\v($u_hi)->u_split("o"))->u_length() === 3), "split()");
\o\v($u_t)->u_ok((\o\v(\o\v($u_hi)->u_split("o"))[0] === "Hell"), "split()");
\o\v($u_t)->u_ok((\o\Runtime::concat(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'String'))->u_char_from_code(65), \o\v(\o\Runtime::getModule(__NAMESPACE__, 'String'))->u_char_from_code(122)) === "Az"), "String.fromCharCode");
\o\v($u_t)->u_ok((\o\v("false")->u_to_flag() === false), "toFlag - false");
\o\v($u_t)->u_ok((\o\v("true")->u_to_flag() === true), "toFlag - true");
\o\v($u_t)->u_ok((\o\v("0")->u_to_flag() === false), "toFlag - 0");
\o\v($u_t)->u_ok((\o\v("null")->u_to_flag() === false), "toFlag - null");
\o\v($u_t)->u_ok((\o\v("123")->u_to_number() === 123), "toNumber");
\o\v($u_t)->u_ok((\o\v("99ft")->u_to_number() === 99), "toNumber - trailing letters");
\o\v($u_t)->u_section("String Methods - Unicode");
$u_uni = "ⒶⒷⒸ①②③ abc123";
\o\v($u_t)->u_ok((\o\v($u_uni)->u_length() === 13), "length");
\o\v($u_t)->u_ok((\o\v($u_uni)->u_char_at(2) === "Ⓒ"), "charAt");
\o\v($u_t)->u_ok((\o\v($u_uni)->u_char_at((- 1)) === "3"), "charAt negative");
\o\v($u_t)->u_ok((\o\v($u_uni)->u_char_code_at(2) === 9400), "codeAt");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'String'))->u_char_from_code(9400) === "Ⓒ"), "charFromCode");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'String'))->u_char_from_code(65) === "A"), "charFromCode, ascii");
\o\v($u_t)->u_ok((\o\v($u_uni)->u_left(3) === "ⒶⒷⒸ"), "left");
\o\v($u_t)->u_ok((\o\v($u_uni)->u_right(3) === "123"), "right");
\o\v($u_t)->u_ok((\o\v($u_uni)->u_substring(4, 5) === "②③ ab"), "substring");
\o\v($u_t)->u_ok((\o\v($u_uni)->u_substring(3) === "①②③ abc123"), "substring - remainder");
\o\v($u_t)->u_ok(\o\v($u_uni)->u_starts_with("ⒶⒷⒸ"), "startsWith");
\o\v($u_t)->u_ok(\o\v("ab ⒶⒷ")->u_ends_with("ⒶⒷ"), "endsWith");
\o\v($u_t)->u_ok(\o\v("abc ⒶⒷ")->u_starts_with("AbC", true), "startsWith ignoreCase");
\o\v($u_t)->u_ok(\o\v($u_uni)->u_ends_with("ABc123", true), "endsWith ignoreCase");
\o\v($u_t)->u_ok((\o\v(" ⒶⒷ ⒶⒷ")->u_index_of("ⒶⒷ") === 1), "indexOf");
\o\v($u_t)->u_ok((\o\v(" ⒶⒷ ⒶⒷ")->u_index_of("ⒶⒷ", 2) === 4), "indexOf - offset");
\o\v($u_t)->u_ok((\o\v("abc")->u_index_of("BC", 0, true) === 1), "indexOf - ignoreCase");
\o\v($u_t)->u_ok((\o\v(" ⒶⒷ ⒶⒷ")->u_last_index_of("ⒶⒷ") === 4), "lastIndexOf");
\o\v($u_t)->u_ok((\o\v(" ⒶⒷ ⒶⒷ")->u_last_index_of("ⒶⒷ", 3) === 1), "lastIndexOf - offset");
\o\v($u_t)->u_ok((\o\v("abab")->u_last_index_of("AB", 0, true) === 2), "lastIndexOf - ignoreCase");
\o\v($u_t)->u_ok((\o\v("ⒶⒸ")->u_insert("Ⓑ", 1) === "ⒶⒷⒸ"), "insert");
\o\v($u_t)->u_ok((\o\v("ⒶⒷⒸ")->u_insert("①", (- 2)) === "Ⓐ①ⒷⒸ"), "insert negative index");
\o\v($u_t)->u_ok(\o\v($u_uni)->u_contains("③ a"), "contains");
\o\v($u_t)->u_ok(\o\v($u_uni)->u_contains("③ ABc", true), "contains ignoreCase");
\o\v($u_t)->u_ok((\o\v("aⒷⒸ")->u_pad_left(5, " ") === "  aⒷⒸ"), "pad left");
\o\v($u_t)->u_ok((\o\v("aⒷⒸ")->u_pad_left(5) === "  aⒷⒸ"), "pad left - no char");
\o\v($u_t)->u_ok((\o\v("aⒷⒸ")->u_pad_right(5, "①") === "aⒷⒸ①①"), "pad right char");
\o\v($u_t)->u_ok((\o\v("aⒷⒸ")->u_pad(5, " ") === " aⒷⒸ "), "pad both");
\o\v($u_t)->u_ok((\o\v("aⒷⒸ")->u_pad(6, " ") === " aⒷⒸ  "), "pad both uneven");
\o\v($u_t)->u_ok((\o\v("  ⒶⒷ ①②  ")->u_trim() === "ⒶⒷ ①②"), "trim");
\o\v($u_t)->u_ok((\o\v("③③  ⒶⒷ ①②  ③")->u_trim("③") === "ⒶⒷ ①②"), "trim mask");
\o\v($u_t)->u_ok((\o\v("  ⒶⒷ ①②")->u_trim_left() === "ⒶⒷ ①②"), "leftTrim");
\o\v($u_t)->u_ok((\o\v("ⒶⒷ ①②  ")->u_trim_right() === "ⒶⒷ ①②"), "rightTrim");
\o\v($u_t)->u_ok((\o\v("ⒶⒷ ①②  ③")->u_trim_right("③") === "ⒶⒷ ①②"), "rightTrim mask");
\o\v($u_t)->u_ok((\o\v("③ ⒶⒷ ①②")->u_trim_left("③") === "ⒶⒷ ①②"), "leftTrim mask");
\o\v($u_t)->u_ok((\o\v("Abc DEF ⒶⒷⒸ")->u_to_upper_case() === "ABC DEF ⒶⒷⒸ"), "upperCase");
\o\v($u_t)->u_ok((\o\v("Abc DEF ⒶⒷⒸ")->u_to_lower_case() === "abc def ⒶⒷⒸ"), "lowerCase");
\o\v($u_t)->u_ok((\o\v("fòôbàř")->u_to_upper_case() === "FÒÔBÀŘ"), "upperCase - extended");
\o\v($u_t)->u_ok((\o\v("FÒÔBÀŘ")->u_to_lower_case() === "fòôbàř"), "lowerCase - extended");
\o\v($u_t)->u_ok((\o\v("ABC")->u_to_lower_case_first() === "aBC"), "lowerCaseFirst");
\o\v($u_t)->u_ok((\o\v("abc")->u_to_upper_case_first() === "Abc"), "upperCaseFirst");
\o\v($u_t)->u_ok((\o\v("ŘÔÀŘ")->u_to_lower_case_first() === "řÔÀŘ"), "lowerCaseFirst - extended");
\o\v($u_t)->u_ok((\o\v("řôàř")->u_to_upper_case_first() === "Řôàř"), "upperCaseFirst - extended");
\o\v($u_t)->u_ok((\o\v("this is a title")->u_to_title_case() === "This is a Title"), "titleCase");
\o\v($u_t)->u_ok((\o\v("a title")->u_to_title_case() === "A Title"), "titleCase - starting ignoreWord");
\o\v($u_t)->u_ok((\o\v("a:title")->u_to_title_case() === "A:title"), "titleCase - close punctuation");
\o\v($u_t)->u_ok((\o\v("horse")->u_to_plural(1) === "horse"), "plural no");
\o\v($u_t)->u_ok((\o\v("horse")->u_to_plural(2) === "horses"), "plural yes");
\o\v($u_t)->u_ok((\o\v("boss")->u_to_plural(2) === "bosses"), "plural s yes");
\o\v($u_t)->u_ok((\o\v("stimulus")->u_to_plural(3, "stimuli") === "stimuli"), "plural custom");
\o\v($u_t)->u_ok((\o\v("ⒶⒷⒸ123")->u_limit(3) === "ⒶⒷⒸ..."), "limit");
\o\v($u_t)->u_ok((\o\v("ⒶⒷⒸ123")->u_limit(3, "!") === "ⒶⒷⒸ!"), "limit");
\o\v($u_t)->u_ok((\o\v(\o\v("Ⓐ,Ⓑ,Ⓒ")->u_split(","))->u_join("|") === "Ⓐ|Ⓑ|Ⓒ"), "split/join");
\o\v($u_t)->u_ok((\o\v(\o\v("Ⓐ,Ⓑ,Ⓒ")->u_split(",", 2))->u_join("|") === "Ⓐ|Ⓑ,Ⓒ"), "split/join limit");
\o\v($u_t)->u_ok((\o\v(\o\v("Ⓐ, Ⓑ, Ⓒ")->u_split(new \o\ORegex (",\s+")))->u_join("|") === "Ⓐ|Ⓑ|Ⓒ"), "split/join regex");
\o\v($u_t)->u_ok((\o\v(\o\v("Ⓐ,Ⓑ,Ⓒ")->u_split(",", 0))->u_length() === 3), "split limit 0");
\o\v($u_t)->u_ok((\o\v(\o\v("Ⓐ,Ⓑ,Ⓒ")->u_split(",", (- 1)))->u_length() === 3), "split limit -1");
\o\v($u_t)->u_ok((\o\v(\o\v("ⒶⒷⒸ")->u_split(""))->u_length() === 3), "split on empty delimiter");
\o\v($u_t)->u_ok((\o\v(\o\v($u_uni)->u_split_chars())[2] === "Ⓒ"), "chars");
$u_uniml = "① item 1
② item 2

③ item 3";
\o\v($u_t)->u_ok((\o\v(\o\v($u_uniml)->u_split_lines())->u_length() === 3), "lines - count");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v($u_uniml)->u_split_lines())[1])->u_char_at(0) === "②"), "lines - trimmed indent");
\o\v($u_t)->u_ok((\o\v(\o\v($u_uniml)->u_split_lines(true))->u_length() === 4), "lines with whitespace");
\o\v($u_t)->u_ok((\o\v(\o\v("ⒶⒷⒸ ①②③ abc 123")->u_split_words())[1] === "①②③"), "words");
$u_words = \o\v("abc,123? ok.")->u_split_words(true);
\o\v($u_t)->u_ok((\o\v($u_words)->u_length() === 3), "words - bare");
\o\v($u_t)->u_ok((\o\v($u_words)[2] === "ok"), "words - bare");
\o\v($u_t)->u_ok((\o\v($u_uni)->u_reverse() === "321cba ③②①ⒸⒷⒶ"), "reverse");
\o\v($u_t)->u_ok((\o\v("<a&b>")->u_encode_html() === "&lt;a&amp;b&gt;"), "encodeHtml");
\o\v($u_t)->u_ok((\o\v("&lt;a&amp;b&gt;")->u_decode_html() === "<a&b>"), "decodeHtml");
$u_esc = "&#97;&#98;&#99;&#9312;&#9313;&#9314;";
\o\v($u_t)->u_ok((\o\v("abc①②③")->u_encode_html(true) === $u_esc), "encodeHtml all");
$u_enc = "a%20%E2%92%B7%2F%E2%92%B8%3Ad";
\o\v($u_t)->u_ok((\o\v("a Ⓑ/Ⓒ:d")->u_encode_url() === $u_enc), "encodeUrl");
\o\v($u_t)->u_ok((\o\v($u_enc)->u_decode_url() === "a Ⓑ/Ⓒ:d"), "decodeUrl");
\o\v($u_t)->u_ok((\o\v("ⒶⒷⒸ①②③")->u_remove_left("ⒶⒷ") === "Ⓒ①②③"), "removeLeft");
\o\v($u_t)->u_ok((\o\v("ⒶⒷⒸ①②③")->u_remove_left("①") === "ⒶⒷⒸ①②③"), "removeLeft - no");
\o\v($u_t)->u_ok((\o\v("Abcdef")->u_remove_left("abc", true) === "def"), "removeLeft - ignoreCase");
\o\v($u_t)->u_ok((\o\v("ⒶⒷⒸ①②③")->u_remove_right("②③") === "ⒶⒷⒸ①"), "removeRight");
\o\v($u_t)->u_ok((\o\v("ⒶⒷⒸ①②③")->u_remove_right("①") === "ⒶⒷⒸ①②③"), "removeRight - no");
\o\v($u_t)->u_ok((\o\v("abcDef")->u_remove_right("def", true) === "abc"), "removeLeft - ignoreCase");
\o\v($u_t)->u_ok((\o\v("Ⓐ    Ⓑ")->u_squeeze() === "Ⓐ Ⓑ"), "squeeze");
\o\v($u_t)->u_ok((\o\v("Ⓐ①①①①Ⓑ①①")->u_squeeze("①") === "Ⓐ①Ⓑ①"), "squeeze char");
\o\v($u_t)->u_ok((\o\v("ⒶⒷⒸ {var}")->u_fill(\o\OMap::create([ 'var' => "①②③" ])) === "ⒶⒷⒸ ①②③"), "fill");
\o\v($u_t)->u_ok((\o\v("abc {0}")->u_fill("123") === "abc 123"), "fill 1 arg");
\o\v($u_t)->u_ok((\o\v("abc {0} {1}")->u_fill("123", "456") === "abc 123 456"), "fill 2 arg");
\o\v($u_t)->u_ok((\o\v("abc {} {}")->u_fill(\o\OList::create([ "123", "456" ])) === "abc 123 456"), "fill blanks & array");
\o\v($u_t)->u_section("Strings - Checks");
\o\v($u_t)->u_ok(\o\v(" \n  ")->u_is_space(), "isSpace true");
\o\v($u_t)->u_ok((! \o\v("  .  ")->u_is_space()), "isSpace false");
\o\v($u_t)->u_ok((! \o\v("")->u_is_space()), "isSpace empty");
\o\v($u_t)->u_ok(\o\v("abc def")->u_has_space(), "hasSpace space");
\o\v($u_t)->u_ok(\o\v("abc\ndef")->u_has_space(), "hasSpace newline");
\o\v($u_t)->u_ok((! \o\v("abcdef")->u_has_space()), "hasSpace empty");
\o\v($u_t)->u_ok((! \o\v("abc 123")->u_is_upper_case()), "isUpperCase - none");
\o\v($u_t)->u_ok((! \o\v("aBc 123")->u_is_upper_case()), "isUpperCase - some");
\o\v($u_t)->u_ok(\o\v("ABC 123")->u_is_upper_case(), "isUpperCase - all");
\o\v($u_t)->u_ok((! \o\v("")->u_is_upper_case()), "isUpperCase - empty");
\o\v($u_t)->u_ok((! \o\v("abc 123")->u_has_upper_case()), "hasUpperCase - none");
\o\v($u_t)->u_ok(\o\v("aBc 123")->u_has_upper_case(), "hasUpperCase - some");
\o\v($u_t)->u_ok(\o\v("ABC 123")->u_has_upper_case(), "hasUpperCase - all");
\o\v($u_t)->u_ok((! \o\v("")->u_has_upper_case()), "hasUpperCase - empty");
\o\v($u_t)->u_ok((! \o\v("ABC 123")->u_is_lower_case()), "isLowerCase - none");
\o\v($u_t)->u_ok((! \o\v("AbC 123")->u_is_lower_case()), "isLowerCase - some");
\o\v($u_t)->u_ok(\o\v("abc 123")->u_is_lower_case(), "isLowerCase - all");
\o\v($u_t)->u_ok((! \o\v("")->u_is_lower_case()), "isLowerCase - empty");
\o\v($u_t)->u_ok((! \o\v("ABC 123")->u_has_lower_case()), "hasLowerCase - none");
\o\v($u_t)->u_ok(\o\v("AbC 123")->u_has_lower_case(), "hasLowerCase - some");
\o\v($u_t)->u_ok(\o\v("abc 123")->u_has_lower_case(), "hasLowerCase - all");
\o\v($u_t)->u_ok((! \o\v("")->u_has_lower_case()), "hasLowerCase - empty");
\o\v($u_t)->u_ok((\o\v("a b c")->u_to_token_case() === "a-b-c"), "tokenCase");
\o\v($u_t)->u_ok((\o\v("aaBbCc")->u_to_token_case() === "aa-bb-cc"), "tokenCase - from camel");
\o\v($u_t)->u_ok((\o\v("AaBbCc")->u_to_token_case() === "aa-bb-cc"), "tokenCase - from uppercamel");
\o\v($u_t)->u_ok((\o\v("AA BB CC")->u_to_token_case() === "aa-bb-cc"), "tokenCase - from uppercamel");
\o\v($u_t)->u_ok((\o\v("a b c")->u_to_token_case("__") === "a__b__c"), "tokenCase - delimiter");
\o\v($u_t)->u_ok((\o\v("aa bb cc")->u_to_camel_case() === "aaBbCc"), "camelCase");
\o\v($u_t)->u_ok((\o\v("-aa-bb--cc!")->u_to_camel_case() === "aaBbCc"), "camelCase - delim");
\o\v($u_t)->u_ok((\o\v("aa-bb-cc")->u_to_camel_case(true) === "AaBbCc"), "upperCamelCase");
\o\v($u_t)->u_ok((\o\v("a b c")->u_to_camel_case() === "aBC"), "camelCase - single chars");
\o\v($u_t)->u_ok(\o\v("abc")->u_is_alpha(), "isAlpha");
\o\v($u_t)->u_ok(\o\v("abcDEF")->u_is_alpha(), "isAlpha");
\o\v($u_t)->u_ok((! \o\v("abc123")->u_is_alpha()), "isAlpha - w numbers");
\o\v($u_t)->u_ok((! \o\v("abc def")->u_is_alpha()), "isAlpha - spaces");
\o\v($u_t)->u_ok((! \o\v("")->u_is_alpha()), "isAlpha - empty");
\o\v($u_t)->u_ok(\o\v("abc")->u_is_alpha_numeric(), "isAlphaNumeric");
\o\v($u_t)->u_ok(\o\v("abcDEF")->u_is_alpha_numeric(), "isAlphaNumeric");
\o\v($u_t)->u_ok(\o\v("abc123")->u_is_alpha_numeric(), "isAlphaNumeric - w numbers");
\o\v($u_t)->u_ok((! \o\v("abc 123")->u_is_alpha_numeric()), "isAlphaNumeric - spaces");
\o\v($u_t)->u_ok((! \o\v("")->u_is_alpha_numeric()), "isAlphaNumeric - empty");
\o\v($u_t)->u_ok(\o\v("123")->u_is_number(), "isNumber");
\o\v($u_t)->u_ok(\o\v("-123")->u_is_number(), "isNumber - negative");
\o\v($u_t)->u_ok(\o\v("123.45")->u_is_number(), "isNumber - float");
\o\v($u_t)->u_ok((! \o\v("123 ")->u_is_number()), "isNumber - space");
\o\v($u_t)->u_ok((! \o\v("123a")->u_is_number()), "isNumber - alphanum");
\o\v($u_t)->u_ok((! \o\v("abc")->u_is_number()), "isNumber - all alpha");
\o\v($u_t)->u_ok((! \o\v("")->u_is_number()), "isNumber - empty");
\o\v($u_t)->u_ok(\o\v("abc 123")->u_is_ascii(), "isAscii");
\o\v($u_t)->u_ok(\o\v("")->u_is_ascii(), "isAscii - empty");
\o\v($u_t)->u_ok((! \o\v("ⒶⒷⒸ")->u_is_ascii()), "isAscii - unicode");
\o\v($u_t)->u_ok((! \o\v("abⒸ")->u_is_ascii()), "isAscii - mixed");
\o\v($u_t)->u_section("Strings - Escapes");
\o\v($u_t)->u_ok(("abcd" === "abcd"), "string - escape normal char");
\o\v($u_t)->u_ok(\o\v("ab\ncd")->u_match(new \o\ORegex ("ab\scd")), "string - newline");
$u_esc = "\$_SERVER[\"REMOTE_ADDR\"]";
\o\v($u_t)->u_ok((! \o\v("lot's\t {} \"double \$quote\"")->u_contains("\\")), "no leaked backslashes");
\o\v($u_t)->u_ok(\o\v("Here's an escaped quote")->u_contains("'"), "escaped quote (\\')");
\o\v($u_t)->u_ok(\o\v($u_esc)->u_starts_with("\$_SERVER"), "prevent php vars - \$_SERVER");
\o\v($u_t)->u_ok((\o\v("\$abc")[0] === "\$"), "prevent php vars - \\\$abc");
\o\v($u_t)->u_ok((\o\v("\${abc}")[0] === "\$"), "prevent php vars - \${abc}");
\o\v($u_t)->u_section("Regular Expressions");
\o\v($u_t)->u_ok((\o\v(\o\v($u_hi)->u_split(new \o\ORegex ("\s")))[1] === "World!"), "split regex");
\o\v($u_t)->u_ok((\o\v(\o\v($u_hi)->u_match(new \o\ORegex ("(\w+)!\$")))[1] === "World"), "regex with dollar");
\o\v($u_t)->u_dies(function  ()  {
  \o\v("longstringlongstring")->u_find(new \o\ORegex ("(?:\D+|<\d+>)*[!?]"));
 return new \o\ONothing(__METHOD__);
 
}
, "regex error");
$u_multi = "one\ntwo\nthree";
\o\v($u_t)->u_ok((\o\v(\o\v($u_multi)->u_split(new \o\ORegex ("\s")))->u_length() === 3), "Newline regex");
$u_cased = "hello WORLD";
\o\v($u_t)->u_ok((\o\v(\o\v($u_cased)->u_match(\o\v(new \o\ORegex ("world"))->u_flags("i")))[0] === "WORLD"), "regex object");
$u_ticks = "hello 'WORLD'";
\o\v($u_t)->u_ok((\o\v(\o\v($u_ticks)->u_match(new \o\ORegex ("'(\w+)'")))[1] === "WORLD"), "regex with backticks");
$u_esc_ticks = "hello `WORLD`";
\o\v($u_t)->u_ok((\o\v($u_esc_ticks)->u_replace(new \o\ORegex ("\`(\w+)\`"), "THERE") === "hello THERE"), "escaped backticks");
\o\v($u_t)->u_ok((\o\v("ab  cd e")->u_replace(new \o\ORegex ("\s+"), "-") === "ab-cd-e"), "replace");
$u_rx = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Regex'))->u_new(\o\v("'{0}'")->u_fill("world"), "i");
\o\v($u_t)->u_ok((\o\v($u_ticks)->u_replace($u_rx, "VAR") === "hello VAR"), "replace with variable");
\o\v($u_t)->u_section("LockStrings");
\o\v($u_t)->u_ok(\o\v(new \o\OLockString ("abc"))->u_is_lock_string(), "isLockString = true");
\o\v($u_t)->u_ok((! \o\v("abc")->u_is_lock_string()), "isLockString = false");
\o\v($u_t)->u_dies(function  ()  {
  return \o\Runtime::concat(new \o\OLockString ("a"), "b");
 return new \o\ONothing(__METHOD__);
 
}
, "Can't combine");
\o\v($u_t)->u_dies(function  ()  {
  return \o\Runtime::concat("a", new \o\OLockString ("b"));
 return new \o\ONothing(__METHOD__);
 
}
, "Can't combine");
$u_lock1 = new \o\OLockString ("1={},");
$u_lock2 = new \o\OLockString ("2={}");
$u_combined = \o\Runtime::concat($u_lock1, $u_lock2);
\o\v($u_combined)->u_fill(\o\OList::create([ "a", "b" ]));
\o\v($u_t)->u_ok((\o\v($u_combined)->u_unlocked() === "1=a,2=b"), "combined lockstrings");
\o\v($u_t)->u_ok((\o\v(u_lock_html("a"))->u_get_string_type() === "html"), "getStringType");
\o\v($u_t)->u_ok((\o\v(new \o\OLockString ("x"))->u_get_string_type() === "text"), "getStringType");
 return new \o\ONothing(__METHOD__);
 
}
function u_test_lists ($u_t)  {
  \o\v($u_t)->u_section("Lists");
$u_ary = \o\OList::create([ 1, 2, 3, 4, 5 ]);
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ 4, 5, 6 ]))->u_reverse())[2] === 4), "direct list method");
\o\v($u_t)->u_ok((\o\v($u_ary)->u_length() === 5), "size");
\o\v($u_t)->u_ok((\o\v($u_ary)->u_get(2) === 3), "at");
\o\v($u_t)->u_ok((\o\v($u_ary)->u_get(10, 9) === 9), "default");
\o\v($u_t)->u_ok((\o\v($u_ary)[1] === 2), "direct");
\o\v($u_t)->u_ok((\o\v($u_ary)->u_join(":") === "1:2:3:4:5"), "join");
\o\v($u_t)->u_ok((\o\v(\o\v($u_ary)->u_reverse())->u_join(":") === "5:4:3:2:1"), "reverse");
$u_ary_extra_comma = \o\OList::create([ 6, 7, 8, 9 ]);
\o\v($u_t)->u_ok((\o\v($u_ary_extra_comma)->u_join(":") === "6:7:8:9"), "trailing comma");
\o\v($u_t)->u_ok((\o\v($u_ary)[(- 2)] === 4), "negative index");
\o\v($u_ary)[0] = 99;
\o\v($u_t)->u_ok((\o\v($u_ary)[0] === 99), "direct set");
$u_mlary = \o\OList::create([ "hello", \o\OMap::create([ 'name' => "world" ]), "yay" ]);
\o\v($u_t)->u_ok((\o\v(\o\v($u_mlary)[1])["name"] === "world"), "multiline array");
$u_copy_ary_a = \o\OList::create([ 1, 2 ]);
$u_copy_ary_b = \o\v($u_copy_ary_a)->u_copy();
\o\v($u_copy_ary_a)[0] = 11;
\o\v($u_copy_ary_b)[0] = 22;
\o\v($u_t)->u_ok(((\o\v($u_copy_ary_a)[0] === 11) && (\o\v($u_copy_ary_b)[0] === 22)), "copy");
$u_ary = \o\OList::create([ 1, 2, 3 ]);
\o\v($u_t)->u_ok((\o\v(\o\v($u_ary)->u_push(40))[3] === 40), "push");
\o\v($u_t)->u_ok((\o\v($u_ary)->u_pop() === 40), "pop");
\o\v($u_t)->u_ok(((\o\v(\o\v($u_ary)->u_insert((- 10), 0))[0] === (- 10)) && (\o\v($u_ary)->u_length() === 4)), "add index 0");
\o\v($u_t)->u_ok(((\o\v($u_ary)->u_remove(0) === (- 10)) && (\o\v($u_ary)->u_length() === 3)), "remove index 0");
$u_ary = \o\OList::create([ 1, 2, 3 ]);
\o\v($u_t)->u_ok((\o\v(\o\v($u_ary)->u_insert(40, (- 1)))[3] === 40), "add index -1");
\o\v($u_ary)->u_pop();
\o\v($u_t)->u_ok((\o\v(\o\v($u_ary)->u_insert(40, (- 2)))[2] === 40), "add index -2");
\o\v($u_t)->u_ok((\o\v(\o\OList::create([ 0, 1, 2 ]))->u_remove((- 1)) === 2), "remove index -1");
\o\v($u_t)->u_ok((\o\v(\o\OList::create([ 0, 1, 2 ]))->u_remove((- 2)) === 1), "remove index -2");
$u_ary = \o\OList::create([ 1, 2, 3 ]);
\o\v($u_ary)->u_pop();
\o\v($u_t)->u_ok(((\o\v($u_ary)->u_length() === 2) && (\o\v($u_ary)->u_last() === 2)), "length after pop");
\o\v($u_ary)->u_push_all(\o\OList::create([ 3, 4 ]));
\o\v($u_t)->u_ok(((\o\v($u_ary)->u_length() === 4) && (\o\v($u_ary)->u_last() === 4)), "pushAll");
$u_ary = \o\OList::create([ 1, 2, 3 ]);
\o\v($u_ary)->u_insert_all(\o\OList::create([ 10, 11 ]), 2);
\o\v($u_t)->u_ok(((\o\v($u_ary)->u_length() === 5) && ((\o\v($u_ary)[2] === 10) && (\o\v($u_ary)->u_last() === 3))), "insertAll");
$u_ary = \o\OList::create([ 1, 2, 3 ]);
\o\v($u_ary)->u_insert_all(\o\OList::create([ 10, 11 ]), (- 2));
\o\v($u_t)->u_ok(((\o\v($u_ary)->u_length() === 5) && ((\o\v($u_ary)[2] === 10) && (\o\v($u_ary)->u_last() === 3))), "insertAll - negative");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ 0, 1, 2, 3 ]))->u_sublist(1))->u_join("|") === "1|2|3"), "sublist");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ 0, 1, 2, 3 ]))->u_sublist((- 2)))->u_join("|") === "2|3"), "sublist -2");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ 0, 1, 2, 3 ]))->u_sublist(1, 2))->u_join("|") === "1|2"), "sublist w length");
\o\v($u_t)->u_ok((\o\v(\o\OList::create([ "aa", "bb", "'cc'" ]))[1] === "bb"), "quoted list");
\o\v($u_t)->u_ok((\o\v(\o\OList::create([ "aa", "bb", "'cc'" ]))[2] === "'cc'"), "quoted list + quotes");
$u_ml = \o\OList::create([ "aa", "bb", "'cc'" ]);
\o\v($u_t)->u_ok((\o\v($u_ml)[1] === "bb"), "multiline quoted list");
\o\v($u_t)->u_ok((\o\v($u_ml)[2] === "'cc'"), "multiline quoted list + quotes");
\o\v($u_t)->u_section("Lists - Sorting");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ "a", "b", "c" ]))->u_sort())->u_join("|") === "a|b|c"), "sort");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ "1", "2", "10" ]))->u_sort())->u_join("|") === "1|2|10"), "sort numeric strings");
$u_list = \o\v(\o\OList::create([ "a", "b", "c" ]))->u_sort(function  ($u_a, $u_b)  {
  return \o\v($u_b)->u_compare_to($u_a);
 return new \o\ONothing(__METHOD__);
 
}
);
\o\v($u_t)->u_ok((\o\v($u_list)->u_join("|") === "c|b|a"), "sort function");
$u_list = \o\v(\o\OList::create([ 1, 3, 2 ]))->u_sort(\o\OMap::create([ 'reverse' => true ]));
\o\v($u_t)->u_ok((\o\v($u_list)->u_join("|") === "3|2|1"), "reverse sort");
$u_list = \o\v(\o\OList::create([ 1, 3, 2 ]))->u_sort(\o\OMap::create([ 'reverse' => false ]));
\o\v($u_t)->u_ok((\o\v($u_list)->u_join("|") === "1|2|3"), "non-reverse sort");
$u_list = \o\v(\o\OList::create([ "a1", "a10", "a2" ]))->u_sort(\o\OMap::create([ 'type' => "natural" ]));
\o\v($u_t)->u_ok((\o\v($u_list)->u_join("|") === "a1|a2|a10"), "natural sort");
$u_list = \o\v(\o\OList::create([ "a1", "a10", "a2" ]))->u_sort(\o\OMap::create([ 'type' => "regular" ]));
\o\v($u_t)->u_ok((\o\v($u_list)->u_join("|") === "a1|a10|a2"), "regular sort");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\OList::create([ "a" ]))->u_sort(\o\OMap::create([ 'type' => "nope" ]));
 return new \o\ONothing(__METHOD__);
 
}
, "unknown sort type");
$u_list = \o\v(\o\OList::create([ "a1", "A2", "a3", "A4" ]))->u_sort(\o\OMap::create([ 'type' => "stringCase" ]));
\o\v($u_t)->u_ok((\o\v($u_list)->u_join("|") === "A2|A4|a1|a3"), "case sensitive");
\o\v($u_t)->u_section("Lists - Size Errors");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\OList::create([ 1, 2 ]))->u_remove(3);
 return new \o\ONothing(__METHOD__);
 
}
, "remove()");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\OList::create([  ]))->u_remove();
 return new \o\ONothing(__METHOD__);
 
}
, "empty");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\OList::create([ 1 ]))->u_sublist(2);
 return new \o\ONothing(__METHOD__);
 
}
, "sublist");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\OList::create([ 1 ]))->u_first(2);
 return new \o\ONothing(__METHOD__);
 
}
, "last");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\OList::create([ 1 ]))->u_last(2);
 return new \o\ONothing(__METHOD__);
 
}
, "first");
 return new \o\ONothing(__METHOD__);
 
}
function u_test_templates ($u_t)  {
  \o\v($u_t)->u_section("Templates");
$u_html_users = \o\v(u_template_html(\o\OList::create([ "Frodo", "Sam", "Gandalf" ])))->u_unlocked();
\o\v($u_t)->u_ok(\o\v($u_html_users)->u_match(new \o\ORegex ("<li>Frodo.*?<li>Sam.*?<li>Gandalf")), "template - loop & variables");
$u_html_users = u_template_html(\o\OList::create([ "Frodo", "<b>Sam</b>", "Gandalf" ]));
\o\v($u_t)->u_ok(\o\v(\o\v($u_html_users)->u_unlocked())->u_contains("&lt;b&gt;Sam"), "template with html escapes");
$u_p = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_parse_html(new \o\OLockString ("<h1>> Hello
<.abc>> 123"));
$u_p = \o\v($u_p)->u_unlocked();
\o\v($u_t)->u_ok(\o\v($u_p)->u_contains("<h1>Hello</h1>"), "parse html string - double arrow");
\o\v($u_t)->u_ok(\o\v($u_p)->u_contains("<div class='abc'>123</div>"), "parse html string - dotted");
\o\v($u_t)->u_section("Template Escaping");
\o\v($u_t)->u_ok(\o\v(\o\v(u_ent_html())->u_unlocked())->u_contains("&gt;"), "html - entity");
\o\v($u_t)->u_ok(\o\v(\o\v(u_format_block_html())->u_unlocked())->u_contains("&lt;foo&gt;"), "html - format block");
$u_h = \o\v(u_exp_html("\"'", "a&b\""))->u_unlocked();
\o\v($u_t)->u_ok(\o\v($u_h)->u_contains("<p &quot;&#039;>"), "html - tag attribute");
\o\v($u_t)->u_ok(\o\v($u_h)->u_contains("a&amp;b"), "html - outer");
\o\v($u_t)->u_ok(\o\v(\o\v(u_tags_html(u_in_css()))->u_unlocked())->u_contains("<style"), "html - css style block");
\o\v($u_t)->u_ok(\o\v(\o\v(u_tags_html(u_in_js()))->u_unlocked())->u_contains("<script"), "html - js block");
\o\v($u_t)->u_ok(\o\v(\o\v(u_tags_html(u_ent_html()))->u_unlocked())->u_contains("<p>2 &gt; 1</p>"), "html - embed html");
$u_ls = new \o\OLockString ("<p>a &gt; c</p>");
\o\v($u_t)->u_ok(\o\v(\o\v(u_tags_html($u_ls))->u_unlocked())->u_contains("<p>a &gt; c</p>"), "html - LockString");
\o\v($u_t)->u_ok(\o\v(\o\v(u_data_js("string"))->u_unlocked())->u_contains("\"string\";"), "js - string");
\o\v($u_t)->u_ok(\o\v(\o\v(u_data_js("a\nb"))->u_unlocked())->u_contains("\"a\\nb\";"), "js - string newline");
\o\v($u_t)->u_ok(\o\v(\o\v(u_data_js("a\"b"))->u_unlocked())->u_contains("\"a\\\"b\";"), "js - string quote");
\o\v($u_t)->u_ok(\o\v(\o\v(u_data_js(1234))->u_unlocked())->u_contains("1234;"), "js - num");
\o\v($u_t)->u_ok(\o\v(\o\v(u_data_js(true))->u_unlocked())->u_contains("true;"), "js - bool");
\o\v($u_t)->u_ok(\o\v(\o\v(u_data_js(\o\OMap::create([ 'a' => 1 ])))->u_unlocked())->u_contains("{\"a\":1};"), "js - object");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_file ($u_t)  {
  \o\v($u_t)->u_section("Module: File");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_exists("../bad.txt");
 return new \o\ONothing(__METHOD__);
 
}
, "parent shortcut (..)");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_read("http://yahoo.com");
 return new \o\ONothing(__METHOD__);
 
}
, "stop remote file read");
\o\v($u_t)->u_ok((! \o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_exists("sdf/sdf")), "Missing file does not exist");
\o\v($u_t)->u_ok((! \o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_is_file("sdf/sdf")), "Missing path is not a file");
\o\v($u_t)->u_ok((! \o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_is_dir("sdf/sdf")), "Missing path is not a dir");
$u_f = "testFile.txt";
$u_d = "testDir";
if (\o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_exists($u_d)) {
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_delete_dir($u_d);

}

\o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_make_dir($u_d);
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_is_dir($u_d), "make dir");
$u_p = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_join_path(\o\OList::create([ $u_d, $u_f ]));
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_write($u_p, "12345");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_get_size($u_p) === 5), "File size");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_exists($u_p), "File exists");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_is_file($u_p), "File is file");
$u_info = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_path_info($u_p);
\o\v($u_t)->u_ok((\o\v(\o\v($u_info)->u_dir_list)->u_last() === $u_d), "Path info dirList has parent dir");
\o\v($u_t)->u_ok((\o\v($u_info)->u_file_ext === "txt"), "Path info extension");
\o\v($u_t)->u_ok((\o\v($u_info)->u_file_name === "testFile.txt"), "Path info fileName");
\o\v($u_t)->u_ok((\o\v($u_info)->u_file_name_short === "testFile"), "Path info shortFileName");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_delete($u_p);
\o\v($u_t)->u_ok((! \o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_exists($u_p)), "File deleted");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_delete_dir($u_d);
\o\v($u_t)->u_ok((! \o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_exists($u_d)), "Dir deleted");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_date ($u_t)  {
  \o\v($u_t)->u_section("Module: Date");
\o\v($u_t)->u_ok((\o\vn(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Date'))->u_now(), 0) > \o\vn(1490000000, 0)), "Date.now");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Date'))->u_minutes(3) === 180), "minutes");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Date'))->u_hours(2) === 7200), "hours");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Date'))->u_days(3) === 259200), "days");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Date'))->u_to_minutes(90) === 1.5), "inMinutes");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Date'))->u_to_hours(7200) === 2), "inHours");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Date'))->u_to_days(259200) === 3), "inDays");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Date'))->u_format("%Y-%m-%d %H:%M:%S", 1400000000) === "2014-05-13 09:53:20"), "Date.format");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Date'))->u_difference(100, 280) === "3 minutes"), "Date.difference");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_db ($u_t)  {
  \o\v($u_t)->u_section("Module: Db");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_query(new \o\OLockString ("delete from test"));
$u_key = \o\Runtime::concat("test", \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_random(0, 1000));
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_insert_row("test", \o\OMap::create([ 'key' => $u_key, 'value' => \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Date'))->u_now() ]));
$u_rows = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_select_rows(new \o\OLockString ("select * from test"));
\o\v($u_t)->u_ok((\o\v($u_rows)->u_length() === 1), "Insert & select row");
\o\v($u_t)->u_ok((\o\v(\o\v($u_rows)[0])->u_key === $u_key), "Check inserted row");
$u_dbh = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_use("default");
$u_rows = \o\v($u_dbh)->u_select_rows(new \o\OLockString ("select * from test"));
\o\v($u_t)->u_ok((\o\v(\o\v($u_rows)[0])->u_key === $u_key), "Db.use");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_update_rows("test", \o\OMap::create([ 'key' => $u_key, 'value' => "new!" ]), \o\v(new \o\OLockString (" key = {}"))->u_fill($u_key));
$u_row = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_select_row(\o\v(new \o\OLockString ("select * from test where key = {}"))->u_fill($u_key));
\o\v($u_t)->u_ok((\o\v($u_row)->u_value === "new!"), "Update row");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_delete_rows("test", \o\v(new \o\OLockString ("key = {}"))->u_fill($u_key));
$u_rows = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_select_rows(new \o\OLockString ("select * from test"));
\o\v($u_t)->u_ok((\o\v($u_rows)->u_length() === 0), "Delete row");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_update_rows("\"bad", \o\OMap::create([ 'key' => $u_key ]), \o\v(new \o\OLockString (" key = {}"))->u_fill($u_key));
 return new \o\ONothing(__METHOD__);
 
}
, "invalid table name - updateRows");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_delete_rows("\"bad", \o\v(new \o\OLockString (" key = {}"))->u_fill($u_key));
 return new \o\ONothing(__METHOD__);
 
}
, "invalid table name - deleteRows");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_query("delete from test");
 return new \o\ONothing(__METHOD__);
 
}
, "reject unlocked query - query");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_select_rows("select * from test");
 return new \o\ONothing(__METHOD__);
 
}
, "reject unlocked query - selectRows");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_jcon_test ($u_t)  {
  \o\v($u_t)->u_section("Module: Jcon");
$u_d = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Jcon'))->u_parse("{\nkey: value\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key === "value"), "string value");
$u_d = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Jcon'))->u_parse("{\nkey: true\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key === true), "true value");
$u_d = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Jcon'))->u_parse("{\nkeyA: valA\nkeyB: valB\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key_b === "valB"), "2nd key");
$u_d = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Jcon'))->u_parse("{\nkey: false\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key === false), "false value");
$u_d = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Jcon'))->u_parse("{\nkey: 1234.5\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key === 1234.5), "num value");
$u_d = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Jcon'))->u_parse("{\nkey: [\nv1\nv2\nv3\n]\n}\n");
\o\v($u_t)->u_ok((\o\v(\o\v($u_d)->u_key)->u_length() === 3), "list value");
\o\v($u_t)->u_ok((\o\v(\o\v($u_d)->u_key)[2] === "v3"), "list value");
$u_d = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Jcon'))->u_parse("{\nkey: '''\nThis is\nmultiline\n'''\n}\n");
\o\v($u_t)->u_ok(\o\v(\o\v($u_d)->u_key)->u_contains("\nmultiline"), "multiline value");
$u_d = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Jcon'))->u_parse("{\nkeyLite: '''\n## Heading!\n'''\n}\n");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\v($u_d)->u_key_lite)->u_unlocked())->u_contains("<h2>"), "Litemark value");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_js ($u_t)  {
  \o\v($u_t)->u_section("Module: Js");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Js'))->u_plugin("colorCode"))->u_unlocked())->u_contains("highlight"), "colorCode");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Js'))->u_plugin("lazyLoadImages"))->u_unlocked())->u_contains("img"), "lazyLoadImages");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Js'))->u_minify("/* comment */\n\nhello\n    \n") === "hello"), "minify");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_json ($u_t)  {
  \o\v($u_t)->u_section("Module: Json");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Json'))->u_decode("{\"k1\":[123,\"hello\"]}"))["k1"])[1] === "hello"), "decode sub-list");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Json'))->u_decode("{\"k1\":{\"k2\":\"hello\"}}"))["k1"])["k2"] === "hello"), "decode sub-map");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Json'))->u_decode("[1,2,3]"))[1] === 2), "decode list");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Json'))->u_decode("true") === true), "decode boolean");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Json'))->u_decode("123.45") === 123.45), "decode number");
$u_st = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Json'))->u_encode(\o\OMap::create([ 'a' => "hi", 'b' => \o\OList::create([ 1, 2, 3 ]) ]));
\o\v($u_t)->u_ok(\o\v($u_st)->u_contains("\"hi\""), "encode string");
\o\v($u_t)->u_ok(\o\v($u_st)->u_contains("[1,2,3]"), "encode list");
\o\v($u_t)->u_ok(\o\v($u_st)->u_contains("\"b\":"), "encode key");
$u_obj = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Json'))->u_decode($u_st);
\o\v($u_t)->u_ok((\o\v(\o\v($u_obj)->u_b)[1] === 2), "decode after encode");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_litemark ($u_t)  {
  \o\v($u_t)->u_section("Module: Litemark");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_math ($u_t)  {
  \o\v($u_t)->u_section("Module: Math");
$u_rand = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_random(6, 8);
\o\v($u_t)->u_ok(((\o\vn($u_rand, 0) >= \o\vn(6, 0)) && (\o\vn($u_rand, 0) <= \o\vn(8, 0))), "random");
$u_rnd = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_random();
\o\v($u_t)->u_ok(((\o\vn($u_rnd, 0) >= \o\vn(0, 0)) && (\o\vn($u_rnd, 0) < \o\vn(1, 0))), "random float");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_round(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_pi(), 2) === 3.14), "rounded pi");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_clamp(5, 1, 10) === 5), "clamp in boundary");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_clamp(20, 1, 10) === 10), "clamp max");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_clamp((- 20), 1, 10) === 1), "clamp min");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_min(1, 3, 5) === 1), "min");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_min(\o\OList::create([ 1, 3, 5 ])) === 1), "min list");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_max(1, 3, 5) === 5), "max");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_max(\o\OList::create([ 1, 3, 5 ])) === 5), "max list");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_convert_base(21, 10, 2) === "10101"), "convertBase: dec to bin");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Math'))->u_convert_base("1af9", 16, 10) === 6905), "convertBase: hex to dec");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_meta ($u_t)  {
  \o\v($u_t)->u_section("Module: Meta");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_function_exists("libMeta"), "functionExists");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_call_function("metaCallMe", \o\OList::create([ "a", "b" ])) === "a|b"), "callFunction & arguments");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_no_template_mode(), "noTemplateMode ok");
\o\v($u_t)->u_dies(function  ()  {
  u_fail_mode_html();
 return new \o\ONothing(__METHOD__);
 
}
, "noTemplateMode fail");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_function_exists("dynamicFunction"), "dynamic function exists");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_call_function("dynamicFunction", \o\OList::create([ "Hey" ])) === "Hey!!!"), "call dynamic function");
 return new \o\ONothing(__METHOD__);
 
}
function u_meta_call_me ()  {
  $u_args = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_arguments();
return \o\v($u_args)->u_join("|");
 return new \o\ONothing(__METHOD__);
 
}
function u_fail_template_mode ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_no_template_mode();
 return new \o\ONothing(__METHOD__);
 
}
function u_fail_mode_html ()  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("
    ");
u_fail_template_mode();
$t->addStatic("");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_lib_perf ($u_t)  {
  \o\v($u_t)->u_section("Module: Perf");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Perf'))->u_force_active(true);
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Perf'))->u_start("testPerf");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'System'))->u_sleep(1);
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Perf'))->u_stop(true);
$u_res = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Perf'))->u_results(true);
$u_found = false;
foreach (\o\uv($u_res) as $u_r) {
if ((\o\v($u_r)->u_task === "testPerf")) {
$u_found = true;
break;

}


}
\o\v($u_t)->u_ok($u_found, "Perf task & results");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Perf'))->u_force_active(false);
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_php ($u_t)  {
  \o\v($u_t)->u_section("Module: Php");
$u_fl = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Php'))->u_options(\o\OList::create([ "PATHINFO_FILENAME", "PATHINFO_BASENAME" ]));
\o\v($u_t)->u_ok(($u_fl === 10), "PHP - constant flags");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Php'))->u_call(new \o\OLockString ("strrev"), \o\OList::create([ "abcdef" ])) === "fedcba"), "call");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Php'))->u_call(new \o\OLockString ("nonexistent"), \o\OList::create([ 1, 2 ]));
 return new \o\ONothing(__METHOD__);
 
}
, "Non-existent PHP call");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Php'))->u_call(new \o\OLockString ("eval"), \o\OList::create([ "print(\"hi\");" ]));
 return new \o\ONothing(__METHOD__);
 
}
, "stop blacklisted function - by name");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Php'))->u_call(new \o\OLockString ("ini_set"), \o\OList::create([ "x", "y" ]));
 return new \o\ONothing(__METHOD__);
 
}
, "stop blacklisted function - by match");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_test ($u_t)  {
  \o\v($u_t)->u_section("Module: Test");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_global ($u_t)  {
  \o\v($u_t)->u_section("Module: Global");
u_set_globals();
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global'))->u_hello === "world"), "global set");
 return new \o\ONothing(__METHOD__);
 
}
function u_set_globals ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global'))->u_hello = "world";
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_web ($u_t)  {
  \o\v($u_t)->u_section("Module: Web");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_redirect("http://google.com");
 return new \o\ONothing(__METHOD__);
 
}
, "redirect - normal");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_redirect("mailto:google.com");
 return new \o\ONothing(__METHOD__);
 
}
, "redirect - mailto");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_redirect("//google.com");
 return new \o\ONothing(__METHOD__);
 
}
, "redirect - no protocol");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_redirect("bob@ftp://google.com");
 return new \o\ONothing(__METHOD__);
 
}
, "redirect - ftp & username");
\o\v($u_t)->u_section("Module: Web - Form Input");
\o\v($u_t)->u_ok((u_form_validate("id123", "id") === "id123"), "id ok");
\o\v($u_t)->u_ok((u_form_validate("\$foo", "id") === ""), "id not ok");
\o\v($u_t)->u_ok((u_form_validate("1234", "number") === 1234), "number ok");
\o\v($u_t)->u_ok((u_form_validate("123,456", "numberAny") === 123456), "number with comma ok");
\o\v($u_t)->u_ok((u_form_validate("123'456", "numberAny") === 123456), "number with apos ok");
\o\v($u_t)->u_ok((u_form_validate("-123.4", "numberAny") === (- 123.4)), "negative number with decimal point");
\o\v($u_t)->u_ok((u_form_validate("\$1", "number") === ""), "number not ok");
\o\v($u_t)->u_ok((u_form_validate("true", "flag") === true), "flag ok");
\o\v($u_t)->u_ok((u_form_validate("false", "flag") === false), "flag ok");
\o\v($u_t)->u_ok((u_form_validate("1", "flag") === true), "flag ok");
\o\v($u_t)->u_ok((u_form_validate("0", "flag") === false), "flag ok");
\o\v($u_t)->u_ok((u_form_validate("\$1", "flag") === ""), "flag not ok");
\o\v($u_t)->u_ok((u_form_validate("me@mail.com", "email") === "me@mail.com"), "email ok");
\o\v($u_t)->u_ok((u_form_validate("me.com", "email") === ""), "email not ok");
\o\v($u_t)->u_ok((u_form_validate("me@mailcom", "email") === ""), "email not ok");
\o\v($u_t)->u_ok((u_form_validate("skip", "email") === ""), "email not ok");
\o\v($u_t)->u_ok((u_form_validate("abc  123!", "text") === "abc 123!"), "text ok");
\o\v($u_t)->u_ok((u_form_validate("abc<b>tag", "text") === "abctag"), "text no tag");
\o\v($u_t)->u_ok((u_form_validate("abc\nline2", "text") === "abc line2"), "text newline");
\o\v($u_t)->u_ok((u_form_validate("abc  123\n\n\nxyz!\n", "textarea") === "abc 123\n\nxyz!"), "textarea spaces");
\o\v($u_t)->u_ok((u_form_validate("abc<b>tag", "textarea") === "abctag"), "textarea no tag");
\o\v($u_t)->u_ok((u_form_validate("abc\n\n\nline2", "textarea") === "abc\n\nline2"), "textarea newline");
\o\v($u_t)->u_dies(function  ()  {
  u_form_validate("abc", "badRule");
 return new \o\ONothing(__METHOD__);
 
}
, "bad rule");
 return new \o\ONothing(__METHOD__);
 
}
function u_form_validate ($u_v, $u_type)  {
  return \o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_temp_validate_input($u_v, $u_type))["value"];
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_settings ($u_t)  {
  \o\v($u_t)->u_section("Module: Settings");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global'))->u_setting("num") === (- 123.45)), "get num");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global'))->u_setting("flagFalse") === false), "get flag");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global'))->u_setting("flagTrue") === true), "get flag");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global'))->u_setting("string") === "value with spaces, etc."), "get string");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global'))->u_setting("map"))->u_key === "value"), "get map");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global'))->u_setting("list"))[1] === "value 1"), "get list");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global'))->u_setting("MISSING");
 return new \o\ONothing(__METHOD__);
 
}
, "missing key");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_map_db ($u_t)  {
  \o\v($u_t)->u_section("Module: MapDb");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'MapDb'))->u_delete_bucket("test"), "delete bucket");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'MapDb'))->u_insert_map("test", "hello", \o\OMap::create([ 'hello' => "World!" ])), "insert");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'MapDb'))->u_insert_map("test", "hello", \o\OMap::create([ 'hello' => "There!" ])), "insert");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'MapDb'))->u_select_map("test", 1))->u_hello === "World!"), "selectMap");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'MapDb'))->u_select_maps("test", "hello"))->u_length() === 2), "selectMaps");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'MapDb'))->u_buckets())[0])->u_num_maps === 2), "buckets()");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_session ($u_t)  {
  \o\v($u_t)->u_section("Module: Session");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_delete_all();
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_set("key1", "value");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_set("key2", \o\OMap::create([ 'a' => "b" ]));
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_get("key1") === "value"), "set/get");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_get("key2"))->u_a === "b"), "get map");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_get_all())->u_keys())->u_join("|") === "key1|key2"), "getAll");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_get("missing", "") === ""), "get with blank default");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_get("missing", "default") === "default"), "get with default");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_has_key("key1"), "hasKey true");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_delete("key1") === "value"), "delete");
\o\v($u_t)->u_ok((! \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_has_key("key1")), "hasKey false");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_delete_all();
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_get_all())->u_keys())->u_length() === 0), "deleteAll");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_add_counter("num") === 1), "counter 1");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_add_counter("num") === 2), "counter 2");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_set_flash("fkey", "fvalue");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_get_flash("fkey") === "fvalue"), "flash set/get");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_has_flash("fkey"), "hasFlash - true");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_has_flash("missing"), "hasFlash - false");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_add_to_list("list", 123);
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_get("list"))[0] === 123), "addToList 1");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_add_to_list("list", 456);
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_get("list"))[1] === 456), "addToList 2");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Session'))->u_get("missing");
 return new \o\ONothing(__METHOD__);
 
}
, "get bad key");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_cache ($u_t)  {
  \o\v($u_t)->u_section("Module: Cache");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_set("test", 123, 1);
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_has("test"), "has");
\o\v($u_t)->u_ok((! \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_has("not")), "has not");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_get("test") === 123), "get");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_get("not", "missing") === "missing"), "get default");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_set("data", \o\OMap::create([ 'a' => \o\OList::create([ "x", "y", "z" ]) ]), 3);
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_get("data"))->u_a)->u_join("|") === "x|y|z"), "get map + list");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_delete("data");
\o\v($u_t)->u_ok((! \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_has("data")), "delete");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_counter("count") === 1), "counter 1");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_counter("count") === 2), "counter 2");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_counter("count", 2) === 4), "counter +2");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_counter("count", (- 1)) === 3), "counter -1");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_delete("count");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_set("short", "a", 0.1);
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_set("longer", "a", 0.5);
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_set("forever", "a", 0);
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'System'))->u_sleep(200);
\o\v($u_t)->u_ok((! \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_has("short")), "100ms expiry");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_has("longer"), "500ms expiry");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_has("forever"), "no expiry");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_delete("short");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_delete("longer");
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Cache'))->u_delete("forever");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_net ($u_t)  {
  \o\v($u_t)->u_section("Module: Net");
$u_content = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Net'))->u_http_get(new \o\OLockString ("https://tht.help"));
\o\v($u_t)->u_ok(\o\v($u_content)->u_match(\o\v(new \o\ORegex ("programming language"))->u_flags("i")), "Net get");
 return new \o\ONothing(__METHOD__);
 
}
function u_template_html ($u_users)  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("<b>Hello</b>");
foreach (\o\uv($u_users) as $u_u) {
$t->addStatic("<li>");
$t->addDynamic($u_u);
$t->addStatic("</li>");

}
$t->addStatic("
");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_data_js ($u_d)  {
$t = \o\Runtime::openTemplate("Js");
$t->addStatic("let d=");
$t->addDynamic($u_d);
$t->addStatic(";");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_ent_html ()  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("<p>2 &gt; 1</p>");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_format_block_html ()  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("<p>&lt;foo&gt;</p>");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_bs_html ()  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("
    a\\nb\\nc
");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_js_html ()  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("<script nonce=\"");
$t->addDynamic(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_nonce());
$t->addStatic("\">var a = '&lt;a\\nb\\nc';</script>");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_exp_html ($u_inner, $u_outer)  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("<p ");
$t->addDynamic($u_inner);
$t->addStatic(">");
$t->addDynamic($u_outer);
$t->addStatic("</p>");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_tags_html ($u_exp)  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("
    ");
$t->addDynamic($u_exp);
$t->addStatic("
");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_in_css ()  {
$t = \o\Runtime::openTemplate("Css");
$t->addStatic("font-weight:bold;");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_in_js ()  {
$t = \o\Runtime::openTemplate("Js");
$t->addStatic("var a=1;");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_lock_html ($u_lock)  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("<p>");
$t->addDynamic($u_lock);
$t->addStatic("</p>");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_exp_css ($u_inp)  {
$t = \o\Runtime::openTemplate("Css");
$t->addStatic("font-weight:");
$t->addDynamic($u_inp);
$t->addStatic(";");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_dynamic_function ($u_a)  {
  return \o\Runtime::concat($u_a, "!!!");
 return new \o\ONothing(__METHOD__);
 
}
function u_no_return ()  {
   return new \o\ONothing(__METHOD__);
 
}
function u_test_default_map ($u_xmap=[ 'a' => 123 ])  {
 $u_xmap = is_object($u_xmap) ? $u_xmap : \o\OMap::create($u_xmap);
 return $u_xmap;
 return new \o\ONothing(__METHOD__);
 
}
function u_test_default_list ($u_xlist=[ "a", "b", "c" ])  {
 $u_xlist = is_object($u_xlist) ? $u_xlist : \o\OList::create($u_xlist);
 return $u_xlist;
 return new \o\ONothing(__METHOD__);
 
}
function u_test_default_maps ($u_m1=[ 'a' => "aa" ], $u_m2=[ 'b' => "bb" ])  {
 $u_m1 = is_object($u_m1) ? $u_m1 : \o\OMap::create($u_m1);
$u_m2 = is_object($u_m2) ? $u_m2 : \o\OMap::create($u_m2);
 \o\v($u_m1)["m2"] = $u_m2;
return $u_m1;
 return new \o\ONothing(__METHOD__);
 
}



/* SOURCE={"file":"pages\/home.tht","6":4,"7":6,"8":7,"9":9,"13":12,"15":18,"16":18,"17":26,"18":26,"19":32,"23":36,"24":38,"25":39,"26":40,"27":41,"28":42,"29":43,"30":44,"31":45,"32":46,"33":48,"34":49,"35":51,"36":52,"37":53,"38":54,"39":55,"40":56,"41":57,"42":58,"43":60,"44":61,"45":62,"46":63,"47":64,"48":66,"49":67,"50":68,"54":72,"55":74,"56":76,"57":76,"61":76,"62":77,"63":77,"67":77,"68":79,"69":80,"73":81,"74":83,"75":84,"79":85,"80":87,"81":88,"82":88,"88":91,"89":93,"90":93,"94":93,"98":97,"99":99,"100":101,"107":112,"108":113,"109":114,"110":115,"111":116,"112":119,"113":121,"114":122,"115":123,"116":124,"117":125,"118":126,"119":127,"120":128,"121":129,"122":132,"123":134,"124":135,"125":136,"126":137,"127":140,"128":142,"129":143,"130":144,"131":145,"132":146,"133":147,"134":148,"135":149,"136":150,"137":151,"138":152,"139":153,"140":154,"141":155,"142":156,"143":157,"144":158,"145":159,"146":160,"147":161,"148":162,"149":163,"150":164,"151":165,"152":166,"153":167,"154":168,"155":169,"156":170,"157":171,"158":172,"159":183,"160":185,"161":186,"162":187,"163":188,"164":189,"165":190,"166":191,"167":192,"168":193,"169":196,"170":198,"171":199,"172":200,"173":201,"174":202,"175":203,"176":207,"177":209,"178":210,"179":211,"180":212,"181":213,"182":214,"183":215,"184":216,"185":217,"186":218,"187":219,"188":220,"189":221,"190":222,"191":223,"192":224,"193":225,"194":226,"195":227,"196":228,"197":229,"198":230,"199":231,"200":232,"201":233,"202":234,"203":235,"204":236,"205":237,"206":238,"207":239,"208":240,"209":241,"210":242,"211":243,"212":245,"213":246,"214":247,"215":248,"216":249,"217":252,"218":254,"219":255,"220":256,"221":257,"222":258,"223":259,"224":261,"225":262,"226":263,"227":264,"228":265,"229":268,"230":270,"231":271,"232":272,"233":273,"234":274,"235":275,"236":276,"237":277,"238":280,"239":282,"240":283,"241":284,"242":285,"243":286,"244":287,"245":288,"246":289,"247":290,"248":291,"249":292,"250":293,"251":294,"252":295,"253":296,"254":297,"255":298,"256":299,"257":301,"261":309,"262":311,"263":314,"264":315,"265":316,"266":317,"267":318,"268":319,"269":320,"270":321,"273":323,"274":324,"275":325,"276":326,"277":329,"278":335,"279":337,"280":338,"281":339,"282":341,"283":342,"284":343,"285":348,"286":350,"287":352,"288":353,"289":355,"290":356,"291":359,"292":360,"293":364,"294":366,"295":368,"296":369,"297":370,"298":372,"299":373,"300":374,"301":375,"302":377,"303":379,"304":379,"308":379,"309":381,"310":381,"314":381,"315":383,"316":385,"317":386,"318":388,"319":389,"320":390,"321":392,"322":393,"323":395,"324":396,"325":396,"329":396,"330":398,"331":400,"332":401,"333":403,"334":404,"335":406,"336":407,"337":409,"341":413,"342":415,"343":417,"344":418,"345":419,"346":420,"347":421,"348":422,"349":423,"350":424,"351":424,"355":425,"356":429,"357":431,"358":432,"359":433,"360":434,"361":435,"362":436,"363":437,"364":439,"365":440,"366":441,"367":442,"368":443,"369":444,"370":445,"374":450,"375":452,"376":454,"377":455,"381":457,"382":459,"383":460,"387":462,"388":464,"389":465,"393":467,"394":469,"395":470,"396":471,"397":472,"400":474,"404":476,"405":477,"406":482,"407":483,"411":485,"412":486,"413":488,"414":489,"415":491,"416":492,"417":493,"422":495,"423":498,"424":499,"428":501,"429":502,"430":503,"431":505,"432":506,"433":510,"434":511,"438":513,"439":514,"440":515,"441":518,"442":519,"443":520,"448":522,"449":525,"450":525,"453":526,"454":526,"458":526,"459":527,"460":527,"464":527,"465":530,"466":531,"467":533,"468":534,"469":536,"470":537,"471":543,"472":545,"473":546,"474":547,"475":548,"476":549,"477":551,"478":554,"479":554,"483":554,"484":556,"485":556,"489":556,"490":557,"491":557,"495":557,"496":558,"497":558,"501":558,"502":559,"503":559,"507":559,"508":561,"509":562,"510":562,"514":562,"515":564,"516":564,"520":564,"521":565,"522":565,"526":565,"527":566,"528":566,"532":566,"536":574,"537":576,"538":578,"539":579,"540":580,"541":581,"542":582,"543":583,"544":584,"545":585,"546":586,"547":587,"548":588,"549":589,"550":592,"551":594,"552":596,"553":597,"554":602,"555":604,"556":605,"557":605,"561":605,"562":608,"563":610,"564":611,"565":612,"566":613,"567":614,"568":615,"569":618,"570":620,"571":621,"572":622,"573":624,"574":625,"575":626,"576":629,"577":631,"578":633,"579":634,"580":636,"581":637,"582":639,"583":640,"584":643,"585":644,"586":645,"587":646,"588":647,"589":648,"590":649,"591":652,"592":653,"593":653,"597":653,"598":654,"599":657,"600":658,"601":659,"602":660,"603":661,"604":664,"605":666,"606":666,"610":666,"611":667,"612":667,"616":667,"620":672,"621":675,"622":677,"623":678,"624":679,"625":680,"626":681,"627":682,"628":683,"629":684,"630":686,"631":687,"632":688,"633":689,"634":690,"635":691,"636":692,"637":694,"638":695,"639":697,"640":700,"641":702,"642":702,"646":702,"647":703,"648":703,"652":703,"653":704,"654":704,"658":704,"659":705,"660":705,"664":705,"665":706,"666":706,"670":706,"671":707,"672":707,"676":707,"677":708,"678":708,"679":708,"683":708,"684":709,"685":709,"689":709,"690":710,"691":710,"695":710,"696":711,"697":711,"701":711,"702":712,"703":712,"707":712,"708":713,"709":713,"713":713,"714":714,"715":714,"719":714,"720":717,"721":719,"722":720,"723":721,"724":722,"725":723,"726":724,"727":727,"728":729,"729":730,"730":731,"731":732,"732":735,"733":737,"734":738,"735":739,"736":740,"737":741,"738":742,"739":743,"740":744,"741":746,"742":747,"743":748,"744":749,"745":751,"746":752,"747":753,"748":754,"749":757,"750":759,"751":760,"752":761,"753":762,"754":763,"755":764,"756":765,"757":766,"758":767,"759":768,"760":769,"761":772,"762":774,"763":775,"764":776,"765":777,"766":778,"767":780,"768":782,"769":783,"770":784,"771":785,"775":789,"776":792,"777":794,"778":795,"779":796,"782":798,"783":800,"784":801,"785":802,"788":804,"789":806,"790":807,"791":808,"792":809,"795":811,"796":814,"797":815,"798":816,"799":817,"800":818,"801":819,"802":819,"808":821,"809":823,"810":824,"811":825,"812":826,"813":827,"814":828,"818":830,"819":831,"820":832,"826":835,"827":837,"828":838,"829":841,"830":842,"831":843,"832":844,"833":845,"834":846,"835":847,"836":848,"837":849,"838":850,"839":851,"840":852,"841":853,"842":854,"843":855,"844":856,"845":857,"846":858,"847":859,"848":860,"849":861,"850":862,"851":863,"852":864,"853":865,"854":866,"855":867,"856":868,"857":871,"858":873,"859":875,"860":875,"864":876,"865":878,"866":878,"870":879,"871":881,"872":882,"876":885,"880":887,"881":889,"882":890,"885":892,"886":893,"891":895,"892":897,"893":898,"896":900,"897":901,"900":903,"901":904,"905":907,"911":909,"912":913,"913":915,"914":916,"915":917,"916":918,"919":920,"920":921,"924":924,"928":927,"929":928,"930":930,"931":931,"932":932,"935":933,"936":934,"940":936,"941":939,"942":941,"943":942,"947":946,"948":948,"949":950,"950":951,"951":952,"954":957,"955":960,"956":962,"957":963,"958":964,"959":965,"960":966,"961":968,"962":969,"963":970,"964":973,"965":975,"966":976,"967":977,"968":978,"969":979,"970":980,"971":981,"972":982,"973":983,"974":984,"975":987,"976":988,"977":989,"978":990,"979":992,"980":993,"981":996,"982":998,"983":999,"984":1001,"985":1002,"986":1004,"987":1005,"988":1006,"989":1008,"990":1009,"991":1011,"992":1012,"993":1014,"994":1015,"995":1016,"996":1017,"997":1019,"998":1020,"999":1021,"1000":1023,"1001":1024,"1002":1025,"1003":1027,"1004":1028,"1005":1030,"1006":1031,"1007":1033,"1008":1034,"1009":1035,"1010":1036,"1011":1037,"1012":1039,"1013":1040,"1014":1041,"1015":1042,"1016":1043,"1017":1044,"1018":1046,"1019":1047,"1020":1049,"1021":1050,"1022":1052,"1023":1053,"1024":1055,"1025":1056,"1026":1058,"1027":1059,"1028":1060,"1029":1062,"1030":1063,"1031":1064,"1032":1065,"1033":1067,"1034":1068,"1035":1070,"1036":1071,"1037":1072,"1038":1073,"1039":1074,"1040":1075,"1041":1077,"1042":1079,"1046":1087,"1047":1088,"1048":1089,"1049":1091,"1050":1092,"1051":1094,"1052":1095,"1053":1097,"1054":1099,"1055":1100,"1056":1101,"1057":1102,"1058":1104,"1059":1105,"1060":1106,"1061":1108,"1062":1109,"1063":1110,"1064":1112,"1065":1113,"1066":1114,"1067":1121,"1068":1122,"1069":1124,"1070":1125,"1071":1126,"1072":1127,"1073":1130,"1074":1132,"1075":1133,"1076":1134,"1077":1136,"1078":1137,"1079":1138,"1080":1140,"1081":1141,"1082":1142,"1083":1143,"1084":1145,"1085":1146,"1086":1147,"1087":1148,"1088":1150,"1089":1151,"1090":1152,"1091":1153,"1092":1155,"1093":1156,"1094":1157,"1095":1158,"1096":1160,"1097":1161,"1098":1162,"1099":1163,"1100":1164,"1101":1166,"1102":1167,"1103":1168,"1104":1169,"1105":1171,"1106":1172,"1107":1173,"1108":1174,"1109":1175,"1110":1177,"1111":1178,"1112":1179,"1113":1180,"1114":1181,"1115":1183,"1116":1184,"1117":1185,"1118":1186,"1119":1187,"1120":1188,"1121":1189,"1122":1191,"1123":1192,"1124":1193,"1125":1194,"1126":1197,"1127":1199,"1128":1200,"1129":1201,"1130":1202,"1131":1203,"1132":1206,"1133":1207,"1134":1208,"1135":1211,"1136":1213,"1137":1214,"1138":1215,"1139":1215,"1143":1215,"1144":1217,"1145":1218,"1146":1220,"1147":1221,"1148":1223,"1149":1224,"1150":1226,"1151":1227,"1152":1229,"1153":1231,"1154":1232,"1155":1235,"1156":1237,"1157":1238,"1158":1240,"1159":1240,"1163":1240,"1164":1241,"1165":1241,"1169":1241,"1170":1243,"1171":1244,"1172":1245,"1173":1246,"1174":1247,"1175":1249,"1176":1250,"1180":1254,"1181":1256,"1182":1258,"1183":1259,"1184":1260,"1185":1261,"1186":1262,"1187":1263,"1188":1264,"1189":1265,"1190":1266,"1191":1267,"1192":1268,"1193":1270,"1194":1271,"1195":1276,"1196":1279,"1197":1284,"1198":1285,"1199":1286,"1200":1287,"1201":1289,"1202":1292,"1203":1294,"1204":1295,"1205":1297,"1206":1298,"1207":1300,"1208":1301,"1209":1302,"1210":1303,"1211":1305,"1212":1306,"1213":1308,"1214":1309,"1215":1310,"1216":1312,"1217":1313,"1218":1315,"1219":1316,"1220":1317,"1221":1319,"1222":1320,"1223":1321,"1224":1325,"1225":1326,"1226":1327,"1227":1331,"1228":1332,"1229":1333,"1230":1337,"1231":1338,"1232":1342,"1233":1345,"1234":1346,"1235":1348,"1236":1348,"1241":1349,"1242":1351,"1243":1352,"1244":1354,"1245":1355,"1246":1357,"1247":1358,"1248":1360,"1249":1361,"1250":1363,"1251":1363,"1255":1363,"1256":1365,"1257":1366,"1258":1369,"1259":1371,"1260":1371,"1264":1371,"1265":1372,"1266":1372,"1270":1372,"1271":1373,"1272":1373,"1276":1373,"1277":1374,"1278":1374,"1282":1374,"1283":1375,"1284":1375,"1288":1375,"1292":1380,"1293":1382,"1294":1384,"1295":1385,"1296":1386,"1297":1387,"1298":1389,"1300":1393,"1301":1394,"1302":1395,"1303":1399,"1304":1401,"1305":1402,"1306":1404,"1307":1405,"1308":1406,"1309":1408,"1310":1409,"1311":1410,"1312":1412,"1313":1413,"1314":1416,"1315":1417,"1316":1418,"1317":1419,"1318":1420,"1319":1421,"1323":1429,"1324":1431,"1325":1433,"1326":1433,"1330":1433,"1331":1434,"1332":1434,"1336":1434,"1337":1436,"1338":1437,"1339":1438,"1340":1440,"1341":1441,"1342":1443,"1343":1444,"1347":1446,"1348":1447,"1349":1449,"1350":1451,"1351":1452,"1352":1453,"1353":1454,"1354":1456,"1355":1457,"1356":1458,"1357":1459,"1358":1460,"1359":1461,"1360":1462,"1361":1464,"1362":1465,"1366":1470,"1367":1472,"1368":1474,"1369":1475,"1370":1476,"1371":1477,"1372":1478,"1373":1479,"1374":1480,"1375":1481,"1376":1482,"1380":1485,"1381":1487,"1382":1489,"1383":1491,"1384":1492,"1385":1494,"1386":1495,"1387":1496,"1388":1498,"1389":1499,"1390":1500,"1391":1502,"1392":1503,"1393":1504,"1394":1506,"1395":1507,"1396":1508,"1397":1510,"1398":1511,"1402":1512,"1403":1514,"1404":1515,"1408":1516,"1409":1518,"1410":1519,"1414":1520,"1415":1522,"1416":1523,"1420":1524,"1424":1527,"1425":1529,"1426":1531,"1427":1532,"1428":1534,"1429":1535,"1430":1537,"1431":1538,"1432":1540,"1433":1541,"1434":1543,"1435":1544,"1436":1546,"1437":1547,"1438":1548,"1439":1550,"1440":1551,"1441":1553,"1442":1554,"1446":1557,"1447":1559,"1448":1561,"1449":1562,"1450":1563,"1454":1566,"1455":1568,"1456":1570,"1457":1571,"1458":1572,"1459":1573,"1460":1574,"1461":1576,"1462":1577,"1463":1578,"1464":1579,"1465":1581,"1466":1582,"1470":1585,"1471":1587,"1475":1591,"1476":1593,"1477":1595,"1478":1596,"1479":1598,"1480":1599,"1481":1601,"1482":1603,"1483":1604,"1484":1605,"1485":1607,"1486":1608,"1487":1610,"1488":1611,"1489":1613,"1490":1614,"1494":1618,"1495":1619,"1496":1621,"1497":1622,"1498":1623,"1499":1624,"1500":1624,"1504":1624,"1505":1626,"1506":1627,"1510":1630,"1511":1631,"1512":1632,"1516":1635,"1517":1636,"1521":1639,"1523":1640,"1525":1640,"1526":1641,"1530":1644,"1531":1645,"1532":1647,"1533":1648,"1534":1649,"1535":1650,"1536":1652,"1537":1653,"1538":1654,"1539":1655,"1540":1656,"1541":1657,"1547":1660,"1548":1662,"1552":1665,"1553":1667,"1554":1669,"1555":1670,"1556":1672,"1557":1673,"1558":1673,"1562":1673,"1563":1674,"1564":1674,"1568":1674,"1569":1675,"1570":1675,"1574":1675,"1578":1685,"1579":1686,"1583":1689,"1584":1690,"1585":1692,"1586":1694,"1590":1697,"1591":1698,"1595":1702,"1596":1704,"1597":1706,"1598":1706,"1602":1706,"1603":1707,"1604":1707,"1608":1707,"1609":1708,"1610":1708,"1614":1708,"1615":1709,"1616":1709,"1620":1709,"1621":1711,"1622":1716,"1623":1717,"1624":1719,"1625":1720,"1626":1721,"1627":1722,"1628":1723,"1629":1725,"1630":1726,"1631":1727,"1632":1728,"1633":1729,"1634":1731,"1635":1732,"1636":1733,"1637":1734,"1638":1743,"1639":1744,"1640":1745,"1641":1747,"1642":1748,"1643":1749,"1644":1751,"1645":1751,"1649":1751,"1653":1755,"1654":1756,"1658":1759,"1659":1761,"1660":1763,"1661":1764,"1662":1765,"1663":1766,"1664":1767,"1665":1768,"1666":1770,"1667":1770,"1671":1770,"1675":1773,"1676":1775,"1677":1777,"1678":1779,"1679":1780,"1680":1783,"1681":1784,"1682":1785,"1686":1790,"1687":1792,"1688":1794,"1689":1796,"1690":1797,"1691":1798,"1692":1799,"1693":1801,"1694":1803,"1695":1804,"1696":1806,"1697":1807,"1698":1808,"1699":1810,"1700":1811,"1701":1813,"1702":1814,"1703":1816,"1704":1817,"1705":1819,"1706":1820,"1707":1822,"1708":1823,"1709":1825,"1710":1826,"1711":1828,"1712":1828,"1716":1828,"1720":1832,"1721":1834,"1722":1836,"1723":1837,"1724":1839,"1725":1840,"1726":1842,"1727":1844,"1728":1845,"1729":1847,"1730":1848,"1731":1850,"1732":1851,"1733":1852,"1734":1853,"1735":1855,"1736":1858,"1737":1859,"1738":1860,"1739":1861,"1740":1862,"1741":1863,"1742":1864,"1743":1866,"1744":1867,"1745":1868,"1749":1871,"1750":1873,"1751":1875,"1752":1876,"1756":1885,"1758":1888,"1759":1888,"1760":1889,"1761":1889,"1762":1890,"1765":1892,"1770":1894,"1772":1895,"1773":1895,"1774":1896,"1778":1898,"1780":1900,"1784":1902,"1786":1906,"1790":1908,"1792":1910,"1798":1912,"1800":1913,"1801":1913,"1802":1916,"1806":1918,"1808":1919,"1809":1919,"1810":1919,"1811":1919,"1812":1920,"1816":1922,"1818":1923,"1820":1923,"1821":1924,"1826":1926,"1828":1928,"1832":1930,"1834":1932,"1838":1934,"1840":1935,"1841":1935,"1842":1936,"1846":1938,"1848":1939,"1849":1939,"1850":1940,"1854":1946,"1855":1947,"1859":1950,"1860":1952,"1863":1954,"1865":1956,"1869":1959,"1871":1961,"1875":1964,"1878":1965,"1879":1966} */

?>