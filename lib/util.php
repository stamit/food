<?php
function repr(&$x) {
	# FIXME reduce verbosity
	return var_export($x, TRUE);
}

function array_head($a) {
	return $a[0];
}

function array_changes($a,$b) {
	$c = array();
	foreach ($b as $x=>$y) {
		if ($a[$x]!=$b[$x]) {
			$c[$x] = $b[$x];
		}
	}
	return $c;
}

function startswith($string,$prefix) {
	return substr($string,0,strlen($prefix))==$prefix;
}

function endswith($string,$prefix) {
	return substr($string,strlen($string)-strlen($prefix),strlen($prefix))==$prefix;
}

function clamp($a, $x, $z) {
	if ($a!==null && $x<$a) return $a;
	if ($z!==null && $z<$x) return $z;
	return $x;
}

function plat($a, $x, $z) {
	if ($a!==null && $x<$a) return null;
	if ($z!==null && $z<$x) return null;
	return $x;
}

function swap(&$a, &$b) {
	$c = $a;
	$a = $b;
	$b = $c;
}

function smart_implode($separator, $strings) {
	$a = array();
	foreach ($strings as $str) {
		if (strlen($str)) {
			$a[] = $str;
		}
	}
	return implode($separator, $a);
}

function ifnull($x,$y) {
	return ($x===null?$y:$x);
}
function ifnil($x,$y) {
	return (strlen($x)?$x:$y);
}
function ifempty($x,$y) {
	return (strlen($x)?$x:$y);
}
function ifnot($x,$y) {
	return $x?$x:$y;
}

function ini_to_bytes($val) {
	$val = trim($val);
	$last = strtolower($val[strlen($val)-1]);
	switch ($last) {
		case 'g': $val *= 1024;
		case 'm': $val *= 1024;
		case 'k': $val *= 1024;
	}
	return $val;
}

# $fn with unsafe characters fixed
# - control characters and 127 (DEL) become ' '
# - * / : < > ? \ and | become '-'
function filename_cleanup($fn) {
	$s = '';
	for ($i = 0 ; $i < strlen($fn) ; ++$i) {
		$n = ord($fn[$i]);
		if ($n < 32 || $n == 127) {
			$s .= ' ';
		} else if (strpos("*/:<>?\|",$fn[$i])) {
			$s .= '-';
		} else {
			$s .= $fn[$i];
		}
	}
	return $s;
}

# a random character string
function rand_str($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890') {
	$chars_length = (strlen($chars) - 1);
	$string = $chars{rand(0, $chars_length)};
	for ($i = 1; $i < $length; $i = strlen($string)) {
		$r = $chars{rand(0, $chars_length)};
		if ($r != $string{$i - 1}) $string .= $r;
	}
	return $string;
}

# capitalize sentence ("foo bar" => "Foo bar")
function capitalize($str) {
	return mb_strtoupper(mb_substr($str,0,1)).mb_substr($str,1);
}


################################################################################


# assumes $_POST is encoded properly
function post($name) {
	return v($name);
}
function v($name) {
	return ($_POST[$name]!==null) ? $_POST[$name] : $_GET[$name];
}

