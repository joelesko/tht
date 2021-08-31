<?php

namespace o;

class u_Date extends OStdModule {

    function u_set_locale ($loc) {

        $this->ARGS('s', func_get_args());

        // PERF: This is actually a bit slow (on Windows at least), at around 0.3ms,
        // but not sure if there is anything we can do.
        setlocale(LC_TIME, $loc);

        return $this;
    }

    function u_now() {

        $this->ARGS('', func_get_args());

        return new u_Date_Object ();
    }

    function u_today() {

        $this->ARGS('', func_get_args());

        $do = new u_Date_Object ();

        return $do->u_clear_time();
    }

    function u_sandwich() {

        $this->ARGS('', func_get_args());

        return new u_Date_Object(newDti('November 13, 1718'));
    }

    public function u_duration_to_secs($durationStr) {

        $this->ARGS('s', func_get_args());

        if (is_numeric($durationStr)) {
            return $durationStr;
        }

        $now = newDti();
        $di = \date_interval_create_from_date_string($durationStr);
        $off = $now->add($di);

        $secs = $off->getTimestamp() - $now->getTimestamp();

        return $secs;
    }

    function u_create($dateArg) {

        $this->ARGS('*', func_get_args());

        if (is_string($dateArg)) {
            return $this->createFromString($dateArg);
        }
        else if (is_numeric($dateArg)) {
            return $this->createFromUnixTime($dateArg);
        }
        else if (OMap::isa($dateArg)) {
            return $this->createFromMap($dateArg);
        }
        else {
            $this->argumentError('First argument for `create` must be a string, map, or integer.', 'create');
        }
    }

    function u_unix_time () {

        $this->ARGS('', func_get_args());

        return time();
    }

    function createFromString($str) {

        try {
            $dti = newDti($str);
        }
        catch (\Exception $e) {
            $this->error("Unable to parse date string: `$str`");
        }

        return new u_Date_Object ($dti);
    }

    function createFromUnixTime($ts) {

        return new u_Date_Object(newDti('@' . $ts));
    }

    // TODO: handle defaults
    function createFromMap($dateMap) {

        $dm = $this->handleArgMap($dateMap);

        $ts = gmmktime(
            $dm['hour'],
            $dm['minute'],
            $dm['second'],
            $dm['month'],
            $dm['day'],
            $dm['year']
        );

        return $this->createFromUnixTime($ts);
    }

    // TODO: Use InputValidator or something more reusable to validate this
    function handleArgMap($origMap) {

        $args = ['hour', 'minute', 'second', 'month', 'day', 'year'];

        $map = [];
        foreach ($args as $a) {
            if (!isset($origMap[$a])) {
                if (in_array($a, ['month', 'day', 'year'])) {
                    $this->error('Date map must at least contain the fields: `year`, `month`, `day`');
                }
                else {
                    // time
                    $map[$a] = 0;
                }
            }
            else {
                $val = $origMap[$a];
                if (!vIsNumber($val) || !is_int($val)) {
                    $this->error("Argument `$a` must be an integer.  Got: $val");
                }
                else if ($val < 0) {
                    $this->error("Argument `$a` must be positive.  Got: $val");
                }

                $map[$a] = $origMap[$a];
            }
        }

        return $map;
    }
}

class u_Date_Object extends OClass {

    private $dti = null;

    protected $errorClass = 'Date';
    protected $cleanClass = 'Date';

    // Make sure helpLink points to module, not class
    function error($msg, $method = '') {
        Tht::module('Date')->error($msg, $method);
    }

    function __construct($dti = null) {

        if (is_null($dti)) {
            $dti = newDti();
        }
        $this->dti = $dti;
    }

    function toStringToken() {

        $fmt = $this->u_format('Y-m-d H:i:s O');

        return OClass::getStringToken('Date', $fmt);
    }

    function u_to_sql_string() {

        return $this->u_format('sql');
    }

