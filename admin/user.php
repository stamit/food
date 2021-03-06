<? $AUTH='admin';
	require_once 'app/init.php';
	require_once 'lib/validation.php';

	$row = given('users.id', array(
		'username'=>'str1',
		'password'=>'str1',
		'email'=>'str1',
		'registered'=>'str1',
		'confirmation'=>'str1',
		'confirmed'=>'str1',
		'active'=>'boolean',
		'timezone'=>'str1',
		'birth'=>'str1',
		'gender'=>'int',
		'pregnancy'=>'int',
		//'demographic_group'=>'int',
	));

	if (posting()) try {
		if ($row['id']===null || $row['id']>0) {
			if (strlen($_POST['username'])<2) {
				mistake('username','Must be at least 2 characters');
			} else if (strlen($_POST['username'])>12) {
				mistake('username','Up to 12 characters');
			} else if (!preg_match('/^(\pL|\pN|-)*$/u',$_POST['username'])) {
				mistake('username','Only letters, numbers and hyphens allowed');
			} else if (value('COUNT(*) FROM users WHERE username='.sql($_POST['username']).' AND id<>'.sql($row['id']))) {
				mistake('username','There is already a user with this username');
			}

			if ($row['password']!==null) {
				if (strlen($_POST['password'])<4) {
					mistake('password','Must be at least 4 characters');
				} else if (strlen($_POST['password'])>12) {
					mistake('password','Not longer than 12 characters');
				} else if ($_POST['password'] !== $_POST['password2']) {
					mistake('password2','You did not repeat correctly');
				}
				$row['password'] = sha1($row['password']);
			} else {
				unset($row['password']);
			}

			if ($row['email'] !== null)
				validate_email($row['email'],'email');
		}

		if (correct()) {
			store('users.id',$row);
			if (success($URL.'/admin/users')) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	if ($row['id']>0) {
		$row = row('* FROM users WHERE id='.sql($row['id']));
		$HEADING = 'User "'.html($row['username']).'"';
	} else {
		$HEADING = 'New user';
	}
?>
<? include 'app/begin.php' ?>

<? begin_form() ?>
<table class="fields">
	<tr><th class="left">Username:</th><td><?
		print input('username',$row,12);
	?></td></tr>
	<tr><th class="left">Password:</th><td><?
		print password_input('password','',16);
	?></td></tr>
	<tr><th class="left">Repeat:</th><td><?
		print password_input('password2','',16);
	?></td></tr>
	<tr><th class="left">Email:</th><td><?
		print input('email',$row,array(64,128));
	?></td></tr>
	<tr><th class="left">Registration date:</th><td><?
		print datetime_input('registered',$row);
	?></td></tr>
	<tr><th class="left">Confirmation code:</th><td><?
		print input('confirmation',$row,40);
	?></td></tr>
	<tr><th class="left">Confirmation date:</th><td><?
		print datetime_input('confirmed',$row);
	?></td></tr>
	<tr><th class="left">Active:</th><td><?
		print checkbox('active',$row);
	?></td></tr>
	<tr><th class="left">Timezone:</th><td><?=dropdown('timezone','Europe/Athens',get_timezone_opts(),0,
		array('','(unknown)')
	)?></td></tr>
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