function posting() {
	global $METHOD;
	if (count($_POST)>0) {
		foreach ($_POST as $a=>$b) {
			if (!startswith($a,'__')) {
				return true;
			}
		}
		return false;
	} else {
		return ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST!==null);
	}
}
function mistake($a, $b=null) {
	global $MISTAKES;
	if ($MISTAKES===null) $MISTAKES = array();
	if ($b!==null) {
		$MISTAKES[$a] = $b;
	} else {
		$MISTAKES[] = $a;
		if ($INLINE_REQUEST) {
			$_SESSION['alert'] = $a;
		}
	}
}
function correct() {
	global $MISTAKES, $INLINE_REQUEST;
	if (!count($MISTAKES)) {
		return true;
	} else if ($INLINE_REQUEST) {
		# exception caught and passed to failure()
		throw new Exception('');
	}
}
function success($redir=null,$id=null) {
	global $ENCODING, $INLINE_REQUEST;
	if ($INLINE_REQUEST>=2) {
		# included script
		return true;
	} else if ($INLINE_REQUEST) {
		header('Content-type: text/plain'.($ENCODING?'; charset='.$ENCODING:''));
		if ($redir!==null || $id!==null) {
			echo js(array($redir,$id));
		}
		return true;
	} else if ($redir!==null) {
		redirect($redir);
		return true;
	} else {
		return false;
	}
}
function failure($msg=null) {
	global $ENCODING, $INLINE_REQUEST, $MISTAKES;

	rollback();

	if ($msg!==null) {
		if ($msg instanceof Exception)
			$msg = 'The action failed because '.$msg->getMessage().'.';
		if ($msg!=='')
			mistake($msg);
	}

	if ($INLINE_REQUEST>=2) {
		# included script
		return true;
	} else if ($INLINE_REQUEST) {
		header(' ', true, 402);  # "payment required"
		header('Content-type: text/javascript'.($ENCODING ? '; charset='.$ENCODING : ''));
		$mist=array();
		foreach ($MISTAKES as $a=>$b)
			$mist[] = array($a,$b);
		print js($mist);
		return true;
	} else {
		return false;
	}
}

# this `redirect' makes absolute addresses from relative (without parameter,
# redirects to current page - useful for turning a POST into a GET)
function redirect($uri='',$status=302) {
	if ($uri===null) $uri='/';
	header(' ', true, $status);
	header('Location: '.make_absolute_url($uri));
	exit;
}

function goback($msg=null) {
	if ($msg!==null) $_SESSION['alert'] = $msg;
	redirect($_SERVER['HTTP_REFERER']);
}

function forbid() {
	header(' ', true, 403);
	exit;
}

function detect_msie() {
	# TODO maybe use session variable
	return isset($_SERVER['HTTP_USER_AGENT'])
	       && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false);
}

function _get_user_ip() {
	$ip = $_SERVER['REMOTE_ADDR'];
	foreach (array('HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','HTTP_X_FORWARDED','HTTP_FORWARDED_FOR','HTTP_FORWARDED') as $header) {
		if (!isset($_SERVER[$header])) continue;
		$ip .= ':'.$_SERVER[$header];
		break;
	}
	return $ip;
}


################################################################################


function xml($x) {
	return ($x!==null?htmlspecialchars(strval($x), ENT_QUOTES):null);
}
function html($x) {
	return ($x!==null?htmlspecialchars(strval($x), ENT_QUOTES):null);
}
function html_stiff($x, $ifempty='') {
	$y = str_replace("\x00", "&nbsp;", strtr(html($x), " \t\r\n", "\x00\x00\x00\x00"));
	return strlen($y) ? $y : $ifempty;
}

function jsencode($x) { return js($x); }
function js($x) {
	if (is_bool($x)) {
		return ($x ? 'true' : 'false');
	} else if (is_integer($x)) {
		return strval($x);
	} else if (is_real($x)) {
		return sprintf('%F',$x);
	} else if (is_string($x)) {
		return javascript_string($x);
	} else if (is_array($x)) {
		if (!count($x)) {
			return '[]';
		} else if (array_key_exists(0,$x)) {
			$s = '[';
			for ($i = 0 ; $i < count($x) ; ++$i) {
				if ($i) $s.=',';
				$s .= js($x[$i]);
			}
			$s.=']';
			return $s;
		} else {
			$s = '{';
			$i = 0;
			foreach ($x as $key => $value) {
				if ($i) $s.=','; else ++$i;
				$s .= js($key).':'.js($value);
			}
			$s .= '}';
			return $s;
		}
	} else {
		return 'null';
	}
}

# accepts string in encoding $ENCODING and returns Javascript literal in
# encoding $ENCODING
function javascript_string($x) {
	$y = '';
	for ($i = 0 ; $i < strlen($x) ; ++$i) {
		$c = $x[$i];
		switch ($c) {
		case "\x08": $c = '\b'; break;
		case "\t": $c = '\t'; break;
		case "\n": $c = '\n'; break;
		case "\r": $c = '\r'; break;
		case "\f": $c = '\f'; break;
		case "\v": $c = '\v'; break;
		case '"': $c = '\"'; break;
		case "\\": $c = '\\\\'; break;
		default:
			$co = ord($c);
			if ($co < 32) {
				$c = '\\x'.bin2hex($c);
			}
			break;
		}
		$y .= $c;
	}
	return '"' . $y . '"';
}

