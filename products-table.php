<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'app/data.php';
	require_once 'lib/maketable/maketable.php';

	include 'app/begin.php';
	maketable(array(
		'self'=>$URL.'/products-table',
		'key'=>'id',
		'join'=>array(
			'product'=>array(),
			array('person',array('id'=>'product.maker'),'left'),
		),
		'where'=>'(product.type IS NULL OR product.type<>1)',
		'columns'=>array(
			'product.barcode'=>array('Barcode',
				'search'=>'left',
				'edit'=>'none',
				'width'=>'85px',
			),
			'product.name'=>array('Product', 'product_name_html',
				'search'=>'mid',
				'edit'=>'none',
				'width'=>'280px',
			),
			'person.name'=>array('Manufacturer','person_name_html',
				'search'=>'mid',
				'edit'=>'none',
				'width'=>'180px',
			),
			'product.typical_units'=>array('Units',
				'class'=>'number',
				'edit'=>'number',
				'tip'=>'Number of units (or pairs if we are talking about shoes, socks etc) that are typically purchased at once',
				'width'=>'40px',
			),
			'product.net_weight'=>array('Weight',
				'class'=>'number',
				'edit'=>'number',
				'postfix'=>'g',
				'width'=>'40px',
			),
			'product.net_volume'=>array('Volume',
				'class'=>'number',
				'edit'=>'number',
				'postfix'=>'ml',
				'width'=>'40px',
			),
			'product.typical_price'=>array('Typical price',
				'class'=>'number',
				'edit'=>'number',
				'postfix'=>'€',
				'width'=>'40px',
			),
		),
		'orderby'=>'product.name',
		'userorder'=>true,

		'update'=>1,
		'updateinc'=>'product.php',
	));
	return include 'app/end.php';
?>
