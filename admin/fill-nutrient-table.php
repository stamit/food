<? $AUTH='admin';
require_once 'app/init.php';

foreach (col('id FROM product') as $product_id) {
	$product = fetch('product.id',$product_id);
	foreach (select('* FROM nutrient') as $nut) {
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
