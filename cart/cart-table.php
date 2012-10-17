<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'lib/maketable/maketable.php';

	if ($cart===null) $cart = fetch(
		'cart.id',
		maketable_param('id',$cart['id'])
	);
	authif($cart['user_id']==$_SESSION['user_id']);

	include 'app/begin.php';
	maketable(array(
		'self'=>$URL.'/cart/cart-table',
		'params'=>array(
			'form_id'=>$form_id,
			'id'=>$cart['id'],
		),
		'key'=>'id',
		'join'=>array(
			'cart_item'=>array(
				'cart'=>maketable_param('id',$cart['id']),
			),
			array('product',array('id'=>'cart_item.product'),'left'),
		),
		'columns'=>array(
			'product.name'=>array('Product',
				'product_name_html',
				'search'=>'mid',
				'edit'=>'none',
				'width'=>'230px',
			),
			'cart_item.quantity'=>array('Quantity',
				'cart_quantity_html',
				'class'=>'number',
				'width'=>'45px',
				'edit'=>'number',
				'size'=>4,
			),
			'cart_item.price'=>array('Price',
				'cart_price_html',
				'class'=>'number',
				'edit'=>'none',
				'width'=>'45px',
			),
			'product.energy*cart_item.multiplier'=>array('Energy',
				'f'=>'quantity_html',
				'class'=>'number',
				'postfix'=>'kcal',
				'width'=>'60px',
			),
			'product.proteins*cart_item.multiplier'=>array('Prot.',
				'f'=>'quantity_html',
				'class'=>'number',
				'postfix'=>'g',
				'width'=>'40px',
			),
			'product.carbohydrates*cart_item.multiplier'=>array('Carb.',
				'f'=>'quantity_html',
				'class'=>'number',
				'postfix'=>'g',
				'width'=>'40px',
			),
/*			'product.sugars*cart_item.multiplier'=>array('Sug.',
				'f'=>'quantity_html',
				'class'=>'number',
				'postfix'=>'g',
				'width'=>'40px',
			),
*/
			'product.fats*cart_item.multiplier'=>array('Fat',
				'f'=>'quantity_html',
				'class'=>'number',
				'postfix'=>'g',
				'width'=>'40px',
			),
/*			'product.fats_saturated*cart_item.multiplier'=>array('S.F.',
				'tip'=>'Saturated fatty acids',
				'f'=>'quantity_html',
				'class'=>'number',
				'postfix'=>'g',
				'width'=>'40px',
			),
			'product.total_fiber*cart_item.multiplier'=>array('Fiber',
				'f'=>'quantity_html',
				'class'=>'number',
				'postfix'=>'g',
				'width'=>'40px',
			),
			'product.sodium*cart_item.multiplier'=>array('Na',
				'f'=>'quantity2_html',
				'class'=>'number',
				'postfix'=>'g',
				'width'=>'40px',
			),
*/
		),
		'hidden'=>array(
			'cart_item.unit',
		),
		'limit'=>10,

		#'dragdrop'=>true,
		'ondrag' => 'if(data.type=="maketablerow" && data.values["product.id"]!=null'
		                .' && data.values["cart_item.id"]==null)accept_dragdrop(event,id);',
		'ondrop' => 'if(data.type=="maketablerow"){'
			.'if(data.values["product.id"]!=null'
			     .' && data.values["cart_item.id"]==null){'
				.'accept_drop(event);'
				.'request(the_url+'.js(
					'/cart/cart-add?cart.id='
					.intval(maketable_param('id',$cart['id']))
				).','
					.'data.values,'
					.'function(){'
						.'maketable_display(id);'
						.'maketable_display(data.id);'
						.'cart_update_sums('.js(maketable_param('form_id',$form_id)).');'
					.'}'
				.');'
			.'}'
		.'}',
		'orderby'=>'product.name',
		'userorder'=>true,

		'update'=>array(0),
		'updateinc'=>'cart-edit.php',
		'onupdate' => 'cart_update_sums('.js(maketable_param('form_id',$form_id)).');',

		'delete'=>array(0),
		'deleteinc'=>'cart-del.php',
		'ondelete' => 'cart_update_sums('.js(maketable_param('form_id',$form_id)).');',
		'deletenoconfirm'=>true,
	));
	return include 'app/end.php';
?>
