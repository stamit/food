<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'app/data.php';
	require_once 'lib/maketable/maketable.php';

	include 'app/begin.php';
	maketable(array(
		'self'=>$URL.'/person-stores',
		'params'=>array(
			'id'=>$row['id'],
		),
		'key'=>'id',
		'join'=>array(
			'person'=>array(
				'id'=>maketable_param('id',$row['id']),
			),
			'store'=>array('owner'=>'person.id'),
		),
		'columns'=>array(
			'store.name'=>array('Store name', 'store_name_html',
				'edit'=>'text',
				'size'=>48,
			),
			'store.address'=>array('Address',
				'edit'=>'text',
				'size'=>32,
			),
		),
	));
	return include 'app/end.php';
?>
