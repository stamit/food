<? $AUTH='admin';
	require_once 'app/init.php';

	$HEADING = 'Rights';
	$BREAD = array($URL=>'home');
?>
<? include 'app/begin.php' ?>
<? include 'rights-table.php' ?>
<? return include 'app/end.php' ?>
