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
    ];


    // ArrayAccess iterface (e.g. $var[1])

    function offsetGet ($k) {

        $k = $this->checkIndex($k, true);

        if ($k == -1) {
            return '';
        }

        return mb_substr($this->val, $k, 1);
    }

    // Unfortunately, we can't do this because we can't autobox strings by reference.
    // And we can't do that because we also autobox expressions, which can't be passed by reference.
    function offsetSet ($ak, $v) {

        if (is_null($ak)) {
            $this->error('Left side of `#=` must be a list. Got: string');
        }

        $this->error('Can not use `[]` to set character on immutable string. Try: `.setChar($index, $newChar)`');
    }

    function offsetExists ($k) {
        // not used
    }

    function offsetUnset ($k) {
        // not used
    }

    // Note: Similar method in OBag for Lists
    function checkIndex ($ak, $allowOutOfRange = false) {

        $k = $ak;

        if (!is_int($k)) {
            $this->error("String index must be numeric.  Saw `$k` instead.");
        }
        else if (ONE_INDEX && $k == 0) {
            $this->error('Index `0` is not valid.  The first character has an index of `1`.');
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

    function u_has_index($i) {

        $this->ARGS('i', func_get_args());

        $i = $this->checkIndex($i, true);

        return $i === -1 ? false : true;
    }

    function u_get_char($i) {

        $this->ARGS('i', func_get_args());

        $i = $this->checkIndex($i, true);
        if ($i == -1) {
            return '';
        }

        return mb_substr($this->val, $i, 1);
    }

    function u_get_char_code($i = 1) {

        $this->ARGS('i', func_get_args());

        $i = $this->checkIndex($i, true);

        if ($i == -1) {
            return -1;
        }

        $char = mb_substr($this->val, $i, 1);

        return unpack('V', iconv('UTF-8', 'UCS-4LE', $char))[1];
    }

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

    function u_index_of($s, $ignoreCase=false, $startPos=ONE_INDEX) {

        $this->ARGS('sbi', func_get_args());

        return $this->_strpos($s, $ignoreCase, $startPos);
    }

    function u_last_index_of($s, $ignoreCase=false, $startPos=ONE_INDEX) {

        $this->ARGS('sbi', func_get_args());

        return $this->_strrpos($s, $ignoreCase, $startPos);
    }

    function u_substring($start, $len=null) {

        $this->ARGS('ii', func_get_args());

        $start = $this->checkIndex($start);

        $len = !$len ? null : $len;

        return mb_substr($this->val, $start, $len);
    }

    function u_contains($s, $ignoreCase=false) {

        $this->ARGS('sb', func_get_args());

        return $this->_strpos($s, $ignoreCase) > -1 + ONE_INDEX;
    }

    function u_count($s, $ignoreCase=false) {

        $this->ARGS('sb', func_get_args());

        $haystack = $ignoreCase ? strtolower($this->val) : $this->val;
        $needle = $ignoreCase ? strtolower($s) : $s;

        return substr_count($haystack, $needle);
    }

    function u_starts_with($s, $ignoreCase=false) {

        $this->ARGS('sb', func_get_args());

        return $this->_strpos($s, $ignoreCase) === ONE_INDEX;
    }

    function u_ends_with($s, $ignoreCase=false) {

        $this->ARGS('sb', func_get_args());

        $x = $this->val;
        $suffLen = v($s . '')->u_length();
        $this->val = $x;

        return $this->_strpos($s, $ignoreCase) == ($this->u_length() - $suffLen) + ONE_INDEX;
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

        return '';
    }

    function getMatchReturn($matches, $sGroupNames) {

        // No capture groups. Just return the full match.
        if (count($matches) == 1) {
            return $matches[0][0];
        }
        else {
            // Return Map of capture groups
            $ret = OMap::create([]);

            $ret['full'] = $matches[0][0];
            array_shift($matches);

            $ret['indexOf'] = OMap::create([]);

            if ($sGroupNames) {
                $groupNames = preg_split('/\s*\|\s*/', $sGroupNames);
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

    function u_replace ($find, $replace, $limit=0) {

        $this->ARGS('**I', func_get_args());

        if ($limit <= 0) { $limit = -1; }

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
            return $fn($find->getPattern(), $replace, $this->val, $limit);
        }
        else {
            return str_replace($find, $replace, $this->val, $limit);
        }
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
    function u_remove_accents() {
        $this->ARGS('', func_get_args());

        $from = "ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿĀāĂăĄąĆćĈĉĊċČčĎďĐđĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħĨĩĪīĬĭĮįİıĲĳĴĵĶķĸĹĺĻļĽľĿŀŁłŃńŅņŇňŉŊŋŌōŎŏŐőŒœŔŕŖŗŘřŚśŜŝŞşŠšŢţŤťŦŧŨũŪūŬŭŮůŰűŲųŴŵŶŷŸŹźŻżŽžſ";
        $to   = "AAAAAAACEEEEIIIIENOOOOOOUUUUYPsaaaaaaaceeeeiiiienoooooouuuuypyAaAaAaCcCcCcCcDdDdEeEeEeEeEeGgGgGgGgHhHhIiIiIiIiIiIiJjKklLlLlLlLlLnNnNnNnnNoOoOoOoOrRrRrRsSsSsSsStTtTtTuUuUuUuUuUuUwWyYyYZzZzZzs";

        $str = $this->u_replace_chars($from, $to);

        return $str;
    }

    function u_remove_left($s, $ignoreCase=false) {

        $this->ARGS('sb', func_get_args());

        if ($this->u_starts_with($s, $ignoreCase)) {
            $orig = $this->val;  // save after singleton changes
            $len = v($s)->u_length();

            return v($orig)->u_substring($len + ONE_INDEX);
        }

        return $this->val;
    }

    function u_remove_right($s, $ignoreCase=false) {

        $this->ARGS('sb', func_get_args());

        if ($this->u_ends_with($s, $ignoreCase)) {
            $orig = $this->val;  // save after singleton changes
            $len = $this->u_length() - v($s)->u_length();

            return v($orig)->u_substring(1, $len);
        }

        return $this->val;
    }

    function u_remove_first($s, $ignoreCase=false) {

        $this->ARGS('sb', func_get_args());

        if ($this->u_ends_with($s, $ignoreCase)) {
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

    function u_split ($delim='', $limit=0, $keepEmpty = false) {

        $this->ARGS('*Ib', func_get_args());

        if ($limit == 0) { $limit = PHP_INT_MAX; }

        if (ORegex::isa($delim)) {
            return OList::create(
                preg_split($delim->getPattern(), $this->val, $limit)
            );
        }
        if ($delim === '') {
            return $this->u_split_chars();
        }

        $parts = explode($delim, $this->val, $limit);

        if (!$keepEmpty) {
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

    function u_split_lines ($keepWhitespace=false) {

        $this->ARGS('b', func_get_args());

        if ($keepWhitespace) {
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

    function u_split_words ($bareWords=false) {

        $this->ARGS('b', func_get_args());

        $v = $this->val;

        if ($bareWords) {
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

    function u_upper_case () {

        $this->ARGS('', func_get_args());

        return mb_strtoupper($this->val);
    }

    function u_lower_case () {

        $this->ARGS('', func_get_args());

        return mb_strtolower($this->val);
    }

    function u_upper_case_first () {

        $this->ARGS('', func_get_args());

        $first = mb_strtoupper(mb_substr($this->val, 0, 1));

        return $first . mb_substr($this->val, 1);
    }

    function u_lower_case_first () {

        $this->ARGS('', func_get_args());

        $first = mb_strtolower(mb_substr($this->val, 0, 1));

        return $first . mb_substr($this->val, 1);
    }

    function u_title_case ($skipWords=null) {

        $this->ARGS('l', func_get_args());

        if (is_null($skipWords)) {
            $skipWords = ['the', 'a', 'is', 'to', 'at', 'by', 'for', 'in', 'of'];
        }

        $titleCased = preg_replace_callback(
            '/([\S]+)/u',
            function ($match) use ($skipWords) {
                if ($skipWords && in_array($match[0], $skipWords)) {
                    return $match[0];
                }
                return v(v($match[0])->u_lower_case())->u_upper_case_first();
            },
            $this->val
        );

        return v($titleCased)->u_upper_case_first();
    }

    function u_plural ($num=2, $plural='') {

        $this->ARGS('ns', func_get_args());

        $last = substr($this->val, -1, 1);

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

    function u_camel_case ($isUpperCamel=false) {

        $this->ARGS('b', func_get_args());

        $v = $this->val;

        $v = strtolower($v);
        $v = preg_replace('/[^a-z0-9]+/', ' ', $v);
        $v = trim($v);

        $parts = preg_split('/\s+/', $v);
        $camel = '';
        foreach ($parts as $p) {
            $camel .= ucfirst($p);
        }

        return $isUpperCamel ? $camel : lcfirst($camel);
    }

    function u_snake_case () {

        $this->ARGS('', func_get_args());

        return $this->u_slug('_');
    }

    function u_dash_case () {

        $this->ARGS('', func_get_args());

        return $this->u_slug('-');
    }

    function u_slug($delim = '-') {

        $this->ARGS('s', func_get_args());

        $v = $this->val;

        // Convert from camel
        $v = preg_replace("/([A-Z]+)/", " $1", $v);

        $v = trim(strtolower($v));
        $v = str_replace("'", '', $v);
        $v = preg_replace('/[^a-z0-9]+/', $delim, $v);
        $v = rtrim($v, $delim);

        return $v;
    }

    function u_fingerprint() {

        $this->ARGS('', func_get_args());

        return Security::hashString($this->val);
    }

    function u_repeat ($num) {

        $this->ARGS('I', func_get_args());

        return str_repeat($this->val, $num);
    }



    // Whitespace

    function u_pad_both($padLen, $padStr = ' ') {

        $this->ARGS('Is', func_get_args());

        return $this->pad($padLen, $padStr, 'both');
    }

    function u_pad_left($padLen, $padStr = ' ') {

        $this->ARGS('Is', func_get_args());

        return $this->pad($padLen, $padStr, 'left');
    }

    function u_pad_right($padLen, $padStr = ' ') {

        $this->ARGS('Is', func_get_args());

        return $this->pad($padLen, $padStr, 'right');
    }

    function pad($padLen, $padStr = ' ', $dir = 'right') {

        $this->ARGS('Iss', func_get_args());

        $str = $this->val;

        $padLeft  = $dir === 'both' || $dir === 'left';
        $padRight = $dir === 'both' || $dir === 'right';

        $padLen -= mb_strlen($str);
        $targetLen = $padLeft && $padRight ? $padLen / 2 : $padLen;

        $strToRepeatLen = mb_strlen($padStr);
        $repeatTimes = ceil($targetLen / $strToRepeatLen);
        $repeatedString = str_repeat($padStr, max(0, $repeatTimes));

        $left = $padLeft  ? mb_substr($repeatedString, 0, floor($targetLen)) : '';
        $right = $padRight ? mb_substr($repeatedString, 0, ceil($targetLen)) : '';

        return $left . $str . $right;
    }

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
            if (preg_match('/\S/', $line)) {
                break;
            } else {
                array_shift($lines);
            }
        }

        return implode("\n", $lines);
    }

    function u_trim_indent ($keepRelative=false) {

        $this->ARGS('b', func_get_args());

        // TODO: do this?
        $trimmed = $this->u_trim_lines();
        if (!strlen($trimmed)) { return ''; }

        $lines = explode("\n", $trimmed);

        $numLines = count($lines);

        if ($numLines === 1) { return ltrim($trimmed); }

        if (!$keepRelative) {
            // Remove all indentation
            for ($i = 0; $i < $numLines; $i += 1) {
                $lines[$i] = ltrim($lines[$i]);
            }
        }
        else {
            // Count relative indent
            $minIndent = 999;
            foreach ($lines as $line) {
                if (!preg_match('/\S/', $line)) { continue; }
                preg_match('/^(\s*)/', $line, $match);
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

        $filled = preg_replace_callback('/\{([a-zA-Z0-9]*)\}/', '\o\OString::cbFill', $this->val);

        return $filled;
    }


    // Encoding

    function u_encode_html ($all = false) {

        $this->ARGS('b', func_get_args());

        if ($all) {
            $str = mb_convert_encoding($this->val , 'UTF-32', 'UTF-8');
            $t = unpack("N*", $str);
            $t = array_map(function($n) { return "&#$n;"; }, $t);
            return implode("", $t);
        }

        return Security::escapeHtml($this->val);
    }

    function u_decode_html () {

        $this->ARGS('', func_get_args());

        return Security::unescapeHtml($this->val);
    }

    function u_encode_url () {

        $this->ARGS('', func_get_args());

        return rawurlencode($this->val);
    }

    function u_decode_url () {

        $this->ARGS('', func_get_args());

        return rawurldecode($this->val);
    }

    function u_encode_base64 () {

        $this->ARGS('', func_get_args());

        return base64_encode($this->val);
    }

    function u_decode_base64 () {

        $this->ARGS('', func_get_args());

        return base64_decode($this->val);
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

    function u_is_upper_case() {

        $this->ARGS('', func_get_args());

        return $this->u_has_upper_case() && !$this->u_has_lower_case();
    }

    function u_has_upper_case() {

        $this->ARGS('', func_get_args());

        return mb_ereg_match('.*[[:upper:]]', $this->val);
    }

    function u_is_lower_case() {

        $this->ARGS('', func_get_args());

        return $this->u_has_lower_case() && !$this->u_has_upper_case();
    }

    function u_has_lower_case() {

        $this->ARGS('', func_get_args());

        return mb_ereg_match('.*[[:lower:]]', $this->val);
    }

    function u_is_space() {

        $this->ARGS('', func_get_args());

        // Matches python's isspace logic
        if ($this->val === '') { return false; }

        return mb_ereg_match('^[[:space:]]+$', $this->val);
    }

    function u_has_space() {

        $this->ARGS('', func_get_args());

        return mb_ereg_match('.*[[:space:]]', $this->val);
    }

    function u_is_alpha() {

        $this->ARGS('', func_get_args());

        return mb_ereg_match('^[[:alpha:]]+$', $this->val);
    }

    function u_is_alpha_numeric() {

        $this->ARGS('', func_get_args());

        return mb_ereg_match('^[[:alnum:]]+$', $this->val);
    }

    function u_is_number() {

        $this->ARGS('', func_get_args());

        return mb_ereg_match('^-?[[:digit:]]+\.?[[:digit:]]*$', $this->val);
    }

    function u_is_ascii() {

        $this->ARGS('', func_get_args());

        return mb_ereg_match('^[[:ascii:]]*$', $this->val);
    }





    // Casting

    function u_to_number ($thousand=',', $decimal='.') {

        $this->ARGS('ss', func_get_args());

        $v = $this->val;

        if ($thousand !== '') {
            $v = str_replace($thousand, '', $v);
        }

        if ($decimal !== '' && $decimal !== '.') {
            $v = str_replace($decimal, '.', $v);
        }

        $f = floatval($v);
        $i = intval($v);

        return $f == $i ? $i : $f;
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
        else if (preg_match('/^-?[0-9\.]+$/', $v)) {
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

            return preg_match('~^(https?:)?//~i', $v);
        }
        else {
            if (preg_match('/^[a-zA-Z]:/', $this->val)) {
                // windows path with drive letter
                return false;
            }
            return preg_match('~(:|//)~', $this->val);
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
        $s = preg_replace('/\n{4,}/', "\n\n\n", $s);
        $s = preg_replace("/!{2,}/", "!", $s);
        $s = preg_replace("/\?{2,}/", "?", $s);
        $s = preg_replace('/[\?!]{3,}/', "?!", $s);
        $s = preg_replace("/(.)\\1{4,}/", '\\1\\1\\1', $s);

        // TODO: break lines over 80 chars instead?
        $s = preg_replace_callback("/(\S{40,})/", '\o\OString::truncateLongString', $s);

        return $s;
    }

    function u_humanize() {

        $this->ARGS('', func_get_args());

        $s = $this->u_slug(' ');
        $s = trim($s);

        // Remove ID
        $s = preg_replace('/^(.*) id$/i', '$1', $s);

        $s = v($s)->u_title_case();

        return $s;
    }

    // prevent ALL CAPS
    function removeAllCaps($s) {

        $alphaOnly = preg_replace("/[^a-zA-Z]/", "", $s);
        $numAlpha = mb_strlen($alphaOnly);

        $capsOnly = preg_replace("/([^A-Z])/", '', $alphaOnly);
        $numCaps = mb_strlen($capsOnly);

        $maxCaps = floor($numAlpha * 0.6);

        if ($numCaps >= $maxCaps) {
            $s = strtolower($s);
            $s = ucfirst($s);
        }

        return $s;
    }

    static function truncateLongString ($raw) {

        if (preg_match("/http/i", $raw[1])) {
            # preserve URLs
            return $raw[1];
        }
        else {
            return mb_substr($raw[1], 0, 40) . '…';
        }
    }

}