if (!function_exists('json_decode')) {
	require_once dirname(__FILE__).'/JSON.php';
	function json_decode($content, $assoc=false) {
		if ($assoc) {
			$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		} else {
			$json = new Services_JSON;
		}
		return $json->decode($content);
	}
}

# this is a json_decode() wrapper which works with $ENCODING instead of UTF-8
function jsdecode($str) {
	global $ENCODING;

	if ($ENCODING !== 'UTF-8') {
		$str = iconv($ENCODING,'UTF-8',$str);
	}

	$x = json_decode($str,true);

	if ($ENCODING !== 'UTF-8') {
		return jsdecode_un_utf_ize($x);
	} else {
		return $x;
	}
}
function jsdecode_un_utf_ize($x) {
	global $ENCODING;

	if (is_string($x)) {
		return iconv('UTF-8',$ENCODING,$x);
	} else if (is_array($x)) {
		$y = array();
		foreach ($x as $key => $value) {
			$y[jsdecode_un_utf_ize($key)] = jsdecode_un_utf_ize($value);
		}
		return $y;
	} else {
		return $x;
	}
}


################################################################################

function hidden_stack() {
	global $STACK;
	$stk = array();
	for ($i = 0 ; $i < count($STACK)-1 ; ++$i)
		$stk[] = $STACK[$i][0];
	return '<input type="hidden" name="_STACK_" value="'.html(js($stk)).'" />';
}

function begin_form($action=null) {
	global $ID;

	if ($action === null)
		$action = $_SERVER['REQUEST_URI'];

	print '<form id="'.html($ID).'" method="post"'
	.' action="'.html($action).'" onsubmit="return false">';

}
function end_form() {
	print '</form>';
}

function hidden($name, $value) {
	global $ID;
	if (is_array($value)) $value = $value[$name];
	return '<input type="hidden" id="'.html($ID.'_'.$name).'" name="'.html($name).'" value="'.html($value).'" />';
}


# HTML for a check box
function checkbox($name,$checked,$label,$disabled=false,$onchange='') {
	global $ID;
	if (is_array($checked)) $checked = $checked[$name];
	return hidden($name.'___OFF__','0')
	.'<input type="checkbox"'
		.' id="'.html($ID.'_'.$name).'"'
		.' name="'.html($name).'"'
		.' value="1"'
		.($checked?' checked="checked"':'')
		.($disabled?' disabled="disabled"':'')
		.($onchange?' onclick="'.html($onchange).'"':'')
		.' class="input tickbox checkbox"'
	.'/>'
	.(strlen($label) ?
		'<label for="'.html($ID.'_'.$name).'">'.$label.'</label>'
	:'');
}

# HTML for a radio button
function radio($name,$value,$checked,$label,$disabled=false,$onchange='') {
	global $ID;
	if (is_array($checked)) {
		$checked = (strval($checked[$name])===strval($value));
	}
	return '<input type="radio"'
		.' id="'.html($ID.'_'.$name.'_'.$value).'"'
		.' name="'.html($name).'"'
		.' value="'.html($value).'"'
		.($checked?' checked="checked"':'')
		.($disabled?' disabled="disabled"':'')
		.($onchange?' onclick="'.html($onchange).'"':'')
		.' class="input tickbox radio"'
	.'/>'
	.(strlen($label) ?
		'<label for="'.html($ID.'_'.$name.'_'.$value).'">&nbsp;'.$label.'</label>'
	:'');
}

