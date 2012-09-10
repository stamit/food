<? $AUTH='admin';
#
# calculates "suggested" prices for products
# also fills in the average net weight/volume and typical units-per-pack
# all is done according to the currently registered receipts (logs of actual purchases)
#
ini_set('include_path',dirname(dirname(__FILE__)));
require_once 'app/init.php';
header('Content-type: text/plain; charset='.$ENCODING);

foreach (query('SELECT * FROM product') as $prod) {
	echo $prod['id']."\n";

	product_calc_typicals($prod);

	if ($prod['sample_volume']!==null && $prod['sample_weight']===null) {
		echo 'http://efood.stamit.gr/product?id='.$prod['id'].' : '
		     .html($prod['name']).' has sample_volume'
		     .' but no sample_weight'."\n";
	}

	flush();
	ob_flush();
}

?>
