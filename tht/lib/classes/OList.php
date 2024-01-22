<?php

namespace o;

class OList extends OBag {

    protected $type = 'list';

    protected $hasNumericKeys = true;

    public $suggestMethod = [
        'shift'   => 'popFirst()',
        'unshift' => 'pushFirst($item)',
        'count'   => 'length()',
        'size'    => 'length()',
        'empty'   => 'isEmpty()',
        'delete'  => 'remove()',
        'splice'  => 'remove($pos, $num = 1) & insertAll($pos, $items)',
        'find'    => 'indexOf($item) or contains($item)',
        'merge'   => 'pushAll($items)',
        'append'  => 'pushAll($items)',
    ];

    public function jsonSerialize():mixed {

        if (!count($this->val)) {
            return '[EMPTY_LIST]';
        }

        return $this->val;
    }

    static function create ($a) {

        $l = new OList ();
        $l->val = $a;

        return $l;
    }

    static function createFromArg ($fnName, $obj) {

        if (self::isa($obj)) {
            return $obj;
        }

        if (!is_array($obj)) {
            Tht::error("Function `$fnName` expects a List argument. Got: `" . v($obj)->u_type() . "`");
        }

        return self::create($obj);
    }

    // Similar to OString
    function checkIndex($ai, $allowOutOfRange = false) {

        // Method in OBag
        $i = $this->checkNumericKey($ai);

        $len = count($this->val);

        $isOutOfRange = ($i < 0 || $i > $len - 1);
        if ($isOutOfRange) {
            if ($allowOutOfRange) {
                return -1;
            }
            else {
                $this->error("Index `$ai` is outside of List length (`$len`).");
            }
        }

        return $i;
    }

    function u_copy($flags = null) {

        $this->ARGS('m', func_get_args());

        $flags = $this->flags($flags, [
            'refs' => false,
        ]);

        // php apparently copies the array when assigned to a new var
        $a = $this->val;

        if (!$flags['refs']) {
            foreach ($a as $k => $el) {
                if (OBag::isa($el)) {
                    $a[$k] = $el->u_copy();
                }
            }
        }

        return OList::create($a);
    }

    function u_is_empty () {

        $this->ARGS('', func_get_args());

        return count($this->val) === 0;
    }

    function u_equals($otherList) {

        $this->ARGS('*', func_get_args());

        if (!OList::isa($otherList)) { return false; }

        return unv($this) === unv($otherList);
    }

    // Forwarding call so that errors are attributed to List
    function u_default ($d) {

        $this->ARGS('*', func_get_args());

        return parent::u_default($d);
    }

    function u_length() {

        $this->ARGS('', func_get_args());

        return parent::u_length();
    }

    //// GETTERS

    function u_has_index ($i) {

        $this->ARGS('i', func_get_args());

        $i = $this->checkIndex($i, true);

        return $i > -1 + ONE_INDEX;
    }

    function u_contains ($v) {

        $this->ARGS('*', func_get_args());

        return in_array($v, $this->val, true);
    }

    function u_contains_all ($otherList) {

        $this->ARGS('l', func_get_args());

        $otherList = array_unique(unv($otherList));

        // Not possible to have all the values
        if (count($otherList) > count($this->val)) { return false; }

        $commonList = array_intersect($this->val, $otherList);
        $commonList = array_unique($commonList);

        return (count($commonList) == count($otherList));
    }

    function u_first ($n=1) {

        $this->ARGS('I', func_get_args());

        $len = count($this->val);

        if ($len == 0) {
            return ($n === 1) ? $this->getDefault() : OList::create([]);
        }

        if ($n === 1) {
            return $this->val[0];
        }
        else {
            $n = min($len, $n);
            return OList::create(array_slice($this->val, 0, $n));
        }
    }

    function u_last ($n=1) {

        $this->ARGS('I', func_get_args());

        $len = count($this->val);

        if ($len == 0) {
            return ($n === 1) ? $this->getDefault() : OList::create([]);
        }

        if ($n === 1) {
            return $this->val[$len - 1];
        }
        else {
            $n = min($len, $n);
            return OList::create(array_slice($this->val, $len - $n, $n));
        }
    }

