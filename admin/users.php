<? $AUTH='admin';
	require_once 'app/init.php';

	$HEADING = 'Users';
	$BREAD = array($URL=>'home');
?>
<? include 'app/begin.php' ?>
<? include 'users-table.php' ?>
<? return include 'app/end.php' ?>
