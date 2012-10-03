<?
	require_once 'app/init.php';
	require_once 'lib/email.php';

	if (posting()) try {
		if (!preg_match('/^[a-zA-Z_.0-9-]+@[^.]+(\.[^.]+)+$/',$_POST['email']))
			mistake('email','This is not a syntactically valid email address');
		if (value('COUNT(*) FROM users WHERE email='.sql($_POST['email'])))
			mistake('email','There is already a user with this email address');

		$_POST['username'] = trim($_POST['username']);
		if (strlen($_POST['username'])<2) {
			mistake('username','Must be at least 2 characters');
		} else if (strlen($_POST['username'])>12) {
			mistake('username','Up to 12 characters');
		} else if (!preg_match('/^(\pL|\pN|-)*$/u',$_POST['username'])) {
			mistake('username','Only letters, numbers and hyphens allowed');
		} else if (value('COUNT(*) FROM users WHERE username='.sql($_POST['username']))) {
			mistake('username','There is already a user with this username');
		}

		if (strlen($_POST['password'])<4) {
			mistake('password','Must be at least 4 characters');
		} else if (strlen($_POST['password'])>12) {
			mistake('password','Up to 12 characters');
		} else if ($_POST['password'] !== $_POST['password2']) {
			mistake('password2','You did not repeat the password correctly');
		}

		if (correct()) {
			$confirm_code = rand_str(39);
			$user_id = insert('users',array(
				'username'=>$_POST['username'],
				'password'=>sha1($_POST['password']),
				'email'=>$_POST['email'],
				'confirmation'=>'*'.$confirm_code,
			));
			if (value('COUNT(*) FROM users')==1) {
				foreach (select('* FROM `right`') as $right) {
					insert('user_right',array('user'=>$user_id,'right'=>$right['id']));
				}
			}

			send_email_template('signup', array(
				'To'=>$_POST['email'],
			), array(
				'IP'=>_get_user_ip(),
				'DATE'=>date_decode(today()),
				'LINK'=>dereference($URL.'/user/signup-confirm?id='.$user_id
				                    .'&code='.urlencode($confirm_code)),
			));

			if (success($URL.'/user/signup-pending')) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	$HEADING = 'New user registration';
?>
<? include 'app/begin.php' ?>
<? include_script($URL.'/user/signup-fun.js') ?>
<? begin_form($URL.'/user/signup') ?>
<table class="fields">
	<tr>
		<th class="left">Email address:</th>
		<td>
			<?=input('email',$_POST['email'],array(28,127),false,'user_signup_check_email('.js($ID).')')?>
			<?=mistake_label('email',$ID.'_email_chk')?>
		</td>
	</tr>
	<tr>
		<th class="left">Username:</th>
		<td>
			<?=input('username',$_POST,12,false,'user_signup_check_username('.js($ID).')')?>
			<?=mistake_label('username',$ID.'_username_chk')?>
		</td>
	</tr>
	<tr>
		<th class="left">Password:</th>
		<td><?=password_input('password','',12)?></td>
	</tr>
	<tr>
		<th class="left">Repeat password:</th>
		<td><?=password_input('password2','',12)?></td>
	</tr>
	<tr>
		<td colspan="2" class="buttons">
			<?=ok_button('Register')?>
		</td>
	</tr>
</table>
<? end_form() ?>
<? include 'app/end.php' ?>