    function u_index_of($v) {

        $this->ARGS('*', func_get_args());

        $i = array_search($v, $this->val, true);

        return ($i === false) ? -1 + ONE_INDEX : $i + ONE_INDEX;
    }

    function u_last_index_of($v) {

        $this->ARGS('*', func_get_args());

        $len = count($this->val);
        for ($i = 0; $i < $len; $i += 1) {
            if ($this->val[$i] === $v) {
                return $i + ONE_INDEX;
            }
        }

        return -1 + ONE_INDEX;
    }

    function u_slice($apos, $len = 0) {

        $this->ARGS('ii', func_get_args());

        $pos = $this->checkIndex($apos);

        $vlen = count($this->val);
        if ($pos + abs($len) > $vlen) {
            $alen = abs($len);
            $this->error("Arguments `index = $apos` & `length = $alen` are greater than List length `$vlen`.");
        }

        // Take all remaining elements
        if ($len <= 0) {
            $len = null;
        }

        return OList::create(
            array_slice($this->val, $pos, $len)
        );
    }




    // ADD / REMOVE

    function u_push ($v) {

        $this->ARGS('*', func_get_args());

        array_push($this->val, $v);

        return $this;
    }

    function u_push_first ($v) {

        $this->ARGS('*', func_get_args());

        array_unshift($this->val, $v);

        return $this;
    }

    function u_push_all ($a2) {

        $this->ARGS('l', func_get_args());

        $this->val = array_merge($this->val, $a2->val);

        return $this;
    }

    function u_pop () {

        $this->ARGS('', func_get_args());

        $ret = array_pop($this->val);

        return is_null($ret) ? $this->getDefault() : $ret;
    }

    function u_pop_first () {

        $this->ARGS('', func_get_args());

        $ret = array_shift($this->val);

        return is_null($ret) ? $this->getDefault() : $ret;
    }

    function u_insert ($pos, $v) {

        $this->ARGS('i*', func_get_args());

        $pos = $this->checkIndex($pos);

        array_splice($this->val, $pos, 0, $v);

        return $this;
    }

    function u_insert_all ($pos, $otherList) {

        $this->ARGS('il', func_get_args());

        $pos = $this->checkIndex($pos);

        array_splice($this->val, $pos, 0, unv($otherList));

        return $this;
    }

    function u_remove ($pos, $numItems = 1) {

        $this->ARGS('ii', func_get_args());

        $pos = $this->checkIndex($pos, true);

        if ($pos == -1) {
            return $numItems == 1 ? $this->getDefault() : OList::create([]);
        }

        $removed = array_splice($this->val, $pos, $numItems);

        return $numItems == 1 ? $removed[0] : OList::create($removed);
    }

    function u_repeat($el, $numTimes) {

        $this->ARGS('*I', func_get_args());

        for ($i = 0; $i < $numTimes; $i += 1) {
            $this->val []= $el;
        }

        return $this;
    }

    //
    // function u_splice($pos, $len=-1, $replace=null) {
    //     if ($len < 0) {
    //         $len = count($this->val);
    //     }
    //     if (is_null($replace)) { $replace = []; }
    //
    //     return array_splice($this->val, $pos, $len, unv($replace));
    // }
    //






    // ORDER / FILTER

    function u_reverse () {

        $this->ARGS('', func_get_args());

        return OList::create(array_reverse($this->val));
    }

    function u_shuffle () {

        $this->ARGS('', func_get_args());

        $this->val = Security::shuffleList($this->val);

        return $this;
    }

    function u_swap($i1, $i2) {

        $this->ARGS('ii', func_get_args());

        $ai1 = $this->checkIndex($i1);
        $ai2 = $this->checkIndex($i2);

        $tmp = $this->val[$ai1];
        $this->val[$ai1] = $this->val[$ai2];
        $this->val[$ai2] = $tmp;

        return $this;
    }

    function u_random($aNumItems = 1) {

        $this->ARGS('I', func_get_args());

        // Using shuffle instead of array_rand, so it is
        // cryptographically secure.
        $indexes = Security::shuffleList(
            array_keys($this->val)
        );

        $numItems = min($aNumItems, count($indexes));

        $out = [];
        for ($i = 0; $i < $numItems; $i += 1) {
            $randIndex = $indexes[$i];
            $out []= $this->val[$randIndex];
        }

        return $aNumItems == 1 ? $out[0] : OList::create($out);
    }

