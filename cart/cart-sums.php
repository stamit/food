<? $AUTH=true;
	require_once 'app/init.php';

	if ($cart===null) $cart = fetch('cart.id',v('id'));
	authif($cart['user_id']==$_SESSION['user_id']);

	if (v('days')!==null) {
		$cart['days'] = max(0,floatval(v('days')));
		execute('UPDATE cart SET days='.sql($cart['days'])
		        .' WHERE id='.sql($cart['id']));
	}

	function f($x) {
		return 'SUM(product.'.$x.'*cart_item.multiplier) as '.$x
		       .', SUM(product.'.$x.'*cart_item.multiplier IS NULL) as null_'.$x;
	}
	$fields = array_map('f',col('name FROM nutrient'));
	$sums = row(implode(', ',$fields)
	            .' FROM cart_item LEFT JOIN product'
	            .' ON product.id=cart_item.product'
	            .' WHERE cart_item.cart='.sql($cart['id']));

	function pernutrient($nut) {
		global $sums,$cart;
		$threshold = row0('* FROM threshold'
			.' WHERE nutrient='.sql($nut['id'])
			.' AND user='.sql($_SESSION['user_id'])
		);
		if ($threshold['min']!==null) $threshold['min']*=$cart['days'];
		if ($threshold['best']!==null)$threshold['best']*=$cart['days'];
		if ($threshold['max']!==null) $threshold['max']*=$cart['days'];

		if ($threshold['min']!==null
		    && $sums[$nut['name']] < $threshold['min']) {
			$class = ' insufficient';
		} else if ($threshold['max']!==null
		           && $threshold['max'] < $sums[$nut['name']]) {
			$class = ' excessive';
		} else {
			$class = '';
		}

		$tip_parts = array();

		if ($threshold['min'] !== null) {
			$tip_parts[] = 'min: '.number_format(
				$threshold['min'],
				$nut['decimals']
			).$nut['unit'];
		}

		if ($threshold['best'] !== null) {
			$tip_parts[] = 'best: '.number_format(
				$threshold['best'],
				$nut['decimals']
			).$nut['unit'];
		}

		if ($threshold['max'] !== null) {
			$tip_parts[] = 'max: '.number_format(
				$threshold['max'],
				$nut['decimals']
			).$nut['unit'];
		}

		$tip = ' title="'.html(implode(' - ', $tip_parts)).'"';

		if ($sums['null_'.$nut['name']])
			$class .= ' uncertain';

		return '<tr>'
			.'<th class="left">'
				.html($nut['description'].':')
			.'</th>'
			.'<td class="number'.$class.'"'.$tip.'>'
				.html(number_format(
					$sums[$nut['name']],
					$nut['decimals']
				).$nut['unit'])
			.'</td>'
		.'</tr>';
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
				<?=pernutrient($nutrient)?>
			<? } ?>
		</table>
	</td>
	<? } ?>
</tr></table>
<p>Price: <?=html(currency_decode(value('SUM(price) FROM cart_item'
                                       .' WHERE cart='.sql($cart['id']))))?></p>
<? return include 'app/end.php' ?>
