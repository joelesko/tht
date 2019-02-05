<?php

namespace tht\pages\home_x;
\o\ModuleManager::registerUserModule('pages/home.tht','tht\\pages\\home_x');

function u_main ()  {
  $u_test = \o\v(\o\ModuleManager::getModule('Test'))->u_new();
u_run($u_test);
\o\v(\o\ModuleManager::getModule('Web'))->u_send_html(u_html(\o\v($u_test)->u_results_html()));
 return new \o\ONothing(__METHOD__);
 
}
function u_html ($u_results)  {
$t = \o\Runtime::openTemplate("html");
$t->addStatic("<!-- this is a comment --><html><head><title>THT Unit Tests</title>");
$t->addDynamic(\o\v(\o\ModuleManager::getModule('Css'))->u_include("base"));
$t->addStatic("</head><body><main><h1>THT Unit Tests</h1><a href=\"#test-results\" style=\"font-weight: bold\">Skip to Results</a><p style=\"font-size: 100%; margin-top: 3rem\"><b>Perf:</b> When measuring raw execution speed of this page, set <code>_disablePhpCache: false</code> in <code>app.jcon</code>.  Then take Server Response Time and subtract <code>System.sleep</code> and <code>Net.httpRequest</code>.</p>");
$t->addDynamic($u_results);
$t->addStatic("</main></body></html>");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_run ($u_t)  {
  u_test_math_and_logic($u_t);
u_test_strings($u_t);
u_test_bitwise($u_t);
u_test_control_flow($u_t);
u_test_lists($u_t);
u_test_maps($u_t);
u_test_functions($u_t);
u_test_types($u_t);
u_test_misc($u_t);
u_test_templates($u_t);
u_test_oop($u_t);
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
u_lib_meta($u_t);
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
$u_long_comment = \o\Runtime::concat(\o\Runtime::concat("// ", \o\v(\o\ModuleManager::getModule('String'))->u_repeat("a", 102)), "\n");
\o\v($u_t)->u_parser_ok($u_long_comment, "line comment over 100 chars");
$u_long_block_comment = \o\Runtime::concat(\o\Runtime::concat("/*\n", \o\v(\o\ModuleManager::getModule('String'))->u_repeat("a", 102)), "\n*/");
\o\v($u_t)->u_parser_ok($u_long_block_comment, "block comment over 100 chars");
\o\v($u_t)->u_section("Parser Errors - Names");
\o\v($u_t)->u_parser_error("let FOO = 3;", "camelCase");
\o\v($u_t)->u_parser_error("let fOO = 3;", "camelCase");
\o\v($u_t)->u_parser_error("let XMLreader = {};", "camelCase");
\o\v($u_t)->u_parser_error("let a_b = 3;", "camelCase");
\o\v($u_t)->u_parser_error("function FOO() {}", "camelCase");
\o\v($u_t)->u_parser_error("function a () {}", "longer than 1");
$u_long_name = \o\v(\o\ModuleManager::getModule('String'))->u_repeat("a", 41);
\o\v($u_t)->u_parser_error(\o\Runtime::concat(\o\Runtime::concat("let ", $u_long_name), " = 1;"), "40 characters or less");
\o\v($u_t)->u_parser_error(\o\Runtime::concat(\o\Runtime::concat("function ", $u_long_name), " () {}"), "40 characters or less");
\o\v($u_t)->u_section("Parser Errors - Aliases");
\o\v($u_t)->u_parser_error("var a = 3;", "try: `let`");
\o\v($u_t)->u_parser_error("const a = 3;", "try: `let`");
\o\v($u_t)->u_parser_error("global a = 3;", "try: `Globals");
\o\v($u_t)->u_parser_error("foreach (ary as a) { }", "try: `for`");
\o\v($u_t)->u_parser_error("let ary = [];\nfor (ary as a) { }", "item in list");
\o\v($u_t)->u_parser_error("\$foo = 123", "remove \$ from name");
\o\v($u_t)->u_parser_error("let a = 1 ^ 2", "+^");
\o\v($u_t)->u_parser_error("let a = 1 & 2", "+&");
\o\v($u_t)->u_parser_error("let a = 1 | 2", "+|");
\o\v($u_t)->u_parser_error("let a = 1 >> 2", "+>");
\o\v($u_t)->u_parser_error("let a = 1 << 2", "+<");
\o\v($u_t)->u_parser_error("let a = 1++;", "+= 1");
\o\v($u_t)->u_parser_error("if (true) { } elif (false) {}", "else if");
\o\v($u_t)->u_parser_error("if (true) { } elsif (false) {}", "else if");
\o\v($u_t)->u_parser_error("if (true) { } elseif (false) {}", "else if");
\o\v($u_t)->u_parser_error("switch() {}", "try: if/else");
\o\v($u_t)->u_parser_error("require();", "try: import");
\o\v($u_t)->u_parser_error("while () {}", "try: for { ... }");
\o\v($u_t)->u_section("Parser Errors - Misc");
\o\v($u_t)->u_parser_error("asdasd;", "unknown variable", "");
\o\v($u_t)->u_parser_error("if (a = 3) { }", "assignment", "if, missing paren");
\o\v($u_t)->u_parser_error("break;\nlet a = 3;", "unreachable");
\o\v($u_t)->u_parser_ok("return;\nlet a = 3;", "may return early");
\o\v($u_t)->u_parser_ok("if (true) { break; }", "newline not needed for one-line if");
\o\v($u_t)->u_parser_ok("function foo() { return 1; }", "newline not needed for one-line fun");
\o\v($u_t)->u_parser_error("let a = 'hello", "unexpected newline");
\o\v($u_t)->u_parser_error("for (a) {}", "expected 'in'");
\o\v($u_t)->u_parser_error("for (let i = 0; i < 10; i += 1) {}", "unexpected 'let'");
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
\o\v($u_t)->u_parser_error("print('a'), print('b');", "Missing semicolon");
\o\v($u_t)->u_parser_error("let a = 1, b = 2;", "Missing semicolon", "");
\o\v($u_t)->u_parser_error("let a = (1 + );", "incomplete");
\o\v($u_t)->u_parser_error("let a = 2 + (1 * ) + 1;", "incomplete");
\o\v($u_t)->u_parser_error("<?", "Unexpected symbol '<'");
\o\v($u_t)->u_parser_error("?>", "Unexpected symbol '?'");
\o\v($u_t)->u_parser_error("'hello'[] = 'a';", "Assignment can not");
\o\v($u_t)->u_parser_error("function test(tma1, tma1) {\n}", "Duplicate argument");
\o\v($u_t)->u_parser_error("function test(tma1, tma1 = 2) {\n}", "Duplicate argument");
\o\v($u_t)->u_parser_error("function test(tma1,\ntma2) {\n}", "Newline");
\o\v($u_t)->u_parser_error("function test(tma1, \ntma2) {\n}", "Newline");
\o\v($u_t)->u_parser_error("let a = 1;;", "Unexpected semicolon");
\o\v($u_t)->u_parser_error("let a = [1,, ];", "Unexpected comma");
\o\v($u_t)->u_parser_error("let a = [,, 1];", "Unexpected comma");
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
\o\v($u_t)->u_parser_error("let a = 1; /*\n", "separate line");
\o\v($u_t)->u_parser_error("/*\nsdf\n*/ d", "missing newline");
\o\v($u_t)->u_parser_error("/*\nsdf", "unclosed comment");
\o\v($u_t)->u_parser_error("template fooText() {\n};", "missing newline");
\o\v($u_t)->u_parser_ok("if (true)\n{ }", "newline after ')'");
\o\v($u_t)->u_parser_error("if\n(true)\n{ } ", "newline after 'if'");
\o\v($u_t)->u_parser_error("let\na = 1;", "newline after 'let'");
\o\v($u_t)->u_parser_error("for\n(el in list)", "newline after 'for'");
\o\v($u_t)->u_parser_error("function\nfoo()", "newline after 'function'");
\o\v($u_t)->u_parser_error("class\nFoo {}", "newline after 'class'");
\o\v($u_t)->u_parser_error("let a = new\nFoo()", "newline after 'new'");
\o\v($u_t)->u_parser_ok("if (true) {\n}\nelse\n{ }", "newline after 'else'");
\o\v($u_t)->u_parser_ok("for (a in ['a'])\n{ }", "newline after ')'");
\o\v($u_t)->u_parser_ok("function fn()\n{ }", "newline after ')'");
\o\v($u_t)->u_parser_error("let a = 1;  let b = 2;", "Only one semicolon statement");
\o\v($u_t)->u_parser_error("let a = 1; a = 2;", "Only one semicolon statement");
\o\v($u_t)->u_parser_ok("let a = b(F { c(); });", "Statement in anon function");
\o\v($u_t)->u_parser_error("let a = b(F { c(); d(); });", "Only one semicolon statement");
\o\v($u_t)->u_parser_ok("if (true) { b(); }", "Statement in conditional block");
\o\v($u_t)->u_parser_error("if (true) { b(); c(); }", "Only one semicolon statement");
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
\o\v($u_t)->u_parser_ok("let a = [1, 2, 3];", "no padding inside '[...]'");
\o\v($u_t)->u_parser_error("let a = [ 1, 2, 3 ];", "space after '['");
\o\v($u_t)->u_parser_error("let a = [1, 2, ];", "space before ']'");
\o\v($u_t)->u_parser_ok("let a = [\n   1, 2,\n];", "newline before ']'");
\o\v($u_t)->u_parser_error("String .random(4);", "space before '.'");
\o\v($u_t)->u_parser_error("String. random(4);", "space after '.'");
\o\v($u_t)->u_parser_error("String.\n    random(4);", "space after '.'");
\o\v($u_t)->u_parser_ok("String\n    .random(4);", "newline before '.'");
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
\o\v($u_t)->u_parser_error("template fHtml() {\nTest\n}", "should be indented");
\o\v($u_t)->u_parser_ok("template fHtml() {\n\tTest\n}", "tab indent");
\o\v($u_t)->u_parser_ok("  template fHtml() {\n\tTest\n}", "tab indent(4) vs space(2)");
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
\o\v(\o\ModuleManager::getModule('Perf'))->u_start("Large Array");
$u_now = \o\v(\o\ModuleManager::getModule('Date'))->u_now(true);
$u_num_els = 1000;
$u_nums = \o\OBare::u_range(1, $u_num_els);
$u_ii = 0;
foreach (\o\uv($u_nums) as $u_nn) {
$u_b = \o\v($u_nums)[$u_ii];
$u_ii += \o\vn(1, 1);

}
\o\v($u_t)->u_ok(($u_ii === $u_num_els), "large loop done");
$u_elapsed = (\o\vn(\o\v(\o\ModuleManager::getModule('Date'))->u_now(true), 0) - \o\vn($u_now, 0));
\o\v($u_t)->u_ok((\o\vn($u_elapsed, 0) < \o\vn(3, 0)), \o\v("ArrayAccess loop ({0} elements) took {1} ms")->u_fill($u_num_els, $u_elapsed));
\o\v(\o\ModuleManager::getModule('Perf'))->u_stop();
\o\v($u_t)->u_section("Functional Methods");
\o\v($u_t)->u_section("Result Objects");
$u_st = \o\v(\o\ModuleManager::getModule('Result'))->u_ok(123);
\o\v($u_t)->u_ok(\o\v($u_st)->u_ok(), "not ok");
\o\v($u_t)->u_ok((\o\v($u_st)->u_get() === 123), "ok value");
$u_st = \o\v(\o\ModuleManager::getModule('Result'))->u_fail(66);
\o\v($u_t)->u_ok((! \o\v($u_st)->u_ok()), "not ok");
\o\v($u_t)->u_ok((\o\v($u_st)->u_fail_code() === 66), "failCode");
\o\v($u_t)->u_section("Modules");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('TestModule'))->u_bare_fun("Joe") === "bare:Joe"), "module call - autoloaded");
\o\v(\o\ModuleManager::getModule('Global'))->u_foo = "BAR";
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('TestModule'))->u_test_global() === "global:BAR"), "module global");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('TestModule'))->u_test_module_var() === "moduleVar:mod"), "module var - inside access");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('TestModule'))->u_module_var === "mod"), "module var - outside access");
\o\OBare::u_import("subDir/OtherModule");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('OtherModule'))->u_ok("Joe") === "ok:Joe"), "import from subfolder");
 return new \o\ONothing(__METHOD__);
 
}
function u_test_oop ($u_t)  {
  \o\v($u_t)->u_section("Classes (OOP)");
$u_tc = \o\ModuleManager::newObject("TestClass", ["green", 123]);
\o\v($u_t)->u_ok((\o\v($u_tc)->u_get_full_name() === "green:123"), "get property");
\o\v($u_t)->u_ok((\o\v(\o\v($u_tc)->u_html())->u_unlocked() === "<b>Hello</b>\n"), "object template");
\o\v($u_t)->u_ok((\o\v($u_tc)->u_get_mod_var() === 123), "Module variable");
\o\v($u_t)->u_dies(function  ()  {
  return \o\v($u_tc)->u_x_field;
 return new \o\ONothing(__METHOD__);
 
}
, "No access to private field");
\o\v($u_t)->u_ok((\o\v($u_tc)->u_get_id() === 123), "getter method");
\o\v($u_t)->u_ok((\o\v($u_tc)->u_id === 123), "magic getter");
\o\v($u_tc)->u_set_id(345);
\o\v($u_t)->u_ok((\o\v($u_tc)->u_id === 345), "setter");
\o\v($u_t)->u_ok((\o\v($u_tc)->u_dependency() === "other"), "dependency");
\o\v($u_t)->u_ok((\o\v(\o\v($u_tc)->u_dep)->u_foo === 1), "dependency dynamic field");
\o\v($u_t)->u_ok((\o\v(\o\v($u_tc)->u_dep)->u_bar === 2), "dependency dynamic field");
\o\v($u_t)->u_dies(function  ()  {
  \o\v($u_tc)->u_foo = 123;
 return new \o\ONothing(__METHOD__);
 
}
, "Fields locked after construction");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\ModuleManager::getModule('TestClass'))->u_factory())->u_get_full_name() === "factory:99"), "module factory");
\o\v($u_t)->u_ok(\o\v(\o\v($u_tc)->u_z_get_methods())->u_contains("getFullName"), "zMethods");
\o\v($u_tc)->u_z_call_method("setId", \o\OList::create([ 789 ]));
\o\v($u_t)->u_ok((\o\v($u_tc)->u_z_call_method("getId") === 789), "zCallMethod");
\o\v($u_t)->u_ok((\o\v($u_tc)->u_ok_field === "dynamic:okField"), "zDynamicGet ok");
\o\v($u_t)->u_dies(function  ()  {
  \o\v($u_tc)->u_bad_field = 1;
 return new \o\ONothing(__METHOD__);
 
}
, "zDynamicGet fail");
\o\v($u_t)->u_ok((\o\v($u_tc)->u_get_secret_number() === 42), "zDynamicCall");
\o\v($u_t)->u_ok(\o\v($u_tc)->u_z_has_method("setId"), "zHasMethod true");
\o\v($u_t)->u_ok((! \o\v($u_tc)->u_z_has_method("xyz")), "zHasMethod false");
\o\v($u_t)->u_ok(\o\v($u_tc)->u_z_has_field("publicField"), "zHasField true");
\o\v($u_t)->u_ok((! \o\v($u_tc)->u_z_has_field("xyz")), "zHasField false");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\v($u_tc)->u_dep)->u_z_get_fields())->u_keys())->u_join(":") === "foo:bar"), "zGetFields");
$u_meths = \o\v($u_tc)->u_z_get_methods();
\o\v($u_t)->u_ok((\o\v($u_meths)->u_contains("getId") && \o\v($u_meths)->u_contains("getFullName")), "zGetMethods()");
\o\v($u_t)->u_dies(function  ()  {
  \o\OBare::u_print(\o\v(\o\ModuleManager::getModule('Php'))->u_version);
 return new \o\ONothing(__METHOD__);
 
}
, "version()");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('Php'))->u_function_exists("strpos"), "function exists");
\o\v($u_t)->u_ok((! \o\v(\o\ModuleManager::getModule('Php'))->u_function_exists("strposxx")), "function exists (not)");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('Php'))->u_class_exists("DateTime"), "class exists");
\o\v($u_t)->u_ok((! \o\v(\o\ModuleManager::getModule('Php'))->u_class_exists("FooBar")), "class exists (not)");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('Php'))->u_class_exists("/o/u_Test"), "class exists (o namespace)");
\o\OBare::u_import("subDir/OtherClass");
$u_oc = \o\ModuleManager::newObject("OtherClass", [\o\OMap::create([ 'a1' => 1, 'a2' => 2 ])]);
\o\v($u_t)->u_ok((\o\v($u_oc)->u_ok() === "other"), "OtherClass");
\o\v($u_t)->u_ok(((\o\v($u_oc)->u_a1 === 1) && (\o\v($u_oc)->u_a2 === 2)), "zSetFields");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('Meta'))->u_new_object("TestClass", \o\OList::create([ "green", 123 ])), "Meta.new");
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
foreach (\o\uv(\o\v(\o\ModuleManager::getModule('Meta'))->u_arguments()) as $u_arg) {
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
  \o\v(\o\ModuleManager::getModule('File'))->u_read();
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
$u_a = \o\OList::create([ 1, 2, 3 ]);
\o\v($u_t)->u_ok((u_spread(...$u_a) === "1:2:3"), "spread operator (...)");
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
$u_map = \o\OMap::create([ 'a' => 1, 'b' => 2, 'c' => 3 ]);
\o\v($u_t)->u_ok((\o\v(\o\v($u_map)->u_slice(\o\OList::create([ "b", "c" ])))->u_c === 3), "slice()");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v($u_map)->u_slice(\o\OList::create([ "b", "c" ])))->u_keys())->u_join(":") === "b:c"), "slice() keys");
\o\v($u_t)->u_ok((\o\v(\o\v($u_map)->u_slice(\o\OList::create([ "a", "z" ])))->u_z === ""), "slice() with missing key");
\o\v($u_t)->u_section("Maps - Misc Errors");
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
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\OMap::create([  ]))->u_get_key(false);
 return new \o\ONothing(__METHOD__);
 
}
, "Map.getKey(<flag>);");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\OMap::create([  ]))->u_merge(\o\OList::create([ "a" ]));
 return new \o\ONothing(__METHOD__);
 
}
, "Map.merge(<list>);");
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
\o\v($u_t)->u_section("Hex & Binary Numbers");
\o\v($u_t)->u_ok((0b10111 === 23), "binary");
\o\v($u_t)->u_ok(((\o\vn(0b10111, 0) * \o\vn(2, 0)) === 46), "binary * dec");
\o\v($u_t)->u_ok((0x1f === 31), "hex");
\o\v($u_t)->u_ok(((\o\vn(0x1f, 0) * \o\vn(2, 0)) === 62), "hex * dec");
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
\o\v($u_t)->u_ok((\o\Runtime::spaceship(4, 2) === 1), "<=> = 1");
\o\v($u_t)->u_ok((\o\Runtime::spaceship(2, 4) === (- 1)), "<=> = -1");
\o\v($u_t)->u_ok((\o\Runtime::spaceship(2, 2) === 0), "<=> = 0");
\o\v($u_t)->u_ok((\o\Runtime::spaceship(2.1, 2) === 1), "<=> float = 1");
\o\v($u_t)->u_ok((\o\Runtime::spaceship(2, 2.1) === (- 1)), "<=> float = -1");
\o\v($u_t)->u_ok((\o\Runtime::spaceship(2, 2) === 0), "<=> float = 0");
$u_str = "moo";
\o\v($u_t)->u_ok(\o\Runtime::spaceship($u_str, ("zoo" === (- 1))), "<=> string -");
\o\v($u_t)->u_ok(\o\Runtime::spaceship($u_str, ("abcdef" === 1)), "<=> string +");
\o\v($u_t)->u_ok(\o\Runtime::spaceship($u_str, ("moo" === 0)), "<=> string =");
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
\o\v($u_t)->u_section("Float & Ints");
\o\v($u_t)->u_ok((5 === 5), "5 == 5.0");
\o\v($u_t)->u_ok(((\o\vn(1, 1) + \o\vn(2, 1)) === 3), "1.0 + 2.0 == 3.0");
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
\o\v(\o\ModuleManager::getModule('File'))->u_read("sdfsdfsdf");

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
\o\v($u_t)->u_section("Multiline Strings");
$u_ml = "this is a
multiline
string.";
\o\v($u_t)->u_ok(\o\v($u_ml)->u_contains("multiline\nstring"), "multiline with indent");
\o\v($u_t)->u_parser_error("let a = ''' sdf", "newline");
\o\v($u_t)->u_parser_error("let a = '''\ndfg ''';", "separate line");
\o\v($u_t)->u_parser_error("let a = '''\ndfg ", "unclosed");
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
\o\v($u_t)->u_ok((\o\Runtime::concat(\o\v(\o\ModuleManager::getModule('String'))->u_char_from_code(65), \o\v(\o\ModuleManager::getModule('String'))->u_char_from_code(122)) === "Az"), "String.fromCharCode");
\o\v($u_t)->u_ok((\o\v("")->u_to_flag() === false), "toFlag - empty string");
\o\v($u_t)->u_ok((\o\v("0")->u_to_flag() === true), "toFlag - 0");
\o\v($u_t)->u_ok((\o\v("false")->u_to_flag() === true), "toFlag - false");
\o\v($u_t)->u_ok((\o\v("true")->u_to_flag() === true), "toFlag - true");
\o\v($u_t)->u_ok((\o\v("123")->u_to_number() === 123), "toNumber");
\o\v($u_t)->u_ok((\o\v("99ft")->u_to_number() === 99), "toNumber - trailing letters");
\o\v($u_t)->u_section("String Methods - Unicode");
$u_uni = "ⒶⒷⒸ①②③ abc123";
\o\v($u_t)->u_ok((\o\v($u_uni)->u_length() === 13), "length");
\o\v($u_t)->u_ok((\o\v($u_uni)->u_char_at(2) === "Ⓒ"), "charAt");
\o\v($u_t)->u_ok((\o\v($u_uni)->u_char_at((- 1)) === "3"), "charAt negative");
\o\v($u_t)->u_ok((\o\v($u_uni)->u_char_code_at(2) === 9400), "codeAt");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('String'))->u_char_from_code(9400) === "Ⓒ"), "charFromCode");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('String'))->u_char_from_code(65) === "A"), "charFromCode, ascii");
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
$u_rx = \o\v(\o\ModuleManager::getModule('Regex'))->u_new(\o\v("'{0}'")->u_fill("world"), "i");
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
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ 0, 1, 2, 3 ]))->u_slice(1))->u_join("|") === "1|2|3"), "slice");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ 0, 1, 2, 3 ]))->u_slice((- 2)))->u_join("|") === "2|3"), "slice -2");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ 0, 1, 2, 3 ]))->u_slice(1, 2))->u_join("|") === "1|2"), "slice w length");
\o\v($u_t)->u_ok((\o\v(\o\OList::create([ "aa", "bb", "'cc'" ]))[1] === "bb"), "quoted list");
\o\v($u_t)->u_ok((\o\v(\o\OList::create([ "aa", "bb", "'cc'" ]))[2] === "'cc'"), "quoted list + quotes");
$u_ml = \o\OList::create([ "aa", "bb", "'cc'" ]);
\o\v($u_t)->u_ok((\o\v($u_ml)[1] === "bb"), "multiline quoted list");
\o\v($u_t)->u_ok((\o\v($u_ml)[2] === "'cc'"), "multiline quoted list + quotes");
\o\v($u_t)->u_section("Lists - Sorting");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ "a", "b", "c" ]))->u_sort())->u_join("|") === "a|b|c"), "sort");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ "1", "2", "10" ]))->u_sort())->u_join("|") === "1|2|10"), "sort numeric strings");
$u_list = \o\v(\o\OList::create([ "a", "b", "c" ]))->u_sort(function  ($u_a, $u_b)  {
  return \o\Runtime::spaceship($u_b, $u_a);
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
\o\v($u_t)->u_section("Lists - Misc");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ "a", "a", "b", "c", "d", "d" ]))->u_unique())->u_join(":") === "a:b:c:d"), "unique");
$u_default_list = \o\v(\o\OList::create([ "a", "b" ]))->u_default("Z");
\o\v($u_t)->u_ok((\o\v($u_default_list)[0] === "a"), "default, normal");
\o\v($u_t)->u_ok((\o\v($u_default_list)[99] === "Z"), "default, missing");
\o\v($u_t)->u_section("Lists - Functional");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ 1, 2, 3 ]))->u_map(function  ($u_a)  {
  return (\o\vn($u_a, 0) * \o\vn(2, 0));
 return new \o\ONothing(__METHOD__);
 
}
))->u_join(":") === "2:4:6"), "map");
\o\v($u_t)->u_ok((\o\v(\o\OList::create([ 1, 2, 3 ]))->u_reduce(function  ($u_a, $u_i)  {
  return (\o\vn($u_i, 1) + \o\vn($u_a, 1));
 return new \o\ONothing(__METHOD__);
 
}
, 3) === 9), "reduce");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ 1, 2, 3, 4 ]))->u_filter(function  ($u_a)  {
  return (\o\vn($u_a, 0) % \o\vn(2, 0));
 return new \o\ONothing(__METHOD__);
 
}
))->u_join(":") === "1:3"), "filter");
$u_mdl = \o\OList::create([ 1, 2, \o\OList::create([  ]), \o\OList::create([ 3, 4 ]), \o\OList::create([ \o\OList::create([ 5, 6 ]), \o\OList::create([ 7, 8 ]) ]) ]);
\o\v($u_t)->u_ok((\o\v(\o\v($u_mdl)->u_flat(99))->u_join("") === "12345678"), "flat");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\OList::create([ 1, \o\OList::create([ 2, \o\OList::create([ 3 ]) ]) ]))->u_flat())->u_length() === 3), "flat(1)");
$u_table = \o\OList::create([ \o\OMap::create([ 'a' => 20 ]), \o\OMap::create([ 'a' => 70 ]), \o\OMap::create([ 'a' => (- 30) ]) ]);
$u_table_vals = \o\v(\o\v(\o\v($u_table)->u_sort_table("a"))->u_map(function  ($u_a)  {
  return \o\v($u_a)->u_a;
 return new \o\ONothing(__METHOD__);
 
}
))->u_join(",");
\o\v($u_t)->u_ok(($u_table_vals === "-30,20,70"), "tableSort by map");
$u_table = \o\OList::create([ \o\OList::create([ 1, 50 ]), \o\OList::create([ 2, (- 30) ]), \o\OList::create([ 3, 10 ]) ]);
$u_table_vals = \o\v(\o\v(\o\v($u_table)->u_sort_table(1))->u_map(function  ($u_a)  {
  return \o\v($u_a)[1];
 return new \o\ONothing(__METHOD__);
 
}
))->u_join(",");
\o\v($u_t)->u_ok(($u_table_vals === "-30,10,50"), "tableSort by index");
$u_table_vals = \o\v(\o\v(\o\v($u_table)->u_sort_table(1, true))->u_map(function  ($u_a)  {
  return \o\v($u_a)[1];
 return new \o\ONothing(__METHOD__);
 
}
))->u_join(",");
\o\v($u_t)->u_ok(($u_table_vals === "50,10,-30"), "tableSort by index (DESC)");
 return new \o\ONothing(__METHOD__);
 
}
function u_test_templates ($u_t)  {
  \o\v($u_t)->u_section("Templates");
$u_html_users = \o\v(u_template_html(\o\OList::create([ "Frodo", "Sam", "Gandalf" ])))->u_unlocked();
\o\v($u_t)->u_ok(\o\v($u_html_users)->u_match(new \o\ORegex ("<li>Frodo.*?<li>Sam.*?<li>Gandalf")), "template - loop & variables");
$u_html_users = u_template_html(\o\OList::create([ "Frodo", "<b>Sam</b>", "Gandalf" ]));
\o\v($u_t)->u_ok(\o\v(\o\v($u_html_users)->u_unlocked())->u_contains("&lt;b&gt;Sam"), "template with html escapes");
$u_p = \o\v(\o\ModuleManager::getModule('Web'))->u_parse_html(new \o\OLockString ("<h1>> Hello\n<.abc>> 123"));
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
function u_test_bitwise ($u_t)  {
  \o\v($u_t)->u_section("Bitwise Operators");
\o\v($u_t)->u_ok(((1 | 2) === 3), "bitwise OR (+|)");
\o\v($u_t)->u_ok(((2 & 3) === 2), "bitwise AND (+&)");
\o\v($u_t)->u_ok(((1 ^ 2) === 3), "bitwise XOR (+^)");
\o\v($u_t)->u_ok(((~ 5) === (- 6)), "bitwise NOT (+~)");
\o\v($u_t)->u_ok(((3 << 2) === 12), "bitwise shift LEFT (+<)");
\o\v($u_t)->u_ok(((13 >> 2) === 3), "bitwise shift RIGHT (+>)");
\o\v($u_t)->u_ok(((0b100 | 0b010) === 0b110), "OR (+|) with binary number");
\o\v($u_t)->u_ok(((0b100 & 0b110) === 0b100), "AND (+&) with binary number");
\o\v($u_t)->u_ok(((0b100 ^ 0b110) === 0b010), "XOR (+^) with binary number");
\o\v($u_t)->u_ok(((~ 0b110) === (- 7)), "NOT (+~) with binary number");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_file ($u_t)  {
  \o\v($u_t)->u_section("Module: File");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('File'))->u_exists("../bad.txt");
 return new \o\ONothing(__METHOD__);
 
}
, "parent shortcut (..)");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('File'))->u_read("http://yahoo.com");
 return new \o\ONothing(__METHOD__);
 
}
, "stop remote file read");
\o\v($u_t)->u_ok((! \o\v(\o\ModuleManager::getModule('File'))->u_exists("sdf/sdf")), "Missing file does not exist");
\o\v($u_t)->u_ok((! \o\v(\o\ModuleManager::getModule('File'))->u_is_file("sdf/sdf")), "Missing path is not a file");
\o\v($u_t)->u_ok((! \o\v(\o\ModuleManager::getModule('File'))->u_is_dir("sdf/sdf")), "Missing path is not a dir");
$u_f = "testFile.txt";
$u_d = "testDir";
if (\o\v(\o\ModuleManager::getModule('File'))->u_exists($u_d)) {
\o\v(\o\ModuleManager::getModule('File'))->u_delete_dir($u_d);

}

