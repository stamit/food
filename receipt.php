<? $AUTH=true;
	require_once 'app/init.php';

	$row = given('receipt.id', array(
		'parent'=>'int',
		'issued'=>'str1',
		'store'=>'int',
		'person'=>'int',
		'product'=>'int',
		'units'=>'int',
		'length'=>'float',
		'area'=>'float',
		'weight'=>'float',
		'net_weight'=>'float',
		'net_volume'=>'float',
		'amount'=>'float',
		'notes'=>'str1',
	));

	if ($row['id']!==null) {
		$uid = value('user_id FROM receipt'
		             .' WHERE id='.sql(abs($row['id'])));
		authif($uid==$_SESSION['user_id'] ||
		       has_right('admin'));
	}

	if (posting()) try {
		$row['user_id'] = $_SESSION['user_id'];

		if ($row['id']===null || $row['id']>=0) {
			if (array_key_exists('parent',$row) && $row['parent']===null) {
				if ($row['store']===null && $row['person']===null)
					mistake('person','Receipt from both unknown person and unknown store?');
			}
			if ($row['person']===null && $row['store']!==null)
				$row['person'] = value('owner FROM store WHERE id='.sql($row['store']));
			if ($row['parent']!==null && $row['product']===null)
				mistake('product','Unknown product?');
			if ($row['length']!==null && $row['length']<=0)
				mistake('length','Length must be positive, or empty.');
			if ($row['area']!==null && $row['area']<=0)
				mistake('area','Area must be positive, or empty.');
			if ($row['volume']!==null && $row['volume']<=0)
				mistake('volume','Volume must be positive, or empty.');
			if ($row['weight']!==null && $row['weight']<=0)
				mistake('weight','Weight must be positive, or empty.');
			if ($row['net_weight']!==null && $row['net_weight']<=0)
				mistake('net_weight','Net weight must be positive, or empty.');
			if ($row['amount']!==null && $row['amount']<=0)
				mistake('amount','Price must be positive.');

			if ($row['parent']!==null &&
			    $row['units']===null &&
			    $row['length']===null &&
			    $row['area']===null &&
			    $row['weight']===null &&
			    $row['net_weight']===null &&
			    $row['net_volume']===null) {
				$prod = row('* FROM product WHERE'
				            .' id='.sql($row['product']));
				$row['units'] = $prod['typical_units'];
				$row['net_weight'] = $prod['net_weight'];
				if ($prod['net_weight']!==null
				    && $prod['glaze_weight']!==null)
					$row['net_weight'] +=
						$prod['glaze_weight'];
				$row['net_volume'] = $prod['net_volume'];

				if ($row['amount']===null) {
					$row['amount'] = $prod['typical_price'];
				}
			}

			if ($row['parent']!==null &&
			    $row['units']===null &&
			    $row['length']===null &&
			    $row['area']===null &&
			    $row['weight']===null &&
			    $row['net_weight']===null &&
			    $row['net_volume']===null) {
				mistake('units','Unkown quantity?');
			}
			if ($row['parent']===null && $row['amount']===null)
				mistake('amount','Unknown price?');
		}

		if (correct()) {
			$id = store('receipt.id',$row);
			if ($row['id']==null) {
				if ($row['parent']!==null) {
					if (success($URL.'/receipt?id='
					            .$row['parent']))
						return true;
				} else {
					if (success($URL.'/receipt?id='.$id))
						return true;
				}
			} else {
				if (success($URL.'/receipts'))
					return true;
			}
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	if ($row['id']>0) {
		$row = row('* FROM receipt WHERE id='.sql($row['id']));
		$HEADING = 'Receipt';
	} else {
		$row = null;
		$HEADING = 'New receipt';
	}
?>
<? include 'app/begin.php' ?>

<? push(); begin_form($URL.'/receipt'.($row['id']?'?id='.$row['id']:'')) ?>
<table class="fields">
	<tr><th class="left">Date/time:</th><td><?
		print input('issued',($row?$row['issued']:
			date_decode(value('NOW()'))
		),19);
	?></td></tr>
	<tr><th class="left">Price:</th><td><?
		print number_input('amount',($row?$row['amount']:'')).' €';
	?></td></tr>
	<tr><th class="left">Store:</th><td><?
		print dropdown('store',($row?$row['store']:''),select(
			"id AS value, "
			."CONCAT(name,' - ',"
				."IF(LENGTH(address)>40,"
					."CONCAT(SUBSTRING(address,1,40),"
					         ."'[...]'),"
					."address"
				.")"
			.") AS text"
			.' FROM store ORDER BY name'
		),'',array('','(unknown store or no store)'));
	?></td></tr>
	<tr><th class="left">Seller person:</th><td><?
		print dropdown('person',($row?$row['person']:''),select(
			"id AS value, name AS text"
			.' FROM person ORDER BY name'
		),'',array('','(owner of above store)'));
	?></td></tr>
	<tr><th class="left">Notes:</th><td><?
		print textarea('notes',$row,48,3);
	?></td></tr>

	<tr><td colspan="2" class="buttons">
		<?=submit_button('Save')?>
	</td></tr>
</table>
<? end_form(); pop() ?>

<? if ($row['id'] && $row['parent']===null) { ?>

<h3>Items
	<?=$row['id'] ? '(€'.value('SUM(amount) FROM receipt WHERE parent='.sql($row['id'])).')' : ''?>
</h3>

<? $mtid = include 'receipt-children.php'; ?>

<? push(); begin_form($URL.'/receipt') ?>
<? print hidden('parent',$row['id']); ?>
<table class="fields">
	<tr><th class="left">Product:</th><td colspan="5"><?
		print dropdown('product',null,select('id AS value, name AS text FROM product ORDER BY name'));
	?></td></tr>
	<tr>
		<th class="left">Rec.Price:</th><td><?
			print number_input('amount','').'€';
		?></td>
	</tr>
	<tr>
		<th class="left">Units:</th><td><?
			print ' × '.number_input('units','');
		?></td>
	</tr>
	<tr>
		<th class="left">Weight:</th><td>
			<strong>net</strong>
			<?=number_input('net_weight','')?>g
			<strong>gross</strong>
			<?=number_input('weight','')?>g
		</td>
	</tr>
	<tr>
		<th class="left">Volume:</th><td><?
			print number_input('net_volume','').'ml';
		?></td>
	</tr>
	<tr>
		<th class="left">Area:</th><td>
			<?=number_input('area','')?>m&sup2;
		</td>
	</tr>
	<tr>
		<th class="left">Length:</th><td>
			<?=number_input('length','')?>m
		</td>
	</tr>
	<tr><td colspan="2" class="buttons">
		<button class="button" onclick="<? print html(
			'validate_post('.js($ID).',function(){'
				.'maketable_display('.js($mtid).');'
				.'elem('.js($ID.'_product').').focus();'
			.'})'
		)?>">Save</button>
	</td></tr>
</table>
<? end_form(); pop() ?>

<? } ?>

<? include 'app/end.php' ?>
