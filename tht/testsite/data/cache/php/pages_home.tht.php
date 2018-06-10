<?php

namespace tht56c96bb25769e47a6a07ef140e39e79f;
\o\Runtime::setNameSpace('pages/home.tht','tht56c96bb25769e47a6a07ef140e39e79f');

function u_main ()  {
 $u_test = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Test'))->u_new();
u_run($u_test);
\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_send_html(u_html(\o\v($u_test)->u_results_html()));
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
function u_runtime_errors ($u_t)  {
 \o\v($u_t)->u_section("Runtime Errors");
\o\v($u_t)->u_dies(function  ()  {
 \o\v("abc")->u_sdf();
 return \o\Runtime::void(__METHOD__);
}
, "non-existent method");
\o\v($u_t)->u_dies(function  ()  {
 \o\v("abc {1}")->u_fill(\o\OList::create([ "foo" ]));
 return \o\Runtime::void(__METHOD__);
}
, "bad fill value");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\OMap::create([ 'a' => 1 ]))->u_sdfsdf();
 return \o\Runtime::void(__METHOD__);
}
, "invalid method");
\o\v($u_t)->u_dies(function  ()  {
 $u_a = \o\v("sdf")->u_reverse;
 return \o\Runtime::void(__METHOD__);
}
, "missing parens in method call");
$u_fun_for = function  ()  {
 foreach (\o\uv(2) as $u_foo) {

}
 return \o\Runtime::void(__METHOD__);
}
;
\o\v($u_t)->u_dies($u_fun_for, "Invalid argument");
\o\v($u_t)->u_dies(function  ()  {
 return \o\v("abc")->u_length;
 return \o\Runtime::void(__METHOD__);
}
, "length()");
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
, "private state");
\o\v($u_t)->u_dies(function  ()  {
 \o\v($u_tc)->u_foo = 123;
 return \o\Runtime::void(__METHOD__);
}
, "Fields locked after construction");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'TestClass'))->u_factory())->u_name === "factory"), "module factory");
\o\OBare::u_import(__NAMESPACE__, "subDir/OtherClass");
$u_oc = \o\Runtime::newObject(__NAMESPACE__, "OtherClass", []);
\o\v($u_t)->u_ok((\o\v($u_oc)->u_ok() === "other"), "OtherClass");
 return \o\Runtime::void(__METHOD__);
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
  return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
