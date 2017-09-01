<?php

namespace o;


class u_Date extends StdModule {

    function u_now ($inMillisecs=false) {
        return $inMillisecs ? ceil(microtime(true) * 1000) : time();
    }

    function u_days ($num) {
        return $num * 24 * 3600;
    }

    function u_hours ($num) {
        return $num * 3600;
    }

    function u_minutes ($num) {
        return $num * 60;
    }

    function u_to_days ($num) {
        return $num / (24 * 3600);
    }

    function u_to_hours ($num) {
        return $num / 3600;
    }

    function u_to_minutes ($num) {
        return $num / 60;
    }

    function u_format ($format, $time=null) {
        if ($time === null) { $time = time(); }
        return strftime($format, $time);
    }

    // THANKS: https://css-tricks.com/snippets/php/time-ago-function/
    function u_difference ($time1, $time2=null, $labels=null) {

       if ($labels == null) {
           $labels = ["now", "second", "minute", "hour", "day", "week", "month", "year"];
       }
       $lengths = ["60", "60", "24", "7", "4.35", "12"];

       if ($time2 === null) { $time2 = time(); }
       $diff = abs($time2 - $time1);
       if (!$diff) { return $labels[0]; }

       $maxLengths = count($lengths) - 1;
       for ($j = 1; $diff >= $lengths[$j] && $j < $maxLengths; $j += 1) {
           $diff /= $lengths[$j];
       }

       $diff = round($diff);

       if ($diff !== 1) {
           $labels[$j] .= "s";
       }

       return "$diff $labels[$j]";
    }
}

