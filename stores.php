<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'app/data.php';

	$HEADING = 'Stores';
?>
<? include 'app/begin.php' ?>
<? include 'stores-table.php' ?>
<? if (has_right('register-stores')) { ?>
<p class="noprint"><a href="store">New store</a></p>
<? } ?>
<? return include 'app/end.php' ?>
