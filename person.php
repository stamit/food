<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'lib/validation.php';

	$row = given('person.id', array(
		'user_id'=>'int',
		'name'=>'str1',
		'address'=>'str1',
		'postcode'=>'str1',
		'postbox'=>'str1',
		'phone'=>'to_phone',
		'phone2'=>'to_phone',
		'fax'=>'to_phone',
		'email'=>'str1',
		'website'=>'str1',
		'afm'=>'str1',
		'doy'=>'str1',
		'notes'=>'str1',
	));

	if (posting()) try {
		if (!has_right('register-persons'))
			mistake('You are not allowed to register persons.');

		if ($row['id']!==null && $row['id']!=0 && !has_right('admin')) {
			$uid = value0('user_id FROM person'
				      .' WHERE id='.sql(abs($row['id'])));
			if ($uid!==null && $uid!=$_SESSION['user_id']) {
				mistake('You are not allowed to edit this record.');
			}
		}

		if (!has_right('admin') || $row['user_id']===null)
			$row['user_id'] = $_SESSION['user_id'];

		if ($row['id']===null || $row['id']>0) {

			$row['name'] = trim($row['name']);
			if (mb_strlen($row['name'])<4)
				mistake('name','At least 4 characters.');
			if (mb_strlen($row['name'])>64)
				mistake('name','Up to 64 characters.');
			if (value('COUNT(*) FROM person WHERE name='.sql($row['name'])
			          .($row['id']?' AND id<>'.sql($row['id']):'')))
				mistake('name','A person with this name already exists.');
	
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
			$row['id'] = (int)store('person.id',$row);
			if (success('?id='.urlencode($row['id']))) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	$MODE = $row['id'];
	if (!$MODE) {
		$HEADING = 'New person';
		$RO = 0;
	} else if ($MODE>0) {
		$row = fetch('person.id',$row);
		if ($row) {
			$HEADING = 'Person '.html($row['name']);
			$RO = ($MODE[0]!='+');
		} else {
			$STATUS = 404;
			$HEADING = 'Person does not exist';
			$RO = -1;
		}
	} else {
		$row = fetch('person.id',-$row['id']);
		if ($row) {
			$HEADING = 'Delete person '.html($row['name']).'?';
			$RO = 1;
		} else {
			$HEADING = 'Person deleted';
			$RO = -1;
		}
	}
?>
<? include 'app/begin.php' ?>

<? if ($RO>=0) { begin_form(); ?>
<table class="fields">
<? if (has_right('admin')) { ?>
	<tr class="noprint"><th>USER ID:</th><td><?=number_input('user_id',$row,null,$RO)?></td></tr>
<? } ?>
	<tr><th>Name Sn:</th><td><?=input('name',$row,array(48,64),$RO)?></td></tr>
	<tr><th>Address:</th><td><?=textarea('address',$row,48,3,$RO)?></td></tr>
	<tr><th>Post code:</th><td><?=input('postcode',$row,6,$RO)?></td></tr>
	<tr><th>P.O. box:</th><td><?=input('postbox',$row,6,$RO)?></td></tr>
	<tr><th>Phone:</th><td><?=input('phone',$row,16,$RO)?></td></tr>
	<tr><th>Phone:</th><td><?=input('phone2',$row,16,$RO)?></td></tr>
	<tr><th>FAX:</th><td><?=input('fax',$row,16,$RO)?></td></tr>
	<tr><th>Email:</th><td><?=input('email',$row,array(24,127),$RO)?></td></tr>
	<tr><th>Website:</th><td><?=input('website',$row,array(32,127),$RO)?></td></tr>
	<tr><th>Notes:</th><td><?=textarea('notes',$row,48,3,$RO)?></td></tr>

<? if (has_right('register-persons')) { ?>
	<tr><td colspan="2" class="buttons">
		<? if ($MODE<0) { ?>
			<?=submit_button('Delete')?>
			<?=link_button('Keep',array('id'=>$row['id']),'cancel')?>
		<? } else if ($RO) { ?>
			<?=link_button('Edit',array('id'=>'+'.$row['id']),'edit')?>
			<?=link_button('Delete',array('id'=>-$row['id']),'delete')?>
		<? } else if ($MODE>0) { ?>
			<?=submit_button('Save')?>
			<?=link_button('Cancel',array('id'=>(int)$row['id']),'cancel')?>
		<? } else { ?>
			<?=submit_button('Save')?>
		<? } ?>
	</td></tr>
<? } ?>
</table>
<? end_form(); } ?>

<? if ($RO>0) { ?>
<h3>Products</h3>
<? include 'person-products.php' ?>
<h3>Imports</h3>
<? include 'person-imports.php' ?>
<h3>Distributions</h3>
<? include 'person-distr.php' ?>
<h3>Stores</h3>
<? include 'person-stores.php' ?>
<? } ?>

<? include 'app/end.php' ?>
