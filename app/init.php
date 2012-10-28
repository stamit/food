<?php list($usec,$sec) = explode(' ', microtime()); $STOPWATCH = ((float)$usec + (float)$sec);
require_once 'lib/util.php';
require_once 'lib/database.php';
require_once 'lib/mysession.php';
require_once 'lib/regional.php';
require_once 'app/settings.php';
require_once 'app/data.php';
require_once 'app/emailtpl.php';

if ($URL===null) $URL='';
$DIR=dirname(dirname(__FILE__));


function my_error_handler($errno, $err_str, $err_file, $err_line) {
	global $PRODUCTION_SERVER;

	if (!(error_reporting()&$errno))
		return;

	if (!$PRODUCTION_SERVER)
		echo "<pre>\n".html($errno.$err_file.':'.$err_line.': '.$err_str)."\n</pre>";

	error_log($err_file.':'.$err_line.': '.$err_str);
	switch ($errno) {
	case E_WARNING: case E_USER_WARNING:
	case E_NOTICE: case E_USER_NOTICE:
	case 2048: // E_STRICT in PHP5
		return;
	default:
		if (headers_sent()) {
			print '<script type="text/javascript">window.location.href='.sql($URL.'/trouble').';</script>';
			include 'app/after.php';
		} else {
			header(' ', true, 500);
			include 'trouble.php';
		}
		exit;
	}
}
set_error_handler('my_error_handler');

function my_exception_handler($exception) {
	global $URL, $TROUBLE, $STOPWATCH;

	error_log('EXCEPTION: '.$exception->getMessage());
	$lines = explode("\n",$exception->getTraceAsString());
	for ($i = 0 ; $i < count($lines) ; ++$i)
		error_log('TRACE: '.$lines[$i]);

	if (headers_sent()) {
		print '<script type="text/javascript">window.location.href='.sql($URL.'/trouble').';</script>';
		include 'app/after.php';
	} else {
		header(' ', true, 500);
		$TROUBLE=1;
		include 'trouble.php';
	}
	exit;
}
set_exception_handler('my_exception_handler');

error_reporting(E_ERROR | E_PARSE);

if ($CONTENT_TYPE === null) $CONTENT_TYPE = 'text/html';
if ($DEFAULT_ENCODING === null) $DEFAULT_ENCODING = 'UTF-8';
if ($ENCODING === null) $ENCODING = $CHARSET;
if ($ENCODING === null) $ENCODING = $DEFAULT_ENCODING;

ob_start();
setlocale(LC_ALL,'el_GR.'.$ENCODING);
mb_internal_encoding($ENCODING);
mb_detect_order(array('UTF-8','ISO-8859-7'));


#
# which host or virtual host are we running on?
#
if ($_SERVER['HTTP_HOST']===null) {
	$_SERVER['HTTP_HOST'] = $DEFAULT_HTTP_HOST;
}


#
# fix the dots-to-underscores problem
#
$_GET = ifnull(querydecode($_SERVER['QUERY_STRING']),array());
$POSTDATA = file_get_contents('php://input');
if ($POSTDATA!=='') $_POST = querydecode($POSTDATA);


#
# fix the "magic quotes" problem
#
if (get_magic_quotes_gpc()) {
	function stripslashes_deep($value) {
		return is_array($value)
			? array_map('stripslashes_deep', $value)
			: stripslashes($value);
	}

	$_POST = array_map('stripslashes_deep', $_POST);
	$_GET = array_map('stripslashes_deep', $_GET);
	$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
	$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}


#
# request for whole page or part?
#
if (function_exists('getallheaders')) {
	$HEADERS = getallheaders();
} else {
	$HEADERS = array();
	foreach ($_SERVER as $k=>$v) {
		if (substr($k, 0, 5) == "HTTP_") {
			$k = str_replace('_', ' ', substr($k, 5));
			$k = str_replace(' ', '-', ucwords(strtolower($k)));
			$HEADERS[$k] = $v;
		}
	}
}
$INLINE_REQUEST=0;
if (strpos($HEADERS['X-Requested-With'],'XMLHttpRequest')!==FALSE) {
	$INLINE_REQUEST=1;
	# all XMLHttpRequest requests are in UTF-8
	$GET_PARAMS_ENCODING = 'UTF-8';
	$POST_PARAMS_ENCODING = 'UTF-8';
}


