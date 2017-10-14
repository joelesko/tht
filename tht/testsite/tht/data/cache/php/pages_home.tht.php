<?php

namespace tht59e27d3b5b6d5;
\o\Runtime::setNameSpace('pages/home.tht','tht59e27d3b5b6d5');

function u_main ()  {
 $u_test = \o\Runtime::getModule(__NAMESPACE__, 'Test')->u_new();
u_run($u_test);
\o\Runtime::getModule(__NAMESPACE__, 'Web')->u_send_html(u_html(\o\v($u_test)->u_results_html()));
 return \o\Runtime::void(__METHOD__);
}
function u_html ($u_results)  {
$t = \o\Runtime::openTemplate("html");
$t->addStatic("<!-- this is a comment --><html><head><title>THT Unit Tests</title>");
$t->addDynamic(\o\Runtime::getModule(__NAMESPACE__, 'Css')->u_include("base"));
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
$u_long_comment = \o\Runtime::concat(\o\Runtime::concat("// ", \o\Runtime::getModule(__NAMESPACE__, 'String')->u_repeat("a", 102)), "\n");
\o\v($u_t)->u_parser_ok($u_long_comment, "line comment over 100 chars");
$u_long_block_comment = \o\Runtime::concat(\o\Runtime::concat("/*\n", \o\Runtime::getModule(__NAMESPACE__, 'String')->u_repeat("a", 102)), "\n*/");
\o\v($u_t)->u_parser_ok($u_long_block_comment, "block comment over 100 chars");
\o\v($u_t)->u_section("Parser Errors - Names");
\o\v($u_t)->u_parser_error("let FOO = 3;", "camelCase");
\o\v($u_t)->u_parser_error("let fOO = 3;", "camelCase");
\o\v($u_t)->u_parser_error("let XMLreader = {};", "camelCase");
\o\v($u_t)->u_parser_error("let a_b = 3;", "camelCase");
\o\v($u_t)->u_parser_error("function FOO() {}", "camelCase");
\o\v($u_t)->u_parser_error("function a () {}", "longer than 1");
$u_long_name = \o\Runtime::getModule(__NAMESPACE__, 'String')->u_repeat("a", 41);
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
$u_now = \o\Runtime::getModule(__NAMESPACE__, 'Date')->u_now(true);
$u_nums = \o\OBare::u_range(1, 100000);
$u_ii = 0;
foreach (\o\uv($u_nums) as $u_nn) {
$u_b = \o\v($u_nums)[$u_ii];
$u_ii += \o\vn(1, 1);

}
\o\v($u_t)->u_ok(($u_ii === 100000), "large loop done");
$u_elapsed = (\o\vn(\o\Runtime::getModule(__NAMESPACE__, 'Date')->u_now(true), 0) - \o\vn($u_now, 0));
\o\v($u_t)->u_ok((\o\vn($u_elapsed, 0) < \o\vn(1000, 0)), \o\v("ArrayAccess loop (100,000) took {0} ms")->u_fill($u_elapsed));
\o\v($u_t)->u_section("Functional Methods");
\o\v($u_t)->u_section("Result Objects");
$u_st = \o\Runtime::getModule(__NAMESPACE__, 'Result')->u_ok(123);
\o\v($u_t)->u_ok(\o\v($u_st)->u_ok(), "not ok");
\o\v($u_t)->u_ok((\o\v($u_st)->u_get() === 123), "ok value");
$u_st = \o\Runtime::getModule(__NAMESPACE__, 'Result')->u_fail(66);
\o\v($u_t)->u_ok((! \o\v($u_st)->u_ok()), "not ok");
\o\v($u_t)->u_ok((\o\v($u_st)->u_fail_code() === 66), "failCode");
\o\v($u_t)->u_section("Modules");
\o\OBare::u_import(__NAMESPACE__, "TestModule");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'TestModule')->u_bare_fun("Joe") === "bare:Joe"), "module call");
\o\Runtime::getModule(__NAMESPACE__, 'Global')->u_foo = "BAR";
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'TestModule')->u_test_global() === "global:BAR"), "module global");
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
foreach (\o\uv(\o\Runtime::getModule(__NAMESPACE__, 'Meta')->u_arguments()) as $u_arg) {
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
\o\Runtime::getModule(__NAMESPACE__, 'File')->u_read("sdfsdfsdf");

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
\o\v($u_t)->u_ok((\o\Runtime::concat(\o\Runtime::getModule(__NAMESPACE__, 'String')->u_char_from_code(65), \o\Runtime::getModule(__NAMESPACE__, 'String')->u_char_from_code(122)) === "Az"), "String.fromCharCode");
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
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'String')->u_char_from_code(9400) === "Ⓒ"), "charFromCode");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'String')->u_char_from_code(65) === "A"), "charFromCode, ascii");
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
$u_rx = \o\Runtime::getModule(__NAMESPACE__, 'Regex')->u_new(\o\v("'{0}'")->u_fill("world"), "i");
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
\o\v($u_t)->u_ok((\o\v(\o\v($u_ary)->u_add(40))[3] === 40), "add");
\o\v($u_t)->u_ok((\o\v($u_ary)->u_remove() === 40), "remove");
\o\v($u_t)->u_ok((\o\v(\o\v($u_ary)->u_add((- 10), 0))[0] === (- 10)), "add index 0");
\o\v($u_t)->u_ok((\o\v($u_ary)->u_remove(0) === (- 10)), "remove index 0");
$u_ary = \o\OList::create([ 1, 2, 3 ]);
\o\v($u_t)->u_ok((\o\v(\o\v($u_ary)->u_add(40, (- 1)))[3] === 40), "add index -1");
\o\v($u_ary)->u_remove();
\o\v($u_t)->u_ok((\o\v(\o\v($u_ary)->u_add(40, (- 2)))[2] === 40), "add index -2");
\o\v($u_t)->u_ok((\o\v(\o\OList::create([ 0, 1, 2 ]))->u_remove((- 1)) === 2), "remove index -1");
\o\v($u_t)->u_ok((\o\v(\o\OList::create([ 0, 1, 2 ]))->u_remove((- 2)) === 1), "remove index -2");
$u_ary = \o\OList::create([ 1, 2, 3 ]);
\o\v($u_ary)->u_remove();
\o\v($u_t)->u_ok((\o\v($u_ary)->u_length() === 2), "length after remove");
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
$u_p = \o\Runtime::getModule(__NAMESPACE__, 'Web')->u_parse_html(new \o\OLockString ("<h1>> Hello
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
 \o\Runtime::getModule(__NAMESPACE__, 'File')->u_exists("../bad.txt");
 return \o\Runtime::void(__METHOD__);
}
, "parent shortcut (..)");
\o\v($u_t)->u_dies(function  ()  {
 \o\Runtime::getModule(__NAMESPACE__, 'File')->u_read("http://yahoo.com");
 return \o\Runtime::void(__METHOD__);
}
, "stop remote file read");
\o\v($u_t)->u_ok((! \o\Runtime::getModule(__NAMESPACE__, 'File')->u_exists("sdf/sdf")), "Missing file does not exist");
\o\v($u_t)->u_ok((! \o\Runtime::getModule(__NAMESPACE__, 'File')->u_is_file("sdf/sdf")), "Missing path is not a file");
\o\v($u_t)->u_ok((! \o\Runtime::getModule(__NAMESPACE__, 'File')->u_is_dir("sdf/sdf")), "Missing path is not a dir");
$u_f = "testFile.txt";
$u_d = "testDir";
\o\Runtime::getModule(__NAMESPACE__, 'File')->u_make_dir($u_d);
\o\v($u_t)->u_ok(\o\Runtime::getModule(__NAMESPACE__, 'File')->u_is_dir($u_d), "make dir");
$u_p = \o\Runtime::getModule(__NAMESPACE__, 'File')->u_path(\o\OList::create([ $u_d, $u_f ]));
\o\Runtime::getModule(__NAMESPACE__, 'File')->u_write($u_p, "12345");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'File')->u_get_size($u_p) === 5), "File size");
\o\v($u_t)->u_ok(\o\Runtime::getModule(__NAMESPACE__, 'File')->u_exists($u_p), "File exists");
\o\v($u_t)->u_ok(\o\Runtime::getModule(__NAMESPACE__, 'File')->u_is_file($u_p), "File is file");
$u_info = \o\Runtime::getModule(__NAMESPACE__, 'File')->u_path_info($u_p);
\o\v($u_t)->u_ok((\o\v(\o\v($u_info)->u_dir_list)->u_last() === $u_d), "Path info dirList has parent dir");
\o\v($u_t)->u_ok((\o\v($u_info)->u_file_ext === "txt"), "Path info extension");
\o\v($u_t)->u_ok((\o\v($u_info)->u_file_name === "testFile.txt"), "Path info fileName");
\o\v($u_t)->u_ok((\o\v($u_info)->u_file_name_short === "testFile"), "Path info shortFileName");
\o\Runtime::getModule(__NAMESPACE__, 'File')->u_delete($u_p);
\o\v($u_t)->u_ok((! \o\Runtime::getModule(__NAMESPACE__, 'File')->u_exists($u_p)), "File deleted");
\o\Runtime::getModule(__NAMESPACE__, 'File')->u_delete_dir($u_d);
\o\v($u_t)->u_ok((! \o\Runtime::getModule(__NAMESPACE__, 'File')->u_exists($u_d)), "Dir deleted");
 return \o\Runtime::void(__METHOD__);
}
function u_lib_date ($u_t)  {
 \o\v($u_t)->u_section("Module: Date");
\o\v($u_t)->u_ok((\o\vn(\o\Runtime::getModule(__NAMESPACE__, 'Date')->u_now(), 0) > \o\vn(1490000000, 0)), "Date.now");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Date')->u_minutes(3) === 180), "minutes");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Date')->u_hours(2) === 7200), "hours");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Date')->u_days(3) === 259200), "days");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Date')->u_to_minutes(90) === 1.5), "inMinutes");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Date')->u_to_hours(7200) === 2), "inHours");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Date')->u_to_days(259200) === 3), "inDays");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Date')->u_format("%Y-%m-%d %H:%M:%S", 1400000000) === "2014-05-13 09:53:20"), "Date.format");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Date')->u_difference(100, 280) === "3 minutes"), "Date.difference");
 return \o\Runtime::void(__METHOD__);
}
function u_lib_db ($u_t)  {
 \o\v($u_t)->u_section("Module: Database");
\o\Runtime::getModule(__NAMESPACE__, 'Db')->u_query(new \o\OLockString ("delete from test"));
$u_key = \o\Runtime::concat("test", \o\Runtime::getModule(__NAMESPACE__, 'Math')->u_random(0, 1000));
\o\Runtime::getModule(__NAMESPACE__, 'Db')->u_insert_row("test", \o\OMap::create([ 'key' => $u_key, 'value' => \o\Runtime::getModule(__NAMESPACE__, 'Date')->u_now() ]));
$u_rows = \o\Runtime::getModule(__NAMESPACE__, 'Db')->u_select_rows(new \o\OLockString ("select * from test"));
\o\v($u_t)->u_ok((\o\v($u_rows)->u_length() === 1), "Insert & select row");
\o\v($u_t)->u_ok((\o\v(\o\v($u_rows)[0])->u_key === $u_key), "Check inserted row");
$u_dbh = \o\Runtime::getModule(__NAMESPACE__, 'Db')->u_use("default");
$u_rows = \o\v($u_dbh)->u_select_rows(new \o\OLockString ("select * from test"));
\o\v($u_t)->u_ok((\o\v(\o\v($u_rows)[0])->u_key === $u_key), "Db.use");
\o\Runtime::getModule(__NAMESPACE__, 'Db')->u_update_rows("test", \o\OMap::create([ 'key' => $u_key, 'value' => "new!" ]), \o\v(new \o\OLockString (" key = {0}"))->u_fill($u_key));
$u_row = \o\Runtime::getModule(__NAMESPACE__, 'Db')->u_select_row(\o\v(new \o\OLockString ("select * from test where key = {0}"))->u_fill($u_key));
\o\v($u_t)->u_ok((\o\v($u_row)->u_value === "new!"), "Update row");
\o\Runtime::getModule(__NAMESPACE__, 'Db')->u_delete_rows("test", \o\v(new \o\OLockString ("key = {0}"))->u_fill($u_key));
$u_rows = \o\Runtime::getModule(__NAMESPACE__, 'Db')->u_select_rows(new \o\OLockString ("select * from test"));
\o\v($u_t)->u_ok((\o\v($u_rows)->u_length() === 0), "Delete row");
 return \o\Runtime::void(__METHOD__);
}
function u_lib_jcon_test ($u_t)  {
 \o\v($u_t)->u_section("Module: Jcon");
$u_d = \o\Runtime::getModule(__NAMESPACE__, 'Jcon')->u_parse("{\nkey: value\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key === "value"), "string value");
$u_d = \o\Runtime::getModule(__NAMESPACE__, 'Jcon')->u_parse("{\nkey: true\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key === true), "true value");
$u_d = \o\Runtime::getModule(__NAMESPACE__, 'Jcon')->u_parse("{\nkeyA: valA\nkeyB: valB\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key_b === "valB"), "2nd key");
$u_d = \o\Runtime::getModule(__NAMESPACE__, 'Jcon')->u_parse("{\nkey: false\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key === false), "false value");
$u_d = \o\Runtime::getModule(__NAMESPACE__, 'Jcon')->u_parse("{\nkey: 1234.5\n}\n");
\o\v($u_t)->u_ok((\o\v($u_d)->u_key === 1234.5), "num value");
$u_d = \o\Runtime::getModule(__NAMESPACE__, 'Jcon')->u_parse("{\nkey: [\nv1\nv2\nv3\n]\n}\n");
\o\v($u_t)->u_ok((\o\v(\o\v($u_d)->u_key)->u_length() === 3), "list value");
\o\v($u_t)->u_ok((\o\v(\o\v($u_d)->u_key)[2] === "v3"), "list value");
$u_d = \o\Runtime::getModule(__NAMESPACE__, 'Jcon')->u_parse("{\nkey: '''\nThis is\nmultiline\n'''\n}\n");
\o\v($u_t)->u_ok(\o\v(\o\v($u_d)->u_key)->u_contains("\nmultiline"), "multiline value");
$u_d = \o\Runtime::getModule(__NAMESPACE__, 'Jcon')->u_parse("{\nkeyLite: '''\n## Heading!\n'''\n}\n");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\v($u_d)->u_key_lite)->u_unlocked())->u_contains("<h2>"), "Litemark value");
 return \o\Runtime::void(__METHOD__);
}
function u_lib_js ($u_t)  {
 \o\v($u_t)->u_section("Module: Js");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Js')->u_plugin("colorCode"))->u_unlocked())->u_contains("highlight"), "colorCode");
\o\v($u_t)->u_ok(\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Js')->u_plugin("lazyLoadImages"))->u_unlocked())->u_contains("img"), "lazyLoadImages");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Js')->u_minify("/* comment */\n\nhello\n    \n") === "hello"), "minify");
 return \o\Runtime::void(__METHOD__);
}
function u_lib_json ($u_t)  {
 \o\v($u_t)->u_section("Module: Json");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Json')->u_decode("{\"k1\":[123,\"hello\"]}"))["k1"])[1] === "hello"), "decode sub-list");
\o\v($u_t)->u_ok((\o\v(\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Json')->u_decode("{\"k1\":{\"k2\":\"hello\"}}"))["k1"])["k2"] === "hello"), "decode sub-map");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Json')->u_decode("[1,2,3]"))[1] === 2), "decode list");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Json')->u_decode("true") === true), "decode boolean");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Json')->u_decode("123.45") === 123.45), "decode number");
$u_st = \o\Runtime::getModule(__NAMESPACE__, 'Json')->u_encode(\o\OMap::create([ 'a' => "hi", 'b' => \o\OList::create([ 1, 2, 3 ]) ]));
\o\v($u_t)->u_ok(\o\v($u_st)->u_contains("\"hi\""), "encode string");
\o\v($u_t)->u_ok(\o\v($u_st)->u_contains("[1,2,3]"), "encode list");
\o\v($u_t)->u_ok(\o\v($u_st)->u_contains("\"b\":"), "encode key");
$u_obj = \o\Runtime::getModule(__NAMESPACE__, 'Json')->u_decode($u_st);
\o\v($u_t)->u_ok((\o\v(\o\v($u_obj)->u_b)[1] === 2), "decode after encode");
 return \o\Runtime::void(__METHOD__);
}
function u_lib_litemark ($u_t)  {
 \o\v($u_t)->u_section("Module: Litemark");
 return \o\Runtime::void(__METHOD__);
}
function u_lib_math ($u_t)  {
 \o\v($u_t)->u_section("Module: Math");
$u_rand = \o\Runtime::getModule(__NAMESPACE__, 'Math')->u_random(6, 8);
\o\v($u_t)->u_ok(((\o\vn($u_rand, 0) >= \o\vn(6, 0)) && (\o\vn($u_rand, 0) <= \o\vn(8, 0))), "random");
$u_rnd = \o\Runtime::getModule(__NAMESPACE__, 'Math')->u_random();
\o\v($u_t)->u_ok(((\o\vn($u_rnd, 0) >= \o\vn(0, 0)) && (\o\vn($u_rnd, 0) < \o\vn(1, 0))), "random float");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Math')->u_round(\o\Runtime::getModule(__NAMESPACE__, 'Math')->u_pi(), 2) === 3.14), "rounded pi");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Math')->u_clamp(5, 1, 10) === 5), "clamp in boundary");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Math')->u_clamp(20, 1, 10) === 10), "clamp max");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Math')->u_clamp((- 20), 1, 10) === 1), "clamp min");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Math')->u_min(1, 3, 5) === 1), "min");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Math')->u_min(\o\OList::create([ 1, 3, 5 ])) === 1), "min list");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Math')->u_max(1, 3, 5) === 5), "max");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Math')->u_max(\o\OList::create([ 1, 3, 5 ])) === 5), "max list");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Math')->u_convert_base(21, 10, 2) === "10101"), "convertBase: dec to bin");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Math')->u_convert_base("1af9", 16, 10) === 6905), "convertBase: hex to dec");
 return \o\Runtime::void(__METHOD__);
}
function u_lib_meta ($u_t)  {
 \o\v($u_t)->u_section("Module: Meta");
\o\v($u_t)->u_ok(\o\Runtime::getModule(__NAMESPACE__, 'Meta')->u_function_exists("libMeta"), "functionExists");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Meta')->u_call_function("metaCallMe", \o\OList::create([ "a", "b" ])) === "a|b"), "callFunction & arguments");
\o\v($u_t)->u_ok(\o\Runtime::getModule(__NAMESPACE__, 'Meta')->u_no_template_mode(), "noTemplateMode ok");
\o\v($u_t)->u_dies(function  ()  {
 u_fail_mode_html();
 return \o\Runtime::void(__METHOD__);
}
, "noTemplateMode fail");
\o\v($u_t)->u_ok(\o\Runtime::getModule(__NAMESPACE__, 'Meta')->u_function_exists("dynamicFunction"), "dynamic function exists");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Meta')->u_call_function("dynamicFunction", \o\OList::create([ "Hey" ])) === "Hey!!!"), "call dynamic function");
 return \o\Runtime::void(__METHOD__);
}
function u_meta_call_me ()  {
 $u_args = \o\Runtime::getModule(__NAMESPACE__, 'Meta')->u_arguments();
return \o\v($u_args)->u_join("|");
 return \o\Runtime::void(__METHOD__);
}
function u_fail_template_mode ()  {
 \o\Runtime::getModule(__NAMESPACE__, 'Meta')->u_no_template_mode();
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
\o\Runtime::getModule(__NAMESPACE__, 'Perf')->u_force_active(true);
\o\Runtime::getModule(__NAMESPACE__, 'Perf')->u_start("testPerf");
\o\Runtime::getModule(__NAMESPACE__, 'System')->u_sleep(1);
\o\Runtime::getModule(__NAMESPACE__, 'Perf')->u_stop(true);
$u_res = \o\Runtime::getModule(__NAMESPACE__, 'Perf')->u_results(true);
$u_found = false;
foreach (\o\uv($u_res) as $u_r) {
if ((\o\v($u_r)->u_task === "testPerf")) {
$u_found = true;
break;

}


}
\o\v($u_t)->u_ok($u_found, "Perf task & results");
\o\Runtime::getModule(__NAMESPACE__, 'Perf')->u_force_active(false);
 return \o\Runtime::void(__METHOD__);
}
function u_lib_php ($u_t)  {
 \o\v($u_t)->u_section("Module: Php");
$u_fl = \o\Runtime::getModule(__NAMESPACE__, 'Php')->u_options(\o\OList::create([ "PATHINFO_FILENAME", "PATHINFO_BASENAME" ]));
\o\v($u_t)->u_ok(($u_fl === 10), "PHP - constant flags");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Php')->u_call(new \o\OLockString ("strrev"), \o\OList::create([ "abcdef" ])) === "fedcba"), "call");
\o\v($u_t)->u_dies(function  ()  {
 \o\Runtime::getModule(__NAMESPACE__, 'Php')->u_call(new \o\OLockString ("nonexistent"), \o\OList::create([ 1, 2 ]));
 return \o\Runtime::void(__METHOD__);
}
, "Non-existent PHP call");
\o\v($u_t)->u_dies(function  ()  {
 \o\Runtime::getModule(__NAMESPACE__, 'Php')->u_call(new \o\OLockString ("eval"));
 return \o\Runtime::void(__METHOD__);
}
, "stop blacklisted function");
 return \o\Runtime::void(__METHOD__);
}
function u_lib_test ($u_t)  {
 \o\v($u_t)->u_section("Module: Test");
 return \o\Runtime::void(__METHOD__);
}
function u_lib_global ($u_t)  {
 \o\v($u_t)->u_section("Module: Global");
u_set_globals();
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Global')->u_hello === "world"), "global set");
 return \o\Runtime::void(__METHOD__);
}
function u_set_globals ()  {
 \o\Runtime::getModule(__NAMESPACE__, 'Global')->u_hello = "world";
 return \o\Runtime::void(__METHOD__);
}
function u_lib_web ($u_t)  {
 \o\v($u_t)->u_section("Module: Web");
\o\v($u_t)->u_dies(function  ()  {
 \o\Runtime::getModule(__NAMESPACE__, 'Web')->u_redirect("http://google.com");
 return \o\Runtime::void(__METHOD__);
}
, "redirect - normal");
\o\v($u_t)->u_dies(function  ()  {
 \o\Runtime::getModule(__NAMESPACE__, 'Web')->u_redirect("mailto:google.com");
 return \o\Runtime::void(__METHOD__);
}
, "redirect - mailto");
\o\v($u_t)->u_dies(function  ()  {
 \o\Runtime::getModule(__NAMESPACE__, 'Web')->u_redirect("//google.com");
 return \o\Runtime::void(__METHOD__);
}
, "redirect - no protocol");
\o\v($u_t)->u_dies(function  ()  {
 \o\Runtime::getModule(__NAMESPACE__, 'Web')->u_redirect("bob@ftp://google.com");
 return \o\Runtime::void(__METHOD__);
}
, "redirect - ftp & username");
\o\v($u_t)->u_section("Module: Web - Form Input");
\o\v($u_t)->u_ok((u_form_validate("123") === "123"), "default token ok");
\o\v($u_t)->u_ok((u_form_validate("\$foo") === ""), "token not ok");
\o\v($u_t)->u_ok((u_form_validate("123", "id") === 123), "id ok");
\o\v($u_t)->u_ok((u_form_validate("\$foo", "id") === ""), "id not ok");
\o\v($u_t)->u_ok((u_form_validate("-123.45", "number") === (- 123.45)), "number ok");
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
\o\v($u_t)->u_ok((u_form_validate("abc  123!", "textarea") === "abc 123!"), "textarea spaces");
\o\v($u_t)->u_ok((u_form_validate("abc<b>tag", "textarea") === "abctag"), "textarea no tag");
\o\v($u_t)->u_ok((u_form_validate("abc\n\n\nline2", "textarea") === "abc\n\nline2"), "textarea newline");
 return \o\Runtime::void(__METHOD__);
}
function u_form_validate ($u_v, $u_type="token")  {
 return \o\v(\o\Runtime::getModule(__NAMESPACE__, 'Web')->u_temp_validate_input($u_v, $u_type))["value"];
 return \o\Runtime::void(__METHOD__);
}
function u_lib_settings ($u_t)  {
 \o\v($u_t)->u_section("Module: Settings");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Global')->u_setting("num") === (- 123.45)), "get num");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Global')->u_setting("flagFalse") === false), "get flag");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Global')->u_setting("flagTrue") === true), "get flag");