    function u_format($aformat = 'iso8601') {

        $this->ARGS('s', func_get_args());

        $format = $this->formatConstant($aformat);

        $str = $this->dti->format($format);

        // Use strftime to get month/day names by locale
        $ts = $this->dti->getTimestamp();
        $str = str_replace($this->dti->format('F'), strftime('%B', $ts), $str);  // long month
        $str = str_replace($this->dti->format('M'), strftime('%b', $ts), $str);  // short month
        $str = str_replace($this->dti->format('l'), strftime('%A', $ts), $str);  // long day
        $str = str_replace($this->dti->format('D'), strftime('%a', $ts), $str);  // short day

        return $str;
    }

    function u_set_timezone($tzStr) {

        $this->ARGS('s', func_get_args());

        $tz = new \DateTimeZone($tzStr);

        return new u_Date_Object(
            $this->dti->setTimezone($tz)
        );
    }

    function u_get_timezone() {

        $this->ARGS('', func_get_args());

        return $this->dti->getTimezone()->getName();
    }

    function formatConstant($format) {

        switch ($format) {
            case 'atom':     return \DateTimeInterface::ATOM;
            case 'cookie':   return \DateTimeInterface::COOKIE;
            case 'iso8601':  return \DateTimeInterface::ISO8601;
            case 'rfc822':   return \DateTimeInterface::RFC822;
            case 'rfc850':   return \DateTimeInterface::RFC850;
            case 'rfc1036':  return \DateTimeInterface::RFC1036;
            case 'rfc1123':  return \DateTimeInterface::RFC1123;
            case 'rfc7231':  return \DateTimeInterface::RFC7231;
            case 'rfc2822':  return \DateTimeInterface::RFC2822;
            case 'rfc3339':  return \DateTimeInterface::RFC3339;
            case 'rfc3339x': return \DateTimeInterface::RFC3339_EXTENDED;
            case 'rss':      return \DateTimeInterface::RSS;
            case 'w3c':      return \DateTimeInterface::W3C;
            case 'sql':      return 'Y-m-d H:i:s';
        }

        return $format;
    }

    function u_unix_time() {

        $this->ARGS('', func_get_args());

        return $this->dti->getTimestamp();
    }

    function u_to_map() {

        $this->ARGS('', func_get_args());

        $ts = $this->dti->getTimestamp();
        $p = getdate($ts);

        $newParts = [
            'second'     => $p['seconds'],
            'minute'     => $p['minutes'],
            'hour'       => $p['hours'],
            'month'      => $p['mon'],
            'year'       => $p['year'],
            'day'        => $p['mday'],
            'dayOfWeek'  => $p['wday'],
            'dayOfYear'  => $p['yday'],

            'dayName'        => strftime('%A', $ts),
            'dayNameShort'   => strftime('%a', $ts),
            'monthName'      => strftime('%B', $ts),
            'monthNameShort' => strftime('%b', $ts),
        ];

        return OMap::create($newParts);
    }


    // Setters

    function u_set_date($y, $m, $d) {

        $this->ARGS('III', func_get_args());

        return new u_Date_Object(
            $this->dti->setDate($y, $m, $d)
        );
    }

    function u_set_time($h, $m, $s = 0) {

        $this->ARGS('III', func_get_args());

        return new u_Date_Object(
            $this->dti->setTime($h, $m, $s)
        );
    }

    // Relative

    function u_add($durationStr) {

        $this->ARGS('s', func_get_args());

        if (is_numeric($durationStr)) {
            $durationStr .= ' seconds';
        }

        $di = \date_interval_create_from_date_string($durationStr);

        return new u_Date_Object(
            $this->dti->add($di)
        );
    }

    function u_clear_time() {

        $this->ARGS('', func_get_args());

        return new u_Date_Object(
            $this->dti->setTime(0, 0, 0)
        );
    }

    function compare($otherDate) {

        if (is_string($otherDate)) {
            $otherDate = Tht::module('Date')->u_create($otherDate);
        }

        $ts1 = $this->u_unix_time();
        $ts2 = $otherDate->u_unix_time();

        return $ts1 <=> $ts2;
    }

    function u_is_past() {

        $this->ARGS('*', func_get_args());

        return $this->u_unix_time() < time();
    }

