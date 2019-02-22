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
$t->addDynamic("none", \o\v(\o\ModuleManager::getModule('Css'))->u_include("base"));
$t->addStatic("</head><body><main><h1>THT Unit Tests</h1><a href=\"#test-results\" style=\"font-weight: bold\">Skip to Results</a><p style=\"font-size: 100%; margin-top: 3rem\"><b>Perf:</b> When measuring raw execution speed of this page, set <code>_disablePhpCache: false</code> in <code>app.jcon</code>.  Then take Server Response Time and subtract <code>System.sleep</code> + <code>Net.httpRequest</code> + <code>CompileErrors</code>.</p>");
$t->addDynamic("tag", $u_results);
$t->addStatic("</main></body></html>");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_run ($u_t)  {
  $u_start = \o\v(\o\ModuleManager::getModule('Date'))->u_now(true);
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
u_lib_system($u_t);
$u_end = \o\v(\o\ModuleManager::getModule('Date'))->u_now(true);
u_lib_cache($u_t);
u_lib_net($u_t);
u_runtime_errors($u_t);
u_compile_errors($u_t);
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
  foreach (2 as $u_foo) {

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
  \o\v(\o\ModuleManager::getModule('Perf'))->u_start("CompileErrors");
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
\o\v($u_t)->u_parser_error("let a = e'foo';", "string modifier");
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
\o\v(\o\ModuleManager::getModule('Perf'))->u_stop();
 return new \o\ONothing(__METHOD__);
 
}
function u_test_misc ($u_t)  {
  \o\v($u_t)->u_section("Performance");
\o\v(\o\ModuleManager::getModule('Perf'))->u_start("Large Array");
$u_now = \o\v(\o\ModuleManager::getModule('Date'))->u_now(true);
$u_num_els = 1000;
$u_nums = \o\OBare::u_range(1, $u_num_els);
$u_ii = 0;
foreach ($u_nums as $u_nn) {
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
\o\v($u_t)->u_ok((\o\v(\o\v($u_tc)->u_html())->u_stringify() === "<b>Hello</b>\n"), "object template");
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
foreach (\o\v(\o\ModuleManager::getModule('Meta'))->u_arguments() as $u_arg) {
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
  \o\v(\o\ModuleManager::getModule('Perf'))->u_start("ControlFlow");
\o\v($u_t)->u_section("Loops");
$u_s = "";
foreach (\o\OBare::u_range(1, 3) as $u_i) {
$u_s .= $u_i;

}
\o\v($u_t)->u_ok(($u_s === "123"), "for, range");
$u_nums = \o\OList::create([ 4, 5, 6 ]);
foreach ($u_nums as $u_n) {
$u_s .= $u_n;

}
\o\v($u_t)->u_ok(($u_s === "123456"), "for, list");
$u_pairs = \o\OMap::create([ 'a' => 1, 'b' => 2, 'c' => 3 ]);
$u_s = "";
foreach ($u_pairs as $u_letter => $u_number) {
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
\o\v(\o\ModuleManager::getModule('Perf'))->u_stop();
 return new \o\ONothing(__METHOD__);
 
}
function u_test_strings ($u_t)  {
  \o\v(\o\ModuleManager::getModule('Perf'))->u_start("Strings");
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
\o\v($u_t)->u_ok((\o\v(\o\v("Ⓐ, Ⓑ, Ⓒ")->u_split(new \o\ORegex(",\s+")))->u_join("|") === "Ⓐ|Ⓑ|Ⓒ"), "split/join regex");
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
\o\v($u_t)->u_ok(\o\v("ab\ncd")->u_match(new \o\ORegex("ab\scd")), "string - newline");
$u_esc = "\$_SERVER[\"REMOTE_ADDR\"]";
\o\v($u_t)->u_ok((! \o\v("lot's\t {} \"double \$quote\"")->u_contains("\\")), "no leaked backslashes");
\o\v($u_t)->u_ok(\o\v("Here's an escaped quote")->u_contains("'"), "escaped quote (\\')");
\o\v($u_t)->u_ok(\o\v($u_esc)->u_starts_with("\$_SERVER"), "prevent php vars - \$_SERVER");
\o\v($u_t)->u_ok((\o\v("\$abc")[0] === "\$"), "prevent php vars - \\\$abc");
\o\v($u_t)->u_ok((\o\v("\${abc}")[0] === "\$"), "prevent php vars - \${abc}");
\o\v($u_t)->u_section("Regular Expressions");
\o\v($u_t)->u_ok((\o\v(\o\v($u_hi)->u_split(new \o\ORegex("\s")))[1] === "World!"), "split regex");
\o\v($u_t)->u_ok((\o\v(\o\v($u_hi)->u_match(new \o\ORegex("(\w+)!\$")))[1] === "World"), "regex with dollar");
\o\v($u_t)->u_dies(function  ()  {
  \o\v("longstringlongstring")->u_find(new \o\ORegex("(?:\D+|<\d+>)*[!?]"));
 return new \o\ONothing(__METHOD__);
 
}
, "regex error");
$u_multi = "one\ntwo\nthree";
\o\v($u_t)->u_ok((\o\v(\o\v($u_multi)->u_split(new \o\ORegex("\s")))->u_length() === 3), "Newline regex");
$u_cased = "hello WORLD";
\o\v($u_t)->u_ok((\o\v(\o\v($u_cased)->u_match(\o\v(new \o\ORegex("world"))->u_flags("i")))[0] === "WORLD"), "regex object");
$u_ticks = "hello 'WORLD'";
\o\v($u_t)->u_ok((\o\v(\o\v($u_ticks)->u_match(new \o\ORegex("'(\w+)'")))[1] === "WORLD"), "regex with backticks");
$u_esc_ticks = "hello `WORLD`";
\o\v($u_t)->u_ok((\o\v($u_esc_ticks)->u_replace(new \o\ORegex("\`(\w+)\`"), "THERE") === "hello THERE"), "escaped backticks");
\o\v($u_t)->u_ok((\o\v("ab  cd e")->u_replace(new \o\ORegex("\s+"), "-") === "ab-cd-e"), "replace");
$u_rx = \o\v(\o\ModuleManager::getModule('Regex'))->u_new(\o\v("'{0}'")->u_fill("world"), "i");
\o\v($u_t)->u_ok((\o\v($u_ticks)->u_replace($u_rx, "VAR") === "hello VAR"), "replace with variable");
\o\v($u_t)->u_section("LockStrings");
\o\v($u_t)->u_ok(\o\v(\o\OLockString::create("sql", "abc"))->u_is_lock_string(), "isLockString = true");
\o\v($u_t)->u_ok((! \o\v("abc")->u_is_lock_string()), "isLockString = false");
\o\v($u_t)->u_dies(function  ()  {
  return \o\Runtime::concat(\o\OLockString::create("lock", "a"), "b");
 return new \o\ONothing(__METHOD__);
 
}
, "Can't combine");
\o\v($u_t)->u_dies(function  ()  {
  return \o\Runtime::concat("a", \o\OLockString::create("lock", "b"));
 return new \o\ONothing(__METHOD__);
 
}
, "Can't combine");
$u_lock1 = \o\OLockString::create("sql", "1={},");
$u_lock2 = \o\OLockString::create("sql", "2={}");
$u_combined = \o\Runtime::concat($u_lock1, $u_lock2);
\o\v($u_combined)->u_fill(\o\OList::create([ "a", "b" ]));
\o\v($u_t)->u_ok((\o\v($u_combined)->u_stringify() === "1=a,2=b"), "combined lockstrings");
\o\v($u_t)->u_ok((\o\v(u_lock_html("a"))->u_lock_type() === "html"), "getLockType");
\o\v($u_t)->u_ok((\o\v(\o\OLockString::create("sql", "x"))->u_lock_type() === "sql"), "getLockType");
$u_l_url = \o\v(\o\OLockString::create("url", "http://test.com/"))->u_query(\o\OMap::create([ 'foo' => "val's" ]));
$u_l_cmd = \o\v(\o\OLockString::create("cmd", "xget {} > file.txt"))->u_fill($u_l_url);
$u_l_html = u_deep_esc_html($u_l_cmd);
$u_esc_out = \o\Runtime::concat("<b>xget &apos;http://test.com/?", "foo=val%27s&apos; &gt; file.txt</b>\n");
\o\v($u_t)->u_ok((\o\v($u_l_html)->u_stringify() === $u_esc_out), "recursive escaped stringify()");
$u_l_url2 = \o\v(\o\OLockString::create("url", "/home"))->u_query(\o\OMap::create([ 'bar' => "my var" ]));
\o\v($u_t)->u_ok((\o\v($u_l_url2)->u_stringify() === \o\Runtime::concat("/home?", "bar=my%20var")), "url: escape query");
\o\v($u_l_url2)->u_query(\o\OMap::create([ 'foo' => 123 ]));
\o\v($u_t)->u_ok((\o\v($u_l_url2)->u_stringify() === \o\Runtime::concat("/home?", "bar=my%20var&foo=123")), "url: add query");
\o\v($u_l_url2)->u_query(\o\OMap::create([ 'bar' => "" ]));
\o\v($u_t)->u_ok((\o\v($u_l_url2)->u_stringify() === \o\Runtime::concat("/home?", "foo=123")), "url: remove one query");
\o\v($u_l_url2)->u_clear_query();
\o\v($u_t)->u_ok((\o\v($u_l_url2)->u_stringify() === "/home"), "url: clear query");
\o\OBare::u_print(\o\v(\o\ModuleManager::getModule('Web'))->u_link(\o\OLockString::create("url", "/page"), "hey"));
\o\v(\o\ModuleManager::getModule('Perf'))->u_stop();
\o\v(\o\ModuleManager::getModule('Perf'))->u_start("String.civilize");
\o\v($u_t)->u_ok((\o\v("PLS HELP HELP!!!!!!")->u_civilize() === "please help help!"), "civilize: all caps");
\o\v($u_t)->u_ok((\o\v("r u ok?!!!")->u_civilize() === "are you ok?!"), "civilize: r u ok?!!!");
\o\v($u_t)->u_ok((\o\v("Teh THing")->u_civilize() === "The Thing"), "civilize: Teh THing");
\o\v($u_t)->u_ok((\o\v("its a suprise.....")->u_civilize() === "it's a surprise..."), "civilize: its a suprise.....");
\o\v($u_t)->u_ok((\o\v("hes a mod")->u_civilize() === "he's a mod"), "civilize: hes a mod");
\o\v($u_t)->u_ok((\o\v("your in trouble")->u_civilize() === "you're in trouble"), "civilize: your in trouble");
\o\v($u_t)->u_ok((\o\v("he could of")->u_civilize() === "he could have"), "civilize: he could of");
\o\v($u_t)->u_ok((\o\v("u know it")->u_civilize() === "you know it"), "civilize: u know it");
$u_long = "aaaaaaaaaaaaaaaaaaaaAAAAAAAAAAAAAAAAAAAAAAARRRRRRRRRGGGGGGHHHHHHHHHHHHHH";
\o\v($u_t)->u_ok((\o\v($u_long)->u_civilize() === "Aaaaaarrrrrggggghhhhh"), "Civ: long string, mixed");
$u_long2 = "zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz";
\o\v($u_t)->u_ok((\o\v($u_long2)->u_civilize() === "zzzzz"), "Civ: long string all same");
$u_long3 = "asdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdasdd";
\o\v($u_t)->u_ok((\o\v($u_long3)->u_civilize() === "asdasdasdasdasdasdasdasdasdasd"), "Civ: long cycled asdasd");
\o\v(\o\ModuleManager::getModule('Perf'))->u_stop();
 return new \o\ONothing(__METHOD__);
 
}
function u_deep_esc_html ($u_val)  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("<b>");
$t->addDynamic("none", $u_val);
$t->addStatic("</b>");
\o\Runtime::closeTemplate();
return $t->getString();
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
$u_html_users = \o\v(u_template_html(\o\OList::create([ "Frodo", "Sam", "Gandalf" ])))->u_stringify();
\o\v($u_t)->u_ok(\o\v($u_html_users)->u_match(new \o\ORegex("<li>Frodo.*?<li>Sam.*?<li>Gandalf")), "template - loop & variables");
$u_html_users = u_template_html(\o\OList::create([ "Frodo", "<b>Sam</b>", "Gandalf" ]));
\o\v($u_t)->u_ok(\o\v(\o\v($u_html_users)->u_stringify())->u_contains("&lt;b&gt;Sam"), "template with html escapes");
$u_p = \o\v(\o\ModuleManager::getModule('Web'))->u_parse_html(\o\OLockString::create("html", "<h1>> Hello\n<.abc>> 123"));
$u_p = \o\v($u_p)->u_stringify();
\o\v($u_t)->u_ok(\o\v($u_p)->u_contains("<h1>Hello</h1>"), "parse html string - double arrow");
\o\v($u_t)->u_ok(\o\v($u_p)->u_contains("<div class='abc'>123</div>"), "parse html string - dotted");
\o\v($u_t)->u_section("Template Escaping");
\o\v($u_t)->u_ok(\o\v(\o\v(u_ent_html())->u_stringify())->u_contains("&gt;"), "html - entity");
\o\v($u_t)->u_ok(\o\v(\o\v(u_format_block_html())->u_stringify())->u_contains("&lt;foo&gt;"), "html - format block");
$u_h = \o\v(u_exp_html("\"'", "a&b\""))->u_stringify();
\o\v($u_t)->u_ok(\o\v($u_h)->u_contains("<p \"&quot;&#039;\">"), "html - tag attribute");
\o\v($u_t)->u_ok(\o\v($u_h)->u_contains("a&amp;b"), "html - outer");
\o\v($u_t)->u_ok(\o\v(\o\v(u_tags_html(u_in_css()))->u_stringify())->u_contains("<style"), "html - css style block");
\o\v($u_t)->u_ok(\o\v(\o\v(u_tags_html(u_in_js()))->u_stringify())->u_contains("<script"), "html - js block");
\o\v($u_t)->u_ok(\o\v(\o\v(u_tags_html(u_ent_html()))->u_stringify())->u_contains("<p>2 &gt; 1</p>"), "html - embed html");
$u_ls = \o\OLockString::create("html", "<p>a &gt; c</p>");
\o\v($u_t)->u_ok(\o\v(\o\v(u_tags_html($u_ls))->u_stringify())->u_contains("<p>a &gt; c</p>"), "html - LockString");
\o\v($u_t)->u_ok(\o\v(\o\v(u_data_js("string"))->u_stringify())->u_contains("\"string\";"), "js - string");
\o\v($u_t)->u_ok(\o\v(\o\v(u_data_js("a\nb"))->u_stringify())->u_contains("\"a\\nb\";"), "js - string newline");
\o\v($u_t)->u_ok(\o\v(\o\v(u_data_js("a\"b"))->u_stringify())->u_contains("\"a\\\"b\";"), "js - string quote");
\o\v($u_t)->u_ok(\o\v(\o\v(u_data_js(1234))->u_stringify())->u_contains("1234;"), "js - num");
\o\v($u_t)->u_ok(\o\v(\o\v(u_data_js(true))->u_stringify())->u_contains("true;"), "js - bool");
\o\v($u_t)->u_ok(\o\v(\o\v(u_data_js(\o\OMap::create([ 'a' => 1 ])))->u_stringify())->u_contains("{\"a\":1};"), "js - object");
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
\o\v(\o\ModuleManager::getModule('Db'))->u_query(\o\OLockString::create("sql", "CREATE TABLE IF NOT EXISTS test (key, value);"));
\o\v(\o\ModuleManager::getModule('Db'))->u_query(\o\OLockString::create("sql", "delete from test"));
$u_key = \o\Runtime::concat("test", \o\v(\o\ModuleManager::getModule('Math'))->u_random(0, 1000));
\o\v(\o\ModuleManager::getModule('Db'))->u_insert_row("test", \o\OMap::create([ 'key' => $u_key, 'value' => \o\v(\o\ModuleManager::getModule('Date'))->u_now() ]));
$u_rows = \o\v(\o\ModuleManager::getModule('Db'))->u_select_rows(\o\OLockString::create("sql", "select * from test"));
\o\v($u_t)->u_ok((\o\v($u_rows)->u_length() === 1), "Insert & select row");
\o\v($u_t)->u_ok((\o\v(\o\v($u_rows)[0])->u_key === $u_key), "Check inserted row");
$u_dbh = \o\v(\o\ModuleManager::getModule('Db'))->u_use("default");
$u_rows = \o\v($u_dbh)->u_select_rows(\o\OLockString::create("sql", "select * from test"));
\o\v($u_t)->u_ok((\o\v(\o\v($u_rows)[0])->u_key === $u_key), "Db.use");
\o\v(\o\ModuleManager::getModule('Db'))->u_update_rows("test", \o\OMap::create([ 'key' => $u_key, 'value' => "new!" ]), \o\v(\o\OLockString::create("sql", " key = {}"))->u_fill($u_key));
$u_row = \o\v(\o\ModuleManager::getModule('Db'))->u_select_row(\o\v(\o\OLockString::create("sql", "select * from test where key = {}"))->u_fill($u_key));
\o\v($u_t)->u_ok((\o\v($u_row)["value"] === "new!"), "Update row");
\o\v(\o\ModuleManager::getModule('Db'))->u_delete_rows("test", \o\v(\o\OLockString::create("sql", "key = {}"))->u_fill($u_key));
$u_rows = \o\v(\o\ModuleManager::getModule('Db'))->u_select_rows(\o\OLockString::create("sql", "select * from test"));
\o\v($u_t)->u_ok((\o\v($u_rows)->u_length() === 0), "Delete row");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Db'))->u_update_rows("\"bad", \o\OMap::create([ 'key' => $u_key ]), \o\v(\o\OLockString::create("sql", " key = {}"))->u_fill($u_key));
 return new \o\ONothing(__METHOD__);
 
}
, "invalid table name - updateRows");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Db'))->u_delete_rows("\"bad", \o\v(\o\OLockString::create("sql", " key = {}"))->u_fill($u_key));
 return new \o\ONothing(__METHOD__);
 
}
, "invalid table name - deleteRows");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Db'))->u_query("delete from test");
 return new \o\ONothing(__METHOD__);
 
}
, "reject stringify query - query");
\o\v($u_t)->u_dies(function  ()  {
  \o\v(\o\ModuleManager::getModule('Db'))->u_select_rows("select * from test");
 return new \o\ONothing(__METHOD__);
 
}
, "reject stringify query - selectRows");
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
\o\v($u_t)->u_ok(\o\v(\o\v(\o\v($u_d)->u_key_lite)->u_stringify())->u_contains("<h2>"), "Litemark value");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_js ($u_t)  {
  \o\v($u_t)->u_section("Module: Js");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\v(\o\v(\o\ModuleManager::getModule('Js'))->u_plugin("colorCode"))[1])->u_stringify())->u_contains("highlight"), "colorCode");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\v(\o\v(\o\ModuleManager::getModule('Js'))->u_plugin("lazyLoadImages"))[0])->u_stringify())->u_contains("img"), "lazyLoadImages");
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
foreach ($u_res as $u_r) {
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
\o\v($u_t)->u_ok(\o\v(\o\v(\o\ModuleManager::getModule('Php'))->u_version())->u_match(new \o\ORegex("\d+\.\d+\.\d+")), "PHP version");
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
$u_content = \o\v(\o\ModuleManager::getModule('Net'))->u_http_get(\o\OLockString::create("url", "https://tht.help"));
\o\v($u_t)->u_ok(\o\v($u_content)->u_match(\o\v(new \o\ORegex("programming language"))->u_flags("i")), "Net get");
 return new \o\ONothing(__METHOD__);
 
}
function u_lib_system ($u_t)  {
  \o\v($u_t)->u_section("Module: System");
 return new \o\ONothing(__METHOD__);
 
}
function u_template_html ($u_users)  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("<b>Hello</b>");
foreach ($u_users as $u_u) {
$t->addStatic("<li>");
$t->addDynamic("none", $u_u);
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
$t->addDynamic("none", $u_d);
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
$t->addDynamic("tag", \o\v(\o\ModuleManager::getModule('Web'))->u_nonce());
$t->addStatic("\">var a = '&lt;a\\nb\\nc';</script>");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_exp_html ($u_inner, $u_outer)  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("<p ");
$t->addDynamic("tag", $u_inner);
$t->addStatic(">");
$t->addDynamic("none", $u_outer);
$t->addStatic("</p>");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_tags_html ($u_exp)  {
$t = \o\Runtime::openTemplate("Html");
$t->addStatic("
    ");
$t->addDynamic("none", $u_exp);
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
$t->addDynamic("none", $u_lock);
$t->addStatic("</p>");
\o\Runtime::closeTemplate();
return $t->getString();
}
function u_exp_css ($u_inp)  {
$t = \o\Runtime::openTemplate("Css");
$t->addStatic("font-weight:");
$t->addDynamic("none", $u_inp);
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



/* SOURCE={"file":"pages\/home.tht","6":3,"7":5,"8":7,"9":8,"13":11,"15":17,"16":17,"17":27,"18":27,"19":33,"23":35,"24":37,"25":39,"26":40,"27":41,"28":42,"29":43,"30":44,"31":45,"32":46,"33":47,"34":48,"35":49,"36":51,"37":52,"38":53,"39":54,"40":55,"41":56,"42":57,"43":58,"44":59,"45":60,"46":61,"47":62,"48":63,"49":64,"50":66,"51":67,"52":69,"53":73,"54":74,"55":77,"56":78,"60":83,"61":85,"62":87,"63":87,"67":87,"68":89,"69":89,"73":89,"74":91,"75":91,"79":91,"80":93,"81":93,"85":93,"86":95,"87":95,"88":95,"94":97,"95":99,"96":99,"100":99,"104":103,"105":105,"106":107,"107":109,"114":120,"115":121,"116":122,"117":123,"118":124,"119":127,"120":129,"121":130,"122":131,"123":132,"124":133,"125":134,"126":135,"127":136,"128":137,"129":140,"130":142,"131":143,"132":144,"133":145,"134":146,"135":147,"136":148,"137":149,"138":150,"139":151,"140":152,"141":153,"142":154,"143":155,"144":156,"145":157,"146":158,"147":159,"148":162,"149":164,"150":165,"151":166,"152":167,"153":168,"154":169,"155":170,"156":171,"157":172,"158":173,"159":174,"160":175,"161":176,"162":177,"163":178,"164":179,"165":180,"166":181,"167":182,"168":183,"169":184,"170":185,"171":186,"172":187,"173":188,"174":189,"175":190,"176":191,"177":192,"178":193,"179":194,"180":195,"181":196,"182":197,"183":198,"184":199,"185":203,"186":205,"187":206,"188":207,"189":208,"190":209,"191":210,"192":211,"193":212,"194":213,"195":216,"196":218,"197":219,"198":220,"199":221,"200":222,"201":223,"202":224,"203":225,"204":226,"205":227,"206":228,"207":229,"208":230,"209":231,"210":233,"211":234,"212":235,"213":236,"214":237,"215":238,"216":241,"217":243,"218":244,"219":245,"220":246,"221":247,"222":248,"223":249,"224":250,"225":251,"226":252,"227":254,"228":255,"229":256,"230":257,"231":258,"232":259,"233":260,"234":261,"235":262,"236":263,"237":264,"238":265,"239":266,"240":267,"241":268,"242":269,"243":270,"244":271,"245":272,"246":273,"247":274,"248":275,"249":276,"250":277,"251":279,"252":280,"253":281,"254":282,"255":284,"256":285,"257":286,"258":287,"259":292,"260":294,"261":295,"262":296,"263":297,"264":298,"265":299,"266":301,"267":302,"268":303,"269":304,"270":305,"271":307,"272":308,"273":309,"274":312,"275":314,"276":315,"277":316,"278":317,"279":318,"280":319,"281":320,"282":321,"283":324,"284":326,"285":327,"286":328,"287":329,"288":330,"289":331,"290":334,"291":335,"292":336,"293":337,"294":338,"295":339,"296":340,"297":341,"298":342,"299":344,"300":350,"304":353,"305":355,"306":358,"307":359,"308":360,"309":361,"310":362,"311":363,"312":364,"313":365,"316":367,"317":368,"318":369,"319":370,"320":373,"321":379,"322":381,"323":382,"324":383,"325":385,"326":386,"327":387,"328":392,"329":394,"330":396,"331":397,"332":399,"333":400,"334":403,"335":404,"339":409,"340":411,"341":413,"342":415,"343":416,"344":418,"345":419,"346":419,"350":419,"351":421,"352":422,"353":423,"354":424,"355":426,"356":427,"357":428,"358":430,"359":430,"363":430,"364":431,"365":432,"366":433,"367":434,"368":436,"369":437,"370":437,"374":437,"375":439,"376":441,"377":442,"378":444,"379":445,"380":447,"381":449,"382":450,"383":452,"384":452,"388":452,"389":454,"390":455,"391":456,"392":457,"393":458,"394":462,"395":463,"396":465,"397":466,"398":468,"402":472,"403":474,"404":476,"405":477,"406":478,"407":479,"408":480,"409":481,"410":482,"411":483,"412":483,"416":484,"417":488,"418":490,"419":491,"420":492,"421":493,"422":494,"423":495,"424":496,"425":498,"426":499,"427":500,"428":501,"429":502,"430":503,"431":504,"435":509,"436":511,"437":513,"438":514,"442":516,"443":518,"444":519,"448":521,"449":523,"450":524,"454":526,"455":528,"456":529,"457":530,"458":531,"461":533,"465":535,"466":536,"467":541,"468":542,"472":544,"473":545,"474":547,"475":548,"476":550,"477":551,"478":552,"483":554,"484":557,"485":558,"489":560,"490":561,"491":562,"492":564,"493":565,"494":569,"495":570,"499":572,"500":573,"501":574,"502":577,"503":578,"504":579,"509":581,"510":584,"511":584,"514":585,"515":585,"519":585,"520":586,"521":586,"525":586,"526":589,"527":590,"528":592,"529":593,"530":595,"531":596,"532":602,"533":604,"534":605,"535":606,"536":607,"537":608,"538":610,"539":613,"540":613,"544":613,"545":615,"546":615,"550":615,"551":616,"552":616,"556":616,"557":617,"558":617,"562":617,"563":618,"564":618,"568":618,"569":620,"570":621,"571":621,"575":621,"576":623,"577":623,"581":623,"582":624,"583":624,"587":624,"588":625,"589":625,"593":625,"594":627,"595":628,"599":632,"600":634,"601":636,"602":637,"603":638,"604":639,"605":640,"606":641,"607":642,"608":643,"609":644,"610":645,"611":646,"612":647,"613":650,"614":652,"615":654,"616":655,"617":660,"618":662,"619":663,"620":663,"624":663,"625":666,"626":668,"627":669,"628":670,"629":671,"630":672,"631":673,"632":676,"633":678,"634":679,"635":680,"636":682,"637":683,"638":684,"639":687,"640":689,"641":691,"642":692,"643":694,"644":695,"645":697,"646":698,"647":701,"648":702,"649":703,"650":704,"651":705,"652":706,"653":707,"654":710,"655":711,"656":711,"660":711,"661":712,"662":715,"663":716,"664":717,"665":718,"666":719,"667":722,"668":723,"669":724,"670":725,"671":728,"672":730,"673":730,"677":730,"678":731,"679":731,"683":731,"684":733,"685":733,"689":733,"690":734,"691":734,"695":734,"699":738,"700":740,"701":742,"702":743,"703":744,"704":745,"705":746,"706":747,"707":748,"708":749,"709":751,"710":752,"711":753,"712":754,"713":755,"714":756,"715":757,"716":759,"717":760,"718":762,"719":765,"720":767,"721":767,"725":767,"726":768,"727":768,"731":768,"732":769,"733":769,"737":769,"738":770,"739":770,"743":770,"744":771,"745":771,"749":771,"750":772,"751":772,"755":772,"756":773,"757":774,"758":775,"762":775,"763":776,"764":776,"768":776,"769":777,"770":777,"774":777,"775":778,"776":778,"780":778,"781":779,"782":779,"786":779,"787":780,"788":780,"792":780,"793":781,"794":781,"798":781,"799":784,"800":786,"801":787,"802":789,"803":790,"804":794,"805":796,"806":797,"807":798,"808":799,"809":800,"810":801,"811":804,"812":806,"813":807,"814":808,"815":809,"816":812,"817":814,"818":815,"819":816,"820":817,"821":818,"822":819,"823":820,"824":821,"825":823,"826":824,"827":825,"828":826,"829":827,"830":828,"831":830,"832":831,"833":832,"834":833,"835":840,"836":842,"837":843,"838":844,"839":845,"840":846,"841":847,"842":848,"843":849,"844":850,"845":851,"846":852,"847":855,"848":857,"849":858,"850":859,"851":860,"852":861,"853":863,"854":865,"855":866,"856":867,"857":868,"858":871,"859":873,"860":874,"864":879,"865":881,"866":883,"867":885,"868":886,"869":887,"872":889,"873":891,"874":892,"875":893,"878":895,"879":897,"880":898,"881":899,"882":900,"885":902,"886":904,"887":905,"888":906,"889":907,"890":908,"891":909,"892":909,"898":911,"899":913,"900":914,"901":915,"902":916,"903":917,"904":918,"908":920,"909":921,"910":922,"916":925,"917":927,"918":928,"919":931,"920":932,"921":933,"922":934,"923":935,"924":936,"925":937,"926":938,"927":939,"928":940,"929":941,"930":942,"931":943,"932":944,"933":945,"934":946,"935":947,"936":948,"937":949,"938":950,"939":951,"940":952,"941":953,"942":954,"943":955,"944":956,"945":957,"946":958,"947":961,"948":963,"949":965,"950":965,"954":966,"955":968,"956":968,"960":969,"961":971,"962":972,"966":975,"970":977,"971":979,"972":980,"975":982,"976":983,"981":985,"982":987,"983":988,"986":990,"987":991,"990":993,"991":994,"995":997,"1001":999,"1002":1003,"1003":1005,"1004":1006,"1005":1007,"1006":1008,"1009":1010,"1010":1011,"1014":1014,"1018":1017,"1019":1018,"1020":1020,"1021":1021,"1022":1022,"1025":1023,"1026":1024,"1030":1026,"1031":1029,"1032":1031,"1033":1032,"1034":1034,"1038":1037,"1039":1039,"1040":1041,"1041":1043,"1042":1044,"1043":1046,"1044":1048,"1047":1053,"1048":1055,"1049":1056,"1050":1057,"1051":1059,"1052":1061,"1053":1062,"1054":1063,"1055":1064,"1056":1065,"1057":1067,"1058":1068,"1059":1069,"1060":1072,"1061":1074,"1062":1075,"1063":1076,"1064":1077,"1065":1078,"1066":1079,"1067":1080,"1068":1081,"1069":1082,"1070":1083,"1071":1086,"1072":1087,"1073":1088,"1074":1089,"1075":1091,"1076":1092,"1077":1095,"1078":1097,"1079":1098,"1080":1100,"1081":1101,"1082":1103,"1083":1104,"1084":1105,"1085":1107,"1086":1108,"1087":1110,"1088":1111,"1089":1113,"1090":1114,"1091":1115,"1092":1116,"1093":1118,"1094":1119,"1095":1120,"1096":1122,"1097":1123,"1098":1124,"1099":1126,"1100":1127,"1101":1129,"1102":1130,"1103":1132,"1104":1133,"1105":1134,"1106":1135,"1107":1136,"1108":1138,"1109":1139,"1110":1140,"1111":1141,"1112":1142,"1113":1143,"1114":1145,"1115":1146,"1116":1148,"1117":1149,"1118":1151,"1119":1152,"1120":1154,"1121":1155,"1122":1157,"1123":1158,"1124":1159,"1125":1161,"1126":1162,"1127":1163,"1128":1164,"1129":1166,"1130":1167,"1131":1169,"1132":1170,"1133":1171,"1134":1172,"1135":1173,"1136":1174,"1137":1176,"1138":1178,"1142":1186,"1143":1187,"1144":1188,"1145":1190,"1146":1191,"1147":1193,"1148":1194,"1149":1196,"1150":1198,"1151":1199,"1152":1200,"1153":1201,"1154":1203,"1155":1204,"1156":1205,"1157":1207,"1158":1208,"1159":1209,"1160":1211,"1161":1212,"1162":1213,"1163":1220,"1164":1221,"1165":1223,"1166":1224,"1167":1225,"1168":1226,"1169":1229,"1170":1231,"1171":1232,"1172":1233,"1173":1235,"1174":1236,"1175":1237,"1176":1239,"1177":1240,"1178":1241,"1179":1242,"1180":1244,"1181":1245,"1182":1246,"1183":1247,"1184":1249,"1185":1250,"1186":1251,"1187":1252,"1188":1254,"1189":1255,"1190":1256,"1191":1257,"1192":1259,"1193":1260,"1194":1261,"1195":1262,"1196":1263,"1197":1265,"1198":1266,"1199":1267,"1200":1268,"1201":1270,"1202":1271,"1203":1272,"1204":1273,"1205":1274,"1206":1276,"1207":1277,"1208":1278,"1209":1279,"1210":1280,"1211":1282,"1212":1283,"1213":1284,"1214":1285,"1215":1286,"1216":1287,"1217":1288,"1218":1290,"1219":1291,"1220":1292,"1221":1293,"1222":1296,"1223":1298,"1224":1299,"1225":1300,"1226":1301,"1227":1302,"1228":1305,"1229":1306,"1230":1307,"1231":1310,"1232":1312,"1233":1313,"1234":1314,"1235":1314,"1239":1314,"1240":1316,"1241":1317,"1242":1319,"1243":1320,"1244":1322,"1245":1323,"1246":1325,"1247":1326,"1248":1328,"1249":1330,"1250":1331,"1251":1334,"1252":1336,"1253":1337,"1254":1339,"1255":1339,"1259":1339,"1260":1340,"1261":1340,"1265":1340,"1266":1342,"1267":1343,"1268":1344,"1269":1345,"1270":1346,"1271":1348,"1272":1349,"1273":1351,"1274":1352,"1275":1353,"1276":1355,"1277":1356,"1278":1358,"1279":1359,"1280":1360,"1281":1361,"1282":1362,"1283":1363,"1284":1364,"1285":1365,"1286":1367,"1287":1369,"1288":1372,"1289":1374,"1290":1375,"1291":1376,"1292":1377,"1293":1378,"1294":1379,"1295":1380,"1296":1381,"1297":1383,"1298":1384,"1299":1386,"1300":1387,"1301":1389,"1302":1390,"1303":1392,"1307":1395,"1309":1396,"1310":1396,"1311":1397,"1315":1399,"1316":1401,"1317":1403,"1318":1404,"1319":1405,"1320":1406,"1321":1407,"1322":1408,"1323":1409,"1324":1410,"1325":1412,"1326":1414,"1327":1415,"1328":1417,"1329":1418,"1330":1423,"1331":1426,"1332":1431,"1333":1432,"1334":1433,"1335":1434,"1336":1436,"1337":1439,"1338":1441,"1339":1442,"1340":1444,"1341":1445,"1342":1447,"1343":1448,"1344":1449,"1345":1450,"1346":1452,"1347":1453,"1348":1455,"1349":1456,"1350":1457,"1351":1459,"1352":1460,"1353":1462,"1354":1463,"1355":1464,"1356":1466,"1357":1467,"1358":1468,"1359":1472,"1360":1473,"1361":1474,"1362":1478,"1363":1479,"1364":1480,"1365":1484,"1366":1485,"1367":1489,"1368":1492,"1369":1493,"1370":1495,"1371":1495,"1376":1496,"1377":1498,"1378":1499,"1379":1501,"1380":1502,"1381":1504,"1382":1505,"1383":1507,"1384":1508,"1385":1510,"1386":1510,"1390":1510,"1391":1512,"1392":1513,"1393":1516,"1394":1518,"1395":1518,"1399":1518,"1400":1519,"1401":1519,"1405":1519,"1406":1520,"1407":1520,"1411":1520,"1412":1521,"1413":1521,"1417":1521,"1418":1522,"1419":1522,"1423":1522,"1424":1525,"1425":1527,"1426":1529,"1427":1530,"1428":1531,"1429":1534,"1430":1536,"1431":1536,"1435":1536,"1436":1537,"1437":1537,"1441":1537,"1442":1538,"1443":1538,"1447":1538,"1448":1540,"1449":1541,"1450":1542,"1451":1544,"1452":1545,"1453":1545,"1457":1545,"1458":1546,"1459":1548,"1460":1549,"1461":1549,"1465":1549,"1466":1550,"1467":1551,"1468":1551,"1472":1551,"1473":1552,"1477":1555,"1478":1557,"1479":1559,"1480":1560,"1481":1561,"1482":1562,"1483":1564,"1484":1565,"1485":1566,"1486":1567,"1487":1571,"1488":1573,"1489":1574,"1490":1576,"1491":1577,"1492":1578,"1493":1580,"1494":1581,"1495":1582,"1496":1584,"1497":1585,"1498":1587,"1499":1588,"1500":1589,"1501":1591,"1502":1592,"1503":1593,"1507":1599,"1508":1601,"1509":1603,"1510":1604,"1511":1605,"1512":1607,"1513":1609,"1514":1610,"1515":1612,"1516":1613,"1517":1614,"1518":1615,"1522":1619,"1523":1621,"1524":1623,"1525":1623,"1529":1623,"1530":1624,"1531":1624,"1535":1624,"1536":1626,"1537":1627,"1538":1628,"1539":1630,"1540":1631,"1541":1633,"1542":1634,"1546":1636,"1547":1637,"1548":1639,"1549":1641,"1550":1642,"1551":1643,"1552":1644,"1553":1646,"1554":1648,"1555":1649,"1556":1650,"1557":1651,"1558":1656,"1559":1657,"1560":1659,"1561":1660,"1565":1665,"1566":1667,"1567":1669,"1568":1670,"1569":1671,"1570":1672,"1571":1673,"1572":1674,"1573":1675,"1574":1676,"1575":1677,"1579":1680,"1580":1682,"1581":1684,"1582":1685,"1583":1687,"1584":1688,"1585":1690,"1586":1691,"1587":1692,"1588":1694,"1589":1695,"1590":1696,"1591":1698,"1592":1699,"1593":1700,"1594":1702,"1595":1703,"1596":1704,"1597":1706,"1598":1707,"1602":1708,"1603":1710,"1604":1711,"1608":1712,"1609":1714,"1610":1715,"1614":1716,"1615":1718,"1616":1719,"1620":1720,"1624":1723,"1625":1725,"1626":1727,"1627":1728,"1628":1730,"1629":1731,"1630":1733,"1631":1734,"1632":1736,"1633":1737,"1634":1739,"1635":1740,"1636":1742,"1637":1743,"1638":1744,"1639":1746,"1640":1747,"1641":1749,"1642":1750,"1646":1753,"1647":1755,"1648":1757,"1649":1758,"1650":1759,"1654":1762,"1655":1764,"1656":1766,"1657":1767,"1658":1768,"1659":1769,"1660":1770,"1661":1772,"1662":1773,"1663":1774,"1664":1775,"1665":1777,"1666":1778,"1670":1781,"1671":1783,"1675":1787,"1676":1789,"1677":1791,"1678":1792,"1679":1793,"1680":1795,"1681":1796,"1682":1798,"1683":1799,"1684":1800,"1685":1802,"1686":1803,"1687":1804,"1688":1806,"1689":1807,"1690":1809,"1691":1810,"1692":1812,"1693":1813,"1697":1834,"1698":1835,"1699":1837,"1700":1838,"1701":1844,"1702":1845,"1706":1848,"1707":1849,"1708":1850,"1712":1853,"1713":1854,"1717":1857,"1719":1858,"1721":1858,"1722":1859,"1726":1862,"1727":1863,"1728":1865,"1729":1866,"1730":1867,"1731":1868,"1732":1870,"1733":1871,"1734":1872,"1735":1873,"1736":1874,"1737":1875,"1743":1878,"1744":1880,"1748":1883,"1749":1885,"1750":1887,"1751":1889,"1752":1890,"1753":1892,"1754":1893,"1755":1893,"1759":1893,"1760":1894,"1761":1894,"1765":1894,"1766":1895,"1767":1895,"1771":1895,"1772":1898,"1773":1900,"1774":1901,"1775":1902,"1776":1903,"1777":1904,"1778":1906,"1779":1907,"1780":1908,"1784":1913,"1785":1914,"1789":1917,"1790":1918,"1791":1920,"1792":1922,"1796":1925,"1797":1926,"1801":1930,"1802":1932,"1803":1939,"1804":1942,"1805":1942,"1809":1942,"1810":1943,"1811":1943,"1815":1943,"1816":1944,"1817":1944,"1821":1944,"1822":1945,"1823":1945,"1827":1945,"1828":1947,"1829":1952,"1830":1953,"1831":1955,"1832":1956,"1833":1957,"1834":1958,"1835":1959,"1836":1961,"1837":1962,"1838":1963,"1839":1964,"1840":1965,"1841":1967,"1842":1968,"1843":1969,"1844":1970,"1845":1979,"1846":1980,"1847":1981,"1848":1983,"1849":1984,"1850":1985,"1851":1987,"1852":1987,"1856":1987,"1860":1991,"1861":1992,"1865":1995,"1866":1997,"1867":1999,"1868":2000,"1869":2001,"1870":2002,"1871":2003,"1872":2004,"1873":2006,"1874":2006,"1878":2006,"1882":2009,"1883":2011,"1884":2013,"1885":2015,"1886":2016,"1887":2019,"1888":2020,"1889":2021,"1893":2026,"1894":2028,"1895":2030,"1896":2032,"1897":2033,"1898":2034,"1899":2035,"1900":2037,"1901":2039,"1902":2040,"1903":2042,"1904":2043,"1905":2044,"1906":2046,"1907":2047,"1908":2049,"1909":2050,"1910":2052,"1911":2053,"1912":2055,"1913":2056,"1914":2058,"1915":2059,"1916":2061,"1917":2062,"1918":2064,"1919":2064,"1923":2064,"1927":2068,"1928":2070,"1929":2072,"1930":2073,"1931":2075,"1932":2076,"1933":2078,"1934":2080,"1935":2081,"1936":2083,"1937":2084,"1938":2086,"1939":2087,"1940":2088,"1941":2089,"1942":2091,"1943":2094,"1944":2095,"1945":2096,"1946":2097,"1947":2098,"1948":2099,"1949":2100,"1950":2102,"1951":2103,"1952":2104,"1956":2107,"1957":2109,"1958":2111,"1959":2112,"1963":2117,"1964":2119,"1968":2128,"1970":2131,"1971":2131,"1972":2132,"1973":2132,"1974":2133,"1977":2135,"1982":2137,"1984":2138,"1985":2138,"1986":2139,"1990":2141,"1992":2143,"1996":2145,"1998":2149,"2002":2151,"2004":2153,"2010":2155,"2012":2156,"2013":2156,"2014":2159,"2018":2161,"2020":2162,"2021":2162,"2022":2162,"2023":2162,"2024":2163,"2028":2165,"2030":2166,"2032":2166,"2033":2167,"2038":2169,"2040":2171,"2044":2173,"2046":2175,"2050":2177,"2052":2178,"2053":2178,"2054":2179,"2058":2181,"2060":2182,"2061":2182,"2062":2183,"2066":2189,"2067":2190,"2071":2193,"2072":2195,"2075":2197,"2077":2199,"2081":2202,"2083":2204,"2087":2207,"2090":2208,"2091":2209,"2095":2212,"2096":2213} */

?>