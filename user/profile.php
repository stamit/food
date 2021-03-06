<? $AUTH=true;
	require_once 'app/init.php';

	$row = given('users.id', array(
		'birth'=>'str1',
		'gender'=>'int',
		'pregnancy'=>'int',
	));

	if (posting()) try {
		$row['id'] = $_SESSION['user_id'];

		if ($row['birth']!==null) {
			//$row['birth'] = date_encode($row['birth']);
			if (strcmp(today(),$row['birth'])<0)
				mistake('birth','The date is in the future.');
		}

		if ($row['gender']==0 && $row['pregnancy']!=0)
			mistake('pregnancy','Male pregnancy?');

		if (!$MISTAKES) {
			$demo = find_demographic_group($row);
			if (!$demo) {
				$row2 = $row;
				$row2['pregnancy'] = 0;
				$demo = find_demographic_group($row2);
				if ($demo) {
					mistake('pregnancy','Not an appropriate age to be pregnant.');
				} else {
					$_SESSION['alert'] = 'Demographic group not found.';
				}
			}
			$row['demographic_group'] = $demo;
		}

		if (correct()) {
			store('users.id',$row);
			user_update_thresholds($row['id']);
			if (success($URL.'/')) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	$row = array_merge(fetch('users.id',$_SESSION['user_id']),$row);

	$HEADING='Your personal information';
?>
<? include 'app/begin.php' ?>
<? begin_form() ?>
<table class="fields">
	<tr><th class="left">Date of birth:</th><td><?
		echo date_input('birth',$row)
	?></td></tr>

	<tr><th class="left">Gender:</th><td><?
		echo dropdown('gender',$row,array(
			array(0,'Male'),
			array(1,'Female'),
		))
	?></td></tr>

	<tr><th class="left">Pregnancy:</th><td><?
		echo dropdown('pregnancy',$row,array(
			array(0,'Not pregnant'),
			array(1,'Pregnant'),
			array(2,'Lactating'),
		))
	?></td></tr>

	<tr><td colspan="2" class="buttons">
		<?=submit_button('Save')?>
	</td></tr>
</table>
<? end_form() ?>
<? include 'app/end.php' ?>
