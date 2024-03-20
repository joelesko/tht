<?php

namespace o;

class OString extends OVar implements \ArrayAccess {

    protected $type = 'string';

    public $val = '';
    private $prevHash = '';
    private $encoding = 'UTF-8';

    protected $errorContext = 'string';

    protected $suggestMethod = [
        'count'   => 'length()',
        'size'    => 'length()',
        'explode' => 'split(delimiter)',
        'find'    => 'indexOf(item), contains(item), match(regex)',

        'replaceall' => 'replace()',

        'upper'       => 'upperCase()',
        'toupper'     => 'upperCase()',
        'touppercase' => 'upperCase()',
        'lower'       => 'lowerCase()',
        'tolower'     => 'lowerCase()',
        'tolowercase' => 'lowerCase()',

        'ltrim'     => 'trimLeft()',
        'rtrim'     => 'trimRight()',
        'lefttrim'  => 'trimLeft()',
        'righttrim' => 'trimRight()',

        'leftpad'   => 'padLeft()',
        'lpad'      => 'padLeft()',
        'rightpad'  => 'padRight()',
        'rpad'      => 'padRight()',

        'substr'    => 'substring()',
        'slice'     => 'substring()',

        'beginswith'  => 'startsWith()',
    ];


    // ArrayAccess iterface (e.g. $var[1])

    // Reserve [] for Maps and Lists
    function offsetGet($k):string {

        $this->error('Can not use `[]` to get a character in a string. Try: `$string.getChar($index)`');
    }

    // Reserve [] for Maps and Lists
    // Also, we can't modify strings in place anyway because of how autoboxing works.
    function offsetSet($ak, $v):void {

        if (is_null($ak)) {
            $this->error('Left side of `#=` must be a list.  Got: `string`');
        }

        $this->error('Can not use `[]` to set a character in a string.  Try: `$string.setChar($index, $newChar)`');
    }

    function offsetExists($k):bool {
        // not used
    }

    function offsetUnset($k):void {
        // not used
    }

    // Note: Similar method in OBag for Lists
    function checkIndex ($ak, $allowOutOfRange = false) {

        $k = $ak;

        if (!is_int($k)) {
            $this->error("String index must be numeric.  Got: `$k`");
        }
        else if (ONE_INDEX && $k == 0) {
            $this->error('Invalid index: `0`  Try: index = `1` (first character)');
        }
        else if ($k < 0) {
            // Count negative indexes from the end.
            $k = mb_strlen($this->val) + $k;
        }
        else {
            $k -= ONE_INDEX;
        }

        $strlen = mb_strlen($this->val);
        $isOutOfRange = ($k < 0 || $k > $strlen - 1);
        if ($isOutOfRange) {
            if ($allowOutOfRange) {
                return -1;
            }
            else {
                $this->error("Index `$ak` is outside of string length (`$strlen`).");
            }
        }

        return $k;
    }

    //// Basic

    function u_length() {
        $this->ARGS('', func_get_args());
        return mb_strlen($this->val);
    }


    //// Substrings


    function _strpos($s, $ignoreCase=false, $startPos=ONE_INDEX) {

        $func = $ignoreCase ? 'mb_stripos' : 'mb_strpos';

        return $this->callStrPos($func, $s, $startPos);
    }

    function _strrpos($s, $ignoreCase=false, $startPos=ONE_INDEX) {

        $func = $ignoreCase ? 'mb_strripos' : 'mb_strrpos';

        return $this->callStrPos($func, $s, $startPos);
    }

    function callStrPos($func, $subString, $aStartPos = ONE_INDEX) {

        $startPos = $this->checkIndex($aStartPos);

        if ($subString === '') {
            $this->error('Invalid empty string as argument.', '');
        }

        $ret = $func($this->val, $subString, $startPos);

        return $ret === false ? -1 + ONE_INDEX : $ret + ONE_INDEX;
    }

    function isOutOfRange($index, $val) {
        return ($index < 0 || $index > mb_strlen($this->val) - 1);
    }

    // function u_has_index($i) {

    //     $this->ARGS('i', func_get_args());

    //     $i = $this->checkIndex($i, true);

    //     return $i === -1 ? false : true;
    // }

    function u_get_char($i) {

        $this->ARGS('i', func_get_args());

        $i = $this->checkIndex($i, true);
        if ($i == -1) {
            return '';
        }

        return mb_substr($this->val, $i, 1);
    }

    function u_char_to_unicode() {

        $this->ARGS('', func_get_args());

        if (mb_strlen($this->val) != 1) {
            $this->error('`charToUnicode` requires a single character.');
        }

        $char = mb_substr($this->val, 0, 1);
        return unpack('V', iconv('UTF-8', 'UCS-4LE', $char))[1];
    }


    // function u_get_char_codes() {

    //     $this->ARGS('', func_get_args());

    //     $codes = [];
    //     $len = $this->u_length();
    //     for ($i = 0; $i < $len; $i += 1) {
    //         $char = $this->val[$i];
    //         $codes []= $this->getCharCode($i);
    //     }

    //     return OList::create($codes);
    // }

    function u_set_char($i, $c) {

        $this->ARGS('is', func_get_args());

        $i = $this->checkIndex($i);

        $len = strlen($c);
        if ($len !== 1) {
            $this->error("Replacement character must be exactly 1 character long. Got: length = `$len`");
        }

        $this->val[$i] = $c;

        return $this->val;
    }

    function u_left($n) {

        $this->ARGS('I', func_get_args());

        return $n <= 0 ? '' : $this->u_substring(1, $n);
    }

    function u_right($n) {

        $this->ARGS('I', func_get_args());

        return $n <= 0 ? '' : $this->u_substring(-1 * $n, $n);
    }