# HTML for drop-down box
function dropdown($name, $current, $options, $attributes=null, $extra=null, $onchange=null) {
	global $ID;

	if (is_array($current)) $current = $current[$name];

	$str = '<select id="'.html($ID.'_'.$name).'"'
	         .' name="'.html($name).'"';
	if ($attributes) {
		if (is_bool($attributes) || is_integer($attributes)) {
			if ($attributes) {
				$str .= ' disabled="disabled"';
			}

		} else if (is_array($attributes)) {
			foreach ($attributes as $name => $value) {
				$str .= ' '.$name.'="'.html($value).'"';
			}

		} else if (is_string($attributes)) {
			$str .= ' '.$attributes;
		}
	}
	if ($onchange !== null)
		$str .= ' onchange="'.html($onchange).'"';
	$str .= ' class="input select"';
	$str .= '>';


	#
	# extra items
	#
	$found = false;
	if ($extra!==null) {
		if ($extra === true) {
			$str .= '<option value="">(select)</option>';
		} else {
			$opt = dropdown_opt($extra);
			if ($opt['value'] == $current) {
				$str .= '<option value="'.html($opt['value']).'" selected="selected">'.html($opt['text']).'</option>';
				$found = true;
			} else {
				$str .= '<option value="'.html($opt['value']).'">'.html($opt['text']).'</option>';
			}
		}
	}


	#
	# grouped/ungrouped
	#
	$grouped = array();
	$remaining = array();
	foreach ($options as $key => $stuff) {
		$opt = dropdown_opt(is_array($stuff) ? $stuff : array($key, $stuff));
		if (strlen($opt['grp'])) {
			$grouped[$opt['grp']][] = $opt;
		} else {
			$remaining[] = $opt;
		}
	}
	ksort($grouped);
	foreach ($remaining as $opt) {
		$str .= '<option value="'.html($opt['value']).'"';
		if ($current == $opt['value']) {
			$str .= ' selected="selected"';
			$found = true;
		}
		$str .= '>'.html($opt['text']).'</option>';
	}
	foreach ($grouped as $grp => $opts) {
		$str .= '<optgroup label="'.html($grp).'">';
		foreach ($opts as $opt) {
			$str .= '<option value="'.html($opt['value']).'"';
			if ($current == $opt['value']) {
				$str .= ' selected="selected"';
				$found = true;
			}
			$str .= '>'.html($opt['text']).'</option>';
		}
		$str .= '</optgroup>';
	}


	#
	# current value not in options?
	#
	if ($current !== null && !$found) {
		$opt = dropdown_opt($current);
		$str .= '<option value="'.html($opt['value']).'" selected="selected">'.html($opt['text']).'</option>';
	}


	$str .= '</select>';
	return $str;
}
# turns $stuff into a dropdown option
function dropdown_opt($stuff) {
	if (is_array($stuff)) {
		if (array_key_exists('value',$stuff)) {
			$value = $stuff['value'];
			$text = $stuff['text'];
			$grp = $stuff['grp'];
		} else if (array_key_exists(0,$stuff)) {
			$value = $stuff[0];
			$text = $stuff[1];
			$grp = $stuff[2];
		} else {
			$keys = array_keys($stuff);
			$value = $stuff[$keys[0]];
			$text = $stuff[$keys[0]];
			$grp = null;
		}
	} else {
		$value = strval($stuff);
		$text = strval($stuff);
		$grp = null;
	}
	return array('value'=>strval($value), 'text'=>strval($text), 'grp'=>strval($grp));
}

# HTML for a text box
function input($name,$text,$length=0,$disabled=false,$onchange='',$onfocus='',$onblur='',$onkeypress='') {
	global $ID;
	if (is_array($text)) $text = $text[$name];
	if (is_int($length)) {
		if ($length>0) $length = array($length,$length);
		if ($length<0) $length = array(-$length,null);
	}
	return '<input type="text"'
		.' id="'.html($ID.'_'.$name).'"'
		.' name="'.html($name).'"'
		.' value="'.html($text).'"'
		.($length[0] ? ' size="'.html($length[0]).'"' : '')
		.($length[1] ? ' maxlength="'.html($length[1]).'"' : '')
		.($disabled?' disabled="disabled"':'')
		.($onchange?' onchange="'.html($onchange).'"':'')
		.($onfocus?' onfocus="'.html($onfocus).'"':'')
		.($onblur?' onblur="'.html($onblur).'"':'')
		.($onkeypress?' onkeypress="'.html($onkeypress).'"':'')
		.' class="input textarea"'
	.'/>'
	.(strlen($label) ?
		'<label for="'.html($name).'">'.$label.'</label>'
	:'');
}

