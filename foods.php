<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'app/data.php';

	$HEADING = 'Foods';
	$BREAD = array($URL=>'home');
?>
<? include 'app/begin.php' ?>
<? include 'foods-table.php' ?>
<p><a href="product?type=1">New food</a></p>
<? return include 'app/end.php' ?>
