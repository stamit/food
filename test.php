<? $AUTH='admin';
	require_once 'app/init.php';

	if (posting()) try {
		error_log('TEST '.repr($_POST));
		if (success()) return true;
	} catch (Exception $x) {
		if (failure($x)) return false;
	}
?>
<? include 'app/begin.php' ?>
<form method="post" action="#">
<input type="text" name="foo" value="bar" />
<button type="submit">Submit</button>
</form>
<? include 'app/end.php' ?>
