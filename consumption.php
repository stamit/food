<? $AUTH='consumption';
	require_once 'app/init.php';

	$row = given('consumption.id', array(
		'consumed'=>array('',''=>null),
		'product'=>array(0,''=>null),
		'units'=>array(0,''=>null),
		'weight'=>array(0.0,''=>null),
		'volume'=>array(0.0,''=>null),
	));

	if ($row['id']!==null) {
		$uid = value('SELECT user_id FROM consumption'
		             .' WHERE id='.sql(abs($row['id'])));
		authif($uid==$_SESSION['user_id']);
	}

	if (posting()) try {
		$row['user_id'] = $_SESSION['user_id'];

		if ($row['product']===null && !$row['id'])
			mistake('product','No product selected.');

		if ($row['weight']!==null && $row['weight']<0)
			mistake('volume','No negative values.');

		if ($row['volume']!==null && $row['volume']<0)
			mistake('volume','No negative values.');

		if ($row['units']===null && $row['weight']===null && $row['volume']===null && !$row['id'])
			mistake('weight','No quantity defined.');

		if (correct()) {
			store('consumption.id',$row);
			if (success()) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	if ($row['id']>0) {
		$row = row('SELECT * FROM consumption WHERE id='.sql($row['id']));
		$product = row0('SELECT * FROM product WHERE id='.sql($row['product']));
		$HEADING = 'Consumption';
		if ($row['weight']!==null) {
			$HEADING .= ' '.html($row['weight']).'g';
		} else if ($row['volume']!==null) {
			$HEADING .= ' '.html($row['volume']).'ml';
		}
		if ($product!==null) {
			$HEADING .= ' <a href="product?id='.html($product['id']).'">'
				.html($product['name'])
			.'</a>';
		}
	} else {
		$HEADING = 'New consumption';
	}
?>
<?php include 'app/begin.php' ?>

<?php begin_form() ?>
<table>
	<tr><th class="left">Date/time:</th><td><?php
		print date_input('consumed',($row?$row['consumed']:
			date_decode(value('SELECT NOW()'))
		))
	?></td></tr>
	<tr><th class="left">Product:</th><td><?php
		print dropdown('product',$row['product'],query(
			'SELECT id AS value, name AS text FROM product ORDER BY name'
		))
	?></td></tr>
	<tr><th class="left">Units:</th><td><?php
		print ' × '.number_input('units',$row);
	?></td></tr>
	<tr><th class="left">Weight:</th><td><?php
		print number_input('weight',$row).' g';
	?></td></tr>
	<tr><th class="left">Volume:</th><td><?php
		print number_input('volume',$row).' ml';
	?></td></tr>

	<tr><td colspan="2" class="buttons">
		<?=ok_button('Save')?>
	</td></tr>
</td></tr></table>
<?php end_form() ?>

<?php include 'app/end.php' ?>