    function u_sort($fnOrFlags=false) {

        $this->ARGS('*', func_get_args());

        if (is_callable($fnOrFlags)) {
            usort($this->val, $fnOrFlags);
        }
        else if (OMap::isa($fnOrFlags)) {

            $flags = $this->flags($fnOrFlags, [
                'reverse'    => false,
                'ignoreCase' => false,
                'ascii'      => false,
            ]);

            $iflags = SORT_NATURAL;

            if ($flags['ascii']) {
                $iflags = SORT_REGULAR;
            }

            // Note: Can't really use SORT_LOCALE_STRING here, because it
            // it causes regular & natural to give the same results.
            $iflags |= SORT_STRING;

            if ($flags['ignoreCase']) {
                $iflags |= SORT_FLAG_CASE;
            }

            if ($flags['reverse']) {
                rsort($this->val, $iflags);
            } else {
                sort($this->val, $iflags);
            }
        }
        else {
            sort($this->val, SORT_NATURAL|SORT_STRING);
        }

        return $this;
    }

    function u_unique () {

        $this->ARGS('', func_get_args());

        // Have to use array_values because array_unique preserves keys
        $uniqueList = array_values(array_unique($this->val));

        return OList::create($uniqueList);
    }


    // TABLE
    //-----------------------------------------

    function u_sort_by_column ($colKey, $flags=null) {

        $this->ARGS('sm', func_get_args());

        $flags = $this->flags($flags, [
            'reverse' => false,
        ]);

        $isDesc = $flags['reverse'];

        usort($this->val, function($a, $b) use ($colKey, $isDesc) {

            $sort = $isDesc ? -1 : +1;

            if (is_string($a[$colKey])) {
                return strcmp($a[$colKey], $b[$colKey]) * $sort;
            }
            else {
               return ($a[$colKey] - $b[$colKey]) * $sort;
            }

        });

        return $this;
    }

    function u_get_column ($colKey, $indexKey='') {

        $this->ARGS('ss', func_get_args());

        $out = $indexKey ? OMap::create([]) : OList::create([]);

        foreach ($this->val as $el) {
            if (isset($el[$colKey])) {
                if ($indexKey && isset($el[$indexKey])) {
                    $out[$el[$indexKey]] = $el[$colKey];
                } else {
                    $out []= $el[$colKey];
                }
            }
        }

        return $out;
    }




    // MISC
    //-----------------------------------------

    function u_join ($delim='') {

        $this->ARGS('S', func_get_args());

        return implode($delim, $this->val);
    }

    // Use zip().toMap()  instead

    // function u_to_zipper_map() {

    //     $this->ARGS('', func_get_args());

    //     if (count($this->val) % 2 !== 0) {
    //         $this->error('List.toZipperMap() requires an even number of items (key/value pairs).');
    //     }

    //     $map = [];
    //     $numEls = count($this->val);
    //     for ($i = 0; $i < $numEls; $i += 2) {
    //         $k = $this->val[$i];
    //         $v = $this->val[$i + 1];
    //         $map[$k] = $v;
    //     }

    //     return OMap::create($map);
    // }

    function u_to_map($keys=null) {

        $this->ARGS('l', func_get_args());

        if (is_null($keys)) {
            $keys = range(ONE_INDEX, count($this->val));
        }

        if (count($this->val) !== count($keys)) {
            $c1 = count($keys);
            $c2 = count($this->val);
            $this->error("List.toMap() requires the number of keys (got: $c1) to be the same as the number of List items (got: $c2).");
        }

        $map = [];
        $i = 0;
        foreach ($keys as $k) {
            $map[$k] = $this->val[$i];
            $i += 1;
        }

        return OMap::create($map);
    }

    function u_to_set() {

        $this->ARGS('', func_get_args());

        $set = array_fill_keys($this->val, true);

        return OMap::create($set);
    }


    // MATH
    //-----------------------------------------

    function u_max() {

        $this->ARGS('', func_get_args());

        return count($this->val) ? max($this->val) : 0;
    }

    function u_min() {

        $this->ARGS('', func_get_args());

        return count($this->val) ? min($this->val) : 0;
    }