function number_input($name,$text,$length=null,$disabled=false,$onchange='',$onfocus='',$onblur='',$onkeypress='') {
	global $ID;
	if (is_array($text)) $text = $text[$name];

	if ($length === null) {
		$length = array(null,null);
	} else if (is_int($length)) {
		if ($length>0) $length = array($length,$length);
		if ($length<0) $length = array(-$length,null);
	}

	if ($length[0]===null)
		$length[0] = 6;

	return '<input type="text"'
		.' id="'.html($ID.'_'.$name).'"'
		.' name="'.html($name).'"'
		.' value="'.html($text).'"'
		.($length[0] ? ' size="'.html($length[0]).'"' : '')
		.($length[1] ? ' maxlength="'.html($length[1]).'"' : '')
		.($disabled?' disabled="disabled"':'')
		.($onchange?' onchange="'.html($onchange).'"':'')
		.($onfocus?' onfocus="'.html($onfocus).'"':'')
		.($onblur?' onblur="'.html($onblur).'"':'')
		.($onkeypress?' onkeypress="'.html($onkeypress).'"':'')
		.' class="input textarea number"'
	.'/>'
	.(strlen($label) ?
		'<label for="'.html($name).'">'.$label.'</label>'
	:'');
}

# HTML for a password box
function password_input($name,$text,$length=0,$disabled=false,$onchange=null,$onfocus=null,$onblur=null,$onkeypress='') {
	global $ID;
	if (is_array($text)) $text = $text[$name];
	if (is_int($length)) {
		if ($length>0) $length = array($length,$length);
		if ($length<0) $length = array(-$length,null);
	}
	return '<input type="password"'
		.' id="'.html($ID.'_'.$name).'"'
		.' name="'.html($name).'"'
		.' value="'.html($text).'"'
		.($length[0] ? ' size="'.html($length[0]).'"' : '')
		.($length[1] ? ' maxlength="'.html($length[1]).'"' : '')
		.($disabled ? ' disabled="disabled"' : '')
		.($onchange ? ' onchange="'.html($onchange).'"' : '')
		.($onfocus ? ' onfocus="'.html($onfocus).'"' : '')
		.($onblur ? ' onblur="'.html($onblur).'"' : '')
		.($onkeypress?' onkeypress="'.html($onkeypress).'"':'')
		.' class="input textarea password"'
	.'/>'
	.(strlen($label) ?
		'<label for="'.html($name).'">'.$label.'</label>'
	:'');
}
function date_input($name, $value, $disabled=false, $onchange=null) {
	global $ID;
	return input($name, $value, -10, $disabled, $onchange,
		'on_dateinput_focus('.js($ID.'_'.$name).')',
		'on_dateinput_blur('.js($ID.'_'.$name).')'
	);
}
function datetime_input($name, $value, $disabled=false, $onchange=null) {
	global $ID;
	return input($name, $value, -16, $disabled, $onchange,
		'on_datetimeinput_focus('.js($ID.'_'.$name).')',
		'on_datetimeinput_blur('.js($ID.'_'.$name).')'
	);
}
function time_input($name, $value, $mins=30) {
	$options = array();
	for ($h = 0 ; $h < 24 ; ++$h) {
		for ($m = 0 ; $m < 60 ; $m+=$mins) {
			$x = sprintf('%02d:%02d',$h,$m);
			$options[] = array(
				'value'=>$x,
				'text'=>$x,
			);
		}
	}
	return dropdown($name, $value, $options);
}

function textarea($name, $value, $cols,$rows, $disabled=false, $onchange=null,$onfocus=null,$onblur=null,$onkeypress='') {
	global $ID;
	if (is_array($value)) $value = $value[$name];
	return '<textarea name="'.html($name).'"'
		.' id="'.html($ID.'_'.$name).'"'
		.($cols>0?' cols="'.html($cols).'"':'')
		.($rows>0?' rows="'.html($rows).'"':'')
		.($disabled?' disabled="disabled"':'')
		.($onchange?' onchange="'.html($onchange).'"':'')
		.($onfocus ? ' onfocus="'.html($onfocus).'"' : '')
		.($onblur ? ' onblur="'.html($onblur).'"' : '')
		.($onkeypress?' onkeypress="'.html($onkeypress).'"':'')
		.' class="input textarea"'
	.'>'
		.html($value)
	.'</textarea>';
}

