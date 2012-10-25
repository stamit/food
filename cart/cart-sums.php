<? $AUTH=true;
	require_once 'app/init.php';

	function nutrient_td_html($n, $nut, $cart) {
		$threshold = row0('* FROM threshold'
			.' WHERE nutrient='.sql($n['id'])
			.' AND user='.sql($_SESSION['user_id'])
		);
		if ($threshold['min']!==null) $threshold['min']*=$cart['days'];
		if ($threshold['best']!==null)$threshold['best']*=$cart['days'];
		if ($threshold['max']!==null) $threshold['max']*=$cart['days'];

		$class = array('number');
		if ($threshold['min']!==null && $n['value'] < $threshold['min'])
			$class[] = 'insufficient';
		if ($threshold['max']!==null && $threshold['max'] < $n['value'])
			$class[] = 'excessive';
		if ($n['nulls'])
			$class[] = 'uncertain';
		$class = (count($class) ? ' class="'.html(implode(' ',$class)).'"' : '');

		$tip = array();
		if ($threshold['min'] !== null) 
			$tip[] = 'min: '.number_format($threshold['min'], $nut['decimals']).$nut['unit'];
		if ($threshold['best'] !== null)
			$tip[] = 'best: '.number_format($threshold['best'], $nut['decimals']).$nut['unit'];
		if ($threshold['max'] !== null)
			$tip[] = 'max: '.number_format($threshold['max'], $nut['decimals']).$nut['unit'];
		if ($n['nulls']>0)
			$tip[] = 'known/unknown: '.strval($n['non_nulls']).'/'.strval($n['nulls']);
		$tip = (count($tip) ? ' title="'.html(implode(' - ', $tip)).'"' : '');

		return "<td $class$tip>"
			.html(number_format($n['value'],$nut['decimals']).$nut['unit'])
		.'</td>';
	}


################################################################################


	if ($cart===null) $cart = fetch('cart.id',v('id'));

	authif($cart['user_id']==$_SESSION['user_id']);

	if (v('days')!==null) {
		$cart['days'] = max(0,floatval(v('days')));
		store('cart.id',$cart);
	}

	$valuelist = select('n.id AS id'
	                    .', SUM(pn.value*ci.multiplier) AS value'
	                    .', SUM(pn.value*ci.multiplier IS NOT NULL) AS non_nulls'
	                    .', SUM(pn.value*ci.multiplier IS NULL) AS nulls'
	                    .' FROM cart_item ci'
	                    .' JOIN product_nutrient pn ON pn.product=ci.product'
	                    .' JOIN nutrient n ON n.id=pn.nutrient'
	                    .' WHERE ci.cart='.sql($cart['id'])
	                    .' GROUP BY n.id'); 
	$byid = array();
	foreach ($valuelist as $v) {
		$byid[$v['id']] = $v;
	}
?>
<? include 'app/begin.php' ?>
<table class="nutrients"><tr>
	<? foreach (array(1,2,3) as $col) { ?>
	<td>
		<table class="fields condensed">
			<? foreach (select('* FROM nutrient'
			                   .' WHERE `column`='.sql($col)
			                   .' ORDER BY `order`') as $nutrient) { ?>
			<tr>
				<th><?=html($nutrient['description'])?>:</th>
				<?=nutrient_td_html($byid[$nutrient['id']], $nutrient, $cart)?>
			</tr>
			<? } ?>
		</table>
	</td>
	<? } ?>
</tr></table>
<p>Price: <?=html(currency_decode(value('SUM(price) FROM cart_item WHERE cart='.sql($cart['id']))))?></p>
<? return include 'app/end.php' ?>