\o\v(\o\ModuleManager::getModule('File'))->u_make_dir($u_d);
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('File'))->u_is_dir($u_d), "make dir");
$u_p = \o\v(\o\ModuleManager::getModule('File'))->u_join_path($u_d, $u_f);
\o\v(\o\ModuleManager::getModule('File'))->u_write($u_p, "12345");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('File'))->u_get_size($u_p) === 5), "File size");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('File'))->u_exists($u_p), "File exists");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('File'))->u_is_file($u_p), "File is file");
$u_info = \o\v(\o\ModuleManager::getModule('File'))->u_parse_path($u_p);
\o\v($u_t)->u_ok((\o\v(\o\v($u_info)->u_dir_list)->u_last() === $u_d), "Path info dirList has parent dir");
\o\v($u_t)->u_ok((\o\v($u_info)->u_file_ext === "txt"), "Path info extension");
\o\v($u_t)->u_ok((\o\v($u_info)->u_file_name === "testFile.txt"), "Path info fileName");
\o\v($u_t)->u_ok((\o\v($u_info)->u_file_name_short === "testFile"), "Path info shortFileName");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\ModuleManager::getModule('File'))->u_parse_path("././profile.png"))->u_dir_list)->u_length() === 0), "remove . from path");
\o\v(\o\ModuleManager::getModule('File'))->u_delete($u_p);
\o\v($u_t)->u_ok((! \o\v(\o\ModuleManager::getModule('File'))->u_exists($u_p)), "File deleted");
\o\v(\o\ModuleManager::getModule('File'))->u_delete_dir($u_d);
\o\v($u_t)->u_ok((! \o\v(\o\ModuleManager::getModule('File'))->u_exists($u_d)), "Dir deleted");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_date ($u_t)  {
  \o\v($u_t)->u_section("Module: Date");
\o\v($u_t)->u_ok((\o\vn(\o\v(\o\ModuleManager::getModule('Date'))->u_now(), 0) > \o\vn(1490000000, 0)), "Date.now");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Date'))->u_minutes(3) === 180), "minutes");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Date'))->u_hours(2) === 7200), "hours");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Date'))->u_days(3) === 259200), "days");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Date'))->u_to_minutes(90) === 1.5), "inMinutes");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Date'))->u_to_hours(7200) === 2), "inHours");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Date'))->u_to_days(259200) === 3), "inDays");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Date'))->u_format("%Y-%m-%d %H:%M:%S", 1400000000) === "2014-05-13 09:53:20"), "Date.format");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Date'))->u_difference(100, 280) === "3 minutes"), "Date.difference");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_db ($u_t)  {
  \o\v($u_t)->u_section("Module: Db");
\o\v(\o\ModuleManager::getModule('Db'))->u_query(new \o\OLockString ("CREATE TABLE IF NOT EXISTS test (key, value);"));
\o\v(\o\ModuleManager::getModule('Db'))->u_query(new \o\OLockString ("delete from test"));
$u_key = \o\Runtime::concat("test", \o\v(\o\ModuleManager::getModule('Math'))->u_random(0, 1000));
\o\v(\o\ModuleManager::getModule('Db'))->u_insert_row("test", \o\OMap::create([ 'key' => $u_key, 'value' => \o\v(\o\ModuleManager::getModule('Date'))->u_now() ]));
$u_rows = \o\v(\o\ModuleManager::getModule('Db'))->u_select_rows(new \o\OLockString ("select * from test"));
\o\v($u_t)->u_ok((\o\v($u_rows)->u_length() === 1), "Insert & select row");
\o\v($u_t)->u_ok((\o\v(\o\v($u_rows)[0])->u_key === $u_key), "Check inserted row");
$u_dbh = \o\v(\o\ModuleManager::getModule('Db'))->u_use("default");
$u_rows = \o\v($u_dbh)->u_select_rows(new \o\OLockString ("select * from test"));
\o\v($u_t)->u_ok((\o\v(\o\v($u_rows)[0])->u_key === $u_key), "Db.use");
\o\v(\o\ModuleManager::getModule('Db'))->u_update_rows("test", \o\OMap::create([ 'key' => $u_key, 'value' => "new!" ]), \o\v(new \o\OLockString (" key = {}"))->u_fill($u_key));
$u_row = \o\v(\o\ModuleManager::getModule('Db'))->u_select_row(\o\v(new \o\OLockString ("select * from test where key = {}"))->u_fill($u_key));
\o\v($u_t)->u_ok((\o\v($u_row)->u_value === "new!"), "Update row");
\o\v(\o\ModuleManager::getModule('Db'))->u_delete_rows("test", \o\v(new \o\OLockString ("key = {}"))->u_fill($u_key));
$u_rows = \o\v(\o\ModuleManager::getModule('Db'))->u_select_rows(new \o\OLockString ("select * from test"));
\o\v($u_t)->u_ok((\o\v($u_rows)->u_length() === 0), "Delete row");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Db'))->u_update_rows("\"bad", \o\OMap::create([ 'key' => $u_key ]), \o\v(new \o\OLockString (" key = {}"))->u_fill($u_key));
 return new \o\ONothing(__METHOD__);
 
}
, "invalid table name - updateRows");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Db'))->u_delete_rows("\"bad", \o\v(new \o\OLockString (" key = {}"))->u_fill($u_key));
 return new \o\ONothing(__METHOD__);
 
}
, "invalid table name - deleteRows");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Db'))->u_query("delete from test");
 return new \o\ONothing(__METHOD__);
 
}
, "reject unlocked query - query");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Db'))->u_select_rows("select * from test");
 return new \o\ONothing(__METHOD__);
 
}
, "reject unlocked query - selectRows");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_jcon_test ($u_t)  {
  \o\v($u_t)->u_section("Module: Jcon");
$u_d = \o\v(\o\ModuleManager::getModule('Jcon'))->u_parse("{\nkey: value\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key === "value"), "string value");
$u_d = \o\v(\o\ModuleManager::getModule('Jcon'))->u_parse("{\nkey: true\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key === true), "true value");
$u_d = \o\v(\o\ModuleManager::getModule('Jcon'))->u_parse("{\nkeyA: valA\nkeyB: valB\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key_b === "valB"), "2nd key");
$u_d = \o\v(\o\ModuleManager::getModule('Jcon'))->u_parse("{\nkey: false\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key === false), "false value");
$u_d = \o\v(\o\ModuleManager::getModule('Jcon'))->u_parse("{\nkey: 1234.5\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key === 1234.5), "num value");
$u_d = \o\v(\o\ModuleManager::getModule('Jcon'))->u_parse("{\nkey: [\nv1\nv2\nv3\n]\n}\n");
\o\v($u_t)->u_ok((\o\v(\o\v($u_d)->u_key)->u_length() === 3), "list value");
\o\v($u_t)->u_ok((\o\v(\o\v($u_d)->u_key)[2] === "v3"), "list value");
$u_d = \o\v(\o\ModuleManager::getModule('Jcon'))->u_parse("{\nkey: '''\nThis is\nmultiline\n'''\n}\n");
\o\v($u_t)->u_ok(\o\v(\o\v($u_d)->u_key)->u_contains("\nmultiline"), "multiline value");
$u_d = \o\v(\o\ModuleManager::getModule('Jcon'))->u_parse("{\nkeyLite: '''\n## Heading!\n'''\n}\n");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\v($u_d)->u_key_lite)->u_unlocked())->u_contains("<h2>"), "Litemark value");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_js ($u_t)  {
  \o\v($u_t)->u_section("Module: Js");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\v(\o\ModuleManager::getModule('Js'))->u_plugin("colorCode"))->u_unlocked())->u_contains("highlight"), "colorCode");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\v(\o\ModuleManager::getModule('Js'))->u_plugin("lazyLoadImages"))->u_unlocked())->u_contains("img"), "lazyLoadImages");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Js'))->u_minify("/* comment */\n\nlet a = '//';\n   // line  \n") === "let a='//';"), "minify");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_json ($u_t)  {
  \o\v($u_t)->u_section("Module: Json");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\ModuleManager::getModule('Json'))->u_decode("{\"k1\":[123,\"hello\"]}"))["k1"])[1] === "hello"), "decode sub-list");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\ModuleManager::getModule('Json'))->u_decode("{\"k1\":{\"k2\":\"hello\"}}"))["k1"])["k2"] === "hello"), "decode sub-map");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\ModuleManager::getModule('Json'))->u_decode("[1,2,3]"))[1] === 2), "decode list");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Json'))->u_decode("true") === true), "decode boolean");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Json'))->u_decode("123.45") === 123.45), "decode number");
