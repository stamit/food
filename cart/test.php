<? $AUTH=true;
	require_once 'app/init.php';

	if (posting()) try {
		error_log('CART-TEST '.repr($_POST));

	} catch (Exception $x) {
		error_log($x->getMessage());
	} 
?>