    function u_substring($start, $len=null) {

        $this->ARGS('ii', func_get_args());

        $start = $this->checkIndex($start);

        $len = !$len ? null : $len;

        return mb_substr($this->val, $start, $len);
    }

    function checkMatchArg($strOrRx, $methodName) {

        if (ORegex::isa($strOrRx)) {
            return 'regex';
        }
        else if (is_string($strOrRx) || gettype($strOrRx) == 'integer') {
            return 'string';
        }
        else {
            $this->argumentError("Argument #1 of `$methodName` must be: `string` or `regex`  Got: `" . v($strOrRx)->u_type() . '`', $methodName);
        }
    }

    function u_index_of($strOrRx, $flags=null) {

        $this->ARGS('*m', func_get_args());

        $flags = $this->flags($flags, [
            'startIndex' => ONE_INDEX,
            'ignoreCase' => false
        ]);

        $matchType = $this->checkMatchArg($strOrRx, 'indexOf');

        if ($matchType == 'regex') {
            if ($flags['ignoreCase']) {
                $strOrRx->addFlag('i');
            }
            if ($flags['startIndex']) {
                $strOrRx->u_start_index($flags['startIndex']);
            }
            $i = $this->u_match($strOrRx)['index'];
            return $i ? $i : 0;
        }
        else if ($matchType == 'string') {
            return $this->_strpos($strOrRx, $flags['ignoreCase'], $flags['startIndex']);
        }
    }

    function u_last_index_of($strOrRx, $flags=null) {

        $this->ARGS('*m', func_get_args());

        $flags = $this->flags($flags, [
            'startIndex' => ONE_INDEX,
            'ignoreCase' => false
        ]);

        $matchType = $this->checkMatchArg($strOrRx, 'indexOf');

        if ($matchType == 'regex') {
            if ($flags['ignoreCase']) {
                $strOrRx->addFlag('i');
            }
            if ($flags['startIndex']) {
                $strOrRx->u_start_index($flags['startIndex']);
            }
            $matches = $this->u_match_all($strOrRx);
            if (count($matches)) {
                $m = $matches->u_pop();
                return $m['index'];
            }
            return 0;
        }
        else if ($matchType == 'string') {
            return $this->_strrpos($strOrRx, $flags['ignoreCase'], $flags['startIndex']);
        }
    }

    function u_contains($strOrRx, $flags=null) {

        $this->ARGS('*m', func_get_args());

        $flags = $this->flags($flags, [
            'ignoreCase' => false
        ]);

        $matchType = $this->checkMatchArg($strOrRx, 'contains');

        if ($matchType == 'regex') {
            if ($flags['ignoreCase']) {
                $strOrRx->addFlag('i');
            }
            return $this->u_match($strOrRx)['full'] !== '';
        }
        else if ($matchType == 'string') {
            return $this->_strpos($strOrRx, $flags['ignoreCase']) > -1 + ONE_INDEX;
        }
        else {
            $this->argumentError("Argument #1 of `contains` must be: `string` or `regex`  Got: `" . v($strOrRx)->u_type() . '`', 'contains');
        }
    }

    function u_count($strOrRx, $flags=null) {

        $this->ARGS('*m', func_get_args());

        $flags = $this->flags($flags, [
            'ignoreCase' => false
        ]);

        $matchType = $this->checkMatchArg($strOrRx, 'count');

        if ($matchType == 'regex') {
            if ($flags['ignoreCase']) {
                $strOrRx->addFlag('i');
            }
            return $this->u_match_all($strOrRx)->u_length();
        }
        else if ($matchType == 'string') {
            $haystack = $flags['ignoreCase'] ? mb_strtolower($this->val) : $this->val;
            $needle   = $flags['ignoreCase'] ? mb_strtolower($strOrRx) : $strOrRx;

            return mb_substr_count($haystack, $needle);
        }

    }

    function u_starts_with($strOrRx, $flags = null) {

        $this->ARGS('*m', func_get_args());

        $flags = $this->flags($flags, [
            'ignoreCase' => false
        ]);

        $matchType = $this->checkMatchArg($strOrRx, 'startsWith');

        if ($matchType == 'regex') {
            if ($flags['ignoreCase']) {
                $strOrRx->addFlag('i');
            }
            $pat = $strOrRx->getRawPattern();
            $pat = v($pat)->u_ensure_left('^');
            $strOrRx->setPattern($pat);
            return $this->u_match($strOrRx)->u_length();
        }
        else if ($matchType == 'string') {
            return $this->_strpos($strOrRx, $flags['ignoreCase']) === ONE_INDEX;
        }

    }

    function u_ends_with($strOrRx, $flags = null) {

        $this->ARGS('*m', func_get_args());

        $flags = $this->flags($flags, [
            'ignoreCase' => false
        ]);

        $matchType = $this->checkMatchArg($strOrRx, 'endsWith');

        if ($matchType == 'regex') {
            if ($flags['ignoreCase']) {
                $strOrRx->addFlag('i');
            }
            $pat = $strOrRx->getRawPattern();
            $pat = v($pat)->u_ensure_right('$');
            $strOrRx->setPattern($pat);
            return $this->u_match($strOrRx)->u_length();
        }
        else if ($matchType == 'string') {
            $x = $this->val;
            $suffLen = v($strOrRx . '')->u_length();
            $this->val = $x;
            return $this->_strpos($strOrRx, $flags['ignoreCase']) == ($this->u_length() - $suffLen) + ONE_INDEX;
        }
    }

    function u_ensure_left($s) {
        $this->ARGS('s', func_get_args());

        if (!$this->u_starts_with($s)) {
            return $s . $this->val;
        }

        return $this->val;
    }

    function u_ensure_right($s) {
        $this->ARGS('s', func_get_args());

        if (!$this->u_ends_with($s)) {
            return $this->val . $s;
        }

        return $this->val;
    }

