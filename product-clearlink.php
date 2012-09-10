<? $AUTH='register-products';
	require_once 'app/init.php';

	if (posting()) try {
		product_clearlink(
			intval($_POST['id']),
			intval($_POST['source'])
		);
		if (success()) return true;
	} catch (Exception $x) {
		if (failure($x)) return false;
	}
?>
