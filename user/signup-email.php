<?
	require_once 'app/init.php';

	if (value('SELECT COUNT(*) FROM users WHERE email='.sql(v('email')))) {
		echo 'Email address has been registered already';
	}
?>