    function u_ensure_wrap($ls, $rs) {
        $this->ARGS('s', func_get_args());

        if (!($this->u_starts_with($ls) && $this->u_ends_with($rs))) {
            return $ls . $this->val . $rs;
        }

        return $this->val;
    }



    // Locking


    // Just for convenience if mixing and matching with OTypeStrings
    function u_render_string () {

        $this->ARGS('', func_get_args());

        return $this->val;
    }

    function u_x_danger_to_type ($type) {

        $this->ARGS('s', func_get_args());

        return OTypeString::create($type, $this->val);
    }



    // Find / Replace

    function pregErrorMessage () {
        // Look up error type for the given error code
        $num = preg_last_error();
        $pcre = get_defined_constants(true)['pcre'];
        foreach ($pcre as $k => $v) {
            if ($v == $num && strpos($k, 'ERROR') > -1) {
                return $k;
            }
        }
        return '(UNKNOWN)';
    }

    function u_match ($match, $sGroupNames = '') {

        $this->ARGS('*s', func_get_args());

        if (!ORegex::isa($match)) {
            $this->error("1st argument must be a Regex string `r'...'`");
        }

        $startPos = $this->checkIndex($match->getStartIndex(), true);

        $found = preg_match($match->getPattern(), $this->val, $matches, PREG_OFFSET_CAPTURE, $startPos);

        if ($found === false) {
            $this->error('Error in match: ' . $this->pregErrorMessage(), 'match');
        }

        if ($found === 1) {
            return $this->getMatchReturn($matches, $sGroupNames);
        }

        return OMap::create([]);
    }

    function getMatchReturn($matches, $sGroupNames) {

        // No capture groups. Just return the full match.
        if (count($matches) == 1) {
            return OMap::create([
                'full' => $matches[0][0],
                'index' => $matches[0][1] + ONE_INDEX,
            ]);
        }
        else {
            // Return Map of capture groups
            $ret = OMap::create([]);

            $ret['full'] = $matches[0][0];
            array_shift($matches);

            $ret['indexOf'] = OMap::create([]);

            if ($sGroupNames) {
                $groupNames = preg_split('/\s*\|\s*/u', $sGroupNames);
                $numNames = count($groupNames);
                $numGroups = count($matches);

                if ($numNames !== $numGroups) {
                    $this->error("The number of `\$groupNames` ($numNames) does not match the number of capture groups ($numGroups).");
                }

                foreach ($groupNames as $i => $name) {
                    $ret[$name] = $matches[$i][0];
                    $ret['indexOf'][$name] = $matches[$i][1] + ONE_INDEX;
                }
            }
            else {
                foreach ($matches as $i => $val) {
                    $ret[$i + 1] = $matches[$i][0];
                    $ret['indexOf'][$i + ONE_INDEX] = $matches[$i][1] + ONE_INDEX;
                }
            }

            return $ret;
        }
    }

    function u_match_all ($match, $sGroupNames = '') {

        $this->ARGS('*s', func_get_args());

        if (!ORegex::isa($match)) {
            $this->error("1st argument must be a Regex string `r'...'`");
        }

        // TODO: Move matching logic into ORegex object?
        $startPos = $this->checkIndex($match->getStartIndex(), true);

        $numMatches = preg_match_all($match->getPattern(), $this->val,
            $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE, $startPos);

        if ($numMatches === false) {
            $this->error('Error in match: ' . $this->pregErrorMessage(), 'match');
        }

        if (!$numMatches) {
            return OList::create([]);
        }

        $retMatches = [];
        foreach ($matches as $m) {
            $retMatches []= $this->getMatchReturn($m, $sGroupNames);
        }

        return OList::create($retMatches);
    }

    function u_replace ($find, $replace, $flags=null) {

        $this->ARGS('**m', func_get_args());

        $flags = $this->flags($flags, [
            'limit' => 0,
            'first' => false,
        ]);

        $limit = $flags['limit'];
        if ($flags['first']) {
            $limit = 1;
        }
        else if ($flags['limit'] == 0) {
            $limit = -1;
        }
        else if ($flags['limit'] < 0) {
            $this->error("Option `limit` can not be negative. Got: `" . $flags['limit'] . "`");
        }


        $ret = $this->val;
        $numReplaced = 0;

        if (ORegex::isa($find)) {
            $fn = is_callable($replace) ? 'preg_replace_callback' : 'preg_replace';
            if (is_callable($replace)) {
                $replace = function ($m) use ($replace) {
                    if (count($m) == 1) { return $replace($m[0]); }
                    $mMap = OMap::create([ 'full' => $m[0] ]);
                    for ($i = 1; $i < count($m); $i += 1) {
                        $mMap[$i] = $m[$i];
                    }
                    return $replace($mMap);
                };
            }

            $ret = $fn($find->getPattern(), $replace, $this->val, $limit, $numReplaced);
        }
        else {
            if ($limit > 0) {
                $pos = strpos($this->val, $find);
                if ($pos !== false) {
                    $ret = substr_replace($this->val, $replace, $pos, strlen($find));
                    $numReplaced = 1;
                }
            }
            else {
                $ret = str_replace($find, $replace, $this->val, $numReplaced);
            }
        }

        Tht::module('String')->lastReplaceCount = $numReplaced;

        return $ret;
    }

    // Thanks: https://stackoverflow.com/questions/1454401/how-do-i-do-a-strtr-on-utf-8-in-php
    function u_replace_chars ($from, $to) {

        $this->ARGS('ss', func_get_args());

        // Allow uneven lengths
        if (mb_strlen($from) !== mb_strlen($to)) {
            $len = min(mb_strlen($from), mb_strlen($to));
            $from = mb_substr($from, 0, $len);
            $to = mb_substr($to, 0, $len);
        }

        $keys = [];
        $values = [];
        preg_match_all('/./u', $from, $keys);
        preg_match_all('/./u', $to, $values);
        $mapping = array_combine($keys[0], $values[0]);

        return strtr($this->val, $mapping);
    }

