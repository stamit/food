<? $AUTH='consumption';
	require_once 'app/init.php';
	require_once 'lib/regional.php';

	$HEADING = 'Consumption history';
	$BREAD = array($URL=>'home');
?>

<? include 'app/begin.php' ?>

<? $mtid = include 'consumptions-table.php' ?>

<h3>New consumption</h3>

<? push(); begin_form($URL.'/consumption') ?>
<table>
	<tr><th class="left">Date/time:</th><td><?
		print datetime_input('consumed',($row?$row['consumed']:today()))
	?></td></tr>
	<tr><th class="left">Product:</th><td><?
		print dropdown('product',$row['product'],query(
			'SELECT id AS value, name AS text FROM product WHERE type=1 ORDER BY name'
		))
	?></td></tr>
	<tr><th class="left">Weight:</th><td><?
		print number_input('weight',$row).' g';
	?></td></tr>
	<tr><th class="left">Volume:</th><td><?
		print number_input('volume',$row).' ml';
	?></td></tr>
	<tr><th class="left">Units:</th><td><?
		print ' × '.number_input('units',$row);
	?></td></tr>

	<tr><td colspan="2" class="buttons">
		<button class="button" onclick="<?=html(
			'validate_post('.js($ID).',function(){'
				.'maketable_display('.js($mtid).');'
				.'elem('.js($ID.'_product').').focus();'
			.'})'
		)?>">Save</button>
	</td></tr>
</td></tr></table>
<? end_form(); pop() ?>

<? include 'app/end.php' ?>