$u_st = \o\v(\o\ModuleManager::getModule('Json'))->u_encode(\o\OMap::create([ 'a' => "hi", 'b' => \o\OList::create([ 1, 2, 3 ]) ]));
\o\v($u_t)->u_ok(\o\v($u_st)->u_contains("\"hi\""), "encode string");
\o\v($u_t)->u_ok(\o\v($u_st)->u_contains("[1,2,3]"), "encode list");
\o\v($u_t)->u_ok(\o\v($u_st)->u_contains("\"b\":"), "encode key");
$u_obj = \o\v(\o\ModuleManager::getModule('Json'))->u_decode($u_st);
\o\v($u_t)->u_ok((\o\v(\o\v($u_obj)->u_b)[1] === 2), "decode after encode");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_litemark ($u_t)  {
  \o\v($u_t)->u_section("Module: Litemark");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_math ($u_t)  {
  \o\v($u_t)->u_section("Module: Math");
$u_rand = \o\v(\o\ModuleManager::getModule('Math'))->u_random(6, 8);
\o\v($u_t)->u_ok(((\o\vn($u_rand, 0) >= \o\vn(6, 0)) && (\o\vn($u_rand, 0) <= \o\vn(8, 0))), "random");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Math'))->u_floor($u_rand) === $u_rand), "random is int");
$u_rnd = \o\v(\o\ModuleManager::getModule('Math'))->u_random();
\o\v($u_t)->u_ok(((\o\vn($u_rnd, 0) >= \o\vn(0, 0)) && (\o\vn($u_rnd, 0) < \o\vn(1, 0))), "random float");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Math'))->u_round(\o\v(\o\ModuleManager::getModule('Math'))->u_pi(), 2) === 3.14), "rounded pi");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Math'))->u_round(2.6) === 3), "round up to int");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Math'))->u_round(2.4) === 2), "round down to int");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Math'))->u_clamp(5, 1, 10) === 5), "clamp in boundary");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Math'))->u_clamp(20, 1, 10) === 10), "clamp max");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Math'))->u_clamp((- 20), 1, 10) === 1), "clamp min");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Math'))->u_min(1, 3, 5) === 1), "min");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Math'))->u_min(\o\OList::create([ 1, 3, 5 ])) === 1), "min list");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Math'))->u_max(1, 3, 5) === 5), "max");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Math'))->u_max(\o\OList::create([ 1, 3, 5 ])) === 5), "max list");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Math'))->u_convert_base(21, 10, 2) === "10101"), "convertBase: dec to bin");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Math'))->u_convert_base("1af9", 16, 10) === 6905), "convertBase: hex to dec");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_meta ($u_t)  {
  \o\v($u_t)->u_section("Module: Meta");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('Meta'))->u_function_exists("libMeta"), "functionExists");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Meta'))->u_call_function("metaCallMe", \o\OList::create([ "a", "b" ])) === "a|b"), "callFunction & arguments");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('Meta'))->u_function_exists("dynamicFunction"), "dynamic function exists");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Meta'))->u_call_function("dynamicFunction", \o\OList::create([ "Hey" ])) === "Hey!!!"), "call dynamic function");
 return new \o\ONothing(__METHOD__);
 
}
function u_meta_call_me ()  {
  $u_args = \o\v(\o\ModuleManager::getModule('Meta'))->u_arguments();
return \o\v($u_args)->u_join("|");
 return new \o\ONothing(__METHOD__);
 
}
function u_fail_template_mode ()  {
  \o\v(\o\ModuleManager::getModule('Meta'))->u_no_template_mode();
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
\o\v(\o\ModuleManager::getModule('Perf'))->u_force_active(true);
\o\v(\o\ModuleManager::getModule('Perf'))->u_start("testPerf");
\o\v(\o\ModuleManager::getModule('System'))->u_sleep(1);
\o\v(\o\ModuleManager::getModule('Perf'))->u_stop(true);
$u_res = \o\v(\o\ModuleManager::getModule('Perf'))->u_results(true);
$u_found = false;
foreach (\o\uv($u_res) as $u_r) {
if ((\o\v($u_r)->u_task === "testPerf")) {
$u_found = true;
break;

}


}
\o\v($u_t)->u_ok($u_found, "Perf task & results");
\o\v(\o\ModuleManager::getModule('Perf'))->u_force_active(false);
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_php ($u_t)  {
  \o\v($u_t)->u_section("Module: Php");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\ModuleManager::getModule('Php'))->u_version())->u_match(new \o\ORegex ("\d+\.\d+\.\d+")), "PHP version");
