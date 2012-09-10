<?
	require_once 'app/init.php';

	if (value('SELECT COUNT(*) FROM users WHERE username='.sql(v('username')))) {
		echo 'This username has already been registered';
	}
?>
