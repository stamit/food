<?
	require_once 'app/init.php';

	if (posting()) try {
		$user = row0('* FROM users'
		             .' WHERE email='.sql($_POST['email']));
		if (!preg_match('/^[a-zA-Z_.0-9-]+@[^.]+(\.[^.]+)+$/',$_POST['email']))
			mistake('email','This email address is incorrect');
		if ($user===null)
			mistake('email','This email address is not registered here.');

		if (correct()) {
			$code = rand_str(39);
			execute('UPDATE users SET confirmation='.sql('!'.$code)
			        .' WHERE id='.sql($user['id']));

			send_email_template('password-reset',array(
				'To'=>$_POST['email'],
			),array(
				'IP'=>_get_user_ip(),
				'DATE'=>date_decode(today()),
				'LINK'=>dereference($URL.'/user/password-reset2?id='.$user['id']
				                    .'&code='.urlencode($code)),
			));
			if (success($URL.'/user/password-reset-pending')) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	$HEADING = 'Change password';
?>
<? include 'app/begin.php' ?>
<? begin_form($URL.'/user/password-reset') ?>
<table class="fields">
	<tr>
		<th class="left">Your email address:</th>
		<td><?=input('email','',array(28,127))?></td>
	</tr>
	<tr>
		<td colspan="2" class="buttons">
			<?=submit_button('Send instructions')?>
		</td>
	</tr>
</table>
<? end_form() ?>
<? include 'app/end.php' ?>
