<?php

namespace o;

class OList extends OBag {

    protected $type = 'list';

    protected $hasNumericKeys = true;

    protected $suggestMethod = [
        'shift'   => 'remove(0)',
        'unshift' => 'insert(0)',
        'count'   => 'length()',
        'size'    => 'length()',
        'empty'   => 'isEmpty()',
        'delete'  => 'remove()',
        'splice'  => 'remove(pos, num = 1) & insertAll(pos, items)',
        'find'    => 'indexOf(item) or contains(item)',
        'random'  => 'shuffle()',
        'merge'   => 'pushAll(items)',
        'append'  => 'pushAll(items)',
    ];

    static function create ($a) {
        $l = new OList ();
        $l->val = $a;
        return $l;
    }

    function u_copy() {
        ARGS('', func_get_args());
        // php apparently copies the array when assigned to a new var
        $a = $this->val;
        return OList::create($a);
    }

    function u_is_empty () {
        ARGS('', func_get_args());
        return count($this->val) === 0;
    }


    //// GETTERS


    function u_contains ($v) {
        ARGS('*', func_get_args());
        return in_array($v, $this->val, true);
    }

    function u_first ($n=1) {
        ARGS('n', func_get_args());
        $len = count($this->val);
        if ($n > $len) {
            Tht::error("Argument `numItems = $n` is greater than List length `$len`.");
        }
        if ($n === 1) {
            return $this->val[0];
        }
        else {
            return OList::create(array_slice($this->val, 0, $n));
        }
    }

    function u_last ($n=1) {
        ARGS('n', func_get_args());
        $len = count($this->val);
        if ($n > $len) {
            Tht::error("Argument `numItems = $n` is greater than List length `$len`.");
        }
        if ($n === 1) {
            return $this->val[$len - 1];
        }
        else {
            return OList::create(array_slice($this->val, $len - $n, $n));
        }
    }

    function u_index_of($v) {
        ARGS('*', func_get_args());
        $i = array_search($v, $this->val, true);
        return ($i === false) ? -1 : $i;
    }

    function u_last_index_of($v) {
        ARGS('*', func_get_args());
        $len = count($this->val);
        for ($i = 0; $i < $len; $i += 1) {
            if ($this->val[$i] === $v) {
                return $i;
            }
        }
        return -1;
    }

    function u_last_index() {
        ARGS('', func_get_args());
        return count($this->val) - 1;
    }

    function u_slice($pos, $len=-1) {
        ARGS('nn', func_get_args());
        $vlen = count($this->val);
        if ($pos < 0) {
            $pos = $vlen + $pos;
        }
        if ($pos + abs($len) > $vlen) {
            $alen = abs($len);
            Tht::error("Argument `index = $pos` + `length = $alen` is greater than List length `$vlen`.");
        }

        if ($len < 0) {
            $len = null;
        }
        return OList::create(array_slice($this->val, $pos, $len));
    }



    // ADD / REMOVE

    function u_push ($v) {
        ARGS('*', func_get_args());
        array_push($this->val, $v);
        return $this;
    }

    // function u_push_first ($v) {
    //     ARGS('*', func_get_args());
    //     array_unshift($this->val, $v);
    //     return $this->val;
    // }

    function u_push_all ($a2) {
        ARGS('l', func_get_args());
        $this->val = array_merge($this->val, $a2->val);
        return $this;
    }

    function u_pop () {
        ARGS('', func_get_args());
        return array_pop($this->val);
    }

    // function u_pop_first () {
    //     return array_shift($this->val);
    // }

    function u_insert ($v, $pos) {
        ARGS('*n', func_get_args());
        return $this->insert($v, $pos, false);
    }

    function u_insert_all ($v, $pos) {
        ARGS('ln', func_get_args());
        return $this->insert($v, $pos, true);
    }

    function insert($v, $pos, $isAll) {
        if ($pos < 0) {
            // insert AFTER index
            if ($pos === -1) {
                if ($isAll) {
                    $this->val = array_merge($this->val, $v->val);
                } else {
                    array_push($this->val, $v);
                }
            } else {
                $v = $isAll ? $v->val : [$v];
                array_splice($this->val, $pos + 1, 0, $v);
            }
        } else {
            // insert BEFORE index
            $v = $isAll ? $v->val : [$v];
            array_splice($this->val, $pos, 0, $v);
        }
        return $this;
    }

    function u_remove ($pos, $numItems = 1) {
        ARGS('nn', func_get_args());
        $len = count($this->val);
        if (!$len) {
            Tht::error("Can not `remove()` from an empty List.");
        }
        $removed = array_splice($this->val, $pos, $numItems);
        if (!count($removed)) {
            $vlen = count($this->val);
            Tht::error("Can't `remove()` at `index=$pos` from List with length `$vlen`.");
        }
        return $numItems == 1 ? $removed[0] : OList::create($removed);
    }


