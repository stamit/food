function product_update_nutrients(id) {
	var prodid = elem(id+'_id').value;
	disable(id);
	busy_on();
	request(the_url+'/product-nutrients?_ID_='+id+'&id='+prodid,function(req,ok){
		busy_off();
		enable(id);
		elem(id+'_nutrients').innerHTML = req.responseText;
	});
}

function product_postit(id,data) {
	disable(id);
	busy_on();
	request(the_url+'/product',data,function(req,ok){
		busy_off();
		enable(id);
		if (ok) {
			elem(id+'_id').value = eval(req.responseText)[1];
			product_update_nutrients(id);
		} else {
			complain(req.responseText,id);
		}
	});
}

function product_fooddb_import(id) {
	var data = formdata(id);
	data['fooddb_import'] = '1';
	product_postit(id,data);
	return false;
}

function product_fooddb_clear(id) {
	var data = formdata(id);
	data['fooddb_clear'] = '1';
	product_postit(id,data);
	return false;
}

function product_parent_link(id) {
	var data = formdata(id);
	data['parent_link'] = '1';
	product_postit(id,data);
	return false;
}

function product_parent_clear(id) {
	var data = formdata(id);
	data['parent_clear'] = '1';
	product_postit(id,data);
	return false;
}

function product_children_link(id) {
	var data = formdata(id);
	data['children_link'] = '1';
	product_postit(id,data);
	return false;
}

function product_children_clearlink(id) {
	var data = formdata(id);
	data['children_clearlink'] = '1';
	product_postit(id,data);
	return false;
}