\o\v($u_t)->u_ok((\o\Runtime::getModule(__NAMESPACE__, 'Global')->u_setting("string") === "value with spaces, etc."), "get string");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global')->u_setting("map"))->u_key === "value"), "get map");
\o\v($u_t)->u_ok((\o\v(\o\Runtime::getModule(__NAMESPACE__, 'Global')->u_setting("list"))[1] === "value 1"), "get list");
\o\v($u_t)->u_dies(function  ()  {
 \o\Runtime::getModule(__NAMESPACE__, 'Global')->u_setting("MISSING");
 return \o\Runtime::void(__METHOD__);
}
, "missing key");
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
$t->addDynamic(\o\Runtime::getModule(__NAMESPACE__, 'Web')->u_nonce());
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



/* SOURCE={"file":"pages\/home.tht","6":4,"7":6,"8":7,"9":9,"12":12,"14":18,"15":18,"16":26,"17":26,"18":32,"22":35,"23":37,"24":38,"25":39,"26":40,"27":41,"28":42,"29":43,"30":44,"31":45,"32":47,"33":48,"34":50,"35":51,"36":52,"37":53,"38":54,"39":55,"40":56,"41":57,"42":59,"43":60,"44":61,"45":62,"46":63,"49":67,"50":69,"51":71,"52":71,"55":71,"56":72,"57":72,"60":72,"61":74,"62":75,"65":76,"66":78,"67":79,"70":80,"71":82,"72":83,"73":83,"78":86,"79":88,"80":88,"83":88,"86":92,"87":94,"88":96,"95":107,"96":108,"97":109,"98":110,"99":111,"100":114,"101":116,"102":117,"103":118,"104":119,"105":120,"106":121,"107":122,"108":123,"109":124,"110":127,"111":129,"112":130,"113":131,"114":132,"115":135,"116":137,"117":138,"118":139,"119":140,"120":141,"121":142,"122":143,"123":144,"124":145,"125":146,"126":147,"127":148,"128":149,"129":150,"130":151,"131":152,"132":153,"133":154,"134":155,"135":156,"136":157,"137":158,"138":159,"139":160,"140":161,"141":162,"142":163,"143":164,"144":165,"145":166,"146":167,"147":178,"148":180,"149":181,"150":182,"151":183,"152":184,"153":185,"154":186,"155":187,"156":188,"157":191,"158":193,"159":194,"160":195,"161":196,"162":197,"163":198,"164":202,"165":204,"166":205,"167":206,"168":207,"169":208,"170":209,"171":210,"172":211,"173":212,"174":213,"175":214,"176":215,"177":216,"178":217,"179":218,"180":219,"181":220,"182":221,"183":222,"184":223,"185":224,"186":225,"187":226,"188":227,"189":228,"190":229,"191":230,"192":231,"193":232,"194":233,"195":234,"196":235,"197":236,"198":237,"199":238,"200":240,"201":241,"202":242,"203":243,"204":244,"205":247,"206":249,"207":250,"208":251,"209":252,"210":253,"211":254,"212":256,"213":257,"214":258,"215":259,"216":260,"217":263,"218":265,"219":266,"220":267,"221":268,"222":269,"223":270,"224":271,"225":272,"226":275,"227":277,"228":278,"229":279,"230":280,"231":281,"232":282,"233":283,"234":284,"235":285,"236":286,"237":287,"238":288,"239":289,"240":290,"241":291,"242":292,"243":293,"244":294,"245":296,"248":304,"249":306,"250":310,"251":311,"252":312,"253":313,"254":314,"255":315,"258":317,"259":318,"260":319,"261":323,"262":327,"263":329,"264":330,"265":331,"266":333,"267":334,"268":335,"269":340,"270":342,"271":343,"272":348,"273":349,"276":354,"277":356,"278":358,"279":359,"280":360,"281":361,"282":362,"283":363,"284":364,"285":365,"286":365,"289":366,"290":370,"291":372,"292":373,"293":374,"294":375,"295":376,"296":377,"297":378,"298":380,"299":381,"300":382,"301":383,"302":384,"303":385,"304":386,"307":391,"308":393,"309":395,"310":396,"313":398,"314":400,"315":401,"318":403,"319":405,"320":406,"323":408,"324":410,"325":411,"326":412,"327":413,"330":415,"333":417,"334":418,"335":423,"336":424,"339":426,"340":427,"341":429,"342":430,"343":432,"344":433,"345":434,"349":436,"350":439,"351":440,"354":442,"355":443,"356":444,"357":446,"358":447,"359":451,"360":452,"363":454,"364":455,"365":456,"366":459,"367":460,"368":461,"372":463,"375":467,"376":469,"377":471,"378":472,"379":473,"380":474,"381":475,"382":476,"383":477,"384":478,"385":479,"386":480,"387":481,"388":482,"389":485,"390":487,"391":489,"392":490,"393":495,"394":497,"395":498,"396":498,"399":498,"400":501,"401":503,"402":504,"403":505,"404":506,"405":507,"406":508,"407":511,"408":513,"409":514,"410":515,"411":517,"412":518,"413":519,"414":522,"415":524,"416":526,"417":527,"418":529,"419":530,"420":532,"421":533,"422":536,"423":537,"424":538,"425":539,"426":540,"427":541,"428":542,"429":545,"430":546,"431":546,"434":546,"435":547,"436":550,"437":551,"438":552,"439":553,"440":554,"441":557,"442":559,"443":559,"446":559,"447":560,"448":560,"451":560,"454":565,"455":568,"456":570,"457":571,"458":572,"459":573,"460":574,"461":575,"462":576,"463":577,"464":579,"465":580,"466":581,"467":582,"468":583,"469":584,"470":585,"471":587,"472":588,"473":590,"474":593,"475":595,"476":595,"479":595,"480":596,"481":596,"484":596,"485":597,"486":597,"489":597,"490":598,"491":598,"494":598,"495":599,"496":599,"499":599,"500":600,"501":600,"504":600,"505":601,"506":601,"507":601,"510":601,"511":602,"512":602,"515":602,"516":603,"517":603,"520":603,"521":604,"522":604,"525":604,"526":605,"527":605,"530":605,"531":606,"532":606,"535":606,"536":607,"537":607,"540":607,"541":610,"542":612,"543":613,"544":614,"545":615,"546":616,"547":617,"548":620,"549":622,"550":623,"551":624,"552":625,"553":628,"554":630,"555":631,"556":632,"557":633,"558":634,"559":635,"560":636,"561":637,"562":639,"563":640,"564":641,"565":642,"566":644,"567":645,"568":646,"569":647,"570":650,"571":652,"572":653,"573":654,"574":655,"575":656,"576":657,"577":658,"578":659,"579":660,"580":661,"581":662,"582":665,"583":667,"584":668,"585":669,"586":670,"587":671,"588":673,"589":675,"590":676,"591":677,"592":678,"595":682,"596":685,"597":687,"598":688,"599":689,"602":691,"603":693,"604":694,"605":695,"608":697,"609":699,"610":700,"611":701,"612":702,"615":704,"616":707,"617":708,"618":709,"619":710,"620":711,"621":712,"622":712,"628":714,"629":716,"630":717,"631":718,"632":719,"633":720,"634":721,"638":723,"639":724,"640":725,"646":728,"647":730,"648":731,"649":734,"650":735,"651":736,"652":737,"653":738,"654":739,"655":740,"656":741,"657":742,"658":743,"659":744,"660":745,"661":746,"662":747,"663":748,"664":749,"665":750,"666":751,"667":752,"668":753,"669":754,"670":755,"671":756,"672":757,"673":758,"674":759,"675":760,"676":761,"677":764,"678":766,"679":768,"680":768,"684":769,"685":771,"686":771,"690":772,"691":774,"692":775,"696":778,"700":780,"701":782,"702":783,"705":785,"706":786,"711":788,"712":790,"713":791,"716":793,"717":794,"720":796,"721":797,"725":800,"731":802,"732":806,"733":808,"734":809,"735":810,"736":811,"739":813,"740":814,"744":817,"748":820,"749":821,"750":823,"751":824,"752":825,"755":826,"756":827,"760":829,"761":832,"762":834,"763":835,"766":839,"767":841,"768":843,"769":844,"770":845,"773":850,"774":853,"775":855,"776":856,"777":857,"778":858,"779":859,"780":861,"781":862,"782":863,"783":866,"784":868,"785":869,"786":870,"787":871,"788":872,"789":873,"790":874,"791":875,"792":876,"793":877,"794":880,"795":881,"796":882,"797":883,"798":885,"799":886,"800":888,"801":890,"802":891,"803":893,"804":894,"805":896,"806":897,"807":898,"808":900,"809":901,"810":903,"811":904,"812":906,"813":907,"814":908,"815":909,"816":911,"817":912,"818":913,"819":915,"820":916,"821":917,"822":919,"823":920,"824":922,"825":923,"826":925,"827":926,"828":927,"829":928,"830":929,"831":931,"832":932,"833":933,"834":934,"835":935,"836":936,"837":938,"838":939,"839":941,"840":942,"841":944,"842":945,"843":947,"844":948,"845":950,"846":951,"847":952,"848":954,"849":955,"850":956,"851":957,"852":959,"853":960,"854":962,"855":963,"856":964,"857":965,"858":966,"859":967,"860":969,"861":971,"865":979,"866":980,"867":981,"868":983,"869":984,"870":986,"871":987,"872":989,"873":991,"874":992,"875":993,"876":994,"877":996,"878":997,"879":998,"880":1000,"881":1001,"882":1002,"883":1004,"884":1005,"885":1006,"886":1013,"887":1014,"888":1016,"889":1017,"890":1018,"891":1019,"892":1022,"893":1024,"894":1025,"895":1026,"896":1028,"897":1029,"898":1030,"899":1032,"900":1033,"901":1034,"902":1035,"903":1037,"904":1038,"905":1039,"906":1040,"907":1042,"908":1043,"909":1044,"910":1045,"911":1047,"912":1048,"913":1049,"914":1050,"915":1052,"916":1053,"917":1054,"918":1055,"919":1056,"920":1058,"921":1059,"922":1060,"923":1061,"924":1063,"925":1064,"926":1065,"927":1066,"928":1067,"929":1069,"930":1070,"931":1071,"932":1072,"933":1073,"934":1075,"935":1076,"936":1077,"937":1078,"938":1079,"939":1080,"940":1081,"941":1083,"942":1084,"943":1085,"944":1086,"945":1089,"946":1091,"947":1092,"948":1093,"949":1094,"950":1095,"951":1098,"952":1099,"953":1100,"954":1103,"955":1105,"956":1106,"957":1107,"958":1107,"961":1107,"962":1109,"963":1110,"964":1112,"965":1113,"966":1115,"967":1116,"968":1118,"969":1119,"970":1121,"971":1123,"972":1124,"973":1127,"974":1129,"975":1130,"976":1132,"977":1132,"980":1132,"981":1133,"982":1133,"985":1133,"986":1135,"987":1136,"988":1137,"989":1138,"990":1139,"991":1141,"992":1142,"995":1146,"996":1148,"997":1150,"998":1151,"999":1152,"1000":1153,"1001":1154,"1002":1155,"1003":1156,"1004":1157,"1005":1158,"1006":1159,"1007":1160,"1008":1162,"1009":1163,"1010":1168,"1011":1171,"1012":1176,"1013":1177,"1014":1178,"1015":1179,"1016":1181,"1017":1184,"1018":1185,"1019":1186,"1020":1187,"1021":1188,"1022":1190,"1023":1191,"1024":1192,"1025":1193,"1026":1195,"1027":1196,"1028":1198,"1029":1199,"1030":1200,"1031":1204,"1032":1205,"1033":1206,"1034":1210,"1035":1211,"1036":1212,"1037":1216,"1038":1217,"1039":1221,"1040":1224,"1041":1225,"1042":1227,"1043":1227,"1047":1228,"1048":1230,"1049":1231,"1050":1233,"1051":1234,"1052":1236,"1053":1237,"1054":1239,"1055":1240,"1056":1242,"1057":1242,"1060":1242,"1061":1244,"1062":1245,"1063":1248,"1064":1250,"1065":1250,"1068":1250,"1069":1251,"1070":1251,"1073":1251,"1074":1252,"1075":1252,"1078":1252,"1079":1253,"1080":1253,"1083":1253,"1084":1254,"1085":1254,"1088":1254,"1091":1259,"1092":1261,"1093":1263,"1094":1264,"1095":1265,"1096":1266,"1097":1268,"1099":1272,"1100":1273,"1101":1274,"1102":1278,"1103":1280,"1104":1281,"1105":1283,"1106":1284,"1107":1285,"1108":1287,"1109":1288,"1110":1289,"1111":1291,"1112":1292,"1113":1295,"1114":1296,"1115":1297,"1116":1298,"1117":1299,"1118":1300,"1121":1309,"1122":1311,"1123":1313,"1124":1313,"1127":1313,"1128":1314,"1129":1314,"1132":1314,"1133":1316,"1134":1317,"1135":1318,"1136":1320,"1137":1321,"1138":1323,"1139":1324,"1140":1325,"1141":1327,"1142":1328,"1143":1329,"1144":1330,"1145":1332,"1146":1333,"1147":1334,"1148":1335,"1149":1336,"1150":1337,"1151":1338,"1152":1340,"1153":1341,"1156":1346,"1157":1348,"1158":1350,"1159":1351,"1160":1352,"1161":1353,"1162":1354,"1163":1355,"1164":1356,"1165":1357,"1166":1358,"1169":1361,"1170":1363,"1171":1365,"1172":1367,"1173":1368,"1174":1370,"1175":1371,"1176":1372,"1177":1374,"1178":1375,"1179":1376,"1180":1378,"1181":1379,"1182":1380,"1183":1382,"1184":1383,"1185":1384,"1188":1387,"1189":1389,"1190":1391,"1191":1392,"1192":1394,"1193":1395,"1194":1397,"1195":1398,"1196":1400,"1197":1401,"1198":1403,"1199":1404,"1200":1406,"1201":1407,"1202":1408,"1203":1410,"1204":1411,"1205":1413,"1206":1414,"1209":1417,"1210":1419,"1211":1421,"1212":1422,"1213":1423,"1216":1426,"1217":1428,"1218":1430,"1219":1431,"1220":1432,"1221":1433,"1222":1434,"1223":1436,"1224":1437,"1225":1438,"1226":1439,"1227":1441,"1228":1442,"1231":1445,"1232":1447,"1235":1451,"1236":1453,"1237":1455,"1238":1456,"1239":1458,"1240":1459,"1241":1461,"1242":1463,"1243":1464,"1244":1465,"1245":1467,"1246":1468,"1247":1470,"1248":1471,"1249":1473,"1250":1474,"1253":1478,"1254":1479,"1255":1481,"1256":1482,"1257":1483,"1258":1484,"1259":1484,"1262":1484,"1263":1486,"1264":1487,"1267":1490,"1268":1491,"1269":1492,"1272":1495,"1273":1496,"1276":1499,"1278":1500,"1280":1500,"1281":1501,"1285":1504,"1286":1505,"1287":1507,"1288":1508,"1289":1509,"1290":1510,"1291":1512,"1292":1513,"1293":1514,"1294":1515,"1295":1516,"1296":1517,"1302":1520,"1303":1522,"1306":1525,"1307":1527,"1308":1529,"1309":1530,"1310":1532,"1311":1533,"1312":1533,"1315":1533,"1316":1534,"1317":1534,"1320":1534,"1323":1545,"1324":1546,"1327":1549,"1328":1550,"1329":1552,"1330":1554,"1333":1557,"1334":1558,"1337":1562,"1338":1564,"1339":1566,"1340":1566,"1343":1566,"1344":1567,"1345":1567,"1348":1567,"1349":1568,"1350":1568,"1353":1568,"1354":1569,"1355":1569,"1358":1569,"1359":1571,"1360":1573,"1361":1574,"1362":1576,"1363":1577,"1364":1579,"1365":1580,"1366":1582,"1367":1583,"1368":1584,"1369":1585,"1370":1586,"1371":1588,"1372":1589,"1373":1590,"1374":1591,"1375":1593,"1376":1594,"1377":1595,"1378":1597,"1379":1598,"1380":1599,"1383":1603,"1384":1604,"1387":1607,"1388":1609,"1389":1611,"1390":1612,"1391":1613,"1392":1614,"1393":1615,"1394":1616,"1395":1618,"1396":1618,"1399":1618,"1402":1625,"1404":1628,"1405":1628,"1406":1629,"1407":1629,"1408":1630,"1411":1632,"1416":1634,"1418":1635,"1419":1635,"1420":1636,"1424":1638,"1426":1640,"1430":1642,"1432":1646,"1436":1648,"1438":1650,"1444":1652,"1446":1653,"1447":1653,"1448":1656,"1452":1658,"1454":1659,"1455":1659,"1456":1659,"1457":1659,"1458":1660,"1462":1662,"1464":1663,"1466":1663,"1467":1664,"1472":1666,"1474":1668,"1478":1670,"1480":1672,"1484":1674,"1486":1675,"1487":1675,"1488":1676,"1492":1678,"1494":1679,"1495":1679,"1496":1680,"1500":1686,"1501":1687,"1504":1690,"1505":1692} */

?>