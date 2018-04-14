<?php

namespace o;


class OString extends OVar implements \ArrayAccess {

    public $val = '';
    private $encoding = 'UTF-8';

    // Indexing

    function offsetGet ($k) {
        if ($k < 0) { $k = strlen($this->val) + $k; }
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
        return mb_substr($this->val, $i, 1);
    }

    function u_char_code_at ($i) {
        $char = mb_substr($this->val, $i, 1);
        return unpack('V', iconv('UTF-8', 'UCS-4LE', $char))[1];
    }

    function u_left($n) {
        return $n <= 0 ? '' : $this->u_substring(0, $n);
    }

    function u_right($n) {
        return $n <= 0 ? '' : $this->u_substring(-1 * $n, $n);
    }

    function u_index_of($s, $offset=0, $ignoreCase=false) {
        return $this->_strpos($s, $ignoreCase, $offset);
    }

    function u_last_index_of($s, $offset=0, $ignoreCase=false) {
        return $this->_strrpos($s, $ignoreCase, $offset);
    }

    function u_substring($start, $len=null) {
        $len = $len === null ? $this->u_length() : $len;
        return mb_substr($this->val, $start, $len);
    }

    function u_contains($s, $ignoreCase=false) {
        return $this->_strpos($s, $ignoreCase) !== false;
    }

    function u_starts_with($s, $ignoreCase=false) {
        return $this->_strpos($s, $ignoreCase) === 0;
    }

    function u_ends_with($s, $ignoreCase=false) {
        $x = $this->val;
        $suffLen = v($s)->u_length();
        $this->val = $x;
        return $this->_strpos($s, $ignoreCase) == ($this->u_length() - $suffLen);
    }





    // Locking


    // Just for convenience if mixing and matching with OLockStrings
    function u_unlocked () {
        return $this->val;
    }

    function u_danger_danger_lock () {
        return OLockString::create('o\OLockString', $this->val);
    }



    // Find / Replace

    function pregErrorMessage () {
        return array_flip(get_defined_constants(true)['pcre'])[preg_last_error()];
    }

    function u_match ($match, $offset=0) {
        if (ORegex::isa($match)) {
            $found = preg_match($match->getPattern(), $this->val, $matches);
            if ($found === false) {
                Tht::error('Error in match: ' . $this->pregErrorMessage(), [
                    'var' => $this->val,
                    'pattern' => $match,
                    'offset' => $offset
                ]);
            }
            return $found === 1 ? $matches : [];
        }
    }

    function u_match_all ($match) {
        if (ORegex::isa($match)) {
            preg_match_all($match->getPattern(), $this->val, $matches);
            // TODO: recursively wrap nested results
            return $matches;
        }
    }

    function u_replace ($find, $replace, $limit=-1) {
        if (ORegex::isa($find)) {
            $fn = is_callable($replace) ? 'preg_replace_callback' : 'preg_replace';
          //  print $find->getPattern(); exit();
            return $fn($find->getPattern(), $replace, $this->val, $limit);
        } else {
            return str_replace($find, $replace, $this->val, $limit);
        }
    }


    function u_remove_left($s, $ignoreCase=false) {
        if ($this->u_starts_with($s, $ignoreCase)) {
            $orig = $this->val;  // save after singleton changes
            $len = v($s)->u_length();
            return v($orig)->u_substring($len);
        }
        return $this->val;
    }

    function u_remove_right($s, $ignoreCase=false) {
        if ($this->u_ends_with($s, $ignoreCase)) {
            $orig = $this->val;  // save after singleton changes
            $len = $this->u_length() - v($s)->u_length();
            return v($orig)->u_substring(0, $len);
        }
        return $this->val;
    }

    function u_remove_first($s, $ignoreCase=false) {
        if ($this->u_ends_with($s, $ignoreCase)) {
            $orig = $this->val;  // save after singleton changes
            $len = $this->u_length() - v($s)->u_length();
            return v($orig)->u_substring(0, $len);
        }
        return $this->val;
    }

    function u_insert($s, $index) {

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
        preg_match_all('/./us', $this->val, $chars);
        return $chars[0];
    }

    function u_split_lines ($keepWhitespace=false) {
        if ($keepWhitespace) {
            return preg_split("/\n/u", $this->val);
        }
        else {
            return preg_split("/\s*\n+\s*/u", trim($this->val));
        }
    }

    function u_split_words ($bareWords=false) {
        $v = $this->val;
        if ($bareWords) {
            $v = str_replace("'", '', $v);
            $v = trim(preg_replace("/[^a-zA-Z0-9]+/u", ' ', $v));
        }
        return preg_split("/\s+/u", trim($v));
    }




    // Transforms

    function u_reverse () {
        preg_match_all('/./us', $this->val, $chars);
        return implode(array_reverse($chars[0]));
    }

    function u_to_upper_case ($pos=null) {
        return mb_strtoupper($this->val);
    }

