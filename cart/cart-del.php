<?
require_once 'app/init.php';

$id = given_field('id','cart_item');
$ci = fetch('cart_item.id',$id);
$cart = fetch('cart.id',$ci['cart']);
authif($cart['user_id']==$_SESSION['user_id']);

if (posting()) try {
	execute('DELETE FROM cart_item WHERE id='.sql($id));
	if (success()) return true;
} catch (Exception $x) {
	if (failure($x)) return false;
}

?>
