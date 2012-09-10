<? $AUTH=true;
	require_once 'app/init.php';

	if ($cart===null) $cart = get(v('id'),'cart');
	authif($cart['user_id']==$_SESSION['user_id']);
?>
<? include 'app/begin.php' ?>

<table class="seplayout">
	<tr class="layout">
		<td class="layout">
			<form method="get" action="<?=html($URL.'/cart/cart-main')?>" autocomplete="off" onsubmit="return false">
			<p>
				Calculate for <?=number_input(
					'days', $cart, array(2,null), false,
					'cart_update_sums('.js($ID).');'
				)?> days
				•
				<a href="<?=html($URL.'/cart/thresholds')?>">Goals</a>
			</p>
			</form>
			
			<div id="<?=html($ID.'_sums')?>"><? include 'cart-sums.php' ?></div>
			<? $ID2=$ID.'_table'; $form_id=$ID; include 'cart-table.php' ?>
		</td>
		<td class="layout">
			<h3>Available products</h3>
			<? include 'cart-foods.php' ?>
		</td>
	</tr>
</table>

<? include 'app/end.php' ?>
