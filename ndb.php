<? $AUTH='ndb';
	require_once 'app/init.php';
	require_once 'app/data.php';
	require_once 'lib/maketable/fun.php';

	include 'app/begin.php';
	maketable(array(
		'self'=>$URL.'/ndb',
		'db'=>$NDB,
		'key'=>'NDB_No',
		'join'=>array(
			'food_des'=>array(),
		),
		'columns'=>array(
			'food_des.NDB_No'=>array('NDB',
				'search'=>'exact',
				'width'=>'40px',
			),
			'food_des.Long_Desc'=>array('Description',
				'search'=>'fulltext',
				'width'=>'500px',
			),
			'food_des.SciName'=>array('Sci.Name',
				'search'=>'fulltext',
				'width'=>'200px',
			),
			'food_des.Refuse'=>array('Refuse',
				'width'=>'40px',
				'postfix'=>'%',
			),
		),
	));
	return include 'app/end.php';
?>