    function u_to_lower_case ($pos=null) {
        return mb_strtolower($this->val);
    }

    function u_to_upper_case_first () {
        $first = mb_strtoupper(mb_substr($this->val, 0, 1));
        return $first . mb_substr($this->val, 1);
    }

    function u_to_lower_case_first () {
        $first = mb_strtolower(mb_substr($this->val, 0, 1));
        return $first . mb_substr($this->val, 1);
    }

    function u_to_title_case ($skipWords=null) {
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
        $v = $this->val;

        // Convert from camel
        $v = preg_replace("/([A-Z]+)/", " $1", $v);

        $v = trim(strtolower($v));
        $v = str_replace("'", '', $v);
        $v = preg_replace('/[^a-z0-9]+/', $delim, $v);
        $v = rtrim($v, $delim);
        return $v;
    }



    // Whitespace

    function u_pad($padLen, $padStr = ' ') {
        return $this->pad($padLen, $padStr, 'both');
    }

    function u_pad_left($padLen, $padStr = ' ') {
        return $this->pad($padLen, $padStr, 'left');
    }

    function u_pad_right($padLen, $padStr = ' ') {
        return $this->pad($padLen, $padStr, 'right');
    }

	function pad ($padLen, $padStr = ' ', $dir = 'right') {

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
        if (is_null($mask)) {
            return trim($this->val);
        } else {
            // necessary for utf-8 support
            $m = '[' . preg_quote($mask) . '\s]+';
            return preg_replace('/^' . $m . '|'. $m . '$/u', '', $this->val);
        }
    }

    function u_trim_left ($mask=null) {
        if (is_null($mask)) {
            return ltrim($this->val);
        } else {
            // necessary for utf-8 support
            $m = '[' . preg_quote($mask) . '\s]+';
            return preg_replace('/^' . $m . '/u', '', $this->val);
        }
    }

    function u_trim_right ($mask=null) {
        if (is_null($mask)) {
            return rtrim($this->val);
        } else {
            // necessary for utf-8 support
            $m = '[' . preg_quote($mask) . '\s]+';
            return preg_replace('/' . $m . '$/u', '', $this->val);
        }
    }

    function u_trim_lines () {

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
        $lines = explode("\n", $this->val);
        $out = '';
        foreach ($lines as $line) {
            $line = str_repeat(' ', $level) . $line;
            $line = rtrim($line);
            $out .= $line . "\n";
        }
        return $out;
    }

    function u_squeeze ($char='') {
        $char = $char === '' ? '\s' : preg_quote($char);
        return preg_replace('/([' . $char . '])+/u', '$1', $this->val);
    }

    function u_limit ($numChars, $end='...') {
        $s = $this->val;
        if (strlen($s) > $numChars) {
            $s = mb_substr($s, 0, $numChars);
            $s = rtrim($s, '?!.;,');
            $s = $s . $end;
        }
        return $s;
    }


    // Format

    static private $fillArg;
    static private $fillArgNum;

    static function cbFill ($matches) {
        $key = $matches[1];
        if ($key == '') {
            $key = OString::$fillArgNum;
            OString::$fillArgNum += 1;
        }
        if (!array_key_exists($key, OString::$fillArg)) {
            Tht::error("Key `$key` is not found in fill value.", [ 'fill' => OString::$fillArg ]);
        }
        return OString::$fillArg[$key];
    }

    function u_fill () {
        $args = func_get_args();
        if (is_array(uv($args[0]))) {
            $args = uv($args[0]);
        }
        OString::$fillArg = $args;
        OString::$fillArgNum = 0;

        $filled = preg_replace_callback('/{([a-zA-Z0-9]*)}/', '\o\OString::cbFill', $this->val);

        return $filled;
    }


    // Encoding

    function u_encode_html ($all = false) {
        if ($all) {
            $str = mb_convert_encoding($this->val , 'UTF-32', 'UTF-8');
            $t = unpack("N*", $str);
            $t = array_map(function($n) { return "&#$n;"; }, $t);
            return implode("", $t);
        }
        return htmlspecialchars($this->val, ENT_QUOTES, 'UTF-8');
    }

    function u_decode_html () {
        return htmlspecialchars_decode($this->val, ENT_QUOTES);
    }

    function u_encode_url () {
        return rawurlencode($this->val);
    }

    function u_decode_url () {
        return rawurldecode($this->val);
    }

    function u_encode_base64 () {
        return base64_encode($this->val);
    }

    function u_decode_base64 () {
        return base64_decode($this->val);
    }

    function u_escape_regex () {
        return preg_quote($this->val, '`');
    }


    // Checks

    function u_is_upper_case() {
        return $this->u_has_upper_case() && !$this->u_has_lower_case();
    }

    function u_has_upper_case() {
        return mb_ereg_match('.*[[:upper:]]', $this->val);
    }

