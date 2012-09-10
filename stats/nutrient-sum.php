<? $AUTH='consumption';
	require_once 'app/init.php';
	require_once 'app/data.php';

	$HEADING='Nutrient intake during a time period';

	$fields = array(
		'energy',
		'proteins',
		'carbohydrates',
		'sugars',
		'fats',
		'fats_saturated',
		'fats_monounsaturated',
		'fats_polyunsaturated',
		'fats_polyunsaturated_n6',
		'fats_polyunsaturated_n3',
		'total_fiber',
		'sodium',
		'calcium',
		'phosphorus',
		'iron',
		'a',
		'c',
		'd',
		'e',
		'b1',
		'b2',
		'b3',
		'b5',
		'b6',
		'b7',
		'b9',
		'b12',
		'choline',
		'cholesterol',
		'magnesium',
		'zinc',
		'manganese',
		'copper',
		'selenium',
	);

	if (posting()) {
		$a = array(
			'TIME_TO_SEC(TIMEDIFF('
				.($_POST['until']?sql($_POST['until']):'MAX(consumed)') .','
				.($_POST['since']?sql($_POST['since']):'MIN(consumed)')
			.'))/60/60/24 AS days',
		);
		foreach ($fields as $f) {
			$a[] = 'SUM(consumption.weight*product.'.$f.'/product.sample_weight) AS '.$f;
		}
		$row = row('SELECT '.implode(',',$a)
			.' FROM consumption JOIN product ON consumption.product=product.id'
			.' WHERE '.($_POST['since']?sql($_POST['since']).'<=consumed':'TRUE')
			.' AND '.($_POST['until']?'consumed<='.sql($_POST['until']):'TRUE')
		);
	}
?>
<?php include 'app/begin.php' ?>

<form id="<?= html($ID) ?>" method="post" action="nutrient-sum">
<table>
	<tr><th>Since:</th><td><?= datetime_input('since',$_POST) ?></td></tr>
	<tr><th>Until:</th><td><?= datetime_input('until',$_POST) ?></td></tr>
	<tr><td colspan="2" class="buttons"><button type="submit" class="button">Calculate</button></td></tr>
</table>
</form>

<?php if (posting()) { ?>
<p><?= html($row['days']) ?></p>
<table>
<tr><th></th><th>Per day</th><th>Sum</th></tr>
<?php foreach ($fields as $field) { ?>
<tr>
	<th><?= html($field) ?></th>
	<td><?= html(quantity_html($row[$field]/$row['days'])) ?></td>
	<td><?= html(quantity_html($row[$field])) ?></td>
</tr>
<?php } ?>
</table>
<?php } ?>

<?php include 'app/end.php' ?>
