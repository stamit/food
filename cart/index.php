<? $AUTH=true; $TITLE='Shopping basket';
	require_once 'app/init.php';

	$cart_id = intval(v('id'));
	if (!$cart_id) {
		$carts = select('id FROM cart'
		                .' WHERE user_id='.sql($_SESSION['user_id']),1);
		if ($carts) {
			$cart_id = $carts[0]['id'];
		} else {
			$cart_id = insert('cart',array(
				'user_id'=>$_SESSION['user_id'],
				'name'=>'πρώτο',
			));
		}
	}

	$uid = $_SESSION['user_id'];
	$demo = value0('demographic_group FROM users WHERE id='.sql($uid));
	$threshold_count = value('COUNT(*) FROM threshold WHERE user='.sql($uid));

	$cart = row('* FROM cart WHERE id='.sql($cart_id));
	authif($cart['user_id']==$_SESSION['user_id']);
?>
<? include 'app/begin.php' ?>

<? if ($demo===null && !$threshold_count) { ?>
<div class="alert">No upper and lower limits have been given about nutrient
quantities.  You can <a href="<?=html($URL.'/user/profile')?>">fill in your
personal information</a> (gender, age etc) or <a
href="<?=html($URL.'/cart/thresholds')?>">define quantities manually</a>.</div>
<? } ?>

<? include_script($URL.'/cart/cart-script.js') ?>
<? $form_id = $ID; ?>

<div id="<?=html($ID.'_switcher')?>"><?
	include 'cart-switcher.php';
?></div>

<div id="<?=html($ID.'_main')?>"><?
	$ID2=$ID; include 'cart-main.php';
?></div>

<? include 'app/end.php' ?>