    // Mapping from #192 to #383
    // https://docs.oracle.com/cd/E29584_01/webhelp/mdex_basicDev/src/rbdv_chars_mapping.html
    function u_to_ascii() {
        $this->ARGS('', func_get_args());

        $from = "ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿĀāĂăĄąĆćĈĉĊċČčĎďĐđĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħĨĩĪīĬĭĮįİıĲĳĴĵĶķĸĹĺĻļĽľĿŀŁłŃńŅņŇňŉŊŋŌōŎŏŐőŒœŔŕŖŗŘřŚśŜŝŞşŠšŢţŤťŦŧŨũŪūŬŭŮůŰűŲųŴŵŶŷŸŹźŻżŽž";
        $to   = "AAAAAAACEEEEIIIIENOOOOOOUUUUYPsaaaaaaaceeeeiiiienoooooouuuuypyAaAaAaCcCcCcCcDdDdEeEeEeEeEeGgGgGgGgHhHhIiIiIiIiIiIiJjKklLlLlLlLlLnNnNnNnnNoOoOoOoOrRrRrRrSsSsSsStTtTtTuUuUuUuUuUuUwWyYyYZzZzZz";

        $str = $this->u_replace_chars($from, $to);

        return $str;
    }

    function u_remove_left($s, $flags = null) {

        $this->ARGS('sm', func_get_args());

        if ($this->u_starts_with($s, $flags)) {
            $orig = $this->val;  // save after singleton changes
            $len = v($s)->u_length();

            return v($orig)->u_substring($len + ONE_INDEX);
        }

        return $this->val;
    }

    function u_remove_right($s, $flags = null) {

        $this->ARGS('sm', func_get_args());

        if ($this->u_ends_with($s, $flags)) {
            $orig = $this->val;  // save after singleton changes
            $len = $this->u_length() - v($s)->u_length();

            return v($orig)->u_substring(1, $len);
        }

        return $this->val;
    }

    function u_remove_first($s, $flags = null) {

        $this->ARGS('sm', func_get_args());

        if ($this->u_ends_with($s, $flags)) {
            $orig = $this->val;  // save after singleton changes
            $len = $this->u_length() - v($s)->u_length();

            return v($orig)->u_substring(1, $len);
        }

        return $this->val;
    }

    function u_insert($s, $index) {

        $this->ARGS('si', func_get_args());

        $index = $this->checkIndex($index);

        $len = $this->u_length();
        if ($index > $len) {
            return $this->val . $s;
        }

        $start = $this->u_substring(ONE_INDEX, $index);
        $end = $this->u_substring($index + 1, $len);

        return $start . $s . $end;
    }



    // To Arrays

