<? $AUTH=true;

require_once 'app/init.php';

$row = given('cart_item.id', array(
	'quantity'=>'float',
	#'unit'=>'int',
));

$ci = fetch('cart_item.id',$row['id']);
$cart = fetch('cart.id',$ci['cart']);
authif($cart['user_id']==$_SESSION['user_id']);

if (posting()) try {
	if (!array_key_exists('id',$row) || $row['id']===null)
		mistake('cart_item.id','Missing ID.');
	if (array_key_exists('quantity',$row)
	    && ($row['quantity']===null || $row['quantity']<0))
		mistake('cart_item.quantity','No negative quantities.');
	#if (array_key_exists('unit',$row)
	#    && ($row['unit']<0 || $row['unit']>2))
	#	mistake('cart_item.unit','Unit must be 0, 1 or 2.');

	if (correct()) {
		$prod = fetch('product.id',$ci['product']);

		$row['unit'] = $ci['unit'];
		switch ($row['unit']) {
		case 0:
			$row['price'] = $prod['typical_price']*$row['quantity'];
			if ($prod['net_weight']!==null && $prod['sample_weight']!==null) {
				$row['multiplier'] = $prod['net_weight']/$prod['sample_weight'];
			} else if ($prod['net_volume']!==null && $prod['sample_volume']!==null) {
				$row['multiplier'] = $prod['net_volume']/$prod['sample_volume'];
			} else if ($prod['sample_weight']!==null) {
				$row['multiplier'] = $prod['sample_weight'];
			} else if ($prod['sample_volume']!==null) {
				$row['multiplier'] = $prod['sample_volume'];
			} else {
				$row['multiplier'] = null;
			}
			break;
		case 1:
			if ($prod['net_weight']>0) {
				$row['price'] = $prod['typical_price']
				                * $row['quantity']
				                / $prod['net_weight'];
			} else {
				$row['price'] = null;
			}
			if ($prod['sample_weight']!==null) {
				$row['multiplier'] = $row['quantity']/$prod['sample_weight'];
			} else {
				$row['multiplier'] = null;
			}
			break;
		case 2:
			if ($prod['net_volume']>0) {
				$row['price'] = $prod['typical_price']
				                * $row['quantity']
				                / $prod['net_volume'];
			} else {
				$row['price'] = null;
			}
			if ($prod['sample_volume']>0) {
				$row['multiplier'] = $row['quantity']/$prod['sample_volume'];
			} else {
				$row['multiplier'] = null;
			}
			break;
		}

		update('cart_item.id',$row);
		if (success()) return true;
	}
} catch (Exception $x) {
	if (failure($x)) return false;
} 

?>
