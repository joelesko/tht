<?php

namespace o;

class u_Date extends OStdModule {

    private $locale = null;

    static function getDateArg($argDate) {

        if (is_string($argDate) || is_numeric($argDate)) {
            $argDate = Tht::module('Date')->u_create($argDate);
        }
        else if (!u_Date_Object::isa($argDate)) {
            $this->error('Must pass in a valid `Date` object.  Got: `' . v($argDate)->getErrorClass() . '`');
        }

        return $argDate;
    }

    function u_set_locale($loc) {

        $this->ARGS('s', func_get_args());

        $this->locale = $loc;

        return $this;
    }

    function getLocalizedParts($ts) {

        $formatter = new \IntlDateFormatter(
            $this->locale,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            null,
            null,
            'EEEE^^EEE^^MMMM^^MMM'
        );
        $fmt = explode('^^', $formatter->format($ts));

        return [
            'dayLong'    => $fmt[0],
            'dayShort'   => $fmt[1],
            'monthLong'  => $fmt[2],
            'monthShort' => $fmt[3],
        ];
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

    function u_create($dateArg) {

        $this->ARGS('*', func_get_args());

        if (is_numeric($dateArg)) {
            return $this->createFromUnixTime($dateArg);
        }
        else if (is_string($dateArg)) {
            return $this->createFromString($dateArg);
        }
        else if (OMap::isa($dateArg)) {
            return $this->createFromMap($dateArg);
        }
        else {
            $this->argumentError('Argument #1 for `create()` must be a String, Map, or Integer.  Got: `' . v($dateArg)->u_z_class_name() . '`', 'create');
        }
    }

    function u_unix_time() {

        $this->ARGS('', func_get_args());

        return time();
    }

    function u_days_in_month($dateObj='now') {

        $this->ARGS('*', func_get_args());

        $dateObj = self::getDateArg($dateObj);

        return cal_days_in_month(CAL_GREGORIAN, $dateObj->u_get('month'), $dateObj->u_get('year'));
    }

    function u_month_list($flags=null) {

        $this->ARGS('m', func_get_args());

        $flags = $this->flags($flags, [
            'short' => false,
        ]);

        $field = $flags['short'] ? 'monthNameShort' : 'monthName';

        $list = OList::create([]);
        foreach (range(1, 12) as $num) {
            $date = Tht::module('Date')->u_create('2033-' . $num);
            $list []= $date->u_get($field);
        }

        return $list;
    }

    function u_sandwich() {

        $this->ARGS('', func_get_args());

        return new u_Date_Object(newDti('November 13, 1718'));
    }

    function u_diff_to_seconds($durationStr) {

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
                    $this->error('Date map must at least contain the fields: `year` `month` `day`');
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

    function toObjectString() {

        $fmt = $this->u_format('Y-m-d H:i:s O');

        return OClass::getObjectString('Date', $fmt);
    }

    function u_z_to_sql_string() {

        return $this->u_format('sql');
    }

    function u_z_to_json_string() {

        return $this->u_format('sql');
    }

    function u_format($aformat = 'iso8601') {

        $this->ARGS('s', func_get_args());

        $format = $this->formatConstant($aformat);

        $str = $this->dti->format($format);

        // Use strftime to get month/day names by locale
        $ts = $this->dti->getTimestamp();

        $locParts = Tht::module('Date')->getLocalizedParts($ts);

        $str = str_replace($this->dti->format('F'), $locParts['monthLong'], $str);
        $str = str_replace($this->dti->format('M'), $locParts['monthShort'], $str);
        $str = str_replace($this->dti->format('l'), $locParts['dayLong'], $str);
        $str = str_replace($this->dti->format('D'), $locParts['dayShort'], $str);

        return $str;
    }

    function u_set_timezone($tzStr) {

        $this->ARGS('s', func_get_args());

        $tz = new \DateTimeZone($tzStr);

        return new u_Date_Object(
            $this->dti->setTimezone($tz)
        );
    }

    function u_is_dst() {

        $this->ARGS('', func_get_args());

        return $this->dti->format('I') == '1';
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

        $locParts = Tht::module('Date')->getLocalizedParts($ts);

        $newParts = [
            'second'     => $p['seconds'],
            'minute'     => $p['minutes'],
            'hour'       => $p['hours'],
            'month'      => $p['mon'],
            'year'       => $p['year'],
            'day'        => $p['mday'],
            'dayOfWeek'  => $p['wday'],
            'dayOfYear'  => $p['yday'],

            'dayName'        => $locParts['dayLong'],
            'dayNameShort'   => $locParts['dayShort'],
            'monthName'      => $locParts['monthLong'],
            'monthNameShort' => $locParts['monthShort'],
        ];

        return OMap::create($newParts);
    }

    function u_get($field) {

        $this->ARGS('s', func_get_args());

        $map = $this->u_to_map();

        if (!isset($map[$field])) {
            $alias = [
                'sec'  => 'second',
                'min'  => 'minute',
                'mon'  => 'month',
                'secs' => 'second',
                'mins' => 'minute',
                'dayinweek' => 'dayOfWeek',
                'dayinyear' => 'dayOfYear',
                'weekday' => 'dayOfWeek',
                'yearday' => 'dayOfYear',
                'daynamelong' => 'dayName',
                'monthnamelong' => 'monthName',
            ];
            $suggest = ErrorHandler::getFuzzySuggest($field, unv($map->u_keys()), false, $alias);
            $this->error("Unknown date field: `$field`  $suggest");
        }

        return $map[$field];
    }


    // Setters

    function u_set($field, $value) {

        $this->ARGS('sI', func_get_args());

        $this->validateDateField($field);

        $map = $this->u_to_map();
        $map[$field] = $value;

        return Tht::module('Date')->u_create($map);
    }

    function validateDateField($field) {
        $allowed = ['year', 'month', 'day', 'hour', 'minute', 'second'];

        if (!in_array($field, $allowed)) {
            $alias = [
                'sec'  => 'second',
                'min'  => 'minute',
                'secs' => 'second',
                'mins' => 'minute',
                'mon'  => 'month',
            ];
            $suggest = ErrorHandler::getFuzzySuggest($field, $allowed, false, $alias);
            $this->error("Unknown date field: `$field`  $suggest");
        }
    }

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

        $otherDate = u_Date::getDateArg($otherDate);

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

    function u_diff($refDate='now', $unit = 'seconds') {

        $this->ARGS('*s', func_get_args());

        return $this->diffByUnit($refDate, $unit)['numFloat'];
    }

    function u_diff_human($refDate='now') {

        $this->ARGS('*', func_get_args());

        $diff = $this->diffByUnit($refDate, 'auto')['human'];

        if ($refDate == 'now' && $this->u_is_before_or_equal($refDate)) {
            $diff .= ' ago'; // TODO: localize?
        }

        return $diff;
    }

    function diffByUnit($refDate, $unit) {

        $refDate = u_Date::getDateArg($refDate);

        $diffSecs = $this->u_unix_time() - $refDate->u_unix_time();

        if ($unit === 'auto') {
            $unit = $this->findNearestDiffUnit($diffSecs);
        } else {
            $unit = rtrim($unit, 's');
        }

        $real = $this->calcDiffForUnit($diffSecs, $unit);
        $rounded = $this->roundUnit($real);

        $label = $rounded == 1 ? $unit : $unit . 's';

        return OMap::create([
            'human'    => "$rounded $label",
            'label'    => $label,
            'numFloat' => $real,
            'num'      => $rounded,
            'seconds'  => $diffSecs,
        ]);
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

        if (!isset($unit[$u])) {
            $suggest = ErrorHandler::getFuzzySuggest($u . 's', array_keys($unit));
            $this->error("Invalid date field: `$u`  $suggest");
        }

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
    function findNearestDiffUnit($diffSecs) {

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

     $tz = new \DateTimeZone(Tht::getTimezone());

     return new \DateTimeImmutable($arg, $tz);
}