# returns HTML for a button of the `icon' class, containing nothing but an
# image ("/$img")
function icon($img, $alt, $onclick) {
	global $URL;
	return '<button type="button"'.($onclick?' onclick="'.html($onclick).'"':'').' class="button icon">'
		.'<img src="'.html($URL.'/'.$img).'" alt="'.html($alt).'" />'
	.'</button>';
}

function img($src, $alt='') {
	return '<img src="'.html($src).'" alt="'.html($alt).'" />';
}

function mistake_label($name,$id=null) {
	global $MISTAKES;
	if ($MISTAKES[$name]!==null) {
		if ($id!==null) {
			return '<span id="'.html($id).'" class="mistake">'.html($MISTAKES[$name]).'</span>';
		} else {
			return '<span class="mistake">'.html($MISTAKES[$name]).'</span>';
		}
	} else if ($id!==null) {
		return '<span id="'.html($id).'" class="mistake"></span>';
	} else {
		return '';
	}
}

function button($html, $onclick) {
	return '<button type="button"'.($onclick?' onclick="'.html($onclick).'"':'').' class="button">'
		.$html
	.'</button>';
}

function ok_button($label='OK', $following_url='', $id=null) {
	global $POPUP, $ID;
	if (strlen($POPUP)) {
		# form shown as pop-up
		$js = 'return close_popup('.js($POPUP).',true);';
	} else {
		if (!$following_url) $following_url = $_SERVER['HTTP_REFERER'];
		$js = 'return post_form('.js($ID).')';
	}
	return '<button type="submit" onclick="'.html($js).'"'
	             .' class="button ok">'.$label.'</button>';
}


################################################################################


# turns array into query string
# - does no conversion
function queryencode($vars,$nopluses=false) {
	$arr = array();
	foreach ($vars as $a => $b) {
		if ($b!==null) {
			$c = urlencode($a).'='.urlencode($b);
			if ($nopluses) $c = strtr($c,array('+'=>'%20'));
			$arr[] = $c;
		}
	}
	return implode('&',$arr);
}

# turns query string into array
# - does no conversion
function querydecode($query) {
	if ($query===null) return null;
	$arr = explode('&',$query);
	$vars = array();
	foreach ($arr as $x) {
		$y = explode('=',$x,2);
		if (count($y) == 2) {
			$vars[urldecode($y[0])] = urldecode($y[1]);
		} else if (count($y) == 1) {
			$vars[$y[0]] = null;
		}
	}
	return $vars;
}

function make_absolute_server_path($uri,$root='') {
	if ($uri == '') {
		$uri = $_SERVER['REQUEST_URI'];
	}
	if (substr($uri,0,1)=='/') {
		$url = $root.$uri;
	} else if (substr($uri,0,1)=='?') {
		$pq = explode('?',$_SERVER['REQUEST_URI'],2);
		$url = $pq[0].$uri;
	} else {
		$dn = dirname($_SERVER['REQUEST_URI'].'x');
		if ($dn=='/') $dn='';
		$url = $dn.'/'.$uri;
	}
	return $url;
}

# makes absolute HTTP URL from relative HTTP URI (based on current page)
# "//foo/bar" => "//foo/bar"
# "/foo/bar"  => "//THISHOST/foo/bar"
# "foo/bar"   => "//THISHOST/PATH/OF/CURRENT/REQUEST/foo/bar"
# etc.
function make_absolute_http_path($uri) {
	$uri = make_absolute_server_path($uri);
	if (substr($uri,0,2)!='//') {
		$host = $_SERVER['HTTP_HOST'];
		if ($host === NULL) $host = $_SERVER['SERVER_NAME'];  # needed?
		$uri = '//'.$host.$uri;
	}
	return $uri;
}

