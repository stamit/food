<? $AUTH=true;
	require_once 'app/data.php';
	require_once 'lib/maketable/maketable.php';

	include 'app/begin.php';
	maketable(array(
		'self'=>$URL.'/cart/cart-foods',
		'params'=>array(
			'id'=>$form_id,
			'cart_id'=>$cart['id'],
		),
		'key'=>'id',
		'join'=>array(
			'product'=>array(),
		),
		'where'=>'product.type=1',
		'columns'=>array(
			'product.name'=>array('Product',
				'product_name_html',
				'search'=>'mid',
				'width'=>'230px',
			),
			'product.typical_units'=>array('Quantity',
				'product_default_quantity_html',
				'class'=>'number',
				'width'=>'50px',
			),
			'product.typical_price'=>array('Price',
				'class'=>'number',
				'width'=>'45px',
				'prefix'=>'€',
			),
		),
		'hidden'=>array(
			'product.net_weight',
			'product.net_volume',
		),
		'buttons'=>array(
			'add'=>array(
				'include'=>$DIR.'/cart/cart-add.php',
				'extra'=>array(
					'cart.id'=>maketable_param(
						'cart_id', $cart['id']
					),
				),
				'onsuccess'=>
					'maketable_display('.js(maketable_param('id',$form_id).'_table').');'
					.'cart_update_sums('.js(maketable_param('id',$form_id)).');',
			),
		),
		'limit'=>20,
		'orderby'=>'product.name',
		'userorder'=>true,
		'dragdrop'=>true,
		'ondrag' => 'if(data.type=="maketablerow" && data.values["product.id"]!=null'
		                .' && data.values["cart_item.id"]!=null)accept_dragdrop(event,id);',
		'ondrop' => 'if(data.type=="maketablerow"){'
			.'if(data.values["product.id"]!=null'
			     .' && data.values["cart_item.id"]!=null){'
				.'accept_drop(event);'
				.'maketable_delete(data.id,data.values,function(){'
					.'maketable_display(id);'
					.'cart_update_sums('.js(maketable_param('id',$form_id)).');'
				.'})'
			.'}'
		.'}',
	));
	return include 'app/end.php';
?>
