<? $AUTH='admin';
	require_once 'app/init.php';
	require_once 'app/data.php';
	require_once 'lib/maketable/fun.php';

	include 'app/begin.php';
	maketable(array(
		'self'=>$URL.'/admin/nutrients-table',
		'key'=>'id',
		'join'=>array(
			'nutrient'=>array(),
		),
		'columns'=>array(
			'nutrient.order'=>array('Row',
				'width'=>'40px',
				'edit'=>'number',
			),
			'nutrient.column'=>array('Column',
				'width'=>'40px',
				'edit'=>'number',
			),
			'nutrient.tag'=>array('Tag',
				'search'=>'mid',
				'width'=>'90px',
				'edit'=>'text',
			),
			'nutrient.name'=>array('Code name',
				'search'=>'mid',
				'width'=>'90px',
				'edit'=>'text',
			),
			'nutrient.description'=>array('Readable name',
				'search'=>'mid',
				'width'=>'180px',
				'edit'=>'text',
			),
			'nutrient.unit'=>array('Unit',
				'search'=>'mid',
				'width'=>'80px',
				'edit'=>'text',
			),
		),
		'orderby'=>'nutrient.order',
		'userorder'=>true,

		'update'=>1,
		'updateinc'=>'nutrient.php',

		'delete'=>1,
	));
	return include 'app/end.php';
?>
