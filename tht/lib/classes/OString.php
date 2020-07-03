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

        'upper'   => 'toUpperCase()',
        'toupper' => 'toUpperCase()',
        'lower'   => 'toLowerCase()',
        'tolower' => 'toLowerCase()',

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

    // Indexing

    function offsetGet ($k) {
        if ($k < 0) { $k = mb_strlen($this->val) + $k; }
        return $this->val[$k];
    }

    function offsetSet ($k, $v) {
        $this->val[$k] = $v;
    }

    function offsetExists ($k) {
        // not used
    }

    function offsetUnset ($k) {
        // not used
    }


    //// Basic

    function u_length() {
        $this->ARGS('', func_get_args());
        return mb_strlen($this->val);
    }


    //// Substrings


    function _strpos($s, $ignoreCase=false, $offset=0) {
        if ($ignoreCase) {
            return mb_stripos($this->val, $s, $offset);
        } else {
            return mb_strpos($this->val, $s, $offset);
        }
    }

    function _strrpos($s, $ignoreCase=false, $offset=0) {
        if ($ignoreCase) {
            return mb_strripos($this->val, $s, -1 * $offset);
        } else {
            return mb_strrpos($this->val, $s, -1 * $offset);
        }
    }

    function u_char_at($i) {
        $this->ARGS('n', func_get_args());
        return mb_substr($this->val, $i, 1);
    }

    function u_char_code_at ($i) {
        $this->ARGS('n', func_get_args());
        $char = mb_substr($this->val, $i, 1);
        return unpack('V', iconv('UTF-8', 'UCS-4LE', $char))[1];
    }

    function u_left($n) {
        $this->ARGS('n', func_get_args());
        return $n <= 0 ? '' : $this->u_substring(0, $n);
    }

    function u_right($n) {
        $this->ARGS('n', func_get_args());
        return $n <= 0 ? '' : $this->u_substring(-1 * $n, $n);
    }

    function u_index_of($s, $offset=0, $ignoreCase=false) {
        $this->ARGS('snf', func_get_args());
        return $this->_strpos($s, $ignoreCase, $offset);
    }

    function u_last_index_of($s, $offset=0, $ignoreCase=false) {
        $this->ARGS('snf', func_get_args());
        return $this->_strrpos($s, $ignoreCase, $offset);
    }

    function u_substring($start, $len=null) {
        $this->ARGS('nn', func_get_args());
        $len = $len === null ? $this->u_length() : $len;
        return mb_substr($this->val, $start, $len);
    }

    function u_contains($s, $ignoreCase=false) {
        $this->ARGS('sf', func_get_args());
        return $this->_strpos($s, $ignoreCase) !== false;
    }

    function u_count($s, $ignoreCase=false) {
        $this->ARGS('*f', func_get_args());

        if (ORegex::isa($s)) {
            if ($ignoreCase) { $s->addFlag('i'); }
            $numMatches = preg_match_all($s->getPattern(), $this->val);
            return $numMatches;
        } else {
            $haystack = $ignoreCase ? strtolower($this->val) : $this->val;
            $needle = $ignoreCase ? strtolower($s) : $s;
            return substr_count($haystack, $needle);
        }
    }

    function u_starts_with($s, $ignoreCase=false) {
        $this->ARGS('sf', func_get_args());
        return $this->_strpos($s, $ignoreCase) === 0;
    }

    function u_ends_with($s, $ignoreCase=false) {
        $this->ARGS('sf', func_get_args());
        $x = $this->val;
        $suffLen = v($s . '')->u_length();
        $this->val = $x;
        return $this->_strpos($s, $ignoreCase) == ($this->u_length() - $suffLen);
    }





    // Locking


    // Just for convenience if mixing and matching with OTypeStrings
    function u_stringify () {
        $this->ARGS('', func_get_args());
        return $this->val;
    }

    function u_x_danger_set_type ($type) {
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

    function u_match ($match, $offset = 0) {
        $this->ARGS('*n', func_get_args());
        if (ORegex::isa($match)) {
            $found = preg_match($match->getPattern(), $this->val, $matches, 0, $offset);
            if ($found === false) {
                $this->error('Error in match: ' . $this->pregErrorMessage(), [
                    'var' => $this->val,
                    'pattern' => $match->getPattern(),
                ]);
            }
            return $found === 1 ? OList::create($matches) : false;
        }
        else {
            $this->error("Argument 1 must be a Regex string `r'...'`");
        }
    }

    function u_match_all ($match, $offset = 0) {
        $this->ARGS('*n', func_get_args());
        if (ORegex::isa($match)) {
            $matched = preg_match_all($match->getPattern(), $this->val, $matches, PREG_SET_ORDER, $offset);
            if (!$matched) {
                return false;
            }
            foreach ($matches as &$m) {
                $m = OList::create($m);
            }
            return OList::create($matches);
        }
        else {
            $this->error("Argument 1 must be a Regex string `r'...'`");
        }
    }

    function u_replace ($find, $replace, $limit=-1) {
        $this->ARGS('**n', func_get_args());
        if (ORegex::isa($find)) {
            $fn = is_callable($replace) ? 'preg_replace_callback' : 'preg_replace';
            return $fn($find->getPattern(), $replace, $this->val, $limit);
        } else {
            return str_replace($find, $replace, $this->val, $limit);
        }
    }

    function u_remove_left($s, $ignoreCase=false) {
        $this->ARGS('sf', func_get_args());
        if ($this->u_starts_with($s, $ignoreCase)) {
            $orig = $this->val;  // save after singleton changes
            $len = v($s)->u_length();
            return v($orig)->u_substring($len);
        }
        return $this->val;
    }

    function u_remove_right($s, $ignoreCase=false) {
        $this->ARGS('sf', func_get_args());
        if ($this->u_ends_with($s, $ignoreCase)) {
            $orig = $this->val;  // save after singleton changes
            $len = $this->u_length() - v($s)->u_length();
            return v($orig)->u_substring(0, $len);
        }
        return $this->val;
    }

    function u_remove_first($s, $ignoreCase=false) {
        $this->ARGS('sf', func_get_args());
        if ($this->u_ends_with($s, $ignoreCase)) {
            $orig = $this->val;  // save after singleton changes
            $len = $this->u_length() - v($s)->u_length();
            return v($orig)->u_substring(0, $len);
        }
        return $this->val;
    }

    function u_insert($s, $index) {

        $this->ARGS('sn', func_get_args());

        $len = $this->u_length();
        if ($index > $len) {
            return $this->val . $s;
        }

        $start = $this->u_substring(0, $index);
        $end = $this->u_substring($index, $len);

        return $start . $s . $end;
    }



    // To Arrays

    function u_split ($delim='', $limit=0) {
        $this->ARGS('*n', func_get_args());
        if ($limit <= 0) { $limit = PHP_INT_MAX; }
        if (ORegex::isa($delim)) {
            return preg_split($delim->getPattern(), $this->val, $limit);
        }
        if ($delim === '') {
            return $this->u_split_chars();
        }
        return explode($delim, $this->val, $limit);
    }

    function u_split_chars() {
        $this->ARGS('', func_get_args());
        preg_match_all('/./us', $this->val, $chars);
        return $chars[0];
    }

    function u_split_lines ($keepWhitespace=false) {
        $this->ARGS('f', func_get_args());
        if ($keepWhitespace) {
            return preg_split("/\n/u", $this->val);
        }
        else {
            return preg_split("/\s*\n+\s*/u", trim($this->val));
        }
    }

    function u_split_words ($bareWords=false) {
        $this->ARGS('f', func_get_args());
        $v = $this->val;
        if ($bareWords) {
            $v = str_replace("'", '', $v);
            $v = trim(preg_replace("/[^a-zA-Z0-9]+/u", ' ', $v));
        }
        return preg_split("/\s+/u", trim($v));
    }




    // Transforms

    function u_reverse () {
        $this->ARGS('', func_get_args());
        preg_match_all('/./us', $this->val, $chars);
        return implode(array_reverse($chars[0]));
    }

    function u_to_upper_case () {
        $this->ARGS('', func_get_args());
        return mb_strtoupper($this->val);
    }

    function u_to_lower_case () {
        $this->ARGS('', func_get_args());
        return mb_strtolower($this->val);
    }

    function u_to_upper_case_first () {
        $this->ARGS('', func_get_args());
        $first = mb_strtoupper(mb_substr($this->val, 0, 1));
        return $first . mb_substr($this->val, 1);
    }

    function u_to_lower_case_first () {
        $this->ARGS('', func_get_args());
        $first = mb_strtolower(mb_substr($this->val, 0, 1));
        return $first . mb_substr($this->val, 1);
    }

    function u_to_title_case ($skipWords=null) {
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
                return v(v($match[0])->u_to_lower_case())->u_to_upper_case_first();
            },
            $this->val
        );

        return v($titleCased)->u_to_upper_case_first();
    }

    function u_to_plural ($num=2, $plural='') {
        $this->ARGS('ns', func_get_args());
        $match = strtolower($this->val);
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

    function u_to_camel_case ($isUpperCamel=false) {

        $this->ARGS('f', func_get_args());

        $v = $this->val;

        $v = strtolower($v);
        $v = preg_replace('/[^a-zA-Z0-9]+/', ' ', $v);
        $v = trim($v);

        $parts = preg_split('/\s+/', $v);
        $camel = '';
        foreach ($parts as $p) {
            $camel .= ucfirst($p);
        }

        return $isUpperCamel ? $camel : lcfirst($camel);
    }

    function u_to_token_case($delim = '-') {

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

    function u_to_password() {
        $this->ARGS('', func_get_args());
        return Security::createPassword($this->val);
    }

    function u_hash() {
        $this->ARGS('', func_get_args());
        return Security::hashString($this->val);
    }



    // Whitespace

    function u_pad($padLen, $padStr = ' ') {
        $this->ARGS('ns', func_get_args());
        return $this->pad($padLen, $padStr, 'both');
    }

    function u_pad_left($padLen, $padStr = ' ') {
        $this->ARGS('ns', func_get_args());
        return $this->pad($padLen, $padStr, 'left');
    }

    function u_pad_right($padLen, $padStr = ' ') {
        $this->ARGS('ns', func_get_args());
        return $this->pad($padLen, $padStr, 'right');
    }

    function pad ($padLen, $padStr = ' ', $dir = 'right') {

        $this->ARGS('nss', func_get_args());

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

    function u_trim ($mask=null) {
        $this->ARGS('s', func_get_args());
        if (is_null($mask)) {
            return trim($this->val);
        } else {
            // necessary for utf-8 support
            $m = '[' . preg_quote($mask) . '\s]+';
            return preg_replace('/^' . $m . '|'. $m . '$/u', '', $this->val);
        }
    }

    function u_trim_left ($mask=null) {
        $this->ARGS('s', func_get_args());
        if (is_null($mask)) {
            return ltrim($this->val);
        } else {
            // necessary for utf-8 support
            $m = '[' . preg_quote($mask) . '\s]+';
            return preg_replace('/^' . $m . '/u', '', $this->val);
        }
    }

    function u_trim_right ($mask=null) {
        $this->ARGS('s', func_get_args());
        if (is_null($mask)) {
            return rtrim($this->val);
        } else {
            // necessary for utf-8 support
            $m = '[' . preg_quote($mask) . '\s]+';
            return preg_replace('/' . $m . '$/u', '', $this->val);
        }
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

    function u_trim_indent () {

        $this->ARGS('', func_get_args());
        $minIndent = 999;
        $trimmed = $this->u_trim_lines();
        if (!strlen($trimmed)) { return ''; }
        $lines = explode("\n", $trimmed);
        if (count($lines) === 1) { return ltrim($trimmed); }
        foreach ($lines as $line) {
            if (!preg_match('/\S/', $line)) { continue; }
            preg_match('/^(\s*)/', $line, $match);
            $indent = strlen($match[1]);
            $minIndent = min($indent, $minIndent);
            if (!$indent) { break; }
        }
        $numLines = count($lines);
        for ($i = 0; $i < $numLines; $i += 1) {
            $lines[$i] = substr($lines[$i], $minIndent);
        }
        return implode("\n", $lines);
    }

    function u_indent($level) {
        $this->ARGS('n', func_get_args());
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

    function u_limit ($numChars, $end='...') {
        $this->ARGS('ns', func_get_args());
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
    static private $fillArg;
    static private $fillArgNum;
    static private $me;

    static function cbFill ($matches) {
        $key = $matches[1];
        if ($key == '') {
            $key = self::$fillArgNum;
            self::$fillArgNum += 1;
        }
        if (!isset(self::$fillArg[$key])) {
            self::$me->error("Key `$key` is not found in fill value.", [ 'fill' => OString::$fillArg ]);
        }
        return self::$fillArg[$key];
    }

    function u_fill () {

        $args = func_get_args();
        if (count($args) == 1) {
            $first = uv($args[0]);
            // accepts both list and map
            if (is_array($first)) {
                $args = $first;
            }
        }
        self::$fillArg = $args;
        self::$fillArgNum = 0;
        self::$me = $this;
        $filled = preg_replace_callback('/{([a-zA-Z0-9]*)}/', '\o\OString::cbFill', $this->val);

        return $filled;
    }


    // Encoding

    function u_encode_html ($all = false) {
        $this->ARGS('f', func_get_args());
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

    function u_to_number () {
        $this->ARGS('', func_get_args());
        $f = floatval($this->val);
        $i = intval($this->val);

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
        $this->ARGS('f', func_get_args());
        if ($isStrict) {
            $v = ltrim($this->val);
            return preg_match('~^(https?:)?//~i', $v);
        } else {
            return preg_match('~(:|//)~', $this->val);
        }

    }


    // Utils

    // Slow-ish due to so many regexes, but this will only be called on inbound user data,
    // not on every output.
    function u_civilize () {

        $this->ARGS('', func_get_args());
        $s = trim($this->val);

        // trim and squeeze spaces
        $s = preg_replace('/\n{2,}/', "\n\n", $s);
        $s = preg_replace('/ {3,}/', "  ", $s);

        $s = $this->removeAllCaps($s);

        // truncate repeated characters
        $s = preg_replace("/(\.|,){4,}/", "...", $s);
        $s = preg_replace("/!{3,}/", "!!", $s);
        $s = preg_replace('/\?{3,}/', "??", $s);
        $s = preg_replace('/[\?!]{3,}/', "?!", $s);
        $s = preg_replace("/(.)\\1{4,}/", '\\1\\1\\1', $s);
        $s = preg_replace_callback("/([^\s]{30,})/", '\o\OString::truncateLongString', $s);

        $s = trim($s);

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
            return substr($raw[1], 0, 30);
        }
    }

}




