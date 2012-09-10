<? $AUTH='register-products';
require_once 'app/init.php';

if (posting()) try {
	$id = intval($_POST['id']);
	$prod = row('SELECT * FROM product WHERE id='.sql($id));
	if ($prod['parent']===null)
		mistake('parent','Product has no parent.');
	if (correct()) {
		product_parent_link($id,$prod['parent']);
		if (success()) return true;
	}
} catch (Exception $x) {
	if (failure($x)) return false;
}
?>
