<?php

namespace o;

class OList extends OBag {

    protected $hasNumericKeys = true;

    static function create ($a) {
        $l = new OList ();
        $l->val = $a;
        return $l;
    }

    function u_copy() {
        // php apparently copies the array when assigned to a new var
        $a = $this->val;
        return OList::create($a);
    }

    function u_is_empty () {
        return count($this->val) === 0;
    }



    //// GETTERS


    function u_contains ($v) {
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
        $i = array_search($v, $this->val, true);
        return ($i === false) ? -1 : $i;
    }

    function u_last_index_of($v) {
        $len = count($this->val);
        for ($i = 0; $i < $len; $i += 1) {
            if ($this->val[$i] === $v) {
                return $i;
            }
        }
        return -1;
    }

    function u_sublist($pos, $len=-1) {
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

    // TODO: remove multiple
    function u_remove ($pos) {
        ARGS('n', func_get_args());
        $len = count($this->val);
        if (!$len) {
            Tht::error("Can not `remove()` from an empty List.");
        }
        if ($pos === -1) {
            return array_pop($this->val);
        }
        $removed = array_splice($this->val, $pos, 1);
        if (!count($removed)) {
            $vlen = count($this->val);
            Tht::error("Can not `remove()` at `index=$pos` from List with length `$vlen`.");
        }
        return $removed[0];
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






    // ORDER

    function u_reverse () {
        return OList::create(array_reverse($this->val));
    }

    function u_shuffle () {
        shuffle($this->val);
        return $this;
    }

    function u_sort ($fnOrArgs=false) {

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

    function u_sort_by_key ($key, $isDesc=false) {

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

    function u_join ($delim='') {
        ARGS('s', func_get_args());
        return implode($this->val, $delim);
    }



    // function u_unique () {
    //     return array_unique($this->val);
    // }

    // TODO: fill with default. aka pad, fill
    // function u_resize ($size, $default='') {
    //     return array_splice($this->val, $size);
    // }

    // function u_to_args () {
    //     return new ArgList ($this->val);
    // }

    // function u_flatten ($array) {
    //     $flat = [];
    //     $stack = array_values($array);
    //     while ($stack)  {
    //         $value = array_shift($stack);
    //         if (is_array($value)) {
    //             $stack = array_merge(array_values($value), $stack);
    //         } else  {
    //            $flat []= $value;
    //         }
    //     }
    //     return $flat;
    // }


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
    // function u_map ($fn) {
    //     return array_map($fn, $this->val);
    // }
    //
    // function u_reduce ($fn, $initial=null) {
    //     return array_reduce($this->val, $fn, $initial);
    // }
    //
    // function u_filter ($fn) {
    //     return array_filter($this->val, $fn);
    // }
    //

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

