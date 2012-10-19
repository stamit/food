<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'app/data.php';
	require_once 'lib/maketable/maketable.php';

	include 'app/begin.php';
	maketable(array(
		'self'=>$URL.'/receipts-table',
		'key'=>'id',
		'join'=>array(
			'receipt'=>array(
				'user_id'=>sql(intval($_SESSION['user_id'])),
				'parent'=>null,
			),
			array('store',array('id'=>'receipt.store'),'group'),
			array('person',array('id'=>'receipt.person'),'group'),
		),
		'where'=>array(
		),
		'where'=>'receipt.parent IS NULL',
		'columns'=>array(
			'receipt.issued'=>array('Date/time',
				'search'=>'mid',
				'width'=>'11.5em',
			),
			'receipt.amount'=>array('Amount',
				'class'=>'number',
				'postfix'=>' €',
				'width'=>'50px',
			),
			'store.name'=>array('Store',
				'store_name_html',
				'search'=>'mid',
				'width'=>'220px',
			),
			'person.name'=>array('Person',
				'person_name_html',
				'search'=>'mid',
				'width'=>'220px',
			),
		),
		'orderby'=>'receipt.issued',
		'userorder'=>true,

		'update'=>1,
		'updateurl'=>'receipt',

		'delete'=>1,
		'deleteinc'=>'receipt.php',
		'deleteneg'=>true,
	));

	return include 'app/end.php';
?>
