<?
	require_once 'app/init.php';

	unset($_SESSION['user_id']);
	unset($_SESSION['user']);
	unset($_SESSION['remember_me']);
	unset($_SESSION['timezone']);

	$HEADING = 'Session expired';
?>
<? include 'app/begin.php' ?>
<p>The session has expired. To log in again, <a
href="<?=html($URL.'/user/login')?>">click here</a>.</p>
<? include 'app/end.php' ?>
