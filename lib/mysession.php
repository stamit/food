<?
include_once 'lib/database.php';

function mysession_random_string($n=26) {
	$chars = '0123456789abcdefghijklmnopqrstuvwxyz';
	$s = '';
	for ($i = 0 ; $i < $n ; ++$i) {
		$s .= $chars[mt_rand(0,strlen($chars)-1)];
	}
	return $s;
}

function mysession_load() {
	global $_COOKIE, $SESSION_ID, $_SESSION, $SESSION_TIMEOUT, $SESSION_REMEMBER_DAYS;
	if ($_COOKIE['SESSION'] !== null) {
		$SESSION_ROW = row0('SELECT * FROM log_sessions WHERE phpid='.sql($_COOKIE['SESSION']));
	}
	if ($SESSION_ROW === null) {
		$_SESSION = array();
		$SESSION_ROW = array(
			'phpid'=>mysession_random_string(),
			'data'=>js($_SESSION),
			'user_id'=>null,
			'expiry'=>value('ADDTIME(NOW(),SEC_TO_TIME(60))'),  # will be modified
		);
		$SESSION_ID = insert('log_sessions',$SESSION_ROW);
	} else {
		$SESSION_ID = $SESSION_ROW['id'];
		$_SESSION = jsdecode($SESSION_ROW['data']);
	}
	
	if ($_SESSION['remember_me']) {
		$SESSION_TIMEOUT = $SESSION_REMEMBER_DAYS * 24*60*60;
		setcookie('SESSION', $SESSION_ROW['phpid'], time()+$SESSION_TIMEOUT, $URL.'/');
	} else {
		$SESSION_TIMEOUT = 1800;
		setcookie('SESSION', $SESSION_ROW['phpid'], time()+$SESSION_TIMEOUT, $URL.'/');
	}
}

function mysession_save() {
	global $SESSION_ID, $_SESSION, $SESSION_TIMEOUT;

	$SESSION_ROW = array(
		'id'=>$SESSION_ID,
		'data'=>js($_SESSION),
		'expiry'=>value('ADDTIME(NOW(),SEC_TO_TIME('.$SESSION_TIMEOUT.'))'),
	);
	if ($_SESSION['user_id']!==null) {
		$SESSION_ROW['user_id'] = $_SESSION['user_id'];
	}
	update('log_sessions.id',$SESSION_ROW);
}

?>