# makes absolute URL from HTTP URI (assumes missing scheme is `http:')
# "ftp:whatever"  => "ftp:whatever"
# "http:whatever" => "http://THISHOST/PATH/OF/CURRENT/REQUEST/whatever"
# "whatever"      => "http://THISHOST/PATH/OF/CURRENT/REQUEST/whatever"
# "//whatever"    => "http://whatever"
function make_absolute_url($uri) {
	if ($uri===null) $uri='/';
	if (preg_match('/^[abcdefghijklmnopqrstuvwxyz+\.-]+:/', $uri)) {
		if (substr($uri,0,5) == 'http:') {
			$uri = substr($uri,5);
			$url = 'http:'.make_absolute_http_path($uri);
		} else {
			$url = $uri;
		}
	} else {
		$url = 'http:'.make_absolute_http_path($uri);
	}
	return $url;
}
function dereference($url) {
	return make_absolute_url($url);
}

function complete_url($url, &$matches=null) {
	if (!preg_match('/^([a-zA-Z][a-zA-Z+.-]*:)?(\/\/)?([A-Za-z0-9-]+([.][A-Za-z0-9-]+)+)(\/.*)?/', $url, $matches))
		return null;
	if (!strlen($matches[1]))
		$matches[1] = 'http:';
	if (!strlen($matches[2]))
		$matches[2] = '//';
	if (!strlen($matches[5]))
		$matches[5] = '/';
	return $matches[1].$matches[2].$matches[3].$matches[5];
}

function hostname($url) {
	return ($url!==null ? parse_url($url, PHP_URL_HOST) : null);
}

function urljoin($absolute, $relative) {
	$p = parse_url($relative);
	if ($p["scheme"])
		return $relative;

	extract(parse_url($absolute));

	$path = dirname($path);

	if ($relative{0} == '/') {
		$cparts = array_filter(explode("/", $relative));
	} else {
		$aparts = array_filter(explode("/", $path));
		$rparts = array_filter(explode("/", $relative));
		$cparts = array_merge($aparts, $rparts);
		foreach ($cparts as $i => $part) {
			if ($part == '.') {
				$cparts[$i] = null;
			}
			if ($part == '..') {
				$cparts[$i - 1] = null;
				$cparts[$i] = null;
			}
		}
		$cparts = array_filter($cparts);
	}
	$path = implode("/", $cparts);
	$url = "";
	if ($scheme) {
		$url = "$scheme://";
	}
	if ($user) {
		$url .= "$user";
		if ($pass) {
			$url .= ":$pass";
		}
		$url .= "@";
	}
	if ($host) {
		$url .= "$host/";
	}
	$url .= $path;
	return $url;
}


################################################################################


# push/pop global state variables onto/from stack to allow for a tree structure
function push($get=null, $post=null) {
	global $STACK,$ID,$ID2,$HEAD,$ICON;

	if ($STACK===null) {
		$STACK = array(array(0));
	}

	$STACKITEM = array(0,$ID,$HEAD,$ICON,'get'=>$_GET,'post'=>$_POST);

	if (count($STACK) > 0) {
		if ($ID2!==null) {
			$ID = $ID2;
			$ID2 = null;
			unset($ID2);
		} else {
			$ID .= '_'.$STACK[count($STACK)-1][0];
		}
	}
	$HEAD = null;
	$ICON = null;
	if (count($STACK) > 0) {
		$_GET = $_GET['__'.$STACK[count($STACK)-1][0]];
		if (is_string($_GET)) $_GET = jsdecode($_GET);
		if ($_GET===null) $_GET = array();
		if ($get!==null) {
			$_GET = array_merge($_GET,$get);
		}

		$_POST = $_POST['__'.$STACK[count($STACK)-1][0]];
		if (is_string($_POST)) $_POST = jsdecode($_POST);
		if ($_POST===null) $_POST = array();
		if ($post!==null) {
			$_POST = array_merge($_POST,$post);
		}
	}

	array_push($STACK, $STACKITEM);
}
function pop() {
	global $ID,$HEAD,$ICON,$STACK;

	$oldprefix = $ID;

	$STACKITEM = array_pop($STACK);
	$ID = $STACKITEM[1];
	$HEAD = $STACKITEM[2];
	$ICON = $STACKITEM[3];
	$_GET = $STACKITEM['get'];
	$_POST = $STACKITEM['post'];

	if (count($STACK) > 0) {
		$STACK[count($STACK)-1][0] = $STACK[count($STACK)-1][0]+1;
	}

	return $oldprefix;
}

