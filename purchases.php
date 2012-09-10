<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'lib/regional.php';

	$HEADING = 'Item purchases';
	$BREAD = array($URL=>'home');
	$prodid = (v('prodid')!==null ? intval(v('prodid')) : null);
?>
<? include 'app/begin.php' ?>
<? include 'purchases-table.php' ?>
<? return include 'app/end.php' ?>
