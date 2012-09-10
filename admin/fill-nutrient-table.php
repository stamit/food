<? $AUTH='admin';
require_once 'app/init.php';

foreach (col('SELECT id FROM product') as $product_id) {
	$product = get($product_id,'product');
	foreach (query('SELECT * FROM nutrient') as $nut) {
		$value = $product[$nut['name']];
		if ($value !== null) {
			insert('product_nutrient',array(
				'product'=>$product_id,
				'nutrient'=>$nut['id'],
				'value'=>$value,
			));
		}
	}
}

?>
