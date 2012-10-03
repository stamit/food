<? $AUTH=true;
	require_once 'app/init.php';

	if ($cart===null) $cart = fetch('cart.id',v('id'));
	authif($_POST['_OP_']=='create' || $cart['user_id']==$_SESSION['user_id']);

	if (posting()) {
		if ($_POST['_OP_']=='create') {
			$num = value(
				'SELECT COUNT(*) FROM cart'
				.' WHERE user_id='.sql($_SESSION['user_id'])
			);
			$cart = array(
				'user_id'=>$_SESSION['user_id'],
				'name'=>'basket '.($num+1),
			);
			$cart['id'] = insert('cart',$cart);
		} else if ($_POST['_OP_']=='rename') {
			execute('UPDATE cart SET name='.sql($_POST['name'])
			        .' WHERE id='.sql($cart['id']));
		} else if ($_POST['_OP_']=='delete') {
			execute('DELETE FROM cart_item WHERE cart='.sql($cart['id']));
			execute('DELETE FROM cart WHERE id='.sql($cart['id']));
			$carts = query('SELECT * FROM cart WHERE user_id='.sql($_SESSION['user_id']),1);
			$cart = ($carts ? $carts[0] : array('id'=>0));
		}
	}

	echo '<form method="post" action="'.html($URL.'/cart/cart-switcher').'" autocomplete="off" onsubmit="return false">';
	echo '<h2>Shopping basket ';
	if ($_GET['_OP_']=='rename') {
		echo hidden('id',$cart);
		echo '"'.input('name',$cart).'"';
		echo '<span class="noprint">';
		echo ' <button type="button" class="okbutton" onclick="'.html('return cart_rename_ok('.js($ID).')').'"></button>';
		echo ' <button type="button" class="cancelbutton" onclick="'.html('return cart_rename_cancel('.js($ID).')').'"></button>';
		echo '</span>';
	} else {
		echo '"'.dropdown('id',$cart,query(
			'SELECT id AS value, name AS text FROM cart'
			.' WHERE user_id='.sql($_SESSION['user_id'])
			.' ORDER BY name'
		),null,array(0,'(create new)'),'cart_change('.js($ID).')').'"';
		echo '<span class="noprint">';
		echo ' <button type="button" class="editbutton" onclick="'.html('return cart_rename('.js($ID).')').'"></button>';
		echo ' <button type="button" class="deletebutton" onclick="'.html('return cart_delete('.js($ID).')').'"></button>';
		echo '</span>';
	}
	echo '</form>';
	echo '</h2>';
?>
