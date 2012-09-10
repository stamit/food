<? $AUTH=true;
	require_once 'app/init.php';

	$user = get($_SESSION['user_id'],'users');
	$need_demos = ($user['birth']===null) ||
	              ($user['gender']===null) ||
	              ($user['pregnancy']===null);
?>
<? include 'app/begin.php' ?>
<? if ($_SESSION['user_id']) { ?>
<p>Welcome, <?=html($_SESSION['user']['username'])?>.</p>
<? } ?>
<? if ($need_demos) { ?>
<p class="notice"><a href="user/profile">Click here</a> to fill in your information.</p>
<? } ?>
<? include 'app/end.php' ?>
