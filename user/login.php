<?
	require_once 'app/init.php';

	if (posting()) try {
		if ($_SESSION['user_id']) {
			mistake('You are already logged in.');
		} else {
			$user = row0('* FROM users'
			             .' WHERE username='.sql($_POST['username']));

			if ($user !== null
			    && !authenticate_user($user,$_POST['password'])) {
				$user = null;
			}

			if ($user === null){
				mistake('Wrong username or password.');
			} else if ( ! $user['active'] ) {
				if ($user['confirmation'] !== null
				    && substr($user['confirmation'],0,1)=='*') {
					mistake("Account is not yet activated. "
					        ." Follow the email instructions"
					        ." or contact the admin.");
				} else {
					mistake('Access is not allowed.');
				}
			}
		}

		if (correct()) {
			$_SESSION['user_id'] = $user['id'];
			$_SESSION['user'] = $user;
			$_SESSION['remember_me'] = ($_POST['remember_me']=='1');

			if ($_POST['timezone']
			    && timezone_open($_POST['timezone'])!==false) {
				$_SESSION['timezone'] = $_POST['timezone'];
				unset($_SESSION['timeoffset']);
				execute('UPDATE users'
					.' SET timezone='
						.sql($_POST['timezone'])
					.' WHERE id='.sql($user['id'])
				);
			} else if ( $_POST['timeoffset']!==''
			            && $_POST['timeoffset']!==null ) {
				unset($_SESSION['timezone']);
				$_SESSION['timeoffset'] =
					intval($_POST['timeoffset']);
			} else {
				$_SESSION['timezone'] = $user['timezone'];
				unset($_SESSION['timeoffset']);
			}

			if ($_SESSION['timezone']===null
			    && $_SESSION['timeoffset']===null) {
				$_SESSION['alert'] = 'Unknown time zone? Using server timezone.';
			}

			if (success(ifempty($_POST['uri'],$URL.'/'))) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	$HEADING = 'Log in';
?>
<? include 'app/begin.php' ?>
<? if ($_SESSION['user_id']) { ?>
<p>You are logged in as "<?=html($_SESSION['user']['username'])?>". If you want
to log out, <a href="<?=html($URL.'/user/logout')?>">click here</a>.</p>
<? } else { ?>
<? begin_form($URL.'/user/login') ?>
<?=hidden('uri',v('uri'))?>
<script type="text/javascript">
<!--
	document.write('<input type="hidden" name="timeoffset" value="'+html(-(new Date()).getTimezoneOffset()*60)+'">');
// -->
</script>
<table class="fields">
	<tr>
		<th class="left">Username:</th>
		<td><?=input('username','',12)?></td>
	</tr>
	<tr>
		<th class="left">Password:</th>
		<td><?=password_input('password','',12)?></td>
	</tr>
	<tr>
		<th class="left">Timezone:</th>
		<td><?=dropdown('timezone','Europe/Athens',get_timezone_opts(),0,
			array('','(same as last time)')
		)?></td>
	</tr>
	<tr>
		<td colspan="2" class="buttons">
			<?=checkbox('remember_me',false,'Lasting session')?>
			&nbsp;
			<?=ok_button('Log in')?>
		</td>
	</tr>
</table>

<p><a href="<?=html($URL.'/user/password-reset')?>">If you forgot your username or password, click here.</a></p>

<p><a href="<?=html($URL.'/user/signup')?>">To register a new account, click here.</a></p>

<? end_form() ?>
<? } ?>
<? include 'app/end.php' ?>
