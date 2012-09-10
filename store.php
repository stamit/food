<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'lib/validation.php';

	$row = given_record(array(
		'owner'=>array('',''=>null),
		'name'=>array('',''=>null),
		'address'=>array('',''=>null),
		'postcode'=>array('',''=>null),
		'phone'=>array('',''=>null),
		'phone2'=>array('',''=>null),
		'fax'=>array('',''=>null),
		'notes'=>array('',''=>null),
	),'id','store');

	if (posting()) try {
		if (!has_right('register-stores'))
			mistake('You are not allowed to register stores.');

		if ($row['name'] !== null) {
			$row['name'] = trim($row['name']);
			if (mb_strlen($row['name'])<4)
				mistake('name','At least 4 characters.');
			if (mb_strlen($row['name'])>127)
				mistake('name','Up to 127 characters.');
		}

		if ($row['address'] !== null) {
			$row['address'] = trim($row['address']);
			if (mb_strlen($row['address'])<4)
				mistake('address','At least 4 characters.');
			if (mb_strlen($row['address'])>127)
				mistake('address','Up to 127 characters.');
		}

		if ($row['phone'] !== null)
			validate_telephone($row['phone'],'phone',$COUNTRY_CODE);
		if ($row['phone2'] !== null)
			validate_telephone($row['phone2'],'phone2',$COUNTRY_CODE);
		if ($row['fax'] !== null)
			validate_telephone($row['fax'],'fax',$COUNTRY_CODE);
	
		if (correct()) {
			put($row,'store');
			if (success($URL.'/stores')) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	if ($row['id']>0) {
		$row = row('SELECT * FROM store WHERE id='.sql($row['id']));
		$HEADING = 'Store "'.html($row['name']).'"';
	} else {
		$HEADING = 'New store';
	}
	$BREAD = array($URL=>'home', 'stores'=>'stores');
?>
<? include 'app/begin.php' ?>

<? begin_form() ?>
<table>
	<tr><th class="left">Owner:</th><td><?
		print dropdown('owner',$row,
			query('SELECT id AS value, name AS text'
			      .' FROM person ORDER BY name'),
		0, array('','(unknown)'));
	?></td></tr>
	<tr><th class="left">Name of store:</th><td><?
		print input('name',$row,array(32,64));
	?></td></tr>
	<tr><th class="left top">Address of store:</th><td><?
		print textarea('address',$row,48,3);
	?></td></tr>
	<tr><th class="left">Zip code:</th><td><?
		print input('postcode',$row,6);
	?></td></tr>
	<tr><th class="left">Phone:</th><td><?
		print input('phone',$row,16);
	?></td></tr>
	<tr><th class="left">Phone:</th><td><?
		print input('phone2',$row,16);
	?></td></tr>
	<tr><th class="left">FAX:</th><td><?
		print input('fax',$row,16);
	?></td></tr>
	<tr><th class="left">Notes:</th><td><?
		print textarea('notes',$row,64,6);
	?></td></tr>
<? if (has_right('register-persons')) { ?>
	<tr><td colspan="2" class="buttons">
		<?=ok_button('Register')?>
	</td></tr>
<? } ?>
</table>
<? end_form() ?>

<? include 'app/end.php' ?>