    //
    // function u_splice($pos, $len=-1, $replace=null) {
    //     if ($len < 0) {
    //         $len = count($this->val);
    //     }
    //     if (is_null($replace)) { $replace = []; }
    //
    //     return array_splice($this->val, $pos, $len, uv($replace));
    // }
    //






    // ORDER / FILTER

    function u_reverse () {
        ARGS('', func_get_args());
        return OList::create(array_reverse($this->val));
    }

    function u_shuffle () {
        ARGS('', func_get_args());
        shuffle($this->val);
        return $this;
    }

    function u_random () {
        ARGS('', func_get_args());
        $i = array_rand($this->val);
        return $this->val[$i];
    }

    function u_sort ($fnOrArgs=false) {

        ARGS('*', func_get_args());

        if (is_callable($fnOrArgs)) {
            usort($this->val, $fnOrArgs);
        }
        else if (OMap::isa($fnOrArgs)) {
            $flags = SORT_REGULAR;
            $isReverse = false;
            if ($fnOrArgs !== false) {
                $args = uv($fnOrArgs);
                if (isset($args['reverse']) && $args['reverse']) {
                    $isReverse = true;
                }
                if (isset($args['type'])) {
                    $map = [
                        'regular'     => SORT_REGULAR,
                        'number'      => SORT_NUMERIC,
                        'string'      => SORT_STRING|SORT_FLAG_CASE,
                        'natural'     => SORT_NATURAL|SORT_FLAG_CASE,
                        'stringCase'  => SORT_STRING,
                        'naturalCase' => SORT_NATURAL,
                    ];
                    if (isset($map[$args['type']])) {
                        $flags |= $map[$args['type']];
                    } else {
                        Tht::error("Unknown sort type: `" . $args['type'] . "`");
                    }
                }
            }

            if ($isReverse) {
                rsort($this->val, $flags);
            } else {
                sort($this->val, $flags);
            }
        }
        else {
            sort($this->val);
        }

        return $this;
    }

    function u_sort_table ($key, $isDesc=false) {

        ARGS('sf', func_get_args());

        usort($this->val, function($a, $b) use ($key, $isDesc) {
            $sort = $isDesc ? -1 : +1;
           if (is_string($a[$key])) {
               return strcmp($a[$key], $b[$key]) * $sort;
           } else {
               return ($a[$key] - $b[$key]) * $sort;
           }
        });
        return $this;
    }

    function u_unique () {
        ARGS('', func_get_args());
        return OList::create(array_unique($this->val));
    }



    // MISC

    function u_join ($delim='') {
        ARGS('s', func_get_args());
        return implode($this->val, $delim);
    }

    function u_to_map() {
        ARGS('', func_get_args());

        if (count($this->val) % 2 !== 0) {
            Tht::error('List.toMap() requires an even number of elements (key/value pairs).');
        }
        $out = [];
        for ($i = 1; $i < count($this->val); $i += 2) {
            $k = $this->val[i];
            $v = $this->val[i + 1];
            $out[$k] = $v;
        }
        return OMap::create($v);
    }





    // TODO: fill with default. aka pad, fill
    // function u_resize ($size, $default='') {
    //     return array_splice($this->val, $size);
    // }

    function u_flat ($maxDepth = 999) {
        ARGS('n', func_get_args());
        return $this->flat($this, 0, $maxDepth);
    }

    function flat($list, $depth, $maxDepth) {
        $result = [];
        if ($depth >= $maxDepth) {
            return $list;
        }
        $depth += 1;
        foreach ($list as $a) {
            if (!OList::isa($a)) {
                $result []= $a;
            }
            else {
                $result = array_merge($result, uv($this->flat($a, $depth, $maxDepth)));
            }
        }
        return OList::create($result);
    }


    // TABLE
    // function u_column ($colKey, $indexKey=null) {
    //     $out = [];
    //     foreach ($this->val as $el) {
    //         $el = uv($el);
    //         if (isset($el[$colKey])) {
    //             if ($indexKey && isset($el[$indexKey])) {
    //                 $out[$el[$indexKey]] = $el[$colKey];
    //             } else {
    //                 $out []= $el[$colKey];
    //             }
    //         }
    //     }
    //     return $out;
    // }
    //



    // Functional Programming
    //
    function u_map ($fn) {
        return OList::create(array_map($fn, $this->val));
    }

    function u_reduce ($fn, $initial) {
        return array_reduce($this->val, $fn, $initial);
    }

    function u_filter ($fn) {
        $a = array_values(array_filter($this->val, $fn));
        return OList::create($a);
    }

    //
    // function u_all ($fn) {
    //     foreach ($this->val as $v) {
    //         if (! $fn($v)) {
    //             return false;
    //         }
    //     }
    //     return true;
    // }
    //
    // function u_any ($fn) {
    //     foreach ($this->val as $v) {
    //         if ($fn($v)) {
    //             return true;
    //         }
    //     }
    //     return false;
    // }
    //
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

