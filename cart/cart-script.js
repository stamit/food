function cart_update_sums(id) {
	elem(id+'_sums').innerHTML = request(
		the_url+'/cart/cart-sums?'+queryencode({
			'id':elem(id+'_id').value,
			'days':elem(id+'_days').value,
		})
	).responseText;
}
function cart_change(id) {
	if (elem(id+'_id').value==0) {
		elem(id+'_switcher').innerHTML = request(
			the_url+'/cart/cart-switcher',
			queryencode({
				'_ID_':id,
				'_OP_':'create',
			})
		).responseText;
	}

	elem(id+'_main').innerHTML = request(
		the_url+'/cart/cart-main?'+queryencode({
			'_ID_':id,
			'id':elem(id+'_id').value,
		})
	).responseText;
}
function cart_rename(id) {
	elem(id+'_switcher').innerHTML = request(
		the_url+'/cart/cart-switcher?'+queryencode({
			'_ID_':id,
			'_OP_':'rename',
			'id':elem(id+'_id').value,
		})
	).responseText;
	elem(id+'_name').focus();
	elem(id+'_name').select();
	return false;
}
function cart_rename_ok(id) {
	elem(id+'_switcher').innerHTML = request(
		the_url+'/cart/cart-switcher',
		queryencode({
			'_ID_':id,
			'_OP_':'rename',
			'id':elem(id+'_id').value,
			'name':elem(id+'_name').value,
		})
	).responseText;
	return false;
}
function cart_rename_cancel(id) {
	elem(id+'_switcher').innerHTML = request(
		the_url+'/cart/cart-switcher?'+queryencode({
			'_ID_':id,
			'id':elem(id+'_id').value,
		})
	).responseText;
	return false;
}
function cart_delete(id) {
	if(confirm('Are you sure you want to delete this basket;')) {
		elem(id+'_switcher').innerHTML = request(
			the_url+'/cart/cart-switcher',
			queryencode({
				'_ID_':id,
				'_OP_':'delete',
				'id':elem(id+'_id').value,
			})
		).responseText;
		cart_change(id);
	}
	return false;
}