    function u_is_lower_case() {
        return $this->u_has_lower_case() && !$this->u_has_upper_case();
    }

    function u_has_lower_case() {
        return mb_ereg_match('.*[[:lower:]]', $this->val);
    }

    function u_is_space() {
        return mb_ereg_match('^[[:space:]]+$', $this->val);
    }

    function u_has_space() {
        return mb_ereg_match('.*[[:space:]]', $this->val);
    }

    function u_is_alpha() {
        return mb_ereg_match('^[[:alpha:]]+$', $this->val);
    }

    function u_is_alpha_numeric() {
        return mb_ereg_match('^[[:alnum:]]+$', $this->val);
    }

    function u_is_number() {
        return mb_ereg_match('^-?[[:digit:]]+\.?[[:digit:]]*$', $this->val);
    }

    function u_is_ascii() {
        return mb_ereg_match('^[[:ascii:]]*$', $this->val);
    }





    // Casting

    function u_to_number () {
        $f = floatval($this->val);
        $i = intval($this->val);

        return $f == $i ? $i : $f;
    }

    function u_to_flag () {
        $v = trim($this->val);
        if ($v === 'false' || $v === 'null' || $v === '0' || $v === '') {
            return false;
        }
        return true;
    }

    function u_to_string () {
        return $this->val;
    }

    function u_to_value () {
        $v = trim($this->val);
        if ($v === '') { return ''; }

        if ($v[0] === '"' || $v[0] === "'" || $v[0] === '`') {
            // trim surrounding quotes
            if ($v[strlen($v) - 1] === $v[0]) {
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


    // Checks

    function u_is_url ($isStrict=false) {
        if ($isStrict) {
            $v = ltrim($this->val);
            return preg_match('~^(https?:)?//~i', $v);
        } else {
            return preg_match('~(:|//)~', $this->val);
        }

    }


    // Utils

    // TODO: don't affect URLs
    function removeCaps($s, $maxCaps) {

        // prevent ALL CAPS
        preg_match_all("/([A-Z])/", $s, $matches);
        $numCaps = count($matches[0]);

        $alphaOnly = preg_replace("/[^a-zA-Z]/", "", $s);
        $numAlpha = strlen($alphaOnly);

        if ($numAlpha > 16) {
            $maxCaps = floor($numAlpha * 0.5);
        }

        if ($numCaps >= $maxCaps) {
            $s = strtolower($s);
            $s = ucfirst($s);
        }

        return $s;
    }

    function u_civilize_title() {

        // Remove quoted strings
        // var m = [];
        // if (m = text.match(/^"(.*)"$/))  text = m[1];
        // m = [];
        // if (m = text.match(/^'(.*)'$/))  text = m[1];

        // Title case.  remove end periods.  Keep one ! or ?


    }






    function u_civilize () {

        $s = $this->val;

        // TODO: trim and squeeze spaces

        $s = $this->removeCaps($s);

        function truncateLongString ($raw) {
            if (preg_match("/http|\.com/", $raw[1])) {
                # preserve URLs
                return $raw[1];
            }
            else {
                return substr($raw[1], 0, 30);
            }
        }

        // truncate repeated characters
        $s = preg_replace("/!{2,}/", "!", $s);
        $s = preg_replace("/\.\.\.\.+/", "...", $s);
        $s = preg_replace("/\?{2,}/", "?", $s);
        $s = preg_replace("/[?!]{2,}/", "?!", $s);
        $s = preg_replace("/(.)\\1{4,}/", '\\1\\1\\1\\1\\1', $s);

        $com = preg_replace_callback("/([^\s]{30,})/", "truncateLongString" , $com);





        // List of potential typos to fix:

        // space after|no space before:   . , ? ! : ;

        // ect => etc
        // isnt, aint = isn't, ain't
        // im -> I'm
        // trailing comma or ...
        // ,,+ => ,
        // .. => .
        // whats, hows, where, whens, whys, thats
        //   its|your|their => it's the|in|a|by|in
        // u r
        // niether
        // taht, adn, teh, ot (or)
        // wether
        // anyways
        // alot
        // could of, would of should of
        // Definately
        // wierd
        // thier
        // reciev
        // realy
        // allmost
        // didnt, doesnt, wouldnt, couldnt, shouldnt, cant, wont
        // seperate, definate, reciev, alot
        // could of, would of, should of
        // thier, wierd, untill, beleive, belive, freind
        // rember, remeber, suprise
        // wich, tommorow, tommorrow


        // Fix accidental CAse problem
        // text = text.replace(/\b([A-Z][A-Z][a-z])/g, title_case);
        //
        // // Fix accidental > instead of ?
        // if (! text.match(/[<>].*>$/))  text = text.replace(/>$/, '?');
        //
        // // Fix accidental / instead of ?
        // if (! text.match(/\/.*\/$/))  text = text.replace(/\/$/, '?');

    }
}




