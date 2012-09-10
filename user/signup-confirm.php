<?
	require_once 'app/init.php';

	$id = intval($_GET['id']);
	$user = get($id,'users');
	if ($user===null) redirect($URL);

	if ( $user['confirmation']!==null
	     && substr($user['confirmation'],0,1)=='*'
	     && !$user['active'] ) {
		if ($user['confirmation']!='*'.$_GET['code'])
			redirect($URL);

		execute('UPDATE users'
		        .' SET confirmation=NULL, confirmed=NOW(), active=1'
		        .' WHERE id='.sql($id));
	}

	$HEADING = 'Account activated';
?>
<? include 'app/begin.php' ?>
<p>Your account has been activated. You may now <a href="login">start a
session</a>.</p>
<? include 'app/end.php' ?>