function u_test_functions ($u_t)  {
 \o\v($u_t)->u_section("Functions");
function u_test ()  {
 return "yay";
 return \o\Runtime::void(__METHOD__);
}
\o\v($u_t)->u_ok((u_test() === "yay"), "no args");
function u_test_a ($u_arg)  {
 return \o\Runtime::concat($u_arg, "!");
 return \o\Runtime::void(__METHOD__);
}
\o\v($u_t)->u_ok((u_test_a("hey") === "hey!"), "with arg");
function u_test_b ($u_arg="default")  {
 return \o\Runtime::concat($u_arg, "!");
 return \o\Runtime::void(__METHOD__);
}
\o\v($u_t)->u_ok((u_test_b() === "default!"), "default");
function u_test_sum ()  {
 $u_asum = 0;
foreach (\o\uv(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_arguments()) as $u_arg) {
$u_asum += \o\vn($u_arg, 1);

}
return $u_asum;
 return \o\Runtime::void(__METHOD__);
}
$u_sum = u_test_sum(1, 2, 3, 4);
\o\v($u_t)->u_ok(($u_sum === 10), "variable args");
function u_with_op ($u_foo, $u_bar="default")  {
 return $u_bar;
 return \o\Runtime::void(__METHOD__);
}
$u_r = u_with_op("hello", "world");
\o\v($u_t)->u_ok(($u_r === "world"), "default, supplied");
$u_r = u_with_op("hello");
\o\v($u_t)->u_ok(($u_r === "default"), "default, fallback");
$u_outer = "OUT";
$u_fun_closure = function  ($u_a) use ($u_outer) {
 return \o\Runtime::concat(\o\Runtime::concat($u_a, "/"), $u_outer);
 return \o\Runtime::void(__METHOD__);
}
;
\o\v($u_t)->u_ok(($u_fun_closure("IN") === "IN/OUT"), "closure");
function u_add_to_list ($u_l)  {
 $u_l []= 4;
 return \o\Runtime::void(__METHOD__);
}
$u_ref_list = \o\OList::create([ 1, 2, 3 ]);
u_add_to_list($u_ref_list);
\o\v($u_t)->u_ok((\o\v($u_ref_list)->u_length() === 4), "list (object) - pass by ref - changed");
\o\v($u_ref_list)->u_reverse();
\o\v($u_t)->u_ok((\o\v($u_ref_list)[0] === 1), "list.reverse - not changed in place");
function u_add_to_string ($u_s)  {
 $u_s .= "4";
 return \o\Runtime::void(__METHOD__);
}
$u_ref_str = "123";
u_add_to_string($u_ref_str);
\o\v($u_t)->u_ok((\o\v($u_ref_str)->u_length() === 3), "string - pass by ref - unchanged");
$u_fn_no_return = function  ()  {
 $u_v = u_no_return();
\o\v($u_v)->u_reverse();
 return \o\Runtime::void(__METHOD__);
}
;
\o\v($u_t)->u_dies($u_fn_no_return, "returned Nothing");
function u_missing_args ($u_arg1, $u_arg2)  {
  return \o\Runtime::void(__METHOD__);
}
\o\v($u_t)->u_dies(function  ()  {
 u_missing_args(1);
 return \o\Runtime::void(__METHOD__);
}
, "Missing argument - user function");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_read();
 return \o\Runtime::void(__METHOD__);
}
, "Missing argument - module");
\o\v($u_t)->u_section("Function - Argument Checking");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_string(""), "string");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_number(123), "number");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_list(\o\OList::create([  ])), "list");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_flag(false), "flag");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_map(\o\OMap::create([  ])), "map");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_multi("", 0, \o\OList::create([  ])), "multi: string, number, list");
\o\v($u_t)->u_dies(function  ()  {
 \o\v($u_t)->u_check_args_map(true, true);
 return \o\Runtime::void(__METHOD__);
}
, "Too many args");
\o\v($u_t)->u_dies(function  ()  {
 \o\v($u_t)->u_check_args_map(\o\OList::create([  ]));
 return \o\Runtime::void(__METHOD__);
}
, "Expect map.  Got List.");
\o\v($u_t)->u_dies(function  ()  {
 \o\v($u_t)->u_check_args_map("x");
 return \o\Runtime::void(__METHOD__);
}
, "Expect map. Got String");
\o\v($u_t)->u_dies(function  ()  {
 \o\v($u_t)->u_check_args_map(123);
 return \o\Runtime::void(__METHOD__);
}
, "Expect map. Got Number");
\o\v($u_t)->u_dies(function  ()  {
 \o\v($u_t)->u_check_args_map(true);
 return \o\Runtime::void(__METHOD__);
}
, "Expect map. Got Flag");
\o\v($u_t)->u_ok(\o\v($u_t)->u_check_args_string(123), "Number as string");
\o\v($u_t)->u_dies(function  ()  {
 \o\v($u_t)->u_check_args_number("123");
 return \o\Runtime::void(__METHOD__);
}
, "String as number");
\o\v($u_t)->u_dies(function  ()  {
 \o\v($u_t)->u_check_args_multi(true, 123, \o\OList::create([  ]));
 return \o\Runtime::void(__METHOD__);
}
, "Multi (snl): bad #1");
\o\v($u_t)->u_dies(function  ()  {
 \o\v($u_t)->u_check_args_multi("", "123", \o\OList::create([  ]));
 return \o\Runtime::void(__METHOD__);
}
, "Multi (snl): bad #2");
\o\v($u_t)->u_dies(function  ()  {
 \o\v($u_t)->u_check_args_multi("", 123, "x");
 return \o\Runtime::void(__METHOD__);
}
, "Multi (snl): bad #3");
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
, "Map key not found");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\OMap::create([  ]))->u_get_key("VAL");
 return \o\Runtime::void(__METHOD__);
}
, "Map value not found");
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
, "Add string to number");
\o\v($u_t)->u_dies(function  ()  {
 return (\o\vn(2, 1) + \o\vn("b", 1));
 return \o\Runtime::void(__METHOD__);
}
, "Add number to string");
\o\v($u_t)->u_dies(function  ()  {
 return (\o\vn("a", 0) * \o\vn(2, 0));
 return \o\Runtime::void(__METHOD__);
}
, "Multiply string");
\o\v($u_t)->u_dies(function  ()  {
 return (\o\vn("a", 0) % \o\vn(2, 0));
 return \o\Runtime::void(__METHOD__);
}
, "Modulo string");
\o\v($u_t)->u_dies(function  ()  {
 return (\o\vn(true, 1) + \o\vn(2, 1));
 return \o\Runtime::void(__METHOD__);
}
, "Add flag to number");
\o\v($u_t)->u_dies(function  ()  {
 return (\o\vn(\o\OMap::create([  ]), 1) + \o\vn(2, 1));
 return \o\Runtime::void(__METHOD__);
}
, "Add Map to number");
\o\v($u_t)->u_dies(function  ()  {
 $u_aa = 1;
$u_aa += \o\vn("v", 1);
 return \o\Runtime::void(__METHOD__);
}
, "+= string");
\o\v($u_t)->u_dies(function  ()  {
 return (\o\vn(1, 0) > \o\vn("a", 0));
 return \o\Runtime::void(__METHOD__);
}
, "number > string");
\o\v($u_t)->u_dies(function  ()  {
 return (\o\vn(1, 0) >= \o\vn("a", 0));
 return \o\Runtime::void(__METHOD__);
}
, "number >= string");
\o\v($u_t)->u_dies(function  ()  {
 return (\o\vn(1, 0) < \o\vn("a", 0));
 return \o\Runtime::void(__METHOD__);
}
, "number < string");
\o\v($u_t)->u_dies(function  ()  {
 return (\o\vn(1, 0) <= \o\vn("a", 0));
 return \o\Runtime::void(__METHOD__);
}
, "number <= string");
\o\v($u_t)->u_dies(function  ()  {
 return (\o\vn(2, 0) ** \o\vn("a", 0));
 return \o\Runtime::void(__METHOD__);
}
, "number ** string");
\o\v($u_t)->u_dies(function  ()  {
 return (\o\vn(2, 0) / \o\vn(0, 0));
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
, "Can't combine");
\o\v($u_t)->u_dies(function  ()  {
 return \o\Runtime::concat("a", new \o\OLockString ("b"));
 return \o\Runtime::void(__METHOD__);
}
, "Can't combine");
$u_lock1 = new \o\OLockString ("1={},");
$u_lock2 = new \o\OLockString ("2={}");
$u_combined = \o\Runtime::concat($u_lock1, $u_lock2);
\o\v($u_combined)->u_fill(\o\OList::create([ "a", "b" ]));
\o\v($u_t)->u_ok((\o\v($u_combined)->u_unlocked() === "1=a,2=b"), "combined lockstrings");
\o\v($u_t)->u_ok((\o\v(u_lock_html("a"))->u_get_string_type() === "html"), "getStringType");
\o\v($u_t)->u_ok((\o\v(new \o\OLockString ("x"))->u_get_string_type() === "text"), "getStringType");
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
, "unknown sort type");
$u_list = \o\v(\o\OList::create([ "a1", "A2", "a3", "A4" ]))->u_sort(\o\OMap::create([ 'type' => "stringCase" ]));
\o\v($u_t)->u_ok((\o\v($u_list)->u_join("|") === "A2|A4|a1|a3"), "case sensitive");
\o\v($u_t)->u_section("Lists - Size Errors");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\OList::create([ 1, 2 ]))->u_remove(3);
 return \o\Runtime::void(__METHOD__);
}
, "remove()");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\OList::create([  ]))->u_remove();
 return \o\Runtime::void(__METHOD__);
}
, "empty");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\OList::create([ 1 ]))->u_sublist(2);
 return \o\Runtime::void(__METHOD__);
}
, "sublist");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\OList::create([ 1 ]))->u_first(2);
 return \o\Runtime::void(__METHOD__);
}
, "last");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\OList::create([ 1 ]))->u_last(2);
 return \o\Runtime::void(__METHOD__);
}
, "first");
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
function u_lib_file ($u_t)  {
 \o\v($u_t)->u_section("Module: File");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_exists("../bad.txt");
 return \o\Runtime::void(__METHOD__);
}
, "parent shortcut (..)");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'File'))->u_read("http://yahoo.com");
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
, "invalid table name - updateRows");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_delete_rows("\"bad", \o\v(new \o\OLockString (" key = {}"))->u_fill($u_key));
 return \o\Runtime::void(__METHOD__);
}
, "invalid table name - deleteRows");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_query("delete from test");
 return \o\Runtime::void(__METHOD__);
}
, "reject unlocked query - query");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Db'))->u_select_rows("select * from test");
 return \o\Runtime::void(__METHOD__);
}
, "reject unlocked query - selectRows");
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
function u_lib_js ($u_t)  {
 \o\v($u_t)->u_section("Module: Js");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Js'))->u_plugin("colorCode"))->u_unlocked())->u_contains("highlight"), "colorCode");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Js'))->u_plugin("lazyLoadImages"))->u_unlocked())->u_contains("img"), "lazyLoadImages");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Js'))->u_minify("/* comment */\n\nhello\n    \n") === "hello"), "minify");
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
function u_lib_litemark ($u_t)  {
 \o\v($u_t)->u_section("Module: Litemark");
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
function u_lib_meta ($u_t)  {
 \o\v($u_t)->u_section("Module: Meta");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_function_exists("libMeta"), "functionExists");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_call_function("metaCallMe", \o\OList::create([ "a", "b" ])) === "a|b"), "callFunction & arguments");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_no_template_mode(), "noTemplateMode ok");
