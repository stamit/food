<?php
/**
  * Currency, Number, Date handling functions
  */

define('PREF_DECIMAL_DIGITS', '2');
define('PREF_DECIMAL_SYMBOL', '.');
define('PREF_GROUPING_SYMBOL', ',');
define('PREF_CURRENCY_SYMBOL', '€');
define('PREF_CURRENCY_LR', 'l');
define('PREF_DATE_FIRST_DAY', '1'); // 0=Sun, 6=Sat
define('PREF_DATE_FORMAT', 'FULL3'); // 17 Nov 2008
define('PREF_DATE_SEPARATOR', ' ');

//
// NUMBERS
//

/**
  * Decode number (e.g. 123456.15 -> 123.456,15).
  * @param float $number a number
  * @param int $decimal_digits decimal digits
  * @param string $decimal_symbol decimal symbol
  * @param string $grouping_symbol grouping symbol
  * @return mixed string formatted number or false on error
  */
function number_decode($number, $decimal_digits = -1, $decimal_symbol = '', $grouping_symbol = '') {
  // because some DB servers return coma as a decimal separator, but PHP's number_format wants a fullstop
  $number = str_replace(',', '.', $number);

  if ($decimal_digits == -1) $decimal_digits = PREF_DECIMAL_DIGITS;
  if ($decimal_symbol == '') $decimal_symbol = PREF_DECIMAL_SYMBOL;
  if ($grouping_symbol == '') $grouping_symbol = PREF_GROUPING_SYMBOL;

  if ($decimal_digits == -1) return false;
  if ($decimal_symbol == '') return false;
  if ($grouping_symbol == '') return false;

  if ($grouping_symbol == '_') $grouping_symbol = ' '; // underscored becomes a space

  return number_format($number, $decimal_digits, $decimal_symbol, $grouping_symbol);
}

//
// CURRENCY
//

/**
  * Decode currency number. Uses decode number (e.g. 123456.15 -> 123.456,15 EUR).
  * @param float $number a number
  * @param string $currency_symbol currency symbol, e.g. EUR, &euro;, $
  * @param string $currency_lr l for left, r for right
  * @param int $decimal_digits decimal digits
  * @param string $decimal_symbol decimal symbol
  * @param string $grouping_symbol grouping symbol
  * @return mixed string formatted currency or false on error
  */
function currency_decode($number, $currency_symbol = '', $currency_lr = '', $decimal_digits = -1, $decimal_symbol = '', $grouping_symbol = '') {
  if ($number == '') return '-';
  if ($currency_symbol == '') $currency_symbol = PREF_CURRENCY_SYMBOL;
  if ($currency_lr == '') $currency_lr = PREF_CURRENCY_LR;

  if ($currency_symbol == '') return false;
  if ($currency_lr == '') return false;

  $num = number_decode($number, $decimal_digits, $decimal_symbol, $grouping_symbol);
  if ($num === false) return false;

  $currency_symbol = str_replace('_', ' ', $currency_symbol);
  if ($currency_lr == 'l')
    $num = $currency_symbol . $num;
  else
    $num = $num . $currency_symbol;

  return $num;
}

//
// DATES
//
// Most functions accept with GMT dates, time, timestamps unless otherwise noted.
//

/**
  * get current timestamp (YYYY-MM-DD HH:MI:SS) in any timezone (passing 0 returns GMT time, passing 7200 returns GMT+2 time)
  * @param int $tz timezone, from -12*3600..13*3600 (e.g. GMT +05:45 will have $tz = 20700)
  * @return string
  */
function now($tz = null) {
  if ($tz===null) $tz = $GLOBALS['PREF_TIMEZONE'];
  return date_from_timestamp(mktime() + $tz);
}
function today($tz = null) {
  if ($tz===null) $tz = $GLOBALS['PREF_TIMEZONE'];
  return date_from_timestamp(mktime() + $tz);
}

/**
  * convert timezone date to gmt date
  * @param string $date a local date/time e.g. YYYY-MM-DD HH:MI:SS or YYYY-MM-DD
  * @param int $tz timezone, from -12*3600..13*3600 (e.g. GMT +05:45 will have $tz = 20700)
  * @return string with date or empty
  */
function tz_to_gmt($date, $tz = -100000) {
  if ($tz == -100000) $tz = 0;
  if (!(($tz >= -12 * 3600) && ($tz <= 13 * 3600))) return '';
  return date_adjust($date, $tz * -1 . ' seconds');
}

/**
  * convert gmt date to timezone date
  * @param string $date a gmt date/time e.g. YYYY-MM-DD HH:MI:SS or YYYY-MM-DD
  * @param int $tz timezone, from -12*3600..13*3600 (e.g. GMT +05:45 will have $tz = 20700)
  * @return string with date or empty
  */
function gmt_to_tz($date, $tz = -100000) {
  if ($tz == -100000) $tz = 0;
  if (!(($tz >= -12 * 3600) && ($tz <= 13 * 3600))) return '';
  return date_adjust($date, $tz . ' seconds');
}

