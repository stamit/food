<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'app/data.php';
	require_once 'lib/maketable/maketable.php';

	include 'app/begin.php';
	maketable(array(
		'self'=>$URL.'/stores-table',
		'key'=>'id',
		'join'=>array(
			'store'=>array(),
		),
		'columns'=>array(
			'store.name'=>array('Store name', 'store_name_html',
				'search'=>'mid',
				'width'=>'220px',
			),
			'store.address'=>array('Address',
				'search'=>'mid',
				'width'=>'300px',
			),
		),
		'orderby'=>'store.name',
		'userorder'=>true,

		'update'=>1,
		'updateurl'=>$URL.'/store',
	));
	return include 'app/end.php';
?>