\o\v($u_t)->u_dies(function  ()  {
 u_fail_mode_html();
 return \o\Runtime::void(__METHOD__);
}
, "noTemplateMode fail");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_function_exists("dynamicFunction"), "dynamic function exists");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_call_function("dynamicFunction", \o\OList::create([ "Hey" ])) === "Hey!!!"), "call dynamic function");
 return \o\Runtime::void(__METHOD__);
}
function u_meta_call_me ()  {
 $u_args = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_arguments();
return \o\v($u_args)->u_join("|");
 return \o\Runtime::void(__METHOD__);
}
function u_fail_template_mode ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Meta'))->u_no_template_mode();
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
function u_lib_php ($u_t)  {
 \o\v($u_t)->u_section("Module: Php");
$u_fl = \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Php'))->u_options(\o\OList::create([ "PATHINFO_FILENAME", "PATHINFO_BASENAME" ]));
\o\v($u_t)->u_ok(($u_fl === 10), "PHP - constant flags");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Php'))->u_call(new \o\OLockString ("strrev"), \o\OList::create([ "abcdef" ])) === "fedcba"), "call");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Php'))->u_call(new \o\OLockString ("nonexistent"), \o\OList::create([ 1, 2 ]));
 return \o\Runtime::void(__METHOD__);
}
, "Non-existent PHP call");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Php'))->u_call(new \o\OLockString ("eval"), \o\OList::create([ "print(\"hi\");" ]));
 return \o\Runtime::void(__METHOD__);
}
, "stop blacklisted function - by name");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Php'))->u_call(new \o\OLockString ("ini_set"), \o\OList::create([ "x", "y" ]));
 return \o\Runtime::void(__METHOD__);
}
, "stop blacklisted function - by match");
 return \o\Runtime::void(__METHOD__);
}
function u_lib_test ($u_t)  {
 \o\v($u_t)->u_section("Module: Test");
 return \o\Runtime::void(__METHOD__);
}
function u_lib_global ($u_t)  {
 \o\v($u_t)->u_section("Module: Global");
u_set_globals();
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global'))->u_hello === "world"), "global set");
 return \o\Runtime::void(__METHOD__);
}
function u_set_globals ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global'))->u_hello = "world";
 return \o\Runtime::void(__METHOD__);
}
function u_lib_web ($u_t)  {
 \o\v($u_t)->u_section("Module: Web");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_redirect("http://google.com");
 return \o\Runtime::void(__METHOD__);
}
, "redirect - normal");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_redirect("mailto:google.com");
 return \o\Runtime::void(__METHOD__);
}
, "redirect - mailto");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_redirect("//google.com");
 return \o\Runtime::void(__METHOD__);
}
, "redirect - no protocol");
\o\v($u_t)->u_dies(function  ()  {
 \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web'))->u_redirect("bob@ftp://google.com");
 return \o\Runtime::void(__METHOD__);
}
, "redirect - ftp & username");
\o\v($u_t)->u_section("Module: Web - Form Input");
\o\v($u_t)->u_ok((u_form_validate("id123", "id") === "id123"), "id ok");
\o\v($u_t)->u_ok((u_form_validate("\$foo", "id") === ""), "id not ok");
\o\v($u_t)->u_ok((u_form_validate("1234", "number") === 1234), "number ok");
\o\v($u_t)->u_ok((u_form_validate("123,456", "number") === 123456), "number with comma ok");
\o\v($u_t)->u_ok((u_form_validate("123'456", "number") === 123456), "number with apos ok");
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
\o\v($u_t)->u_ok((u_form_validate("1", "accepted") === true), "accepted ok");
\o\v($u_t)->u_ok((u_form_validate("0", "accepted") === ""), "accepted not ok");
\o\v($u_t)->u_ok((u_form_validate("", "accepted") === ""), "accepted not ok");
\o\v($u_t)->u_ok((u_form_validate("(123) 456-7890 x23", "phone") === "(123) 456-7890 x23"), "phone ok");
\o\v($u_t)->u_ok((u_form_validate("badPhone", "phone") === ""), "phone not ok");
\o\v($u_t)->u_ok((u_form_validate("abc  123!", "text") === "abc 123!"), "text ok");
\o\v($u_t)->u_ok((u_form_validate("abc<b>tag", "text") === "abctag"), "text no tag");
\o\v($u_t)->u_ok((u_form_validate("abc\nline2", "text") === "abc line2"), "text newline");
\o\v($u_t)->u_ok((u_form_validate("abc  123\n\n\nxyz!\n", "textarea") === "abc 123\n\nxyz!"), "textarea spaces");
\o\v($u_t)->u_ok((u_form_validate("abc<b>tag", "textarea") === "abctag"), "textarea no tag");
\o\v($u_t)->u_ok((u_form_validate("abc\n\n\nline2", "textarea") === "abc\n\nline2"), "textarea newline");
\o\v($u_t)->u_dies(function  ()  {
 u_form_validate("abc", "badRule");
 return \o\Runtime::void(__METHOD__);
}
, "bad rule");
 return \o\Runtime::void(__METHOD__);
}
function u_form_validate ($u_v, $u_type)  {
 return \o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'FormValidator'))->u_validate($u_v, $u_type))["value"];
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
, "missing key");
 return \o\Runtime::void(__METHOD__);
}
function u_lib_map_db ($u_t)  {
 \o\v($u_t)->u_section("Module: MapDb");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'MapDb'))->u_delete_bucket("test"), "delete bucket");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'MapDb'))->u_insert_map("test", "hello", \o\OMap::create([ 'hello' => "World!" ])), "insert");
