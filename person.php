<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'lib/validation.php';

	$row = given_record('person.id', array(
		'user_id'=>array(0,''=>null),
		'name'=>array('',''=>null),
		'address'=>array('',''=>null),
		'postcode'=>array('',''=>null),
		'postbox'=>array('',''=>null),
		'phone'=>array('',''=>null),
		'phone2'=>array('',''=>null),
		'fax'=>array('',''=>null),
		'email'=>array('',''=>null),
		'website'=>array('',''=>null),
		'afm'=>array('',''=>null),
		'doy'=>array('',''=>null),
		'notes'=>array('',''=>null),
	));

	if ($row['id']!==null) {
		$uid = value('SELECT user_id FROM person'
		             .' WHERE id='.sql(abs($row['id'])));
		authif($uid==$_SESSION['user_id'] ||
		       has_right('admin'));
	}

	if (posting()) try {
		if (!has_right('register-persons'))
			mistake('You are not allowed to register persons.');

		if (!has_right('admin') || $row['user_id']===null)
			$row['user_id'] = $_SESSION['user_id'];

		if ($row['id']===null || $row['id']>0) {
			$row['name'] = trim($row['name']);
			if (mb_strlen($row['name'])<4)
				mistake('name','At least 4 characters.');
			if (mb_strlen($row['name'])>64)
				mistake('name','Up to 64 characters.');
	
			if ($row['address'] !== null) {
				$row['address'] = trim($row['address']);
				if (mb_strlen($row['address'])<4)
					mistake('address','At least 4 characters.');
				if (mb_strlen($row['address'])>127)
					mistake('address','Up to 127 characters.');
			}
	
			#if ($row['afm'] !== null) {
			#	validate_afm($row['afm'],'afm');
			#}
	
			if ($row['doy'] !== null) {
				$row['doy'] = trim($row['doy']);
				if (mb_strlen($row['doy'])<4)
					mistake('doy','At least 4 characters.');
				if (mb_strlen($row['doy'])>48)
					mistake('doy','Up to 48 characters.');
			}
	
			if ($row['phone'] !== null)
				validate_telephone($row['phone'],'phone',$COUNTRY_CODE);
			if ($row['phone2'] !== null)
				validate_telephone($row['phone2'],'phone2',$COUNTRY_CODE);
			if ($row['fax'] !== null)
				validate_telephone($row['fax'],'fax',$COUNTRY_CODE);
	
			if ($row['email'] !== null)
				validate_email($row['email'],'email');
		}

		if (correct()) {
			put($row,'person');
			if (success($URL.'/persons')) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	if ($row['id']>0) {
		$row = row('SELECT * FROM person WHERE id='.sql($row['id']));
		$HEADING = 'Person '.html($row['name']);
	} else {
		$HEADING = 'New person';
	}
?>
<? include 'app/begin.php' ?>

<? begin_form() ?>
<table class="fields">
<? if (has_right('admin')) { ?>
	<tr><th>USER ID:</th><td><?=number_input('user_id',$row)?></td></tr>
<? } ?>
	<tr><th>Name Sn:</th><td><?=input('name',$row,array(48,64))?></td></tr>
	<tr><th>Address:</th><td><?=textarea('address',$row,48,3)?></td></tr>
	<tr><th>Zip code:</th><td><?=input('postcode',$row,6)?></td></tr>
	<tr><th>P.O. box:</th><td><?=input('postbox',$row,6)?></td></tr>
	<tr><th>Phone:</th><td><?=input('phone',$row,16)?></td></tr>
	<tr><th>Phone:</th><td><?=input('phone2',$row,16)?></td></tr>
	<tr><th>FAX:</th><td><?=input('fax',$row,16)?></td></tr>
	<tr><th>Email:</th><td><?=input('email',$row,array(24,127))?></td></tr>
	<tr><th>Website:</th><td><?=input('website',$row,array(32,127))?></td></tr>
<?/*
	<tr><th>ΑΦΜ:</th><td><?=input('afm',$row,9)?></td></tr>
	<tr><th>ΔΟΥ:</th><td><?=input('doy',$row,array(24,48))?></td></tr>
*/?>
	<? if (!$OPTION) { ?>
	<tr><th>Notes:</th><td><?=textarea('notes',$row,64,10)?></td></tr>
	<? } ?>

<? if (has_right('register-persons')) { ?>
	<tr><td colspan="2" class="buttons">
		<?=ok_button('Save')?>
	</td></tr>
<? } ?>
</table>

<? if ($row['id']>0) { ?>
<h3>Products</h3>
<? include 'person-products.php' ?>
<h3>Imports</h3>
<? include 'person-imports.php' ?>
<h3>Distributions</h3>
<? include 'person-distr.php' ?>
<h3>Stores</h3>
<? include 'person-stores.php' ?>
<? } ?>

<? end_form() ?>
<? include 'app/end.php' ?>
