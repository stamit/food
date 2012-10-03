<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'lib/email.php';

	$user = fetch('users.id',$_SESSION['user_id']);

	if (posting()) try {
		if ($user===null) {
			mistake('The user does not exist.');
		}

		if ( $user['confirmation']!='?' &&
		     !authenticate_user($user,$_POST['oldpassword']) ) {
			mistake('oldpassword',
			        'You did not fill in your old password correctly.');
		}

		if (strlen($_POST['password'])<4) {
			mistake('password','Must be at least 4 characters');
		} else if (strlen($_POST['password'])>12) {
			mistake('password','Not longer than 12 characters');
		} else if ($_POST['password'] !== $_POST['password2']) {
			mistake('password2','You did not repeat correctly');
		}

		if (correct()) {
			execute('UPDATE users'
			        .' SET password='.sql(sha1($_POST['password']))
			        .' WHERE id='.sql($_SESSION['user_id']));
			if (success($URL.'/user/password-changed')) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	$HEADING = 'Change password';
?>
<? include 'app/begin.php' ?>
<? begin_form($URL.'/user/password-change') ?>
<table class="fields">
	<? if ($user['confirmation']!='?') { ?>
	<tr>
		<th class="left">Old password:</th>
		<td><?=password_input('oldpassword','',12)?></td>
	</tr>
	<? } else { ?>
	<tr>
		<td colspan="2">You have forgotten your password.</td>
	</tr>
	<? } ?>
	<tr>
		<th class="left">New password:</th>
		<td><?=password_input('password','',12)?></td>
	</tr>
	<tr>
		<th class="left">Repeat new password:</th>
		<td><?=password_input('password2','',12)?></td>
	</tr>
	<tr>
		<td colspan="2" class="buttons">
			<?=ok_button('Change password')?>
		</td>
	</tr>
</table>
<? end_form() ?>
<? include 'app/end.php' ?>