\o\v($u_t)->u_ok(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'MapDb'))->u_insert_map("test", "hello", \o\OMap::create([ 'hello' => "There!" ])), "insert");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'MapDb'))->u_select_map("test", 1))->u_hello === "World!"), "selectMap");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'MapDb'))->u_select_maps("test", "hello"))->u_length() === 2), "selectMaps");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'MapDb'))->u_buckets())[0])->u_num_maps === 2), "buckets()");
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
, "get bad key");
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
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
 return \o\Runtime::void(__METHOD__);
}
function u_no_return ()  {
  return \o\Runtime::void(__METHOD__);
}



/* SOURCE={"file":"pages\/home.tht","6":4,"7":6,"8":7,"9":9,"12":13,"14":19,"15":19,"16":27,"17":27,"18":33,"22":37,"23":39,"24":40,"25":41,"26":42,"27":43,"28":44,"29":45,"30":46,"31":47,"32":49,"33":50,"34":52,"35":53,"36":54,"37":55,"38":56,"39":57,"40":58,"41":59,"42":61,"43":62,"44":63,"45":64,"46":65,"47":67,"48":68,"51":72,"52":74,"53":76,"54":76,"57":76,"58":77,"59":77,"62":77,"63":79,"64":80,"67":81,"68":83,"69":84,"72":85,"73":87,"74":88,"75":88,"80":91,"81":93,"82":93,"85":93,"88":97,"89":99,"90":101,"97":112,"98":113,"99":114,"100":115,"101":116,"102":119,"103":121,"104":122,"105":123,"106":124,"107":125,"108":126,"109":127,"110":128,"111":129,"112":132,"113":134,"114":135,"115":136,"116":137,"117":140,"118":142,"119":143,"120":144,"121":145,"122":146,"123":147,"124":148,"125":149,"126":150,"127":151,"128":152,"129":153,"130":154,"131":155,"132":156,"133":157,"134":158,"135":159,"136":160,"137":161,"138":162,"139":163,"140":164,"141":165,"142":166,"143":167,"144":168,"145":169,"146":170,"147":171,"148":172,"149":183,"150":185,"151":186,"152":187,"153":188,"154":189,"155":190,"156":191,"157":192,"158":193,"159":196,"160":198,"161":199,"162":200,"163":201,"164":202,"165":203,"166":207,"167":209,"168":210,"169":211,"170":212,"171":213,"172":214,"173":215,"174":216,"175":217,"176":218,"177":219,"178":220,"179":221,"180":222,"181":223,"182":224,"183":225,"184":226,"185":227,"186":228,"187":229,"188":230,"189":231,"190":232,"191":233,"192":234,"193":235,"194":236,"195":237,"196":238,"197":239,"198":240,"199":241,"200":242,"201":243,"202":245,"203":246,"204":247,"205":248,"206":249,"207":252,"208":254,"209":255,"210":256,"211":257,"212":258,"213":259,"214":261,"215":262,"216":263,"217":264,"218":265,"219":268,"220":270,"221":271,"222":272,"223":273,"224":274,"225":275,"226":276,"227":277,"228":280,"229":282,"230":283,"231":284,"232":285,"233":286,"234":287,"235":288,"236":289,"237":290,"238":291,"239":292,"240":293,"241":294,"242":295,"243":296,"244":297,"245":298,"246":299,"247":301,"250":309,"251":311,"252":314,"253":315,"254":316,"255":317,"256":318,"257":319,"258":320,"259":321,"262":323,"263":324,"264":325,"265":326,"266":329,"267":335,"268":337,"269":338,"270":339,"271":341,"272":342,"273":343,"274":348,"275":350,"276":352,"277":353,"278":355,"279":356,"280":359,"281":360,"282":364,"283":366,"284":368,"285":369,"286":370,"287":372,"288":373,"289":374,"290":375,"291":377,"292":379,"293":379,"296":379,"297":381,"298":381,"301":381,"302":383,"303":386,"304":387,"305":389,"308":393,"309":395,"310":397,"311":398,"312":399,"313":400,"314":401,"315":402,"316":403,"317":404,"318":404,"321":405,"322":409,"323":411,"324":412,"325":413,"326":414,"327":415,"328":416,"329":417,"330":419,"331":420,"332":421,"333":422,"334":423,"335":424,"336":425,"339":430,"340":432,"341":434,"342":435,"345":437,"346":439,"347":440,"350":442,"351":444,"352":445,"355":447,"356":449,"357":450,"358":451,"359":452,"362":454,"365":456,"366":457,"367":462,"368":463,"371":465,"372":466,"373":468,"374":469,"375":471,"376":472,"377":473,"381":475,"382":478,"383":479,"386":481,"387":482,"388":483,"389":485,"390":486,"391":490,"392":491,"395":493,"396":494,"397":495,"398":498,"399":499,"400":500,"404":502,"405":505,"406":505,"408":506,"409":506,"412":506,"413":507,"414":507,"417":507,"418":513,"419":515,"420":516,"421":517,"422":518,"423":519,"424":521,"425":524,"426":524,"429":524,"430":526,"431":526,"434":526,"435":527,"436":527,"439":527,"440":528,"441":528,"444":528,"445":529,"446":529,"449":529,"450":531,"451":532,"452":532,"455":532,"456":534,"457":534,"460":534,"461":535,"462":535,"465":535,"466":536,"467":536,"470":536,"473":542,"474":544,"475":546,"476":547,"477":548,"478":549,"479":550,"480":551,"481":552,"482":553,"483":554,"484":555,"485":556,"486":557,"487":560,"488":562,"489":564,"490":565,"491":570,"492":572,"493":573,"494":573,"497":573,"498":576,"499":578,"500":579,"501":580,"502":581,"503":582,"504":583,"505":586,"506":588,"507":589,"508":590,"509":592,"510":593,"511":594,"512":597,"513":599,"514":601,"515":602,"516":604,"517":605,"518":607,"519":608,"520":611,"521":612,"522":613,"523":614,"524":615,"525":616,"526":617,"527":620,"528":621,"529":621,"532":621,"533":622,"534":625,"535":626,"536":627,"537":628,"538":629,"539":632,"540":634,"541":634,"544":634,"545":635,"546":635,"549":635,"552":640,"553":643,"554":645,"555":646,"556":647,"557":648,"558":649,"559":650,"560":651,"561":652,"562":654,"563":655,"564":656,"565":657,"566":658,"567":659,"568":660,"569":662,"570":663,"571":665,"572":668,"573":670,"574":670,"577":670,"578":671,"579":671,"582":671,"583":672,"584":672,"587":672,"588":673,"589":673,"592":673,"593":674,"594":674,"597":674,"598":675,"599":675,"602":675,"603":676,"604":676,"605":676,"608":676,"609":677,"610":677,"613":677,"614":678,"615":678,"618":678,"619":679,"620":679,"623":679,"624":680,"625":680,"628":680,"629":681,"630":681,"633":681,"634":682,"635":682,"638":682,"639":685,"640":687,"641":688,"642":689,"643":690,"644":691,"645":692,"646":695,"647":697,"648":698,"649":699,"650":700,"651":703,"652":705,"653":706,"654":707,"655":708,"656":709,"657":710,"658":711,"659":712,"660":714,"661":715,"662":716,"663":717,"664":719,"665":720,"666":721,"667":722,"668":725,"669":727,"670":728,"671":729,"672":730,"673":731,"674":732,"675":733,"676":734,"677":735,"678":736,"679":737,"680":740,"681":742,"682":743,"683":744,"684":745,"685":746,"686":748,"687":750,"688":751,"689":752,"690":753,"693":757,"694":760,"695":762,"696":763,"697":764,"700":766,"701":768,"702":769,"703":770,"706":772,"707":774,"708":775,"709":776,"710":777,"713":779,"714":782,"715":783,"716":784,"717":785,"718":786,"719":787,"720":787,"726":789,"727":791,"728":792,"729":793,"730":794,"731":795,"732":796,"736":798,"737":799,"738":800,"744":803,"745":805,"746":806,"747":809,"748":810,"749":811,"750":812,"751":813,"752":814,"753":815,"754":816,"755":817,"756":818,"757":819,"758":820,"759":821,"760":822,"761":823,"762":824,"763":825,"764":826,"765":827,"766":828,"767":829,"768":830,"769":831,"770":832,"771":833,"772":834,"773":835,"774":836,"775":839,"776":841,"777":843,"778":843,"782":844,"783":846,"784":846,"788":847,"789":849,"790":850,"794":853,"798":855,"799":857,"800":858,"803":860,"804":861,"809":863,"810":865,"811":866,"814":868,"815":869,"818":871,"819":872,"823":875,"829":877,"830":881,"831":883,"832":884,"833":885,"834":886,"837":888,"838":889,"842":892,"846":895,"847":896,"848":898,"849":899,"850":900,"853":901,"854":902,"858":904,"859":907,"860":909,"861":910,"864":914,"865":916,"866":918,"867":919,"868":920,"871":925,"872":928,"873":930,"874":931,"875":932,"876":933,"877":934,"878":936,"879":937,"880":938,"881":941,"882":943,"883":944,"884":945,"885":946,"886":947,"887":948,"888":949,"889":950,"890":951,"891":952,"892":955,"893":956,"894":957,"895":958,"896":960,"897":961,"898":964,"899":966,"900":967,"901":969,"902":970,"903":972,"904":973,"905":974,"906":976,"907":977,"908":979,"909":980,"910":982,"911":983,"912":984,"913":985,"914":987,"915":988,"916":989,"917":991,"918":992,"919":993,"920":995,"921":996,"922":998,"923":999,"924":1001,"925":1002,"926":1003,"927":1004,"928":1005,"929":1007,"930":1008,"931":1009,"932":1010,"933":1011,"934":1012,"935":1014,"936":1015,"937":1017,"938":1018,"939":1020,"940":1021,"941":1023,"942":1024,"943":1026,"944":1027,"945":1028,"946":1030,"947":1031,"948":1032,"949":1033,"950":1035,"951":1036,"952":1038,"953":1039,"954":1040,"955":1041,"956":1042,"957":1043,"958":1045,"959":1047,"963":1055,"964":1056,"965":1057,"966":1059,"967":1060,"968":1062,"969":1063,"970":1065,"971":1067,"972":1068,"973":1069,"974":1070,"975":1072,"976":1073,"977":1074,"978":1076,"979":1077,"980":1078,"981":1080,"982":1081,"983":1082,"984":1089,"985":1090,"986":1092,"987":1093,"988":1094,"989":1095,"990":1098,"991":1100,"992":1101,"993":1102,"994":1104,"995":1105,"996":1106,"997":1108,"998":1109,"999":1110,"1000":1111,"1001":1113,"1002":1114,"1003":1115,"1004":1116,"1005":1118,"1006":1119,"1007":1120,"1008":1121,"1009":1123,"1010":1124,"1011":1125,"1012":1126,"1013":1128,"1014":1129,"1015":1130,"1016":1131,"1017":1132,"1018":1134,"1019":1135,"1020":1136,"1021":1137,"1022":1139,"1023":1140,"1024":1141,"1025":1142,"1026":1143,"1027":1145,"1028":1146,"1029":1147,"1030":1148,"1031":1149,"1032":1151,"1033":1152,"1034":1153,"1035":1154,"1036":1155,"1037":1156,"1038":1157,"1039":1159,"1040":1160,"1041":1161,"1042":1162,"1043":1165,"1044":1167,"1045":1168,"1046":1169,"1047":1170,"1048":1171,"1049":1174,"1050":1175,"1051":1176,"1052":1179,"1053":1181,"1054":1182,"1055":1183,"1056":1183,"1059":1183,"1060":1185,"1061":1186,"1062":1188,"1063":1189,"1064":1191,"1065":1192,"1066":1194,"1067":1195,"1068":1197,"1069":1199,"1070":1200,"1071":1203,"1072":1205,"1073":1206,"1074":1208,"1075":1208,"1078":1208,"1079":1209,"1080":1209,"1083":1209,"1084":1211,"1085":1212,"1086":1213,"1087":1214,"1088":1215,"1089":1217,"1090":1218,"1093":1222,"1094":1224,"1095":1226,"1096":1227,"1097":1228,"1098":1229,"1099":1230,"1100":1231,"1101":1232,"1102":1233,"1103":1234,"1104":1235,"1105":1236,"1106":1238,"1107":1239,"1108":1244,"1109":1247,"1110":1252,"1111":1253,"1112":1254,"1113":1255,"1114":1257,"1115":1260,"1116":1262,"1117":1263,"1118":1265,"1119":1266,"1120":1268,"1121":1269,"1122":1270,"1123":1271,"1124":1273,"1125":1274,"1126":1276,"1127":1277,"1128":1278,"1129":1280,"1130":1281,"1131":1283,"1132":1284,"1133":1285,"1134":1287,"1135":1288,"1136":1289,"1137":1293,"1138":1294,"1139":1295,"1140":1299,"1141":1300,"1142":1301,"1143":1305,"1144":1306,"1145":1310,"1146":1313,"1147":1314,"1148":1316,"1149":1316,"1153":1317,"1154":1319,"1155":1320,"1156":1322,"1157":1323,"1158":1325,"1159":1326,"1160":1328,"1161":1329,"1162":1331,"1163":1331,"1166":1331,"1167":1333,"1168":1334,"1169":1337,"1170":1339,"1171":1339,"1174":1339,"1175":1340,"1176":1340,"1179":1340,"1180":1341,"1181":1341,"1184":1341,"1185":1342,"1186":1342,"1189":1342,"1190":1343,"1191":1343,"1194":1343,"1197":1348,"1198":1350,"1199":1352,"1200":1353,"1201":1354,"1202":1355,"1203":1357,"1205":1361,"1206":1362,"1207":1363,"1208":1367,"1209":1369,"1210":1370,"1211":1372,"1212":1373,"1213":1374,"1214":1376,"1215":1377,"1216":1378,"1217":1380,"1218":1381,"1219":1384,"1220":1385,"1221":1386,"1222":1387,"1223":1388,"1224":1389,"1227":1397,"1228":1399,"1229":1401,"1230":1401,"1233":1401,"1234":1402,"1235":1402,"1238":1402,"1239":1404,"1240":1405,"1241":1406,"1242":1408,"1243":1409,"1244":1411,"1245":1412,"1249":1414,"1250":1415,"1251":1417,"1252":1419,"1253":1420,"1254":1421,"1255":1422,"1256":1424,"1257":1425,"1258":1426,"1259":1427,"1260":1428,"1261":1429,"1262":1430,"1263":1432,"1264":1433,"1267":1438,"1268":1440,"1269":1442,"1270":1443,"1271":1444,"1272":1445,"1273":1446,"1274":1447,"1275":1448,"1276":1449,"1277":1450,"1280":1453,"1281":1455,"1282":1457,"1283":1459,"1284":1460,"1285":1462,"1286":1463,"1287":1464,"1288":1466,"1289":1467,"1290":1468,"1291":1470,"1292":1471,"1293":1472,"1294":1474,"1295":1475,"1296":1476,"1297":1478,"1298":1479,"1301":1480,"1302":1482,"1303":1483,"1306":1484,"1307":1486,"1308":1487,"1311":1488,"1312":1490,"1313":1491,"1316":1492,"1319":1495,"1320":1497,"1321":1499,"1322":1500,"1323":1502,"1324":1503,"1325":1505,"1326":1506,"1327":1508,"1328":1509,"1329":1511,"1330":1512,"1331":1514,"1332":1515,"1333":1516,"1334":1518,"1335":1519,"1336":1521,"1337":1522,"1340":1525,"1341":1527,"1342":1529,"1343":1530,"1344":1531,"1347":1534,"1348":1536,"1349":1538,"1350":1539,"1351":1540,"1352":1541,"1353":1542,"1354":1544,"1355":1545,"1356":1546,"1357":1547,"1358":1549,"1359":1550,"1362":1553,"1363":1555,"1366":1559,"1367":1561,"1368":1563,"1369":1564,"1370":1566,"1371":1567,"1372":1569,"1373":1571,"1374":1572,"1375":1573,"1376":1575,"1377":1576,"1378":1578,"1379":1579,"1380":1581,"1381":1582,"1384":1586,"1385":1587,"1386":1589,"1387":1590,"1388":1591,"1389":1592,"1390":1592,"1393":1592,"1394":1594,"1395":1595,"1398":1598,"1399":1599,"1400":1600,"1403":1603,"1404":1604,"1407":1607,"1409":1608,"1411":1608,"1412":1609,"1416":1612,"1417":1613,"1418":1615,"1419":1616,"1420":1617,"1421":1618,"1422":1620,"1423":1621,"1424":1622,"1425":1623,"1426":1624,"1427":1625,"1433":1628,"1434":1630,"1437":1633,"1438":1635,"1439":1637,"1440":1638,"1441":1640,"1442":1641,"1443":1641,"1446":1641,"1447":1642,"1448":1642,"1451":1642,"1452":1643,"1453":1643,"1456":1643,"1459":1653,"1460":1654,"1463":1657,"1464":1658,"1465":1660,"1466":1662,"1469":1665,"1470":1666,"1473":1670,"1474":1672,"1475":1674,"1476":1674,"1479":1674,"1480":1675,"1481":1675,"1484":1675,"1485":1676,"1486":1676,"1489":1676,"1490":1677,"1491":1677,"1494":1677,"1495":1679,"1496":1684,"1497":1685,"1498":1687,"1499":1688,"1500":1689,"1501":1690,"1502":1692,"1503":1693,"1504":1694,"1505":1695,"1506":1696,"1507":1698,"1508":1699,"1509":1700,"1510":1701,"1511":1703,"1512":1704,"1513":1705,"1514":1707,"1515":1708,"1516":1710,"1517":1711,"1518":1712,"1519":1714,"1520":1715,"1521":1716,"1522":1718,"1523":1718,"1526":1718,"1529":1722,"1530":1723,"1533":1726,"1534":1728,"1535":1730,"1536":1731,"1537":1732,"1538":1733,"1539":1734,"1540":1735,"1541":1737,"1542":1737,"1545":1737,"1548":1740,"1549":1742,"1550":1744,"1551":1746,"1552":1747,"1553":1750,"1554":1751,"1555":1752,"1558":1757,"1559":1759,"1560":1761,"1561":1763,"1562":1764,"1563":1765,"1564":1766,"1565":1768,"1566":1770,"1567":1771,"1568":1773,"1569":1774,"1570":1775,"1571":1777,"1572":1778,"1573":1780,"1574":1781,"1575":1783,"1576":1784,"1577":1786,"1578":1787,"1579":1789,"1580":1790,"1581":1792,"1582":1793,"1583":1795,"1584":1795,"1587":1795,"1590":1799,"1591":1801,"1592":1803,"1593":1804,"1594":1806,"1595":1807,"1596":1809,"1597":1811,"1598":1812,"1599":1814,"1600":1815,"1601":1817,"1602":1818,"1603":1819,"1604":1820,"1605":1822,"1606":1825,"1607":1826,"1608":1827,"1609":1828,"1610":1829,"1611":1830,"1612":1831,"1613":1833,"1614":1834,"1615":1835,"1618":1842,"1620":1845,"1621":1845,"1622":1846,"1623":1846,"1624":1847,"1627":1849,"1632":1851,"1634":1852,"1635":1852,"1636":1853,"1640":1855,"1642":1857,"1646":1859,"1648":1863,"1652":1865,"1654":1867,"1660":1869,"1662":1870,"1663":1870,"1664":1873,"1668":1875,"1670":1876,"1671":1876,"1672":1876,"1673":1876,"1674":1877,"1678":1879,"1680":1880,"1682":1880,"1683":1881,"1688":1883,"1690":1885,"1694":1887,"1696":1889,"1700":1891,"1702":1892,"1703":1892,"1704":1893,"1708":1895,"1710":1896,"1711":1896,"1712":1897,"1716":1903,"1717":1904,"1720":1907,"1721":1909} */

?>