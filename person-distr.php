<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'app/data.php';
	require_once 'lib/maketable/maketable.php';

	include 'app/begin.php';
	maketable(array(
		'self'=>$URL.'/person-distr',
		'params'=>array(
			'id'=>$row['id'],
		),
		'key'=>'id',
		'join'=>array(
			'person'=>array(
				'id'=>maketable_param('id',$row['id']),
			),
			'product'=>array('distributor'=>'person.id'),
		),
		'columns'=>array(
			'product.name'=>array('Product name', 'product_name_html'),
			'product.barcode'=>array('Barcode'),
		),
	));
	return include 'app/end.php';
?>