$u_fl = \o\v(\o\ModuleManager::getModule('Php'))->u_options(\o\OList::create([ "PATHINFO_FILENAME", "PATHINFO_BASENAME" ]));
\o\v($u_t)->u_ok(($u_fl === 10), "PHP - constant flags");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Php'))->u_call("strrev", "abcdef") === "fedcba"), "call");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Php'))->u_call("nonexistent", 1, 2);
 return new \o\ONothing(__METHOD__);
 
}
, "Non-existent PHP call");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Php'))->u_call("eval", "print(\"hi\");");
 return new \o\ONothing(__METHOD__);
 
}
, "stop blacklisted function - by name");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Php'))->u_call("ini_set", "x", "y");
 return new \o\ONothing(__METHOD__);
 
}
, "stop blacklisted function - by match");
\o\v(\o\ModuleManager::getModule('Php'))->u_require("vendor/testVendorClass.php");
$u_vc = \o\v(\o\ModuleManager::getModule('Php'))->u_new("Abc/VendorClass");
\o\v($u_t)->u_ok((\o\v($u_vc)->u_take_array(\o\OList::create([ 1, 2, 3 ])) === 1), "Vendor class - take array");
\o\v($u_t)->u_ok((\o\v(\o\v($u_vc)->u_return_array(\o\OList::create([ 1, 2, 3 ])))[0] === "a"), "Vendor class - return array");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v($u_vc)->u_return_records())->u_remove(0))["color"] === "Red"), "Vendor class - recursive arrays");
\o\v($u_t)->u_ok((\o\v(\o\v($u_vc)->u_return_object())->u_call_me() === "abc"), "Vendor subClass");
\o\v($u_t)->u_ok(\o\v($u_vc)->u_z_set("ALL_CAP_FIELD", 789), "Vendor class - ALL_CAP_FIELD");
\o\v($u_t)->u_ok((\o\v($u_vc)->u_z_get("ALL_CAP_FIELD") === 789), "Vendor class - ALL_CAP_FIELD");
\o\v($u_t)->u_ok((\o\v($u_vc)->u_z_call("ALL_CAP_METHOD") === "FOO"), "Vendor class - ALL_CAP_METHOD");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_test ($u_t)  {
  \o\v($u_t)->u_section("Module: Test");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_global ($u_t)  {
  \o\v($u_t)->u_section("Module: Global");
u_set_globals();
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Global'))->u_hello === "world"), "global set");
 return new \o\ONothing(__METHOD__);
 
}
function u_set_globals ()  {
  \o\v(\o\ModuleManager::getModule('Global'))->u_hello = "world";
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_web ($u_t)  {
  \o\v($u_t)->u_section("Module: Web");
\o\OBare::u_print(\o\v(\o\ModuleManager::getModule('Web'))->u_parse_query("foo=1&bar=2&bar=3", \o\OList::create([ "bar" ])));
\o\OBare::u_print(\o\v(\o\ModuleManager::getModule('Web'))->u_stringify_query(\o\OMap::create([ 'foo' => 2, 'bar' => 3, 'baz' => \o\OList::create([ 4, 5 ]) ])));
return new \o\ONothing(__METHOD__);
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Web'))->u_redirect("http://google.com");
 return new \o\ONothing(__METHOD__);
 
}
, "redirect - normal");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Web'))->u_redirect("mailto:google.com");
 return new \o\ONothing(__METHOD__);
 
}
, "redirect - mailto");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Web'))->u_redirect("//google.com");
 return new \o\ONothing(__METHOD__);
 
}
, "redirect - no protocol");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Web'))->u_redirect("bob@ftp://google.com");
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
  return \o\v(\o\v(\o\ModuleManager::getModule('Web'))->u_temp_validate_input($u_v, $u_type))["value"];
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_settings ($u_t)  {
  \o\v($u_t)->u_section("Module: Settings");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Global'))->u_setting("num") === (- 123.45)), "get num");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Global'))->u_setting("flagFalse") === false), "get flag");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Global'))->u_setting("flagTrue") === true), "get flag");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Global'))->u_setting("string") === "value with spaces, etc."), "get string");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\ModuleManager::getModule('Global'))->u_setting("map"))->u_key === "value"), "get map");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\ModuleManager::getModule('Global'))->u_setting("list"))[1] === "value 1"), "get list");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Global'))->u_setting("MISSING");
 return new \o\ONothing(__METHOD__);
 
}
, "missing key");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_map_db ($u_t)  {
  \o\v($u_t)->u_section("Module: MapDb");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('MapDb'))->u_delete_bucket("test"), "delete bucket");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('MapDb'))->u_insert_map("test", "hello", \o\OMap::create([ 'hello' => "World!" ])), "insert");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('MapDb'))->u_insert_map("test", "hello", \o\OMap::create([ 'hello' => "There!" ])), "insert");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\ModuleManager::getModule('MapDb'))->u_select_map("test", 1))->u_hello === "World!"), "selectMap");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\ModuleManager::getModule('MapDb'))->u_select_maps("test", "hello"))->u_length() === 2), "selectMaps");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\ModuleManager::getModule('MapDb'))->u_buckets())[0])->u_num_maps === 2), "buckets()");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_session ($u_t)  {
  \o\v($u_t)->u_section("Module: Session");
\o\v(\o\ModuleManager::getModule('Session'))->u_delete_all();
\o\v(\o\ModuleManager::getModule('Session'))->u_set("key1", "value");
\o\v(\o\ModuleManager::getModule('Session'))->u_set("key2", \o\OMap::create([ 'a' => "b" ]));
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Session'))->u_get("key1") === "value"), "set/get");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\ModuleManager::getModule('Session'))->u_get("key2"))->u_a === "b"), "get map");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\ModuleManager::getModule('Session'))->u_get_all())->u_keys())->u_join("|") === "key1|key2"), "getAll");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Session'))->u_get("missing", "") === ""), "get with blank default");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Session'))->u_get("missing", "default") === "default"), "get with default");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('Session'))->u_has_key("key1"), "hasKey true");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Session'))->u_delete("key1") === "value"), "delete");
\o\v($u_t)->u_ok((! \o\v(\o\ModuleManager::getModule('Session'))->u_has_key("key1")), "hasKey false");
\o\v(\o\ModuleManager::getModule('Session'))->u_delete_all();
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\ModuleManager::getModule('Session'))->u_get_all())->u_keys())->u_length() === 0), "deleteAll");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Session'))->u_add_counter("num") === 1), "counter 1");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Session'))->u_add_counter("num") === 2), "counter 2");
\o\v(\o\ModuleManager::getModule('Session'))->u_set_flash("fkey", "fvalue");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Session'))->u_get_flash("fkey") === "fvalue"), "flash set/get");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('Session'))->u_has_flash("fkey"), "hasFlash - true");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('Session'))->u_has_flash("missing"), "hasFlash - false");
\o\v(\o\ModuleManager::getModule('Session'))->u_add_to_list("list", 123);
\o\v($u_t)->u_ok((\o\v(\o\v(\o\ModuleManager::getModule('Session'))->u_get("list"))[0] === 123), "addToList 1");
\o\v(\o\ModuleManager::getModule('Session'))->u_add_to_list("list", 456);
\o\v($u_t)->u_ok((\o\v(\o\v(\o\ModuleManager::getModule('Session'))->u_get("list"))[1] === 456), "addToList 2");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Session'))->u_get("missing");
 return new \o\ONothing(__METHOD__);
 
}
, "get bad key");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_cache ($u_t)  {
  \o\v($u_t)->u_section("Module: Cache");
\o\v(\o\ModuleManager::getModule('Cache'))->u_set("test", 123, 1);
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('Cache'))->u_has("test"), "has");
\o\v($u_t)->u_ok((! \o\v(\o\ModuleManager::getModule('Cache'))->u_has("not")), "has not");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Cache'))->u_get("test") === 123), "get");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Cache'))->u_get("not", "missing") === "missing"), "get default");
\o\v(\o\ModuleManager::getModule('Cache'))->u_set("data", \o\OMap::create([ 'a' => \o\OList::create([ "x", "y", "z" ]) ]), 3);
\o\v($u_t)->u_ok((\o\v(\o\v(\o\v(\o\ModuleManager::getModule('Cache'))->u_get("data"))->u_a)->u_join("|") === "x|y|z"), "get map + list");
\o\v(\o\ModuleManager::getModule('Cache'))->u_delete("data");
\o\v($u_t)->u_ok((! \o\v(\o\ModuleManager::getModule('Cache'))->u_has("data")), "delete");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Cache'))->u_counter("count") === 1), "counter 1");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Cache'))->u_counter("count") === 2), "counter 2");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Cache'))->u_counter("count", 2) === 4), "counter +2");
\o\v($u_t)->u_ok((\o\v(\o\ModuleManager::getModule('Cache'))->u_counter("count", (- 1)) === 3), "counter -1");
\o\v(\o\ModuleManager::getModule('Cache'))->u_delete("count");
\o\v(\o\ModuleManager::getModule('Cache'))->u_set("short", "a", 0.1);
\o\v(\o\ModuleManager::getModule('Cache'))->u_set("longer", "a", 0.5);
\o\v(\o\ModuleManager::getModule('Cache'))->u_set("forever", "a", 0);
\o\v(\o\ModuleManager::getModule('System'))->u_sleep(200);
\o\v($u_t)->u_ok((! \o\v(\o\ModuleManager::getModule('Cache'))->u_has("short")), "100ms expiry");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('Cache'))->u_has("longer"), "500ms expiry");
\o\v($u_t)->u_ok(\o\v(\o\ModuleManager::getModule('Cache'))->u_has("forever"), "no expiry");
\o\v(\o\ModuleManager::getModule('Cache'))->u_delete("short");
\o\v(\o\ModuleManager::getModule('Cache'))->u_delete("longer");
\o\v(\o\ModuleManager::getModule('Cache'))->u_delete("forever");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_net ($u_t)  {
  \o\v($u_t)->u_section("Module: Net");
$u_content = \o\v(\o\ModuleManager::getModule('Net'))->u_http_get(new \o\OLockString ("https://tht.help"));
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
$t->addDynamic(\o\v(\o\ModuleManager::getModule('Web'))->u_nonce());
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
function u_spread (...$u_args)  {
  return \o\v($u_args)->u_join(":");
 return new \o\ONothing(__METHOD__);
 
}



<<<<<<< HEAD:tht/testsite/data/cache/php/pages_home.tht.php
/* SOURCE={"file":"pages\/home.tht","6":4,"7":6,"8":7,"9":9,"13":13,"15":19,"16":19,"17":27,"18":27,"19":33,"23":37,"24":39,"25":40,"26":41,"27":42,"28":43,"29":44,"30":45,"31":46,"32":47,"33":49,"34":50,"35":52,"36":53,"37":54,"38":55,"39":56,"40":57,"41":58,"42":59,"43":61,"44":62,"45":63,"46":64,"47":65,"48":67,"49":68,"50":69,"54":73,"55":75,"56":77,"57":77,"61":77,"62":78,"63":78,"67":78,"68":80,"69":81,"73":82,"74":84,"75":85,"79":86,"80":88,"81":89,"82":89,"88":92,"89":94,"90":94,"94":94,"98":98,"99":100,"100":102,"107":113,"108":114,"109":115,"110":116,"111":117,"112":120,"113":122,"114":123,"115":124,"116":125,"117":126,"118":127,"119":128,"120":129,"121":130,"122":133,"123":135,"124":136,"125":137,"126":138,"127":141,"128":143,"129":144,"130":145,"131":146,"132":147,"133":148,"134":149,"135":150,"136":151,"137":152,"138":153,"139":154,"140":155,"141":156,"142":157,"143":158,"144":159,"145":160,"146":161,"147":162,"148":163,"149":164,"150":165,"151":166,"152":167,"153":168,"154":169,"155":170,"156":171,"157":172,"158":173,"159":184,"160":186,"161":187,"162":188,"163":189,"164":190,"165":191,"166":192,"167":193,"168":194,"169":197,"170":199,"171":200,"172":201,"173":202,"174":203,"175":204,"176":208,"177":210,"178":211,"179":212,"180":213,"181":214,"182":215,"183":216,"184":217,"185":218,"186":219,"187":220,"188":221,"189":222,"190":223,"191":224,"192":225,"193":226,"194":227,"195":228,"196":229,"197":230,"198":231,"199":232,"200":233,"201":234,"202":235,"203":236,"204":237,"205":238,"206":239,"207":240,"208":241,"209":242,"210":243,"211":244,"212":246,"213":247,"214":248,"215":249,"216":250,"217":253,"218":255,"219":256,"220":257,"221":258,"222":259,"223":260,"224":262,"225":263,"226":264,"227":265,"228":266,"229":269,"230":271,"231":272,"232":273,"233":274,"234":275,"235":276,"236":277,"237":278,"238":281,"239":283,"240":284,"241":285,"242":286,"243":287,"244":288,"245":289,"246":290,"247":291,"248":292,"249":293,"250":294,"251":295,"252":296,"253":297,"254":298,"255":299,"256":300,"257":302,"261":310,"262":312,"263":315,"264":316,"265":317,"266":318,"267":319,"268":320,"269":321,"270":322,"273":324,"274":325,"275":326,"276":327,"277":330,"278":336,"279":338,"280":339,"281":340,"282":342,"283":343,"284":344,"285":349,"286":351,"287":353,"288":354,"289":356,"290":357,"291":360,"292":361,"293":365,"294":367,"295":369,"296":370,"297":371,"298":373,"299":374,"300":375,"301":376,"302":378,"303":380,"304":380,"308":380,"309":382,"310":382,"314":382,"315":384,"316":386,"317":387,"318":389,"319":390,"320":391,"321":393,"322":394,"323":396,"324":397,"325":397,"329":397,"330":399,"331":401,"332":402,"333":404,"334":405,"335":407,"336":408,"337":410,"341":414,"342":416,"343":418,"344":419,"345":420,"346":421,"347":422,"348":423,"349":424,"350":425,"351":425,"355":426,"356":430,"357":432,"358":433,"359":434,"360":435,"361":436,"362":437,"363":438,"364":440,"365":441,"366":442,"367":443,"368":444,"369":445,"370":446,"374":451,"375":453,"376":455,"377":456,"381":458,"382":460,"383":461,"387":463,"388":465,"389":466,"393":468,"394":470,"395":471,"396":472,"397":473,"400":475,"404":477,"405":478,"406":483,"407":484,"411":486,"412":487,"413":489,"414":490,"415":492,"416":493,"417":494,"422":496,"423":499,"424":500,"428":502,"429":503,"430":504,"431":506,"432":507,"433":511,"434":512,"438":514,"439":515,"440":516,"441":519,"442":520,"443":521,"448":523,"449":526,"450":526,"453":527,"454":527,"458":527,"459":528,"460":528,"464":528,"465":531,"466":532,"467":534,"468":535,"469":537,"470":538,"471":544,"472":546,"473":547,"474":548,"475":549,"476":550,"477":552,"478":555,"479":555,"483":555,"484":557,"485":557,"489":557,"490":558,"491":558,"495":558,"496":559,"497":559,"501":559,"502":560,"503":560,"507":560,"508":562,"509":563,"510":563,"514":563,"515":565,"516":565,"520":565,"521":566,"522":566,"526":566,"527":567,"528":567,"532":567,"536":575,"537":577,"538":579,"539":580,"540":581,"541":582,"542":583,"543":584,"544":585,"545":586,"546":587,"547":588,"548":589,"549":590,"550":593,"551":595,"552":597,"553":598,"554":603,"555":605,"556":606,"557":606,"561":606,"562":609,"563":611,"564":612,"565":613,"566":614,"567":615,"568":616,"569":619,"570":621,"571":622,"572":623,"573":625,"574":626,"575":627,"576":630,"577":632,"578":634,"579":635,"580":637,"581":638,"582":640,"583":641,"584":644,"585":645,"586":646,"587":647,"588":648,"589":649,"590":650,"591":653,"592":654,"593":654,"597":654,"598":655,"599":658,"600":659,"601":660,"602":661,"603":662,"604":665,"605":667,"606":667,"610":667,"611":668,"612":668,"616":668,"620":673,"621":676,"622":678,"623":679,"624":680,"625":681,"626":682,"627":683,"628":684,"629":685,"630":687,"631":688,"632":689,"633":690,"634":691,"635":692,"636":693,"637":695,"638":696,"639":698,"640":701,"641":703,"642":703,"646":703,"647":704,"648":704,"652":704,"653":705,"654":705,"658":705,"659":706,"660":706,"664":706,"665":707,"666":707,"670":707,"671":708,"672":708,"676":708,"677":709,"678":709,"679":709,"683":709,"684":710,"685":710,"689":710,"690":711,"691":711,"695":711,"696":712,"697":712,"701":712,"702":713,"703":713,"707":713,"708":714,"709":714,"713":714,"714":715,"715":715,"719":715,"720":718,"721":720,"722":721,"723":722,"724":723,"725":724,"726":725,"727":728,"728":730,"729":731,"730":732,"731":733,"732":736,"733":738,"734":739,"735":740,"736":741,"737":742,"738":743,"739":744,"740":745,"741":747,"742":748,"743":749,"744":750,"745":752,"746":753,"747":754,"748":755,"749":758,"750":760,"751":761,"752":762,"753":763,"754":764,"755":765,"756":766,"757":767,"758":768,"759":769,"760":770,"761":773,"762":775,"763":776,"764":777,"765":778,"766":779,"767":781,"768":783,"769":784,"770":785,"771":786,"775":790,"776":793,"777":795,"778":796,"779":797,"782":799,"783":801,"784":802,"785":803,"788":805,"789":807,"790":808,"791":809,"792":810,"795":812,"796":815,"797":816,"798":817,"799":818,"800":819,"801":820,"802":820,"808":822,"809":824,"810":825,"811":826,"812":827,"813":828,"814":829,"818":831,"819":832,"820":833,"826":836,"827":838,"828":839,"829":842,"830":843,"831":844,"832":845,"833":846,"834":847,"835":848,"836":849,"837":850,"838":851,"839":852,"840":853,"841":854,"842":855,"843":856,"844":857,"845":858,"846":859,"847":860,"848":861,"849":862,"850":863,"851":864,"852":865,"853":866,"854":867,"855":868,"856":869,"857":872,"858":874,"859":876,"860":876,"864":877,"865":879,"866":879,"870":880,"871":882,"872":883,"876":886,"880":888,"881":890,"882":891,"885":893,"886":894,"891":896,"892":898,"893":899,"896":901,"897":902,"900":904,"901":905,"905":908,"911":910,"912":914,"913":916,"914":917,"915":918,"916":919,"919":921,"920":922,"924":925,"928":928,"929":929,"930":931,"931":932,"932":933,"935":934,"936":935,"940":937,"941":940,"942":942,"943":943,"947":947,"948":949,"949":951,"950":952,"951":953,"954":958,"955":961,"956":963,"957":964,"958":965,"959":966,"960":967,"961":969,"962":970,"963":971,"964":974,"965":976,"966":977,"967":978,"968":979,"969":980,"970":981,"971":982,"972":983,"973":984,"974":985,"975":988,"976":989,"977":990,"978":991,"979":993,"980":994,"981":997,"982":999,"983":1000,"984":1002,"985":1003,"986":1005,"987":1006,"988":1007,"989":1009,"990":1010,"991":1012,"992":1013,"993":1015,"994":1016,"995":1017,"996":1018,"997":1020,"998":1021,"999":1022,"1000":1024,"1001":1025,"1002":1026,"1003":1028,"1004":1029,"1005":1031,"1006":1032,"1007":1034,"1008":1035,"1009":1036,"1010":1037,"1011":1038,"1012":1040,"1013":1041,"1014":1042,"1015":1043,"1016":1044,"1017":1045,"1018":1047,"1019":1048,"1020":1050,"1021":1051,"1022":1053,"1023":1054,"1024":1056,"1025":1057,"1026":1059,"1027":1060,"1028":1061,"1029":1063,"1030":1064,"1031":1065,"1032":1066,"1033":1068,"1034":1069,"1035":1071,"1036":1072,"1037":1073,"1038":1074,"1039":1075,"1040":1076,"1041":1078,"1042":1080,"1046":1088,"1047":1089,"1048":1090,"1049":1092,"1050":1093,"1051":1095,"1052":1096,"1053":1098,"1054":1100,"1055":1101,"1056":1102,"1057":1103,"1058":1105,"1059":1106,"1060":1107,"1061":1109,"1062":1110,"1063":1111,"1064":1113,"1065":1114,"1066":1115,"1067":1122,"1068":1123,"1069":1125,"1070":1126,"1071":1127,"1072":1128,"1073":1131,"1074":1133,"1075":1134,"1076":1135,"1077":1137,"1078":1138,"1079":1139,"1080":1141,"1081":1142,"1082":1143,"1083":1144,"1084":1146,"1085":1147,"1086":1148,"1087":1149,"1088":1151,"1089":1152,"1090":1153,"1091":1154,"1092":1156,"1093":1157,"1094":1158,"1095":1159,"1096":1161,"1097":1162,"1098":1163,"1099":1164,"1100":1165,"1101":1167,"1102":1168,"1103":1169,"1104":1170,"1105":1172,"1106":1173,"1107":1174,"1108":1175,"1109":1176,"1110":1178,"1111":1179,"1112":1180,"1113":1181,"1114":1182,"1115":1184,"1116":1185,"1117":1186,"1118":1187,"1119":1188,"1120":1189,"1121":1190,"1122":1192,"1123":1193,"1124":1194,"1125":1195,"1126":1198,"1127":1200,"1128":1201,"1129":1202,"1130":1203,"1131":1204,"1132":1207,"1133":1208,"1134":1209,"1135":1212,"1136":1214,"1137":1215,"1138":1216,"1139":1216,"1143":1216,"1144":1218,"1145":1219,"1146":1221,"1147":1222,"1148":1224,"1149":1225,"1150":1227,"1151":1228,"1152":1230,"1153":1232,"1154":1233,"1155":1236,"1156":1238,"1157":1239,"1158":1241,"1159":1241,"1163":1241,"1164":1242,"1165":1242,"1169":1242,"1170":1244,"1171":1245,"1172":1246,"1173":1247,"1174":1248,"1175":1250,"1176":1251,"1180":1255,"1181":1257,"1182":1259,"1183":1260,"1184":1261,"1185":1262,"1186":1263,"1187":1264,"1188":1265,"1189":1266,"1190":1267,"1191":1268,"1192":1269,"1193":1271,"1194":1272,"1195":1277,"1196":1280,"1197":1285,"1198":1286,"1199":1287,"1200":1288,"1201":1290,"1202":1293,"1203":1295,"1204":1296,"1205":1298,"1206":1299,"1207":1301,"1208":1302,"1209":1303,"1210":1304,"1211":1306,"1212":1307,"1213":1309,"1214":1310,"1215":1311,"1216":1313,"1217":1314,"1218":1316,"1219":1317,"1220":1318,"1221":1320,"1222":1321,"1223":1322,"1224":1326,"1225":1327,"1226":1328,"1227":1332,"1228":1333,"1229":1334,"1230":1338,"1231":1339,"1232":1343,"1233":1346,"1234":1347,"1235":1349,"1236":1349,"1241":1350,"1242":1352,"1243":1353,"1244":1355,"1245":1356,"1246":1358,"1247":1359,"1248":1361,"1249":1362,"1250":1364,"1251":1364,"1255":1364,"1256":1366,"1257":1367,"1258":1370,"1259":1372,"1260":1372,"1264":1372,"1265":1373,"1266":1373,"1270":1373,"1271":1374,"1272":1374,"1276":1374,"1277":1375,"1278":1375,"1282":1375,"1283":1376,"1284":1376,"1288":1376,"1292":1381,"1293":1383,"1294":1385,"1295":1386,"1296":1387,"1297":1388,"1298":1390,"1300":1394,"1301":1395,"1302":1396,"1303":1400,"1304":1402,"1305":1403,"1306":1405,"1307":1406,"1308":1407,"1309":1409,"1310":1410,"1311":1411,"1312":1413,"1313":1414,"1314":1417,"1315":1418,"1316":1419,"1317":1420,"1318":1421,"1319":1422,"1323":1430,"1324":1432,"1325":1434,"1326":1434,"1330":1434,"1331":1435,"1332":1435,"1336":1435,"1337":1437,"1338":1438,"1339":1439,"1340":1441,"1341":1442,"1342":1444,"1343":1445,"1347":1447,"1348":1448,"1349":1450,"1350":1452,"1351":1453,"1352":1454,"1353":1455,"1354":1457,"1355":1458,"1356":1459,"1357":1460,"1358":1461,"1359":1462,"1360":1463,"1361":1465,"1362":1466,"1366":1471,"1367":1473,"1368":1475,"1369":1476,"1370":1477,"1371":1478,"1372":1479,"1373":1480,"1374":1481,"1375":1482,"1376":1483,"1380":1486,"1381":1488,"1382":1490,"1383":1492,"1384":1493,"1385":1495,"1386":1496,"1387":1497,"1388":1499,"1389":1500,"1390":1501,"1391":1503,"1392":1504,"1393":1505,"1394":1507,"1395":1508,"1396":1509,"1397":1511,"1398":1512,"1402":1513,"1403":1515,"1404":1516,"1408":1517,"1409":1519,"1410":1520,"1414":1521,"1415":1523,"1416":1524,"1420":1525,"1424":1528,"1425":1530,"1426":1532,"1427":1533,"1428":1535,"1429":1536,"1430":1538,"1431":1539,"1432":1541,"1433":1542,"1434":1544,"1435":1545,"1436":1547,"1437":1548,"1438":1549,"1439":1551,"1440":1552,"1441":1554,"1442":1555,"1446":1558,"1447":1560,"1448":1562,"1449":1563,"1450":1564,"1454":1567,"1455":1569,"1456":1571,"1457":1572,"1458":1573,"1459":1574,"1460":1575,"1461":1577,"1462":1578,"1463":1579,"1464":1580,"1465":1582,"1466":1583,"1470":1586,"1471":1588,"1475":1592,"1476":1594,"1477":1596,"1478":1597,"1479":1599,"1480":1600,"1481":1602,"1482":1604,"1483":1605,"1484":1606,"1485":1608,"1486":1609,"1487":1611,"1488":1612,"1489":1614,"1490":1615,"1494":1619,"1495":1620,"1496":1622,"1497":1623,"1498":1624,"1499":1625,"1500":1625,"1504":1625,"1505":1627,"1506":1628,"1510":1631,"1511":1632,"1512":1633,"1516":1636,"1517":1637,"1521":1640,"1523":1641,"1525":1641,"1526":1642,"1530":1645,"1531":1646,"1532":1648,"1533":1649,"1534":1650,"1535":1651,"1536":1653,"1537":1654,"1538":1655,"1539":1656,"1540":1657,"1541":1658,"1547":1661,"1548":1663,"1552":1666,"1553":1668,"1554":1670,"1555":1671,"1556":1673,"1557":1674,"1558":1674,"1562":1674,"1563":1675,"1564":1675,"1568":1675,"1569":1676,"1570":1676,"1574":1676,"1578":1686,"1579":1687,"1583":1690,"1584":1691,"1585":1693,"1586":1695,"1590":1698,"1591":1699,"1595":1703,"1596":1705,"1597":1707,"1598":1707,"1602":1707,"1603":1708,"1604":1708,"1608":1708,"1609":1709,"1610":1709,"1614":1709,"1615":1710,"1616":1710,"1620":1710,"1621":1712,"1622":1717,"1623":1718,"1624":1720,"1625":1721,"1626":1722,"1627":1723,"1628":1724,"1629":1726,"1630":1727,"1631":1728,"1632":1729,"1633":1730,"1634":1732,"1635":1733,"1636":1734,"1637":1735,"1638":1744,"1639":1745,"1640":1746,"1641":1748,"1642":1749,"1643":1750,"1644":1752,"1645":1752,"1649":1752,"1653":1756,"1654":1757,"1658":1760,"1659":1762,"1660":1764,"1661":1765,"1662":1766,"1663":1767,"1664":1768,"1665":1769,"1666":1771,"1667":1771,"1671":1771,"1675":1774,"1676":1776,"1677":1778,"1678":1780,"1679":1781,"1680":1784,"1681":1785,"1682":1786,"1686":1791,"1687":1793,"1688":1795,"1689":1797,"1690":1798,"1691":1799,"1692":1800,"1693":1802,"1694":1804,"1695":1805,"1696":1807,"1697":1808,"1698":1809,"1699":1811,"1700":1812,"1701":1814,"1702":1815,"1703":1817,"1704":1818,"1705":1820,"1706":1821,"1707":1823,"1708":1824,"1709":1826,"1710":1827,"1711":1829,"1712":1829,"1716":1829,"1720":1833,"1721":1835,"1722":1837,"1723":1838,"1724":1840,"1725":1841,"1726":1843,"1727":1845,"1728":1846,"1729":1848,"1730":1849,"1731":1851,"1732":1852,"1733":1853,"1734":1854,"1735":1856,"1736":1859,"1737":1860,"1738":1861,"1739":1862,"1740":1863,"1741":1864,"1742":1865,"1743":1867,"1744":1868,"1745":1869,"1749":1872,"1750":1874,"1751":1876,"1752":1877,"1756":1886,"1758":1889,"1759":1889,"1760":1890,"1761":1890,"1762":1891,"1765":1893,"1770":1895,"1772":1896,"1773":1896,"1774":1897,"1778":1899,"1780":1901,"1784":1903,"1786":1907,"1790":1909,"1792":1911,"1798":1913,"1800":1914,"1801":1914,"1802":1917,"1806":1919,"1808":1920,"1809":1920,"1810":1920,"1811":1920,"1812":1921,"1816":1923,"1818":1924,"1820":1924,"1821":1925,"1826":1927,"1828":1929,"1832":1931,"1834":1933,"1838":1935,"1840":1936,"1841":1936,"1842":1937,"1846":1939,"1848":1940,"1849":1940,"1850":1941,"1854":1947,"1855":1948,"1859":1951,"1860":1953,"1863":1955,"1865":1957,"1869":1960,"1871":1962,"1875":1965,"1878":1966,"1879":1967} */
=======
/* SOURCE={"file":"pages\/home.tht","6":3,"7":5,"8":7,"9":8,"13":12,"15":18,"16":18,"17":28,"18":28,"19":34,"23":36,"24":38,"25":39,"26":40,"27":41,"28":42,"29":43,"30":44,"31":45,"32":46,"33":47,"34":48,"35":50,"36":51,"37":53,"38":54,"39":55,"40":56,"41":57,"42":58,"43":59,"44":60,"45":61,"46":62,"47":63,"48":64,"49":65,"50":66,"51":68,"52":69,"53":70,"57":73,"58":75,"59":77,"60":77,"64":77,"65":79,"66":79,"70":79,"71":81,"72":81,"76":81,"77":83,"78":83,"82":83,"83":85,"84":85,"85":85,"91":87,"92":89,"93":89,"97":89,"101":93,"102":96,"103":98,"110":109,"111":110,"112":111,"113":112,"114":113,"115":116,"116":118,"117":119,"118":120,"119":121,"120":122,"121":123,"122":124,"123":125,"124":126,"125":129,"126":131,"127":132,"128":133,"129":134,"130":135,"131":136,"132":137,"133":138,"134":139,"135":140,"136":141,"137":142,"138":143,"139":144,"140":145,"141":146,"142":147,"143":148,"144":151,"145":153,"146":154,"147":155,"148":156,"149":157,"150":158,"151":159,"152":160,"153":161,"154":162,"155":163,"156":164,"157":165,"158":166,"159":167,"160":168,"161":169,"162":170,"163":171,"164":172,"165":173,"166":174,"167":175,"168":176,"169":177,"170":178,"171":179,"172":180,"173":181,"174":182,"175":183,"176":184,"177":185,"178":186,"179":187,"180":188,"181":189,"182":193,"183":195,"184":196,"185":197,"186":198,"187":199,"188":200,"189":201,"190":202,"191":203,"192":206,"193":208,"194":209,"195":210,"196":211,"197":212,"198":213,"199":214,"200":215,"201":216,"202":217,"203":218,"204":219,"205":220,"206":221,"207":223,"208":224,"209":225,"210":226,"211":227,"212":228,"213":231,"214":233,"215":234,"216":235,"217":236,"218":237,"219":238,"220":239,"221":240,"222":241,"223":242,"224":244,"225":245,"226":246,"227":247,"228":248,"229":249,"230":250,"231":251,"232":252,"233":253,"234":254,"235":255,"236":256,"237":257,"238":258,"239":259,"240":260,"241":261,"242":262,"243":263,"244":264,"245":265,"246":266,"247":267,"248":269,"249":270,"250":271,"251":272,"252":274,"253":275,"254":276,"255":277,"256":282,"257":284,"258":285,"259":286,"260":287,"261":288,"262":289,"263":291,"264":292,"265":293,"266":294,"267":295,"268":297,"269":298,"270":299,"271":302,"272":304,"273":305,"274":306,"275":307,"276":308,"277":309,"278":310,"279":311,"280":314,"281":316,"282":317,"283":318,"284":319,"285":320,"286":321,"287":324,"288":325,"289":326,"290":327,"291":328,"292":329,"293":330,"294":331,"295":332,"296":334,"300":342,"301":344,"302":347,"303":348,"304":349,"305":350,"306":351,"307":352,"308":353,"309":354,"312":356,"313":357,"314":358,"315":359,"316":362,"317":368,"318":370,"319":371,"320":372,"321":374,"322":375,"323":376,"324":381,"325":383,"326":385,"327":386,"328":388,"329":389,"330":392,"331":393,"335":398,"336":400,"337":402,"338":404,"339":405,"340":407,"341":408,"342":408,"346":408,"347":410,"348":411,"349":412,"350":413,"351":415,"352":416,"353":417,"354":419,"355":419,"359":419,"360":420,"361":421,"362":422,"363":423,"364":425,"365":426,"366":426,"370":426,"371":428,"372":430,"373":431,"374":433,"375":434,"376":436,"377":438,"378":439,"379":441,"380":441,"384":441,"385":443,"386":444,"387":445,"388":446,"389":447,"390":451,"391":452,"392":454,"393":455,"394":457,"398":461,"399":463,"400":465,"401":466,"402":467,"403":468,"404":469,"405":470,"406":471,"407":472,"408":472,"412":473,"413":477,"414":479,"415":480,"416":481,"417":482,"418":483,"419":484,"420":485,"421":487,"422":488,"423":489,"424":490,"425":491,"426":492,"427":493,"431":498,"432":500,"433":502,"434":503,"438":505,"439":507,"440":508,"444":510,"445":512,"446":513,"450":515,"451":517,"452":518,"453":519,"454":520,"457":522,"461":524,"462":525,"463":530,"464":531,"468":533,"469":534,"470":536,"471":537,"472":539,"473":540,"474":541,"479":543,"480":546,"481":547,"485":549,"486":550,"487":551,"488":553,"489":554,"490":558,"491":559,"495":561,"496":562,"497":563,"498":566,"499":567,"500":568,"505":570,"506":573,"507":573,"510":574,"511":574,"515":574,"516":575,"517":575,"521":575,"522":578,"523":579,"524":581,"525":582,"526":584,"527":585,"528":591,"529":593,"530":594,"531":595,"532":596,"533":597,"534":599,"535":602,"536":602,"540":602,"541":604,"542":604,"546":604,"547":605,"548":605,"552":605,"553":606,"554":606,"558":606,"559":607,"560":607,"564":607,"565":609,"566":610,"567":610,"571":610,"572":612,"573":612,"577":612,"578":613,"579":613,"583":613,"584":614,"585":614,"589":614,"590":616,"591":617,"595":621,"596":623,"597":625,"598":626,"599":627,"600":628,"601":629,"602":630,"603":631,"604":632,"605":633,"606":634,"607":635,"608":636,"609":639,"610":641,"611":643,"612":644,"613":649,"614":651,"615":652,"616":652,"620":652,"621":655,"622":657,"623":658,"624":659,"625":660,"626":661,"627":662,"628":665,"629":667,"630":668,"631":669,"632":671,"633":672,"634":673,"635":676,"636":678,"637":680,"638":681,"639":683,"640":684,"641":686,"642":687,"643":690,"644":691,"645":692,"646":693,"647":694,"648":695,"649":696,"650":699,"651":700,"652":700,"656":700,"657":701,"658":704,"659":705,"660":706,"661":707,"662":708,"663":711,"664":712,"665":713,"666":714,"667":717,"668":719,"669":719,"673":719,"674":720,"675":720,"679":720,"680":722,"681":722,"685":722,"686":723,"687":723,"691":723,"695":727,"696":729,"697":731,"698":732,"699":733,"700":734,"701":735,"702":736,"703":737,"704":738,"705":740,"706":741,"707":742,"708":743,"709":744,"710":745,"711":746,"712":748,"713":749,"714":751,"715":754,"716":756,"717":756,"721":756,"722":757,"723":757,"727":757,"728":758,"729":758,"733":758,"734":759,"735":759,"739":759,"740":760,"741":760,"745":760,"746":761,"747":761,"751":761,"752":762,"753":763,"754":764,"758":764,"759":765,"760":765,"764":765,"765":766,"766":766,"770":766,"771":767,"772":767,"776":767,"777":768,"778":768,"782":768,"783":769,"784":769,"788":769,"789":770,"790":770,"794":770,"795":773,"796":775,"797":776,"798":778,"799":779,"800":783,"801":785,"802":786,"803":787,"804":788,"805":789,"806":790,"807":793,"808":795,"809":796,"810":797,"811":798,"812":801,"813":803,"814":804,"815":805,"816":806,"817":807,"818":808,"819":809,"820":810,"821":812,"822":813,"823":814,"824":815,"825":816,"826":817,"827":819,"828":820,"829":821,"830":822,"831":829,"832":831,"833":832,"834":833,"835":834,"836":835,"837":836,"838":837,"839":838,"840":839,"841":840,"842":841,"843":844,"844":846,"845":847,"846":848,"847":849,"848":850,"849":852,"850":854,"851":855,"852":856,"853":857,"854":860,"855":862,"856":863,"860":868,"861":871,"862":873,"863":874,"864":875,"867":877,"868":879,"869":880,"870":881,"873":883,"874":885,"875":886,"876":887,"877":888,"880":890,"881":893,"882":894,"883":895,"884":896,"885":897,"886":898,"887":898,"893":900,"894":902,"895":903,"896":904,"897":905,"898":906,"899":907,"903":909,"904":910,"905":911,"911":914,"912":916,"913":917,"914":920,"915":921,"916":922,"917":923,"918":924,"919":925,"920":926,"921":927,"922":928,"923":929,"924":930,"925":931,"926":932,"927":933,"928":934,"929":935,"930":936,"931":937,"932":938,"933":939,"934":940,"935":941,"936":942,"937":943,"938":944,"939":945,"940":946,"941":947,"942":950,"943":952,"944":954,"945":954,"949":955,"950":957,"951":957,"955":958,"956":960,"957":961,"961":964,"965":966,"966":968,"967":969,"970":971,"971":972,"976":974,"977":976,"978":977,"981":979,"982":980,"985":982,"986":983,"990":986,"996":988,"997":992,"998":994,"999":995,"1000":996,"1001":997,"1004":999,"1005":1000,"1009":1003,"1013":1006,"1014":1007,"1015":1009,"1016":1010,"1017":1011,"1020":1012,"1021":1013,"1025":1015,"1026":1018,"1027":1020,"1028":1021,"1032":1025,"1033":1027,"1034":1029,"1035":1030,"1036":1032,"1037":1034,"1040":1039,"1041":1041,"1042":1042,"1043":1043,"1044":1045,"1045":1047,"1046":1048,"1047":1049,"1048":1050,"1049":1051,"1050":1053,"1051":1054,"1052":1055,"1053":1058,"1054":1060,"1055":1061,"1056":1062,"1057":1063,"1058":1064,"1059":1065,"1060":1066,"1061":1067,"1062":1068,"1063":1069,"1064":1072,"1065":1073,"1066":1074,"1067":1075,"1068":1077,"1069":1078,"1070":1081,"1071":1083,"1072":1084,"1073":1086,"1074":1087,"1075":1089,"1076":1090,"1077":1091,"1078":1093,"1079":1094,"1080":1096,"1081":1097,"1082":1099,"1083":1100,"1084":1101,"1085":1102,"1086":1104,"1087":1105,"1088":1106,"1089":1108,"1090":1109,"1091":1110,"1092":1112,"1093":1113,"1094":1115,"1095":1116,"1096":1118,"1097":1119,"1098":1120,"1099":1121,"1100":1122,"1101":1124,"1102":1125,"1103":1126,"1104":1127,"1105":1128,"1106":1129,"1107":1131,"1108":1132,"1109":1134,"1110":1135,"1111":1137,"1112":1138,"1113":1140,"1114":1141,"1115":1143,"1116":1144,"1117":1145,"1118":1147,"1119":1148,"1120":1149,"1121":1150,"1122":1152,"1123":1153,"1124":1155,"1125":1156,"1126":1157,"1127":1158,"1128":1159,"1129":1160,"1130":1162,"1131":1164,"1135":1172,"1136":1173,"1137":1174,"1138":1176,"1139":1177,"1140":1179,"1141":1180,"1142":1182,"1143":1184,"1144":1185,"1145":1186,"1146":1187,"1147":1189,"1148":1190,"1149":1191,"1150":1193,"1151":1194,"1152":1195,"1153":1197,"1154":1198,"1155":1199,"1156":1206,"1157":1207,"1158":1209,"1159":1210,"1160":1211,"1161":1212,"1162":1215,"1163":1217,"1164":1218,"1165":1219,"1166":1221,"1167":1222,"1168":1223,"1169":1225,"1170":1226,"1171":1227,"1172":1228,"1173":1230,"1174":1231,"1175":1232,"1176":1233,"1177":1235,"1178":1236,"1179":1237,"1180":1238,"1181":1240,"1182":1241,"1183":1242,"1184":1243,"1185":1245,"1186":1246,"1187":1247,"1188":1248,"1189":1249,"1190":1251,"1191":1252,"1192":1253,"1193":1254,"1194":1256,"1195":1257,"1196":1258,"1197":1259,"1198":1260,"1199":1262,"1200":1263,"1201":1264,"1202":1265,"1203":1266,"1204":1268,"1205":1269,"1206":1270,"1207":1271,"1208":1272,"1209":1273,"1210":1274,"1211":1276,"1212":1277,"1213":1278,"1214":1279,"1215":1282,"1216":1284,"1217":1285,"1218":1286,"1219":1287,"1220":1288,"1221":1291,"1222":1292,"1223":1293,"1224":1296,"1225":1298,"1226":1299,"1227":1300,"1228":1300,"1232":1300,"1233":1302,"1234":1303,"1235":1305,"1236":1306,"1237":1308,"1238":1309,"1239":1311,"1240":1312,"1241":1314,"1242":1316,"1243":1317,"1244":1320,"1245":1322,"1246":1323,"1247":1325,"1248":1325,"1252":1325,"1253":1326,"1254":1326,"1258":1326,"1259":1328,"1260":1329,"1261":1330,"1262":1331,"1263":1332,"1264":1334,"1265":1335,"1269":1339,"1270":1341,"1271":1343,"1272":1344,"1273":1345,"1274":1346,"1275":1347,"1276":1348,"1277":1349,"1278":1350,"1279":1352,"1280":1354,"1281":1355,"1282":1357,"1283":1358,"1284":1363,"1285":1366,"1286":1371,"1287":1372,"1288":1373,"1289":1374,"1290":1376,"1291":1379,"1292":1381,"1293":1382,"1294":1384,"1295":1385,"1296":1387,"1297":1388,"1298":1389,"1299":1390,"1300":1392,"1301":1393,"1302":1395,"1303":1396,"1304":1397,"1305":1399,"1306":1400,"1307":1402,"1308":1403,"1309":1404,"1310":1406,"1311":1407,"1312":1408,"1313":1412,"1314":1413,"1315":1414,"1316":1418,"1317":1419,"1318":1420,"1319":1424,"1320":1425,"1321":1429,"1322":1432,"1323":1433,"1324":1435,"1325":1435,"1330":1436,"1331":1438,"1332":1439,"1333":1441,"1334":1442,"1335":1444,"1336":1445,"1337":1447,"1338":1448,"1339":1450,"1340":1450,"1344":1450,"1345":1452,"1346":1453,"1347":1456,"1348":1458,"1349":1458,"1353":1458,"1354":1459,"1355":1459,"1359":1459,"1360":1460,"1361":1460,"1365":1460,"1366":1461,"1367":1461,"1371":1461,"1372":1462,"1373":1462,"1377":1462,"1378":1465,"1379":1467,"1380":1469,"1381":1470,"1382":1471,"1383":1474,"1384":1476,"1385":1476,"1389":1476,"1390":1477,"1391":1477,"1395":1477,"1396":1478,"1397":1478,"1401":1478,"1402":1480,"1403":1481,"1404":1482,"1405":1484,"1406":1485,"1407":1485,"1411":1485,"1412":1486,"1413":1488,"1414":1489,"1415":1489,"1419":1489,"1420":1490,"1421":1491,"1422":1491,"1426":1491,"1427":1492,"1431":1495,"1432":1497,"1433":1499,"1434":1500,"1435":1501,"1436":1502,"1437":1504,"1438":1505,"1439":1506,"1440":1507,"1441":1511,"1442":1513,"1443":1514,"1444":1516,"1445":1517,"1446":1518,"1447":1520,"1448":1521,"1449":1522,"1450":1524,"1451":1525,"1452":1528,"1453":1529,"1454":1530,"1455":1531,"1456":1532,"1457":1533,"1461":1539,"1462":1541,"1463":1543,"1464":1544,"1465":1545,"1466":1547,"1467":1549,"1468":1550,"1469":1552,"1470":1553,"1471":1554,"1472":1555,"1476":1559,"1477":1561,"1478":1563,"1479":1563,"1483":1563,"1484":1564,"1485":1564,"1489":1564,"1490":1566,"1491":1567,"1492":1568,"1493":1570,"1494":1571,"1495":1573,"1496":1574,"1500":1576,"1501":1577,"1502":1579,"1503":1581,"1504":1582,"1505":1583,"1506":1584,"1507":1586,"1508":1588,"1509":1589,"1510":1590,"1511":1591,"1512":1593,"1513":1596,"1514":1597,"1515":1599,"1516":1600,"1520":1605,"1521":1607,"1522":1609,"1523":1610,"1524":1611,"1525":1612,"1526":1613,"1527":1614,"1528":1615,"1529":1616,"1530":1617,"1534":1620,"1535":1622,"1536":1624,"1537":1625,"1538":1627,"1539":1628,"1540":1630,"1541":1631,"1542":1632,"1543":1634,"1544":1635,"1545":1636,"1546":1638,"1547":1639,"1548":1640,"1549":1642,"1550":1643,"1551":1644,"1552":1646,"1553":1647,"1557":1648,"1558":1650,"1559":1651,"1563":1652,"1564":1654,"1565":1655,"1569":1656,"1570":1658,"1571":1659,"1575":1660,"1579":1663,"1580":1665,"1581":1667,"1582":1668,"1583":1670,"1584":1671,"1585":1673,"1586":1674,"1587":1676,"1588":1677,"1589":1679,"1590":1680,"1591":1682,"1592":1683,"1593":1684,"1594":1686,"1595":1687,"1596":1689,"1597":1690,"1601":1693,"1602":1695,"1603":1697,"1604":1698,"1605":1699,"1609":1702,"1610":1704,"1611":1706,"1612":1707,"1613":1708,"1614":1709,"1615":1710,"1616":1712,"1617":1713,"1618":1714,"1619":1715,"1620":1717,"1621":1718,"1625":1721,"1626":1723,"1630":1727,"1631":1729,"1632":1731,"1633":1732,"1634":1733,"1635":1735,"1636":1736,"1637":1738,"1638":1739,"1639":1740,"1640":1742,"1641":1743,"1642":1744,"1643":1746,"1644":1747,"1645":1749,"1646":1750,"1647":1752,"1648":1753,"1652":1774,"1653":1775,"1654":1777,"1655":1778,"1656":1784,"1657":1785,"1661":1788,"1662":1789,"1663":1790,"1667":1793,"1668":1794,"1672":1797,"1674":1798,"1676":1798,"1677":1799,"1681":1802,"1682":1803,"1683":1805,"1684":1806,"1685":1807,"1686":1808,"1687":1810,"1688":1811,"1689":1812,"1690":1813,"1691":1814,"1692":1815,"1698":1818,"1699":1820,"1703":1823,"1704":1825,"1705":1827,"1706":1829,"1707":1830,"1708":1832,"1709":1833,"1710":1833,"1714":1833,"1715":1834,"1716":1834,"1720":1834,"1721":1835,"1722":1835,"1726":1835,"1727":1838,"1728":1840,"1729":1841,"1730":1842,"1731":1843,"1732":1844,"1733":1846,"1734":1847,"1735":1848,"1739":1853,"1740":1854,"1744":1857,"1745":1858,"1746":1860,"1747":1862,"1751":1865,"1752":1866,"1756":1870,"1757":1872,"1758":1874,"1759":1877,"1760":1879,"1761":1882,"1762":1882,"1766":1882,"1767":1883,"1768":1883,"1772":1883,"1773":1884,"1774":1884,"1778":1884,"1779":1885,"1780":1885,"1784":1885,"1785":1887,"1786":1892,"1787":1893,"1788":1895,"1789":1896,"1790":1897,"1791":1898,"1792":1899,"1793":1901,"1794":1902,"1795":1903,"1796":1904,"1797":1905,"1798":1907,"1799":1908,"1800":1909,"1801":1910,"1802":1919,"1803":1920,"1804":1921,"1805":1923,"1806":1924,"1807":1925,"1808":1927,"1809":1927,"1813":1927,"1817":1931,"1818":1932,"1822":1935,"1823":1937,"1824":1939,"1825":1940,"1826":1941,"1827":1942,"1828":1943,"1829":1944,"1830":1946,"1831":1946,"1835":1946,"1839":1949,"1840":1951,"1841":1953,"1842":1955,"1843":1956,"1844":1959,"1845":1960,"1846":1961,"1850":1966,"1851":1968,"1852":1970,"1853":1972,"1854":1973,"1855":1974,"1856":1975,"1857":1977,"1858":1979,"1859":1980,"1860":1982,"1861":1983,"1862":1984,"1863":1986,"1864":1987,"1865":1989,"1866":1990,"1867":1992,"1868":1993,"1869":1995,"1870":1996,"1871":1998,"1872":1999,"1873":2001,"1874":2002,"1875":2004,"1876":2004,"1880":2004,"1884":2008,"1885":2010,"1886":2012,"1887":2013,"1888":2015,"1889":2016,"1890":2018,"1891":2020,"1892":2021,"1893":2023,"1894":2024,"1895":2026,"1896":2027,"1897":2028,"1898":2029,"1899":2031,"1900":2034,"1901":2035,"1902":2036,"1903":2037,"1904":2038,"1905":2039,"1906":2040,"1907":2042,"1908":2043,"1909":2044,"1913":2047,"1914":2049,"1915":2051,"1916":2052,"1920":2061,"1922":2064,"1923":2064,"1924":2065,"1925":2065,"1926":2066,"1929":2068,"1934":2070,"1936":2071,"1937":2071,"1938":2072,"1942":2074,"1944":2076,"1948":2078,"1950":2082,"1954":2084,"1956":2086,"1962":2088,"1964":2089,"1965":2089,"1966":2092,"1970":2094,"1972":2095,"1973":2095,"1974":2095,"1975":2095,"1976":2096,"1980":2098,"1982":2099,"1984":2099,"1985":2100,"1990":2102,"1992":2104,"1996":2106,"1998":2108,"2002":2110,"2004":2111,"2005":2111,"2006":2112,"2010":2114,"2012":2115,"2013":2115,"2014":2116,"2018":2122,"2019":2123,"2023":2126,"2024":2128,"2027":2130,"2029":2132,"2033":2135,"2035":2137,"2039":2140,"2042":2141,"2043":2142,"2047":2145,"2048":2146} */
>>>>>>> oop:tht/testsite/app/data/cache/php/pages_home.tht.php

?>