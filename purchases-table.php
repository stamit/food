<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'app/data.php';
	require_once 'lib/maketable/fun.php';

	include 'app/begin.php';
	maketable(array(
		'self'=>$URL.'/purchases-table',
		'params'=>array(
			'prodid'=>$prodid,
		),
		'key'=>'id',
		'join'=>array(
			array('receipt',array(
				'user_id'=>sql(intval($_SESSION['user_id'])),
				'parent'=>null
			)),
			array('receipt',array('parent'=>'receipt.id'),'as'=>'item'),
			array('product',array('id'=>'item.product')),
			array('store',array('id'=>'receipt.store'),'left'),
			array('person',array('id'=>'receipt.person'),'left'),
		),
		'where'=>array(
			(maketable_param('prodid',$prodid) ?
				'product.id='.sql(intval(maketable_param('prodid',$prodid)))
			:null),
		),
		'columns'=>array(
			'receipt.issued'=>array('Date',
				'edit'=>'none',
				'width'=>'110px',
			),

			'receipt.person'=>array('From', 'person_or_store_name_html',
				'search'=>'mid',
				'search_in'=>"CONCAT(IFNULL(person.name,''),IFNULL(store.name,''))",
				'edit'=>'none',
				'width'=>'150px',
			),

			'item.product'=>array('Product', 'product_name_html',
				'search'=>'mid',
				'search_in'=>'product.name',
				'edit'=>'dropdown',
				'opts_sql'=>'SELECT id AS value, name AS text'
				           .' FROM product ORDER BY name',
				'width'=>'230px',
			),

			'item.units'=>array('Units',
				'class'=>'number',
				'edit'=>'number',
				'width'=>'30px',
			),

			'item.net_volume'=>array('Volume',
				'class'=>'number',
				'edit'=>'number',
				'postfix'=>'ml',
				'width'=>'40px',
			),

			'item.weight'=>array('Gross',
				'class'=>'number',
				'edit'=>'number',
				'postfix'=>'g',
				'width'=>'40px',
			),

			'item.net_weight'=>array('Net',
				'class'=>'number',
				'edit'=>'number',
				'postfix'=>'g',
				'width'=>'40px',
			),

			'item.amount'=>array('Price',
				'class'=>'number',
				'edit'=>'number',
				'postfix'=>' €',
				'width'=>'40px',
			),

			'1000*item.amount/item.net_volume'=>array('€/l',
				'class'=>'number',
				'f'=>'quantity2_html',
				'postfix'=>' €/l',
				'width'=>'55px',
			),

			'1000*item.amount/item.net_weight'=>array('€/kg',
				'class'=>'number',
				'f'=>'quantity2_html',
				'postfix'=>' €/kg',
				'width'=>'55px',
			),

			'item.notes'=>array(
				'Notes',
				'search'=>'mid',
				'width'=>'100px',
			),
		),
		'hidden' => array(
			'person.name',
			'store.name',
		),
		'orderby'=>'receipt.issued',
		'userorder'=>true,

		'update'=>array(0),
		'updateurl'=>'receipt.php',
	));
	return include 'app/end.php';
?>