#
# put parameters in the correct encoding
#
if ($GET_PARAMS_ENCODING!==null || $POST_PARAMS_ENCODING!==null) {
	function _convert_param_get($string) {
		global $ENCODING, $GET_PARAMS_ENCODING;
		return iconv($GET_PARAMS_ENCODING,"$ENCODING//TRANSLIT",$string);
	}
	if ($GET_PARAMS_ENCODING!=$ENCODING)
		$_GET = array_map('_convert_param_get', $_GET);

	function _convert_param_post($string) {
		global $ENCODING, $POST_PARAMS_ENCODING;
		return iconv($POST_PARAMS_ENCODING,"$ENCODING//TRANSLIT",$string);
	}
	if ($POST_PARAMS_ENCODING!=$ENCODING)
		$_POST = array_map('_convert_param_post', $_POST);
}


#
# set ID prefix for form controls
#
if (ifnull($HEADERS['X-ID'],$HEADERS['X-Id'])!==null) {
	$ID = $HEADERS['X-ID'];
} else {
	$ID = v('_ID_');
	unset($_GET['_ID_']);
	unset($_POST['_ID_']);
	if ($ID !== null) {
		$DEPTH = 1;
		$INLINE_REQUEST=1;
	} else {
		$POPUP = v('_POPUP_');
		unset($_GET['_POPUP_']);
		unset($_POST['_POPUP_']);
		if ($POPUP !== null) {
			$ID = $POPUP.'_ROOT';
			$DEPTH = 1;
			$INLINE_REQUEST=1;
		} else {
			$ID = 'ROOT';
			$DEPTH = 0;
		}
	}
}


#
# for not including a script twice
#
$JS = array();
function include_script($scriptname) {
	global $JS;
	$path = make_absolute_server_path($scriptname);
	if (!$JS[$path]) {
		print '<script src="'.html($path).'" type="text/javascript"></script>'."\n";
		$JS[$path] = true;
	}
}


#
# possibly wrap request so the POST goes to the right script
#
if (!$INLINE_REQUEST && $_POST['_STACK_'] !== null) {
	$_POST = wrap_request($_POST,$_POST['_STACK_'],'_STACK_');
}


#
# connect to databases
#
$NDB = connect($NDB);
$DB = connect($DB);

# load configuration
try {
	foreach (select('* FROM options') as $o) {
		$GLOBALS[$o['name']] = $o['value'];
		define($o['name'],$o['value']);
	}
} catch (Exception $x) {
	# no 'options' table probably
}


#
# completely replace PHP's session mechanism
#
mysession_load();
register_shutdown_function('mysession_save');
register_shutdown_function('commit');


#
# MySQL-ism for automatic timezone conversions
#
function _mysql_tzoffset($offset) {
	return
		($offset<0 ? '-' : '+')
		.intval(abs($_SESSION['timeoffset'])/60/60)
		.':'
		.str_pad(intval($_SESSION['timeoffset']/60)%60,
		         2, '0', STR_PAD_LEFT);
}
if ($_SESSION['timezone']!==null) {
	$tz = timezone_open($_SESSION['timezone']);
	$PREF_TIMEZONE = $tz->getOffset(new DateTime());
	try {
		execute('SET time_zone='.sql($_SESSION['timezone']));
	} catch (Exception $x) {
		error_log('The time zone tables are not populated!'
		          .' Use "mysql_tzinfo_to_sql"!');
		execute('SET time_zone='.sql(_mysql_tzoffset($PREF_TIMEZONE)));
	}

} else if ($_SESSION['timeoffset']!==null) {
	execute('SET time_zone='.sql(_mysql_tzoffset($_SESSION['timeoffset'])));
	$PREF_TIMEZONE = $_SESSION['timeoffset'];

} else {
	#execute('SET time_zone="+0:00"');
	execute('SET time_zone=SYSTEM');
	$PREF_TIMEZONE = 0;
}


