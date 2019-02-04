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
  \o\OBare::u_print(\o\v(\o\OList::create([ 1, 2, 3 ]))->u_filter(function  ($u_a)  {
  return (\o\vn($u_a, 0) >= \o\vn(2, 0));
 return new \o\ONothing(__METHOD__);
 
}
));
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
$u_info = \o\v(\o\ModuleManager::getModule('File'))->u_path_info($u_p);
\o\v($u_t)->u_ok((\o\v(\o\v($u_info)->u_dir_list)->u_last() === $u_d), "Path info dirList has parent dir");
\o\v($u_t)->u_ok((\o\v($u_info)->u_file_ext === "txt"), "Path info extension");
\o\v($u_t)->u_ok((\o\v($u_info)->u_file_name === "testFile.txt"), "Path info fileName");
\o\v($u_t)->u_ok((\o\v($u_info)->u_file_name_short === "testFile"), "Path info shortFileName");
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
  return new \o\ONothing(__METHOD__);
\o\v($u_t)->u_section("Module: Web");
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
$t->addStatic(" font-weight:bold; ");
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
$t->addStatic(" font-weight:");
$t->addDynamic($u_inp);
$t->addStatic("; ");
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



/* SOURCE={"file":"pages\/home.tht","6":3,"7":5,"8":7,"9":8,"13":12,"15":18,"16":18,"17":28,"18":28,"19":34,"23":36,"24":38,"25":39,"26":40,"27":41,"28":42,"29":43,"30":44,"31":45,"32":46,"33":47,"34":48,"35":50,"36":51,"37":53,"38":54,"39":55,"40":56,"41":57,"42":58,"43":59,"44":60,"45":61,"46":62,"47":63,"48":64,"49":65,"50":66,"51":68,"52":69,"53":70,"57":73,"58":75,"59":77,"60":77,"64":77,"65":79,"66":79,"70":79,"71":81,"72":81,"76":81,"77":83,"78":83,"82":83,"83":85,"84":85,"85":85,"91":87,"92":89,"93":89,"97":89,"101":93,"102":96,"103":98,"110":109,"111":110,"112":111,"113":112,"114":113,"115":116,"116":118,"117":119,"118":120,"119":121,"120":122,"121":123,"122":124,"123":125,"124":126,"125":129,"126":131,"127":132,"128":133,"129":134,"130":135,"131":136,"132":137,"133":138,"134":139,"135":140,"136":141,"137":142,"138":143,"139":144,"140":145,"141":146,"142":147,"143":148,"144":151,"145":153,"146":154,"147":155,"148":156,"149":157,"150":158,"151":159,"152":160,"153":161,"154":162,"155":163,"156":164,"157":165,"158":166,"159":167,"160":168,"161":169,"162":170,"163":171,"164":172,"165":173,"166":174,"167":175,"168":176,"169":177,"170":178,"171":179,"172":180,"173":181,"174":182,"175":183,"176":184,"177":185,"178":186,"179":187,"180":188,"181":189,"182":193,"183":195,"184":196,"185":197,"186":198,"187":199,"188":200,"189":201,"190":202,"191":203,"192":206,"193":208,"194":209,"195":210,"196":211,"197":212,"198":213,"199":214,"200":215,"201":216,"202":217,"203":218,"204":219,"205":220,"206":221,"207":223,"208":224,"209":225,"210":226,"211":227,"212":228,"213":231,"214":233,"215":234,"216":235,"217":236,"218":237,"219":238,"220":239,"221":240,"222":241,"223":242,"224":244,"225":245,"226":246,"227":247,"228":248,"229":249,"230":250,"231":251,"232":252,"233":253,"234":254,"235":255,"236":256,"237":257,"238":258,"239":259,"240":260,"241":261,"242":262,"243":263,"244":264,"245":265,"246":266,"247":267,"248":269,"249":270,"250":271,"251":272,"252":274,"253":275,"254":276,"255":277,"256":282,"257":284,"258":285,"259":286,"260":287,"261":288,"262":289,"263":291,"264":292,"265":293,"266":294,"267":295,"268":297,"269":298,"270":299,"271":302,"272":304,"273":305,"274":306,"275":307,"276":308,"277":309,"278":310,"279":311,"280":314,"281":316,"282":317,"283":318,"284":319,"285":320,"286":321,"287":324,"288":325,"289":326,"290":327,"291":328,"292":329,"293":330,"294":331,"295":332,"296":334,"300":342,"301":344,"302":347,"303":348,"304":349,"305":350,"306":351,"307":352,"308":353,"309":354,"312":356,"313":357,"314":358,"315":359,"316":362,"317":368,"318":370,"319":371,"320":372,"321":374,"322":375,"323":376,"324":381,"325":383,"326":385,"327":386,"328":388,"329":389,"330":392,"331":393,"335":398,"336":400,"337":400,"342":402,"343":404,"344":406,"345":407,"346":409,"347":410,"348":410,"352":410,"353":412,"354":413,"355":414,"356":415,"357":417,"358":418,"359":419,"360":421,"361":421,"365":421,"366":422,"367":423,"368":424,"369":425,"370":427,"371":428,"372":428,"376":428,"377":430,"378":432,"379":433,"380":435,"381":436,"382":438,"383":440,"384":441,"385":443,"386":443,"390":443,"391":445,"392":446,"393":447,"394":448,"395":449,"396":453,"397":454,"398":456,"399":457,"400":459,"404":463,"405":465,"406":467,"407":468,"408":469,"409":470,"410":471,"411":472,"412":473,"413":474,"414":474,"418":475,"419":479,"420":481,"421":482,"422":483,"423":484,"424":485,"425":486,"426":487,"427":489,"428":490,"429":491,"430":492,"431":493,"432":494,"433":495,"437":500,"438":502,"439":504,"440":505,"444":507,"445":509,"446":510,"450":512,"451":514,"452":515,"456":517,"457":519,"458":520,"459":521,"460":522,"463":524,"467":526,"468":527,"469":532,"470":533,"474":535,"475":536,"476":538,"477":539,"478":541,"479":542,"480":543,"485":545,"486":548,"487":549,"491":551,"492":552,"493":553,"494":555,"495":556,"496":560,"497":561,"501":563,"502":564,"503":565,"504":568,"505":569,"506":570,"511":572,"512":575,"513":575,"516":576,"517":576,"521":576,"522":577,"523":577,"527":577,"528":580,"529":581,"530":583,"531":584,"532":586,"533":587,"534":593,"535":595,"536":596,"537":597,"538":598,"539":599,"540":601,"541":604,"542":604,"546":604,"547":606,"548":606,"552":606,"553":607,"554":607,"558":607,"559":608,"560":608,"564":608,"565":609,"566":609,"570":609,"571":611,"572":612,"573":612,"577":612,"578":614,"579":614,"583":614,"584":615,"585":615,"589":615,"590":616,"591":616,"595":616,"596":618,"597":619,"601":623,"602":625,"603":627,"604":628,"605":629,"606":630,"607":631,"608":632,"609":633,"610":634,"611":635,"612":636,"613":637,"614":638,"615":641,"616":643,"617":645,"618":646,"619":651,"620":653,"621":654,"622":654,"626":654,"627":657,"628":659,"629":660,"630":661,"631":662,"632":663,"633":664,"634":667,"635":669,"636":670,"637":671,"638":673,"639":674,"640":675,"641":678,"642":680,"643":682,"644":683,"645":685,"646":686,"647":688,"648":689,"649":692,"650":693,"651":694,"652":695,"653":696,"654":697,"655":698,"656":701,"657":702,"658":702,"662":702,"663":703,"664":706,"665":707,"666":708,"667":709,"668":710,"669":713,"670":714,"671":715,"672":716,"673":719,"674":721,"675":721,"679":721,"680":722,"681":722,"685":722,"686":724,"687":724,"691":724,"692":725,"693":725,"697":725,"701":729,"702":731,"703":733,"704":734,"705":735,"706":736,"707":737,"708":738,"709":739,"710":740,"711":742,"712":743,"713":744,"714":745,"715":746,"716":747,"717":748,"718":750,"719":751,"720":753,"721":756,"722":758,"723":758,"727":758,"728":759,"729":759,"733":759,"734":760,"735":760,"739":760,"740":761,"741":761,"745":761,"746":762,"747":762,"751":762,"752":763,"753":763,"757":763,"758":764,"759":765,"760":766,"764":766,"765":767,"766":767,"770":767,"771":768,"772":768,"776":768,"777":769,"778":769,"782":769,"783":770,"784":770,"788":770,"789":771,"790":771,"794":771,"795":772,"796":772,"800":772,"801":775,"802":777,"803":778,"804":780,"805":781,"806":785,"807":787,"808":788,"809":789,"810":790,"811":791,"812":792,"813":795,"814":797,"815":798,"816":799,"817":800,"818":803,"819":805,"820":806,"821":807,"822":808,"823":809,"824":810,"825":811,"826":812,"827":814,"828":815,"829":816,"830":817,"831":818,"832":819,"833":821,"834":822,"835":823,"836":824,"837":831,"838":833,"839":834,"840":835,"841":836,"842":837,"843":838,"844":839,"845":840,"846":841,"847":842,"848":843,"849":846,"850":848,"851":849,"852":850,"853":851,"854":852,"855":854,"856":856,"857":857,"858":858,"859":859,"860":862,"861":864,"862":865,"866":870,"867":873,"868":875,"869":876,"870":877,"873":879,"874":881,"875":882,"876":883,"879":885,"880":887,"881":888,"882":889,"883":890,"886":892,"887":895,"888":896,"889":897,"890":898,"891":899,"892":900,"893":900,"899":902,"900":904,"901":905,"902":906,"903":907,"904":908,"905":909,"909":911,"910":912,"911":913,"917":916,"918":918,"919":919,"920":922,"921":923,"922":924,"923":925,"924":926,"925":927,"926":928,"927":929,"928":930,"929":931,"930":932,"931":933,"932":934,"933":935,"934":936,"935":937,"936":938,"937":939,"938":940,"939":941,"940":942,"941":943,"942":944,"943":945,"944":946,"945":947,"946":948,"947":949,"948":952,"949":954,"950":956,"951":956,"955":957,"956":959,"957":959,"961":960,"962":962,"963":963,"967":966,"971":968,"972":970,"973":971,"976":973,"977":974,"982":976,"983":978,"984":979,"987":981,"988":982,"991":984,"992":985,"996":988,"1002":990,"1003":994,"1004":996,"1005":997,"1006":998,"1007":999,"1010":1001,"1011":1002,"1015":1005,"1019":1008,"1020":1009,"1021":1011,"1022":1012,"1023":1013,"1026":1014,"1027":1015,"1031":1017,"1032":1020,"1033":1022,"1034":1023,"1038":1027,"1039":1029,"1040":1031,"1041":1032,"1042":1034,"1043":1036,"1046":1041,"1047":1043,"1048":1044,"1049":1045,"1050":1047,"1051":1049,"1052":1050,"1053":1051,"1054":1052,"1055":1053,"1056":1055,"1057":1056,"1058":1057,"1059":1060,"1060":1062,"1061":1063,"1062":1064,"1063":1065,"1064":1066,"1065":1067,"1066":1068,"1067":1069,"1068":1070,"1069":1071,"1070":1074,"1071":1075,"1072":1076,"1073":1077,"1074":1079,"1075":1080,"1076":1083,"1077":1085,"1078":1086,"1079":1088,"1080":1089,"1081":1091,"1082":1092,"1083":1093,"1084":1095,"1085":1096,"1086":1098,"1087":1099,"1088":1101,"1089":1102,"1090":1103,"1091":1104,"1092":1106,"1093":1107,"1094":1108,"1095":1110,"1096":1111,"1097":1112,"1098":1114,"1099":1115,"1100":1117,"1101":1118,"1102":1120,"1103":1121,"1104":1122,"1105":1123,"1106":1124,"1107":1126,"1108":1127,"1109":1128,"1110":1129,"1111":1130,"1112":1131,"1113":1133,"1114":1134,"1115":1136,"1116":1137,"1117":1139,"1118":1140,"1119":1142,"1120":1143,"1121":1145,"1122":1146,"1123":1147,"1124":1149,"1125":1150,"1126":1151,"1127":1152,"1128":1154,"1129":1155,"1130":1157,"1131":1158,"1132":1159,"1133":1160,"1134":1161,"1135":1162,"1136":1164,"1137":1166,"1141":1174,"1142":1175,"1143":1176,"1144":1178,"1145":1179,"1146":1181,"1147":1182,"1148":1184,"1149":1186,"1150":1187,"1151":1188,"1152":1189,"1153":1191,"1154":1192,"1155":1193,"1156":1195,"1157":1196,"1158":1197,"1159":1199,"1160":1200,"1161":1201,"1162":1208,"1163":1209,"1164":1211,"1165":1212,"1166":1213,"1167":1214,"1168":1217,"1169":1219,"1170":1220,"1171":1221,"1172":1223,"1173":1224,"1174":1225,"1175":1227,"1176":1228,"1177":1229,"1178":1230,"1179":1232,"1180":1233,"1181":1234,"1182":1235,"1183":1237,"1184":1238,"1185":1239,"1186":1240,"1187":1242,"1188":1243,"1189":1244,"1190":1245,"1191":1247,"1192":1248,"1193":1249,"1194":1250,"1195":1251,"1196":1253,"1197":1254,"1198":1255,"1199":1256,"1200":1258,"1201":1259,"1202":1260,"1203":1261,"1204":1262,"1205":1264,"1206":1265,"1207":1266,"1208":1267,"1209":1268,"1210":1270,"1211":1271,"1212":1272,"1213":1273,"1214":1274,"1215":1275,"1216":1276,"1217":1278,"1218":1279,"1219":1280,"1220":1281,"1221":1284,"1222":1286,"1223":1287,"1224":1288,"1225":1289,"1226":1290,"1227":1293,"1228":1294,"1229":1295,"1230":1298,"1231":1300,"1232":1301,"1233":1302,"1234":1302,"1238":1302,"1239":1304,"1240":1305,"1241":1307,"1242":1308,"1243":1310,"1244":1311,"1245":1313,"1246":1314,"1247":1316,"1248":1318,"1249":1319,"1250":1322,"1251":1324,"1252":1325,"1253":1327,"1254":1327,"1258":1327,"1259":1328,"1260":1328,"1264":1328,"1265":1330,"1266":1331,"1267":1332,"1268":1333,"1269":1334,"1270":1336,"1271":1337,"1275":1341,"1276":1343,"1277":1345,"1278":1346,"1279":1347,"1280":1348,"1281":1349,"1282":1350,"1283":1351,"1284":1352,"1285":1354,"1286":1356,"1287":1357,"1288":1359,"1289":1360,"1290":1365,"1291":1368,"1292":1373,"1293":1374,"1294":1375,"1295":1376,"1296":1378,"1297":1381,"1298":1383,"1299":1384,"1300":1386,"1301":1387,"1302":1389,"1303":1390,"1304":1391,"1305":1392,"1306":1394,"1307":1395,"1308":1397,"1309":1398,"1310":1399,"1311":1401,"1312":1402,"1313":1404,"1314":1405,"1315":1406,"1316":1408,"1317":1409,"1318":1410,"1319":1414,"1320":1415,"1321":1416,"1322":1420,"1323":1421,"1324":1422,"1325":1426,"1326":1427,"1327":1431,"1328":1434,"1329":1435,"1330":1437,"1331":1437,"1336":1438,"1337":1440,"1338":1441,"1339":1443,"1340":1444,"1341":1446,"1342":1447,"1343":1449,"1344":1450,"1345":1452,"1346":1452,"1350":1452,"1351":1454,"1352":1455,"1353":1458,"1354":1460,"1355":1460,"1359":1460,"1360":1461,"1361":1461,"1365":1461,"1366":1462,"1367":1462,"1371":1462,"1372":1463,"1373":1463,"1377":1463,"1378":1464,"1379":1464,"1383":1464,"1384":1467,"1385":1469,"1386":1471,"1387":1472,"1388":1473,"1389":1476,"1390":1478,"1391":1478,"1395":1478,"1396":1479,"1397":1479,"1401":1479,"1402":1480,"1403":1480,"1407":1480,"1408":1482,"1409":1483,"1410":1484,"1411":1486,"1412":1487,"1413":1487,"1417":1487,"1418":1488,"1419":1490,"1420":1491,"1421":1491,"1425":1491,"1426":1492,"1427":1493,"1428":1493,"1432":1493,"1433":1494,"1437":1497,"1438":1499,"1439":1501,"1440":1502,"1441":1503,"1442":1504,"1443":1506,"1444":1507,"1445":1508,"1446":1509,"1447":1513,"1448":1515,"1449":1516,"1450":1518,"1451":1519,"1452":1520,"1453":1522,"1454":1523,"1455":1524,"1456":1526,"1457":1527,"1458":1530,"1459":1531,"1460":1532,"1461":1533,"1462":1534,"1463":1535,"1467":1541,"1468":1543,"1469":1545,"1470":1546,"1471":1547,"1472":1549,"1473":1551,"1474":1552,"1475":1554,"1476":1555,"1477":1556,"1478":1557,"1482":1561,"1483":1563,"1484":1565,"1485":1565,"1489":1565,"1490":1566,"1491":1566,"1495":1566,"1496":1568,"1497":1569,"1498":1570,"1499":1572,"1500":1573,"1501":1575,"1502":1576,"1506":1578,"1507":1579,"1508":1581,"1509":1583,"1510":1584,"1511":1585,"1512":1586,"1513":1588,"1514":1589,"1515":1590,"1516":1591,"1517":1592,"1518":1593,"1519":1594,"1520":1596,"1521":1597,"1525":1602,"1526":1604,"1527":1606,"1528":1607,"1529":1608,"1530":1609,"1531":1610,"1532":1611,"1533":1612,"1534":1613,"1535":1614,"1539":1617,"1540":1619,"1541":1621,"1542":1622,"1543":1624,"1544":1625,"1545":1627,"1546":1628,"1547":1629,"1548":1631,"1549":1632,"1550":1633,"1551":1635,"1552":1636,"1553":1637,"1554":1639,"1555":1640,"1556":1641,"1557":1643,"1558":1644,"1562":1645,"1563":1647,"1564":1648,"1568":1649,"1569":1651,"1570":1652,"1574":1653,"1575":1655,"1576":1656,"1580":1657,"1584":1660,"1585":1662,"1586":1664,"1587":1665,"1588":1667,"1589":1668,"1590":1670,"1591":1671,"1592":1673,"1593":1674,"1594":1676,"1595":1677,"1596":1679,"1597":1680,"1598":1681,"1599":1683,"1600":1684,"1601":1686,"1602":1687,"1606":1690,"1607":1692,"1608":1694,"1609":1695,"1610":1696,"1614":1699,"1615":1701,"1616":1703,"1617":1704,"1618":1705,"1619":1706,"1620":1707,"1621":1709,"1622":1710,"1623":1711,"1624":1712,"1625":1714,"1626":1715,"1630":1718,"1631":1720,"1635":1724,"1636":1726,"1637":1728,"1638":1729,"1639":1730,"1640":1732,"1641":1733,"1642":1735,"1643":1736,"1644":1737,"1645":1739,"1646":1740,"1647":1741,"1648":1743,"1649":1744,"1650":1746,"1651":1747,"1652":1749,"1653":1750,"1657":1771,"1658":1772,"1659":1774,"1660":1775,"1661":1781,"1662":1782,"1666":1785,"1667":1786,"1668":1787,"1672":1790,"1673":1791,"1677":1794,"1679":1795,"1681":1795,"1682":1796,"1686":1799,"1687":1800,"1688":1802,"1689":1803,"1690":1804,"1691":1805,"1692":1807,"1693":1808,"1694":1809,"1695":1810,"1696":1811,"1697":1812,"1703":1815,"1704":1817,"1708":1820,"1709":1822,"1710":1824,"1711":1826,"1712":1827,"1713":1829,"1714":1830,"1715":1830,"1719":1830,"1720":1831,"1721":1831,"1725":1831,"1726":1832,"1727":1832,"1731":1832,"1732":1835,"1733":1837,"1734":1838,"1735":1839,"1736":1840,"1737":1841,"1738":1843,"1739":1844,"1740":1845,"1744":1850,"1745":1851,"1749":1854,"1750":1855,"1751":1857,"1752":1859,"1756":1862,"1757":1863,"1761":1867,"1762":1869,"1763":1871,"1764":1873,"1765":1873,"1769":1873,"1770":1874,"1771":1874,"1775":1874,"1776":1875,"1777":1875,"1781":1875,"1782":1876,"1783":1876,"1787":1876,"1788":1878,"1789":1883,"1790":1884,"1791":1886,"1792":1887,"1793":1888,"1794":1889,"1795":1890,"1796":1892,"1797":1893,"1798":1894,"1799":1895,"1800":1896,"1801":1898,"1802":1899,"1803":1900,"1804":1901,"1805":1910,"1806":1911,"1807":1912,"1808":1914,"1809":1915,"1810":1916,"1811":1918,"1812":1918,"1816":1918,"1820":1922,"1821":1923,"1825":1926,"1826":1928,"1827":1930,"1828":1931,"1829":1932,"1830":1933,"1831":1934,"1832":1935,"1833":1937,"1834":1937,"1838":1937,"1842":1940,"1843":1942,"1844":1944,"1845":1946,"1846":1947,"1847":1950,"1848":1951,"1849":1952,"1853":1957,"1854":1959,"1855":1961,"1856":1963,"1857":1964,"1858":1965,"1859":1966,"1860":1968,"1861":1970,"1862":1971,"1863":1973,"1864":1974,"1865":1975,"1866":1977,"1867":1978,"1868":1980,"1869":1981,"1870":1983,"1871":1984,"1872":1986,"1873":1987,"1874":1989,"1875":1990,"1876":1992,"1877":1993,"1878":1995,"1879":1995,"1883":1995,"1887":1999,"1888":2001,"1889":2003,"1890":2004,"1891":2006,"1892":2007,"1893":2009,"1894":2011,"1895":2012,"1896":2014,"1897":2015,"1898":2017,"1899":2018,"1900":2019,"1901":2020,"1902":2022,"1903":2025,"1904":2026,"1905":2027,"1906":2028,"1907":2029,"1908":2030,"1909":2031,"1910":2033,"1911":2034,"1912":2035,"1916":2038,"1917":2040,"1918":2042,"1919":2043,"1923":2052,"1925":2055,"1926":2055,"1927":2056,"1928":2056,"1929":2057,"1932":2059,"1937":2061,"1939":2062,"1940":2062,"1941":2063,"1945":2065,"1947":2067,"1951":2069,"1953":2073,"1957":2075,"1959":2077,"1965":2079,"1967":2080,"1968":2080,"1969":2083,"1973":2085,"1975":2086,"1976":2086,"1977":2086,"1978":2086,"1979":2087,"1983":2089,"1985":2090,"1987":2090,"1988":2091,"1993":2093,"1995":2095,"1999":2097,"2001":2099,"2005":2101,"2007":2102,"2008":2102,"2009":2103,"2013":2105,"2015":2106,"2016":2106,"2017":2107,"2021":2113,"2022":2114,"2026":2117,"2027":2119,"2030":2121,"2032":2123,"2036":2126,"2038":2128,"2042":2131,"2045":2132,"2046":2133,"2050":2136,"2051":2137} */

?>