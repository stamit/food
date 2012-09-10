<?
require_once 'app/init.php';

$id = given_field('id','cart_item');
$ci = get($id,'cart_item');
$cart = get($ci['cart'],'cart');
authif($cart['user_id']==$_SESSION['user_id']);

if (posting()) try {
	execute('DELETE FROM cart_item WHERE id='.sql($id));
	if (success()) return true;
} catch (Exception $x) {
	if (failure($x)) return false;
}

?>
