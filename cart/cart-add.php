<? $AUTH=true;
	require_once 'app/init.php';

	$cart = fetch('cart.id',v('cart.id'));
	authif($cart['user_id']==$_SESSION['user_id']);

	if (posting()) try {
		$prod = fetch('product.id',v('product.id'));
		$row = array(
			'cart'=>v('cart.id'),
			'product'=>$prod['id'],
		);

		if ($prod['net_weight']!==null && $prod['sample_weight']!==null) {
			$row['quantity'] = $prod['net_weight'];
			$row['unit'] = 1;
			$row['price'] = $prod['typical_price'];
			$row['multiplier'] = $row['quantity']/$prod['sample_weight'];
		} else if ($prod['net_volume']!==null && $prod['sample_volume']!==null) {
			$row['quantity'] = $prod['net_volume'];
			$row['unit'] = 2;
			$row['price'] = $prod['typical_price'];
			$row['multiplier'] = $row['quantity']/$prod['sample_volume'];
		} else if ($prod['sample_weight']!==null) {
			$row['quantity'] = $prod['sample_weight'];
			$row['unit'] = 1;
			$row['price'] = null;
			$row['multiplier'] = 1.0;
		} else if ($prod['sample_volume']!==null) {
			$row['quantity'] = $prod['sample_volume'];
			$row['unit'] = 2;
			$row['price'] = null;
			$row['multiplier'] = 1.0;
		} else if ($prod['net_weight']!==null) {
			$row['quantity'] = $prod['net_weight'];
			$row['unit'] = 1;
			$row['price'] = $prod['typical_price'];
			$row['multiplier'] = null;
		} else if ($prod['net_volume']!==null) {
			$row['quantity'] = $prod['net_volume'];
			$row['unit'] = 2;
			$row['price'] = $prod['typical_price'];
			$row['multiplier'] = null;
		} else {
			$row['quantity'] = ifnull($prod['typical_units'],1);
			$row['unit'] = 0;
			$row['price'] = $prod['typical_price'];
			$row['multiplier'] = null;
		}

		insert('cart_item',$row);
	} catch (Exception $x) {
		error_log($x->getMessage());
	} 
?>
