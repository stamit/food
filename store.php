<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'lib/validation.php';

	$row = given('store.id', array(
		'owner'=>'int',
		'name'=>'str',
		'address'=>'str1',
		'postcode'=>'str1',
		'phone'=>'str1',
		'phone2'=>'str1',
		'fax'=>'str1',
		'notes'=>'str1',
	));

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
			$row['id'] = (int)store('store.id',$row);
			if (success('?'.queryencode(array('id'=>$row['id'])))) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	$MODE = $row['id'];
	if (!$MODE) {
		$HEADING = 'New store';
		$RO = 0;
	} else if ($MODE>0) {
		$row = fetch('store.id',$row);
		if ($row) {
			$HEADING = 'Store '.html($row['name']);
			$RO = ($MODE[0]!='+');
		} else {
			$STATUS = 404;
			$HEADING = 'Store does not exist';
			$RO = -1;
		}
	} else {
		$row = fetch('store.id',-$row['id']);
		if ($row) {
			$HEADING = 'Delete store '.html($row['name']).'?';
			$RO = 1;
		} else {
			$HEADING = 'Store deleted';
			$RO = -1;
		}
	}
?>
<? include 'app/begin.php' ?>

<? if ($RO>=0) { begin_form(); ?>
<table class="fields">
	<tr>
		<th class="left">Owner:</th>
		<td><?=person_select_html('owner',$row,$RO,'(unknown)')?></td>
	</tr>
	<tr>
		<th class="left">Name of store:</th>
		<td><?=input('name',$row,array(32,64),$RO)?></td>
	</tr>
	<tr>
		<th class="left top">Address of store:</th>
		<td><?=textarea('address',$row,48,3,$RO)?></td>
	</tr>

	<tr>
		<th class="left">Post code:</th>
		<td><?=input('postcode',$row,6,$RO)?></td>
	</tr>

	<tr>
		<th class="left">Phone:</th>
		<td><?=input('phone',$row,16,$RO)?></td>
	</tr>

	<tr>
		<th class="left">Phone:</th>
		<td><?=input('phone2',$row,16,$RO)?></td>
	</tr>

	<tr>
		<th class="left">FAX:</th>
		<td><?=input('fax',$row,16,$RO)?></td>
	</tr>

	<tr>
		<th class="left">Notes:</th>
		<td><?=textarea('notes',$row,48,3,$RO)?></td>
	</tr>

<? if (has_right('register-stores')) { ?>
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

<? include 'app/end.php' ?>