    function u_product() {

        $this->ARGS('', func_get_args());

        return array_product($this->val);
    }

    function u_sum() {

        $this->ARGS('', func_get_args());

        return array_sum($this->val);
    }


    function u_compact () {

        $this->ARGS('', func_get_args());

        $truthyEls = [];
        foreach ($this->val as $el) {
            if ($el) { $truthyEls []= $el; }
        }

        return OList::create($truthyEls);
    }


    // TODO: fill with default. aka pad, fill
    // function u_resize ($size, $default='') {
    //     return array_splice($this->val, $size);
    // }

    function u_flat ($maxDepth = 999) {

        $this->ARGS('I', func_get_args());

        return $this->flat($this, 0, $maxDepth);
    }

    function flat($list, $depth, $maxDepth) {

        if ($depth >= $maxDepth) {
            return $list;
        }

        $result = [];
        $depth += 1;

        foreach ($list as $a) {
            if (!OList::isa($a)) {
                $result []= $a;
            }
            else {
                $result = array_merge($result, unv($this->flat($a, $depth, $maxDepth)));
            }
        }

        return OList::create($result);
    }




    // Functional Programming
    //---------------------------------------------------

    function u_map ($fn) {

        $this->ARGS('c', func_get_args());

        return OList::create(
            array_map($fn, $this->val)
        );
    }

    function u_reduce ($fn, $initial) {

        $this->ARGS('c*', func_get_args());

        // Single return value
        return array_reduce($this->val, $fn, $initial);
    }

    function u_filter ($fn) {

        $this->ARGS('c', func_get_args());

        return OList::create(
            array_values(array_filter($this->val, $fn))
        );
    }

    function u_has_all ($fn) {

        $this->ARGS('c', func_get_args());

        foreach ($this->val as $v) {
            if (!$fn($v)) { return false; }
        }

        return true;
    }

    function u_has_any ($fn) {

        $this->ARGS('c', func_get_args());

        foreach ($this->val as $v) {
            if ($fn($v)) { return true; }
        }

        return false;
    }

    function u_find_first ($fn) {

        $this->ARGS('c', func_get_args());

        foreach ($this->val as $v) {
            if ($fn($v)) { return $v; }
        }

        return false;
    }

    function checkReturnString($arg, $fnName) {
        if (vIsNumber($arg)) {
            return '' . $arg;
        }
        if (!is_string($arg)) {
            $this->error("Callback function for `List.$fnName` must return a string key.");
        }
        return $arg;
    }

    function u_count_by($fn) {

        $this->ARGS('c', func_get_args());

        $count = [];
        foreach ($this->val as $v) {
            $key = $fn($v);
            $key = $this->checkReturnString($key, 'countBy');
            if (!isset($count[$key])) {
                $count[$key] = 1;
            }
            else {
                $count[$key] += 1;
            }
        }

        return OMap::create($count);
    }

    function u_index_by($fn) {

        $this->ARGS('c', func_get_args());

        $index = [];
        foreach ($this->val as $v) {
            $key = $fn($v);
            $key = $this->checkReturnString($key, 'indexBy');
            $index[$key] = $v;
        }

        return OMap::create($index);
    }

    function u_group_by($fn) {

        $this->ARGS('c', func_get_args());

        $group = [];
        foreach ($this->val as $v) {

            $key = $fn($v);
            $key = $this->checkReturnString($key, 'groupBy');

            if (!isset($group[$key])) {
                $group[$key] = OList::create([$v]);
            }
            else {
                $group[$key] = $group[$key]->u_push($v);
            }
        }

        return OMap::create($group);
    }


    // function u_count ($fnOrVal) {
    //     $n = 0;
    //     $isFn = is_callable($fnOrVal);
    //     foreach ($this->val as $v) {
    //         if ($isFn) {
    //             if ($fn($v)) {  $n += 1;  }
    //         } else if ($fnOrVal === $v) {
    //             $n += 1;
    //         }
    //     }
    //     return $n;
    // }
    //
    // function u_zip () {
    //    //  return array_map(null, $a, $b, $c, ...);
    // }

    // collect_concat (flat_map), detect/find (first true),
    // findAll, min (by), max (by), minmaxby, toMap, zip, unzip
    // clean = no nulls

}

