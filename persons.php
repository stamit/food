<? $AUTH=true;
	require_once 'app/init.php';

	$HEADING = 'Persons';
	$BREAD = array($URL=>'home');
?>
<? include 'app/begin.php' ?>
<? include 'persons-table.php' ?>
<? if (has_right('register-persons')) { ?>
<p class="noprint"><a href="person">New person</a></p>
<? } ?>
<? return include 'app/end.php' ?>