function wrap_request($POST, $POST_STACK, $remove_this) {
	if ($remove_this!==null) unset($POST[$remove_this]);

	$STACK = jsdecode($POST_STACK);
	for ($i=count($STACK)-1 ; $i>=0 ; --$i) {
		if (count($POST)) {
			if ($i==1) $POST = jsencode($POST);
			$POST = array('__'.$STACK[$i] => $POST);
		} else {
			$POST = array();
		}
	}

	return $POST;
}

function mutate($vars,$params=null) {
	global $STACK;

	if ($params===null) $params = array_merge($_GET,$_POST);
	$vars = array_merge($params,$vars);
	foreach ($vars as $a => $b) if ($b===null) unset($vars[$a]);

	# wrap request
	for ($i=count($STACK)-1 ; $i>0 ; --$i) {
		if (count($vars)) {
			if ($i==1) $vars = jsencode($vars);
			$vars = array_merge($STACK[$i]['get'], array(
				'__'.$STACK[$i-1][0] => $vars,
			));
		} else {
			$vars = $STACK[$i]['get'];
			unset($vars['__'.$STACK[$i-1][0]]);
		}
	}

	return queryencode($vars);
}

function mutate_attrs($uri,$vars,$params=null) {
	global $ID;

	if ($params===null) $params = array_merge($_GET,$_POST);
	$q = array_merge($params,$vars);
	foreach ($q as $a => $b) if ($b===null) unset($q[$a]);
	$q['_ID_'] = $ID;

	return ' href="'.html('?'.mutate($vars,$params)).'"'
		.' onclick="'.html(
			'busy_on();'
			.'request('.js($uri.'?'.queryencode($q)).',function(req){'
				.'busy_off();'
				.'fill(elem('.js($ID).'),req.responseText);'
			.'});return false;'
		).'"'
	;
}

function mutate_deep($new,$stack,$olduri=null) {
	if (is_string($stack)) $stack = jsdecode($stack);
	if ($olduri===null) $olduri = $_SERVER['REQUEST_URI'];

	$pq = explode('?',$olduri,2);
	$old = querydecode($pq[1]);
	$result = mutate_deep_rec($new,$stack,0,$old);
	if ($result!==null) {
		foreach (array_keys($result) as $k) {
			if ($result[$k]!==null && !is_string($result[$k])) {
				$result[$k] = js($result[$k]);
			}
		}
		$newuri = $pq[0].'?'.queryencode($result);
	} else {
		$newuri = $pq[0];
	}
	return $newuri;
}

function mutate_deep_rec($new,$stack,$i,$old) {
	if ($old===null) {
		$old = array();
	} else if (is_string($old)) {
		$old = jsdecode($old);
	}
	if (count($stack)<=$i) {
		$result = array_merge($old,$new);
	} else {
		$result = array_merge($old,array(
			'__'.$stack[$i] => mutate_deep_rec($new,$stack,$i+1,
				$old['__'.$stack[$i]]
			)
		));
	}
	if (!count($result)) {
		$result = null;
	}
	return $result;
}


################################################################################


class LoggedException extends Exception {
	function __construct($msg) {
		Exception::__construct($msg);
		$x = 0;
		$bt = debug_backtrace();
		for ($i = 0 ; $i < count($bt) ; ++$i) {
			$stack_frame = $bt[$i];
			$fun = ($bt[$i+1]['function'] ? $bt[$i+1]['function'].':' : '');
			if ($x == 0) {
				error_log($stack_frame['file'].':'.$stack_frame['line'].':'.$fun.' '.$msg);
			} else if ($x > 0) {
				error_log($stack_frame['file'].':'.$stack_frame['line'].':'.$fun.' called from here');
			}
			++$x;
		}
	}
};


?>
