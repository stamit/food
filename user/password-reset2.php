<?
	require_once 'app/init.php';

	$id = intval($_GET['id']);
	$user = fetch('users.id',$id);
	authif($user!==null && $user['confirmation']!==null
	       && $user['confirmation']=='!'.$_GET['code'] && $user['active']);

	execute("UPDATE users SET confirmation='?' WHERE id=".sql($id));
	$_SESSION['user_id'] = $id;
	$_SESSION['user'] = $user;

	redirect($URL.'/user/password-change');
?>