#
# request logging
#
function log_url($url,&$host_id,&$url_id) {
	$pieces = parse_url($url);
	if ($pieces['path']) {
		$uri = $pieces['path'].($pieces['query']?
			'?'.$pieces['query']
		:'');
	} else {
		$uri = null;
	}

	if ($pieces['host']) {
		$host_id = value0('id FROM log_hosts'
		                  .' WHERE host='.sql($pieces['host']));
		if ($host_id===null) {
			$host_id = insert('log_hosts',array(
				'host'=>$pieces['host'],
			));
			$url_id = null;
		} else if ($uri!==null) {
			$url_id = value0('id FROM log_urls'
			                 .' WHERE host_id='.sql($host_id)
			                 .' AND uri='.sql($uri));
		} else {
			$url_id = null;
		}

		if ($url_id===null && $uri!==null) {
			$url_id = insert('log_urls',array(
				'host_id'=>$host_id,
				'uri'=>$uri,
			));
		}
	} else {
		$host_id = null;
		$url_id = null;
	}
}
function log_request() {
	global $SESSION_ID, $REQUEST_ID;

	# find user-agent ID
	if ($_SERVER['HTTP_USER_AGENT']===null || !$_SERVER['HTTP_USER_AGENT']){
		$user_agent_id = null;
	} else {
		$user_agent_id = value0('id FROM log_user_agents WHERE name='
		                        .sql($_SERVER['HTTP_USER_AGENT']));
		if ($user_agent_id===null) {
			$user_agent_id = insert('log_user_agents',array(
				'name'=>$_SERVER['HTTP_USER_AGENT'],
			));
		}
	}

	if ($_SERVER['HTTP_REFERER']===null || !$_SERVER['HTTP_REFERER']) {
		$referer_host_id = null;
		$referer_url_id = null;
	} else {
		log_url($_SERVER['HTTP_REFERER'],
		        $referer_host_id,
		        $referer_url_id);
	}

	$port = $_SERVER['SERVER_PORT'];
	if ($_SERVER['HTTPS']) {
		$defport = 443;
	} else {
		$defport = 80;
	}
	if ($port==$defport) {
		$port = null;
	}

	if ($_SERVER['HTTP_HOST']!==null && $_SERVER['REQUEST_URI']!==null) {
		log_url(($_SERVER['HTTPS']?'https:':'http:')
		        .'//'.$_SERVER['HTTP_HOST'].($port!==null?':'.$port:'')
		        .$_SERVER['REQUEST_URI'],
		        $host_id, $url_id);

		$REQUEST_ID = insert('log_requests',array(
			'client_ip'=>$_SERVER['REMOTE_ADDR'],
			'method'=>$_SERVER['REQUEST_METHOD'],
			'host_id'=>$host_id,
			'url_id'=>$url_id,
			'referer_host_id'=>$referer_host_id,
			'referer_url_id'=>$referer_url_id,
			'user_agent_id'=>$user_agent_id,
			'session_id'=>$SESSION_ID,
			'user_id'=>$_SESSION['user_id'],
		));

		commit();
	}
}

if (!$NOLOG) {
	log_request();
}


#
# access control
#
function authif($cond) {
	global $URL, $INLINE_REQUEST;
	if ($cond) return;

	if ($_SESSION['user_id']===null) {
		$msg = 'You are not logged in.';
	} else {
		$msg = 'You are not allowed to do that.';
	}

	header(' ', true, 403);
	if ($INLINE_REQUEST) {
		echo html($msg);
	} else {
		$_SESSION['alert'] = $msg;
		redirect($URL.'/user/login?uri='.urlencode($_SERVER['REQUEST_URI']));
	}
	exit;
}

if (is_string($AUTH)) {
	authif($_SESSION['user_id']!==null && has_right($AUTH));
} else if ($AUTH) {
	authif($_SESSION['user_id']!==null);
}

?>
