<? $AUTH='admin';
	require_once 'app/init.php';
	require_once 'app/data.php';
	require_once 'lib/maketable/fun.php';

	include 'app/begin.php';
	maketable(array(
		'self'=>$URL.'/admin/users-table',
		'key'=>'id',
		'join'=>array(
			'users'=>array(),
		),
		'columns'=>array(
			'users.username'=>array('Username',
				'search'=>'mid',
				'width'=>'100px',
				'edit'=>'text',
				'size'=>12,
				'maxlen'=>12,
			),
			/*'users.password'=>array('Password',
				'search'=>'mid',
				'width'=>'100px',
				'edit'=>'text',
				'size'=>40,
			),*/
			'users.email'=>array('Email',
				'search'=>'mid',
				'width'=>'200px',
				'edit'=>'text',
				'size'=>20,
			),
			'users.registered'=>array('Reg.date',
				'search'=>'mid',
				'width'=>'120px',
				'edit'=>'none',
				'size'=>20,
				'tip'=>'Registration date',
			),
			'users.confirmed'=>array('Conf.date',
				'search'=>'mid',
				'width'=>'120px',
				'edit'=>'none',
				'size'=>20,
				'tip'=>'Confirmation date',
			),
		),
		'orderby'=>'users.username',
		'userorder'=>true,

		'update'=>1,
		'updateurl'=>'user',
		#'updateinc'=>'user.php',
	));
	return include 'app/end.php';
?>