/**
  * for given date/time (YYYY-MM-DD[ HH:SS[:MI]]), return GMT timestamp. Missing time is assumed 12:00:00
  * @param string $date the date/time e.g. YYYY-MM-DD HH:MI:SS or YYYY-MM-DD
  * @return int timestamp 0 on error
  */
function date_to_timestamp($date) {
  if ((strlen($date) != 10) && (strlen($date) != 16) && (strlen($date) != 19)) return 0;

  $dd = substr($date, 8, 2);
  $mm = substr($date, 5, 2);
  $yy = substr($date, 0, 4);

  $hh = 12;
  $mi = 0;
  $ss = 0;
  if (strlen($date) > 10) {
    $hh = substr($date, 11, 2);
    $mi = substr($date, 14, 2);
    if (strlen($date) == 19)
      $ss = substr($date, 17, 2);
  }

  return gmmktime($hh, $mi, $ss, $mm, $dd, $yy, -1);
}

/**
  * from given timestamp return GMT date/time (YYYY-MM-DD HH:MI:SS).
  * @param string $time timestamp
  * @return string date, empty on error
  */
function date_from_timestamp($time) {
  return gmdate('Y-m-d H:i:s', $time);
}

/**
  * add/sub days, weeks, years from given date/time (YYYY-MM-DD[ HH:SS[:MI]])
  * @param string $date the date/time e.g. YYYY-MM-DD HH:MI:SS or YYYY-MM-DD
  * @param string $adjustment e.g. +1 day, -2 week, +5 year
  * @return string new date (with same structure e.g date[time[seconds]]) or empty
  */
function date_adjust($date, $adjustment) {
  $res = '';

  $time = date_to_timestamp($date);
  if ($time == 0) return $res;

  $res = strtotime($adjustment, $time);
  if ($res !== -1) {
    $res = date_from_timestamp($res);
    $res = substr($res, 0, strlen($date));
  }

  return $res;
}

/**
  * get date's (YYYY-MM-DD[ HH:SS:MI]) day number (0=Sunday, 6=Sat)
  * @param string $date the TZ date/time e.g. YYYY-MM-DD HH:MI:SS or YYYY-MM-DD
  * @return integer day number, -1 on error
  */
function date_day_num($date) {
  $res = -1;

  $time = date_to_timestamp($date);
  if ($time == 0) return $res;

  // getdate() is locale aware. Giving it a GMT timestamp as created by gmmktime() (used in date_to_timestamp()) will produce local time results
  // So I shift the timestamp by -"GMT offset seconds" to counter that
  $time += mktime() - gmmktime();

  $elements = getdate($time);
  return $elements['wday'];
}

/**
  * get date's (YYYY-MM-DD[ HH:SS:MI]) day name
  * @param string $date the TZ date/time e.g. YYYY-MM-DD HH:MI:SS or YYYY-MM-DD
  * @return string full day name (Monday - Sunday)
  */
function date_day_name($date) {
  $res = '';

  $day_num = date_day_num($date);
  switch ($day_num) {
    case 0:
      $res = 'Sunday';
      break;
    case 1:
      $res = 'Monday';
      break;
    case 2:
      $res = 'Tuesday';
      break;
    case 3:
      $res = 'Wednesday';
      break;
    case 4:
      $res = 'Thursday';
      break;
    case 5:
      $res = 'Friday';
      break;
    case 6:
      $res = 'Saturday';
      break;
  }

  return $res;
}

/**
  * get date's (YYYY-MM-DD[ HH:SS:MI]) month name
  * @param string $date the date/time e.g. YYYY-MM-DD HH:MI:SS or YYYY-MM-DD
  * @return string full month name (January - December)
  */
function date_month_name($date) {
  $res = '';

  $month_num = substr($date, 5, 2);
  switch ($month_num) {
    case 1:
      $res = 'January';
      break;
    case 2:
      $res = 'February';
      break;
    case 3:
      $res = 'March';
      break;
    case 4:
      $res = 'April';
      break;
    case 5:
      $res = 'May';
      break;
    case 6:
      $res = 'June';
      break;
    case 7:
      $res = 'July';
      break;
    case 8:
      $res = 'August';
      break;
    case 9:
      $res = 'September';
      break;
    case 10:
      $res = 'October';
      break;
    case 11:
      $res = 'November';
      break;
    case 12:
      $res = 'December';
      break;
  }

  return $res;
}

/**
  * Decode date (YYYY-MM-DD HH:MI:SS -> DD/MM/YYYY HH:MI:SS). Time is optional (and when exists, seconds are optional)
  * @param string $date a gmt date/time e.g. YYYY-MM-DD HH:MI:SS or YYYY-MM-DD
  * @param int $tz timezone to translate date in
  * @param string $date_format DMY, MDY or YMD
  * @param string $date_separator e.g. '/' or '-' or '.'
  * @return mixed decoded date/time or false on error
  */
