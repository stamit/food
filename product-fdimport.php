<? $AUTH='register-products';
	require_once 'app/init.php';

	if (posting()) try {
		$id = intval($_POST['id']);
		product_fooddb_import($id,intval($_POST['fooddb_source']));
		if (success($URL.'/product?id='.$id)) return true;
	} catch (Exception $x) {
		if (failure($x)) return false;
	}
?>