    function u_is_future() {

        $this->ARGS('*', func_get_args());

        return $this->u_unix_time() > time();
    }

    function u_is_after($otherDate) {

        $this->ARGS('*', func_get_args());

        return $this->compare($otherDate) > 0;
    }

    function u_is_before($otherDate) {

        $this->ARGS('*', func_get_args());

        return $this->compare($otherDate) < 0;
    }

    function u_equals($otherDate) {

        $this->ARGS('*', func_get_args());

        return $this->compare($otherDate) === 0;
    }

    function u_is_before_or_equal($otherDate) {

        $this->ARGS('*', func_get_args());

        return $this->compare($otherDate) <= 0;
    }

    function u_is_after_or_equal($otherDate) {
        $this->ARGS('*', func_get_args());

        return $this->compare($otherDate) >= 0;
    }

    function u_diff($refDate='now', $unit = 'secs') {

        $this->ARGS('*s', func_get_args());

        return $this->diffByUnit($refDate, $unit)['numFloat'];
    }

    function u_diff_human($refDate='now') {

        $this->ARGS('*', func_get_args());

        return $this->diffByUnit($refDate, 'auto')['human'];
    }


    function diffByUnit($refDate, $unit) {

        if (is_string($refDate) || is_numeric($refDate)) {
            $refDate = Tht::module('Date')->u_create($refDate);
        }
        else if (!u_Date_Object::isa($refDate)) {
            $this->error('Must pass in a valid Date object. Got instead: `' . v($refDate)->getErrorClass() . '`');
        }

        $diffSecs = $this->u_unix_time() - $refDate->u_unix_time();

        if ($unit === 'auto') {
            $unit = $this->findNearestDiffUnit($diffSecs);
        } else {
            $unit = $this->normalizeUnit($unit);
        }

        $real = $this->calcDiffForUnit($diffSecs, $unit);
        $rounded = $this->roundUnit($real);

        $label = $rounded == 1 ? $unit : $unit . 's';

        return OMap::create([
            'human'    => "$rounded $label",
            'label'    => $label,
            'numFloat' => $real,
            'num'      => $rounded,
            'secs'     => $diffSecs,
        ]);
    }

    function normalizeUnit($u) {

        $u = rtrim($u, 's');

        if ($u == 'min') return 'minute';
        if ($u == 'sec') return 'second';

        return $u;
    }

    function calcDiffForUnit($secs, $u) {

        $unit = [];

        $unit['second'] = $secs;
        $unit['minute'] = $unit['second'] / 60;
        $unit['hour']   = $unit['minute'] / 60;
        $unit['day']    = $unit['hour']   / 24;
        $unit['week']   = $unit['day']    / 7;
        $unit['month']  = $unit['week']   / 4.35;
        $unit['year']   = $unit['month']  / 12;

        return $unit[$u];
    }

    function roundUnit($num) {

        $num = abs($num);

        $rem = $num - floor($num);
        if ($rem >= 0.9) {
            $num = ceil($num);
        }
        else {
            $num = floor($num);
        }

        // Larger numbers should be simpler multiples
        // 1, 2, 3, 4, 5, 10, 15, 20, 30, 40, 50
        if ($num >= 20) {
            $num = round($num / 10) * 10;
        }
        else if ($num >= 5) {
            $num = round($num / 5) * 5;
        }

        return (int) $num;
    }

    // Get biggest whole unit
    function findNearestDiffUnit ($diffSecs) {

        $labels = ["second", "minute", "hour", "day", "week", "month", "year"];
        $scales = [60, 60, 24, 7, 4.35, 12, 1];

        $diff = abs($diffSecs);

        foreach ($labels as $i => $label) {
            $scale = $scales[$i];
            if ($this->roundUnit($diff / $scale) < 1) {
                return $label;
            }
            else {
                $diff /= $scale;
            }
        }

        return $label;
    }
}

function newDti($arg = 'now') {
    $tz = new \DateTimeZone(Tht::getConfig('timezone'));
    return new \DateTimeImmutable($arg, $tz);
}