function date_decode($date, $tz = -100000, $date_format = '', $date_separator = '') {
  if ((strlen($date) != 10) && (strlen($date) != 16) && (strlen($date) != 19)) return false;

  // get current user settings
  if ($tz == -100000) $tz = 0;
  if ($date_format == '') $date_format = PREF_DATE_FORMAT;
  if ($date_separator == '') $date_separator = PREF_DATE_SEPARATOR;

  if (!(($tz >= -12 * 3600) && ($tz <= 13 * 3600))) return false;
  if ($date_format == '') return false;
  if ($date_separator == '') return false;

  // if I have time, translate to TZ time
  if (strlen($date) > 10)
    $date = gmt_to_tz($date, $tz);

  $dd = substr($date, 8, 2);
  $mm = substr($date, 5, 2);
  $yy = substr($date, 0, 4);

  if ($date_format == 'DMY')
    $res = $dd . $date_separator . $mm . $date_separator . $yy;
  else
  if ($date_format == 'MDY')
    $res = $mm . $date_separator . $dd . $date_separator . $yy;
  else
  if ($date_format == 'YMD')
    $res = $yy . $date_separator . $mm . $date_separator . $dd;
  else
  if ($date_format == 'FULL')
    $res = $dd . $date_separator . date_month_name($date) . $date_separator . $yy;
  else
  if ($date_format == 'FULL3')
    $res = $dd . $date_separator . substr(date_month_name($date), 0, 3) . $date_separator . $yy;
  else
    return false;

  if (strlen($date) > 10) {
    $hh = substr($date, 11, 2);
    $mi = substr($date, 14, 2);
    $res .= ' ' . $hh . ':' . $mi;
    if (strlen($date) == 19) {
      $ss = substr($date, 17, 2);
      $res .= ':' . $ss;
    }
  }

  return $res;
}

/**
  * Encode TZ date to GMT datestamp (DD/MM/YYYY HH:MI:SS-> YYYY-MM-DD HH:MI:SS). Time is optional (and when exists, seconds are optional)
  * @param string $date a local/TZ date/time e.g. DD/MM/YYYY HH:MI:SS or DD/MM/YYYY
  * @param int $tz timezone of the date
  * @param string $date_format DMY, MDY or YMD
  * @param string $date_separator e.g. '/' or '-' or '.'
  * @return mixed encoded date/time or false on error
  */
function date_encode($date, $tz = -100000, $date_format = '', $date_separator = '') {
  // get current user settings
  if ($tz == -100000) $tz = 0;
  if ($date_format == '') $date_format = PREF_DATE_FORMAT;
  if ($date_separator == '') $date_separator = PREF_DATE_SEPARATOR;

  if (!(($tz >= -12 * 3600) && ($tz <= 13 * 3600))) return false;
  if ($date_format == '') return false;
  if ($date_separator == '') return false;

  // normalize and explode date-time
  $ts = trim($date);
  // double spaces -> single spaces
  while (($ts = str_replace('  ', ' ', $ts)) != $ts) {}
  // the last single space -> $date_separator
  $ts = str_replace(' ', $date_separator, $ts);
  // the time separator -> $date_separator
  $ts = str_replace(':', $date_separator, $ts);
  // explode
  $s = explode($date_separator, $ts);

  // check for enough tokens
  if ((count($s) != 3) && (count($s) != 5) && (count($s) != 6)) return false;

  if ($date_format == 'DMY') {
    $dd = $s[0];
    $mm = $s[1];
    $yy = $s[2];
  } else
  if ($date_format == 'MDY') {
    $dd = $s[1];
    $mm = $s[0];
    $yy = $s[2];
  } else
  if ($date_format == 'YMD') {
    $dd = $s[2];
    $mm = $s[1];
    $yy = $s[0];
  } else
    return false;

  // pad with leading zeros
  $dd = str_pad($dd, 2, '0', STR_PAD_LEFT);
  $mm = str_pad($mm, 2, '0', STR_PAD_LEFT);

  // check for valid date
  if (strlen($yy) != 4) return false;
  if ((!is_numeric($dd)) || (!is_numeric($mm)) || (!is_numeric($yy)) ||
      (!checkdate($mm, $dd, $yy))) return false;

  $res = $yy . '-' . $mm . '-' . $dd;

  // encode time
  if (count($s) > 3) {
    $hh = $s[3];
    $mi = $s[4];
    if (count($s) == 6)
      $ss = $s[5];
    else
      $ss = 0;

    // pad with leading zeros
    $hh = str_pad($hh, 2, '0', STR_PAD_LEFT);
    $mi = str_pad($mi, 2, '0', STR_PAD_LEFT);
    $ss = str_pad($ss, 2, '0', STR_PAD_LEFT);

    // check for valid time
    if ((!is_numeric($hh)) || (!is_numeric($mi)) || (!is_numeric($ss))) return false;
    if (($hh < 0) || ($hh > 23)) return false;
    if (($mi < 0) || ($mi > 59)) return false;
    if (($ss < 0) || ($ss > 59)) return false;

    $res .= ' ' . $hh . ':' . $mi;
    if (count($s) == 6)
      $res .= ':' . $ss;
  }

  // if I have time, translate to GMT time
  if (strlen($res) > 10)
    $res = tz_to_gmt($res, $tz);

  return $res;
}

?>