    function u_split ($delim='', $flags = null) {

        $this->ARGS('*m', func_get_args());

        $flags = $this->flags($flags, [
            'keepWhitespace' => false,
            'limit' => 0,
        ]);

        if ($flags['limit'] == 0) { $flags['limit'] = PHP_INT_MAX; }

        if (ORegex::isa($delim)) {
            return OList::create(
                preg_split($delim->getPattern(), $this->val, $flags['limit'])
            );
        }
        if ($delim === '') {
            return $this->u_split_chars();
        }

        $parts = explode($delim, $this->val, $flags['limit']);

        if (!$flags['keepWhitespace']) {
            $filtered = [];
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p != '') { $filtered []= $p; }
            }
            $parts = $filtered;
        }

        return OList::create($parts);
    }

    function u_split_chars() {

        $this->ARGS('', func_get_args());

        preg_match_all('/./us', $this->val, $chars);

        return OList::create($chars[0]);
    }

    function u_split_lines ($flags = null) {

        $this->ARGS('m', func_get_args());

        $flags = $this->flags($flags, [
            'keepWhitespace' => false,
        ]);

        if ($flags['keepWhitespace']) {
            return OList::create(
                preg_split("/\n/u", $this->val)
            );
        }
        else {
            return OList::create(
                preg_split("/\s*\n+\s*/u", trim($this->val))
            );
        }
    }

    function u_split_words ($flags = null) {

        $this->ARGS('m', func_get_args());

        $flags = $this->flags($flags, [
            'keepAllChars' => false,
        ]);

        $v = $this->val;

        if (!$flags['keepAllChars']) {
            $v = str_replace("'", '', $v);
            $v = trim(preg_replace("/[^a-zA-Z0-9]+/u", ' ', $v));
        }

        return OList::create(
            preg_split("/\s+/u", trim($v))
        );
    }




    // Transforms

    function u_reverse () {
        $this->ARGS('', func_get_args());

        preg_match_all('/./us', $this->val, $chars);

        return implode(array_reverse($chars[0]));
    }

    function u_to_case($case, $skipWords=null) {

        $this->ARGS('s', func_get_args());

        if ($case == 'lower') {
            return mb_strtolower($this->val);
        }
        else if ($case == 'upper') {
            return mb_strtoupper($this->val);
        }
        else if ($case == 'lowerFirst') {
            $first = mb_strtolower(mb_substr($this->val, 0, 1));
            return $first . mb_substr($this->val, 1);
        }
        else if ($case == 'upperFirst') {
            $first = mb_strtoupper(mb_substr($this->val, 0, 1));
            return $first . mb_substr($this->val, 1);
        }
        else if ($case == 'camel') {
            return $this->camelCase();
        }
        else if ($case == 'upperCamel') {
            return $this->camelCase(true);
        }
        else if ($case == 'label') {
            return $this->u_humanize();
        }
        else if ($case == 'title') {
            if (is_null($skipWords)) {
                $skipWords = ['the', 'a', 'is', 'to', 'at', 'by', 'for', 'in', 'of'];
            }

            $titleCased = preg_replace_callback(
                '/([\S]+)/u',
                function ($match) use ($skipWords) {
                    if ($skipWords && in_array($match[0], $skipWords)) {
                        return $match[0];
                    }
                    return v(v($match[0])->u_to_case('lower'))->u_to_case('upperFirst');
                },
                $this->val
            );

            return v($titleCased)->u_to_case('upperFirst');
        }
        else {
            $this->error("Unknown case: `$case`");
        }

    }

    function u_test_remove_tags() {
        return Security::removeHtmlTags($this->val);
    }

    // TODO: When updating this, re-test app creation code. It uses the 'label' call.
    function u_to_token_case($joiner='') {

        $this->ARGS('s', func_get_args());

        if ($joiner == '') {
            return $this->camelCase();
        }
        else if ($joiner == 'label') {
            return $this->u_humanize();
        }
        else {
            if (mb_strlen($joiner) > 2) {
                $this->error('Joiner must be 1 or 2 characters long.');
            }

            return $this->tokenize($joiner);
        }
    }

    function u_to_upper_case ($flags = null) {

        $this->ARGS('m', func_get_args());

        $flags = $this->flags($flags, [
            'first' => false,
        ]);

        if ($flags['first']) {
            $first = mb_strtoupper(mb_substr($this->val, 0, 1));
            return $first . mb_substr($this->val, 1);
        }

        return mb_strtoupper($this->val);
    }

    function u_to_lower_case ($flags = null) {

        $this->ARGS('m', func_get_args());

        $flags = $this->flags($flags, [
            'first' => false,
        ]);

        if ($flags['first']) {
            $first = mb_strtolower(mb_substr($this->val, 0, 1));
            return $first . mb_substr($this->val, 1);
        }

        return mb_strtolower($this->val);
    }

    function u_to_title_case () {

        $this->ARGS('', func_get_args());

        // Why simple?
        // https://stackoverflow.com/questions/58858772/what-is-the-purpose-of-the-mb-case-simple-constants
        return mb_convert_case($this->val, MB_CASE_TITLE_SIMPLE);
    }

    function u_to_plural ($num=2, $plural='') {

        $this->ARGS('ns', func_get_args());

        $last = mb_substr($this->val, -1, 1);

        if ($plural === '') {
            if ($last === 's') {
                $plural = $this->val . 'es';
            }
            else {
                $plural = $this->val . 's';
            }
        }

        return $num == 1 ? $this->val : $plural;
    }

    function camelCase ($isUpper=false) {

        $v = $this->u_to_ascii();

        $v = strtolower($v);
        $v = preg_replace('/[^a-z0-9]+/', ' ', $v);
        $v = trim($v);

        $parts = preg_split('/\s+/', $v);
        $camel = '';
        foreach ($parts as $p) {
            $camel .= ucfirst($p);
        }

        return $isUpper ? $camel : lcfirst($camel);
    }

    function u_to_url_slug($skipWords = '') {

        $this->ARGS('s', func_get_args());

        if ($skipWords == ':common') {
            $skipWords = "
                a an the for in as of on at
                to and or but not nor vs
                is am was are be been being were will
                have had has
                it its
                his her
                do does did done
                go goes went gone going
                i you your my their
                from with about
                this these those that
                than then
                will now soon
                so very too just
                why who what when where how
                cant wont
                -ly
            ";
        }

        $slug = v($this->val)->u_to_ascii();
        $slug = trim(mb_strtolower($slug));

        // Glue some substrings together before splitting
        $slug = preg_replace("/(\d)[,'’`](\d)/u", '$1$2', $slug);   // 10,000 -> 10000
        $slug = preg_replace("/\b(\w)\./u", '$1', $slug);           // acronym: U.S. -> US

        if ($skipWords) {
            // English contractions
            $slug = preg_replace("/['’`](ve)\b/u", ' have', $slug);
            $slug = preg_replace("/['’`](re)\b/u", ' are', $slug);
            $slug = preg_replace("/['’`](ll)\b/u", ' will', $slug);
            $slug = preg_replace("/\bcan['’`]t\b/u", 'can not', $slug);
            $slug = preg_replace("/\bwon['’`]t\b/u", 'will not', $slug);
            $slug = preg_replace("/\b(have|could|is|do)n['’`]t\b/u", '$1 not', $slug);
            $slug = preg_replace("/\b(who|what|when|where|why|how|it)['’`]s\b/u", '$1 is', $slug);
            $slug = preg_replace("/\b(was|is|had|has|do|does|did|could|would)n['’`]t\b/u", '$1 not', $slug);
            $slug = preg_replace("/['’`]d\b/u", '', $slug);
            $slug = preg_replace("/['’`]s\b/u", '', $slug);    // Convert posessive to keyword (Company's -> Company)
        }

        $slug = preg_replace("/(\w)['’`](\w)/u", '$1$2', $slug);   // glue remaining contractions together

        $words = preg_split('/[^a-z0-9]+/u', trim($slug));

        $slugWords = $words;

        if ($skipWords) {
            $slugWords = [];
            $skipWords = preg_split('/\s+/', trim($skipWords));
            foreach ($words as $word) {
                if (!$this->isSlugSkipWord($word, $skipWords)) {
                    $slugWords []= $word;
                }
            }
            $slugWords = array_unique($slugWords, SORT_STRING);
        }

        $slug = implode('-', $slugWords);
        $slug = trim($slug, '-');

        return $slug;
    }

    function isSlugSkipWord($word, $skipWords) {

        foreach ($skipWords as $skipWord) {
            if ($word == $skipWord) {
                return true;
            }
            else if ($skipWord[0] == '-') {
                // Allow fuzzy end-match with `-`: -ly -> really, finally, etc.
                $match = ltrim($skipWord, '-');
                if (preg_match("/\b\w+" . $match . "\b\-?/u", $word)) {
                    return true;
                }
            }
        }
        return false;
    }

    function tokenize($delim = '-') {

        $this->ARGS('s', func_get_args());

        $v = $this->u_to_ascii();

        // Convert from camel
        $v = preg_replace("/([A-Z]+)/", " $1", $v);

        $v = trim(strtolower($v));
        $v = str_replace("'", '', $v);
        $v = preg_replace('/[^a-z0-9]+/', $delim, $v);
        $v = rtrim($v, $delim);

        return $v;
    }

    function u_repeat ($num) {

        $this->ARGS('I', func_get_args());

        return str_repeat($this->val, $num);
    }



    // Whitespace

    function u_pad_both($padLen, $padStr = ' ') {

        $this->ARGS('Is', func_get_args());

        return mb_str_pad($this->val, $padLen, $padStr, STR_PAD_BOTH);

    //    return $this->pad($padLen, $padStr, 'both');
    }

    function u_pad_left($padLen, $padStr = ' ') {

        $this->ARGS('Is', func_get_args());

        return mb_str_pad($this->val, $padLen, $padStr, STR_PAD_LEFT);

      //  return $this->pad($padLen, $padStr, 'left');
    }

    function u_pad_right($padLen, $padStr = ' ') {

        $this->ARGS('Is', func_get_args());

        return mb_str_pad($this->val, $padLen, $padStr, STR_PAD_RIGHT);

     //   return $this->pad($padLen, $padStr, 'right');
    }

    // function pad($padLen, $padStr = ' ', $dir = 'right') {

    //     $this->ARGS('Iss', func_get_args());

    //     $str = $this->val;

    //     $padLeft  = $dir === 'both' || $dir === 'left';
    //     $padRight = $dir === 'both' || $dir === 'right';

    //     $padLen -= mb_strlen($str);
    //     $targetLen = $padLeft && $padRight ? $padLen / 2 : $padLen;

    //     $strToRepeatLen = mb_strlen($padStr);
    //     $repeatTimes = ceil($targetLen / $strToRepeatLen);
    //     $repeatedString = str_repeat($padStr, max(0, $repeatTimes));

    //     $left = $padLeft  ? mb_substr($repeatedString, 0, floor($targetLen)) : '';
    //     $right = $padRight ? mb_substr($repeatedString, 0, ceil($targetLen)) : '';

    //     return $left . $str . $right;
    // }

    function u_trim ($mask='') {

        $this->ARGS('s', func_get_args());

        if ($mask === '') {
            return trim($this->val);
        }
        else {
            // necessary for utf-8 support
            $m = $this->get_trim_regex($mask);
            return preg_replace('/^' . $m . '|'. $m . '$/u', '', $this->val);
        }
    }

    function u_trim_left ($mask='') {

        $this->ARGS('s', func_get_args());

        if ($mask === '') {
            return ltrim($this->val);
        }
        else {
            // necessary for utf-8 support
            $m = $this->get_trim_regex($mask);
            return preg_replace('/^' . $m . '/u', '', $this->val);
        }
    }

    function u_trim_right ($mask='') {

        $this->ARGS('s', func_get_args());

        if ($mask === '') {
            return rtrim($this->val);
        }
        else {
            // necessary for utf-8 support
            $m = $this->get_trim_regex($mask);
            return preg_replace('/' . $m . '$/u', '', $this->val);
        }
    }

    function get_trim_regex($mask) {
        return '[' . preg_quote($mask, '/') . '\s]+';
    }

    function u_trim_lines () {

        $this->ARGS('', func_get_args());

        $trimmed = rtrim($this->val);

        $lines = explode("\n", $trimmed);

        while (count($lines)) {
            $line = $lines[0];
            if (preg_match('/\S/u', $line)) {
                break;
            } else {
                array_shift($lines);
            }
        }

        return implode("\n", $lines);
    }

    function u_trim_indent ($flags = null) {

        $this->ARGS('m', func_get_args());

        $flags = $this->flags($flags, [
            'keepRelative' => false,
        ]);

        // TODO: do this?
        $trimmed = $this->u_trim_lines();
        if (!strlen($trimmed)) { return ''; }

        $lines = explode("\n", $trimmed);

        $numLines = count($lines);

        if ($numLines === 1) { return ltrim($trimmed); }

        if (!$flags['keepRelative']) {
            // Remove all indentation
            for ($i = 0; $i < $numLines; $i += 1) {
                $lines[$i] = ltrim($lines[$i]);
            }
        }
        else {
            // Count relative indent
            $minIndent = 999;
            foreach ($lines as $line) {
                if (!preg_match('/\S/u', $line)) { continue; }
                preg_match('/^(\s*)/u', $line, $match);
                $indent = strlen($match[1]);
                $minIndent = min($indent, $minIndent);
                if (!$indent) { break; }
            }

            for ($i = 0; $i < $numLines; $i += 1) {
                $lines[$i] = substr($lines[$i], $minIndent);
            }
        }

        return implode("\n", $lines);
    }

    function u_indent($level) {

        $this->ARGS('i', func_get_args());

        $lines = explode("\n", $this->val);
        $out = '';

        foreach ($lines as $line) {
            $line = str_repeat(' ', $level) . $line;
            $line = rtrim($line);
            $out .= $line . "\n";
        }

        return $out;
    }

    // TODO: undocumented - necessary?
    function u_squeeze ($char='') {

        $this->ARGS('s', func_get_args());

        $char = $char === '' ? '\s' : preg_quote($char);

        return preg_replace('/([' . $char . '])+/u', '$1', $this->val);
    }

    function u_limit ($numChars, $end='…') {

        $this->ARGS('Is', func_get_args());
        $s = $this->val;

        if (mb_strlen($s) > $numChars) {
            $s = mb_substr($s, 0, $numChars);
            $s = rtrim($s, '?!.;,');
            $s = $s . $end;
        }

        return $s;
    }


    // Format

    // This is a little wonky.  Using statics for preg_callback below.
    static private $fillArgs;
    static private $fillArgNum;
    static private $me;

    static function cbFill ($matches) {

        $key = $matches[1];

        // Interpret empty {} as sequential args: {1}, {2}, ...
        if ($key == '') {
            $key = self::$fillArgNum;
            self::$fillArgNum += 1;
        }
        else if (is_numeric($key)) {
            $key = (int) $key;
        }
        else if (!OMap::isa(self::$fillArgs)) {
            self::$me->error("Can't look up `fill` value for `$key` because it was not given a Map.");
        }

        if (!isset(self::$fillArgs[$key])) {
            self::$me->error("Key `$key` is not found in list of fill values.");
        }

        return self::$fillArgs[$key];
    }

    function u_fill () {

        $args = OList::create(func_get_args());

        // Allow single arg as list or map
        if ($args->u_length() == 1) {
            if (OBag::isa($args[1])) {
                $args = $args[1];
            }
        }

        self::$fillArgs = $args;
        self::$fillArgNum = ONE_INDEX;
        self::$me = $this;

        $filled = preg_replace_callback('/\{([a-zA-Z0-9]*)\}/u', '\o\OString::cbFill', $this->val);

        return $filled;
    }

    function u_escape_regex () {

        $this->ARGS('', func_get_args());

        return preg_quote($this->val, '`');
    }




    // TODO: move this to a Csv module with related methods
    // function u_parse_csv () {

    //     $this->ARGS('l', func_get_args());

    //     $data = str_getcsv($this->val);

    //     if (!$data || $data[0] == null) {
    //         return false;
    //     }

    //     // Convert numeric fields to numbers
    //     foreach ($data as $i => $cell) {
    //         if (is_numeric($cell)) { $data[$i] = floatval($cell); }
    //     }

    //     return OList::create($data);
    // }


    // Checks

    // function u_has_char_type($charType) {

    //     $rxClass = $this->ereg_class($charType);

    //     return preg_match('/' . $rxClass . '/u', $this->val);
    // }

    // function u_has_all_char_type($charType) {

    //     $rxClass = $this->ereg_class($charType);

    //     return preg_match('/^' . $rxClass . '+$/u', $this->val);

    // }



    // function u_is_upper_case() {

    //     $this->ARGS('', func_get_args());

    //     return $this->u_has_upper_case() && !$this->u_has_lower_case();
    // }

    // function u_has_upper_case() {

    //     $this->ARGS('', func_get_args());

    //     return mb_ereg_match('.*[[:upper:]]', $this->val);
    // }

    // function u_is_lower_case() {

    //     $this->ARGS('', func_get_args());

    //     return $this->u_has_lower_case() && !$this->u_has_upper_case();
    // }

    // function u_has_lower_case() {

    //     $this->ARGS('', func_get_args());

    //     return mb_ereg_match('.*[[:lower:]]', $this->val);
    // }

    // function u_is_whitespace() {

    //     $this->ARGS('', func_get_args());

    //     // Matches python's isspace logic
    //     if ($this->val === '') { return false; }

    //     return mb_ereg_match('^[[:space:]]+$', $this->val);
    // }

    // function u_has_whitespace() {

    //     $this->ARGS('', func_get_args());

    //     return mb_ereg_match('.*[[:space:]]', $this->val);
    // }

    // function u_is_alpha() {

    //     $this->ARGS('', func_get_args());

    //     return mb_ereg_match('^[[:alpha:]]+$', $this->val);
    // }

    // function u_is_alpha_numeric() {

    //     $this->ARGS('', func_get_args());

    //     return mb_ereg_match('^[[:alnum:]]+$', $this->val);
    // }

    // TODO: Allow scientific notation?
    function u_is_numeric($numType='any') {

        $this->ARGS('s', func_get_args());

        if (!preg_match('/^-?[0-9]+\.?[[0-9]*$/u', $this->val)) {
            return false;
        }

        if ($numType == 'float') {
            return is_float($this->val);
        }
        else if ($numType == 'any' || $numType == 'int') {
            return true;
        }
        else {
            $this->error('Invalid `numType` argument: `$numType`  Try: `int`, `float`, `any`');
        }
    }

    // function u_is_ascii() {

    //     $this->ARGS('', func_get_args());

    //     return mb_ereg_match('^[[:ascii:]]*$', $this->val);
    // }

    // Encoding

    function u_to_encoding($encodingId) {

        $this->ARGS('s', func_get_args());

        if ($encodingId == 'html') {
            return Security::escapeHtml($this->val);
        }
        else if ($encodingId == 'htmlAll') {
            return Security::escapeHtmlAllChars($this->val);
        }
        else if ($encodingId == 'url') {
            return rawurlencode($this->val);
        }
        else if ($encodingId == 'base64') {
            return base64_encode($this->val);
        }
        else if ($encodingId == 'punycode') {
            $out = idn_to_ascii($this->val);
            if ($out === false) {
                $this->error("Unable to convert string to `punycode`: `" . $this->val . "`");
            }
            return $out;
        }
        else {
            return $this->convertEncoding($this->val,'to', $encodingId);
        }
    }

    function u_from_encoding($encodingId) {

        $this->ARGS('s', func_get_args());

        if ($encodingId == 'html' || $encodingId == 'htmlAll') {
            return Security::unescapeHtml($this->val);
        }
        else if ($encodingId == 'url') {
            return rawurldecode($this->val);
        }
        else if ($encodingId == 'base64') {
            $failIfInvalidChar = true;
            $plain = base64_decode($this->val, $failIfInvalidChar);
            if ($plain === false) {
                $this->error('base64 string contains invalid character.');
            }
            return $plain;
        }
        else if ($encodingId == 'punycode') {
            $out = idn_to_utf8($this->val);
            if ($out === false) {
                $this->error("Unable to convert string from `punycode`: `" . $this->val . "`");
            }
            return $out;
        }
        else {
            return $this->convertEncoding($this->val, 'from', $encodingId);
        }
    }

    function convertEncoding($str, $toFrom, $encodingId) {
        if (!in_array($encodingId, mb_list_encodings())) {
            $this->error("Invalid encoding: `$encodingId`");
        }
        $out = mb_convert_encoding($this->val, $toFrom == 'to' ? $encodingId : 'UTF-8');
        if ($out === false) {
            $this->error("Unable to convert string $toFrom encoding: `$encodingId`");
        }
        return $out;
    }

    function u_fingerprint($algo='sha256', $flags=null) {

        $flags = $this->flags($flags, [
            'binary' => false,
        ]);

        $this->ARGS('s*', func_get_args());

        if (!in_array($algo, hash_algos())) {
            $this->error("Unknown hash algorithm: `$algo`");
        }

        $hash = Security::hashString($this->val, $algo, $flags['binary']);

        return $hash;
    }

    function u_to_bytes() {

        $this->ARGS('', func_get_args());

        $len = strlen($this->val);
        $bytes = [];
        for ($i = 0; $i < $len; $i += 1) {
            $bytes []= unpack('C*', $this->val[$i])[1];
        }

        return OList::create($bytes);
    }





    // Casting

    function u_to_number ($numType='any') {

        if (!$this->u_is_numeric()) { return 0; }

        if ($numType == 'float') {
            return floatval($this->val);
        }
        else if ($numType == 'int') {
            return intval($this->val);
        }
        else if ($numType == 'any') {
            $f = floatval($this->val);
            $i = intval($this->val);
            return $f == $i ? $i : $f;
        }
        else {
            $this->error('Invalid `numType` argument: `$numType`  Try: `int`, `float`, `any`');
        }
    }

    function u_to_boolean () {

        $this->ARGS('', func_get_args());
        $v = trim($this->val);

        if ($v === '') {
            return false;
        }

        return true;
    }

    function u_to_string () {

        $this->ARGS('', func_get_args());

        return $this->val;
    }

    function u_to_value () {

        $this->ARGS('', func_get_args());

        $v = trim($this->val);
        if ($v === '') { return ''; }

        if ($v[0] === '"' || $v[0] === "'" || $v[0] === '`') {
            // trim surrounding quotes
            if ($v[mb_strlen($v) - 1] === $v[0]) {
                $v = trim($v, $v[0]);
            }
            return $v;
        }
        if ($v === 'true') {
            return true;
        }
        else if ($v === 'false') {
            return false;
        }
        else if (preg_match('/^-?[0-9\.]+$/u', $v)) {
            if (strpos($v, '.') !== false) {
                return floatval($v);
            } else {
                return intval($v);
            }
        } else {
            return $v;
        }
    }

    function u_to_url () {

        $this->ARGS('', func_get_args());

        return OTypeString::create('url', $this->val);
    }


    // Checks

    function u_is_url ($isStrict=false) {

        $this->ARGS('b', func_get_args());

        if ($isStrict) {
            $v = ltrim($this->val);

            return preg_match('~^(https?:)?//~iu', $v);
        }
        else {
            if (preg_match('/^[a-zA-Z]:/', $this->val)) {
                // windows path with drive letter
                return false;
            }
            return preg_match('~(:|//)~u', $this->val);
        }

    }


    // Utils

    // Slow-ish due to so many regexes, but this will usually only be called on
    // inbound user data, not on every output.
    function u_civilize() {

        $this->ARGS('', func_get_args());

        $s = trim($this->val);

        $s = $this->removeAllCaps($s);

        // truncate repeated characters
        $s = preg_replace('/\n{4,}/u', "\n\n\n", $s);
        $s = preg_replace("/!{2,}/u", "!", $s);
        $s = preg_replace("/\?{2,}/u", "?", $s);
        $s = preg_replace('/[\?!]{3,}/u', "?!", $s);
        $s = preg_replace("/(.)\\1{4,}/u", '\\1\\1\\1', $s);

        // TODO: break lines over 80 chars instead?
        $s = preg_replace_callback("/(\S{40,})/u", '\o\OString::truncateLongString', $s);

        return $s;
    }

    function u_humanize() {

        $this->ARGS('', func_get_args());

        $s = $this->tokenize(' ');
        $s = trim($s);

        // Remove ID
        $s = preg_replace('/^(.*) id$/iu', '$1', $s);

        $s = v($s)->u_to_case('title');

        return $s;
    }

    // prevent ALL CAPS
    function removeAllCaps($s) {

        $alphaOnly = preg_replace("/[^a-zA-Z]/u", "", $s);
        $numAlpha = mb_strlen($alphaOnly);

        $capsOnly = preg_replace("/([^A-Z])/u", '', $alphaOnly);
        $numCaps = mb_strlen($capsOnly);

        $maxCaps = floor($numAlpha * 0.6);

        if ($numCaps >= $maxCaps) {
            $s = mb_strtolower($s);
            $s = v($s)->u_to_upper_case(Omap::create([ 'first' => true ]));
        }

        return $s;
    }

    static function truncateLongString ($raw) {

        if (preg_match("/http/ui", $raw[1])) {
            # preserve URLs
            return $raw[1];
        }
        else {
            return mb_substr($raw[1], 0, 40) . '…';
        }
    }

}




