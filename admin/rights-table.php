<? $AUTH='admin';
	require_once 'app/init.php';
	require_once 'app/data.php';
	require_once 'lib/maketable/maketable.php';

	include 'app/begin.php';
	maketable(array(
		'self'=>$URL.'/admin/rights-table',
		'key'=>'id',
		'join'=>array(
			'right'=>array(),
		),
		'columns'=>array(
			'right.name'=>array('Code name',
				'search'=>'mid',
				'width'=>'100px',
				'edit'=>'none',
				'size'=>10,
			),
			'right.expression'=>array('Human expression',
				'search'=>'mid',
				'width'=>'200px',
				'edit'=>'text',
				'size'=>30,
			),
			'right.description'=>array('Long description',
				'search'=>'mid',
				'width'=>'300px',
				'edit'=>'text',
				'size'=>50,
			),
		),
		'orderby'=>'right.name',
		'userorder'=>true,

		'update'=>1,
		#'updateinc'=>'right.php',
	));
	return include 'app/end.php';
?>
