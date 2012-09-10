<? $AUTH='admin';
	include 'app/init.php';
	$_SESSION['foo']='bar';
?>
<? include 'app/begin.php' ?>
<? print repr($_SESSION) ?>
<? include 'app/end.php' ?>
