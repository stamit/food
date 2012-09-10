// requires lib/util.js, lib/mouse.js

function maketable_display(id, newparams, then) {
	return maketable_request(id, 'GET', newparams, then);
}

function maketable_request(id, method, newparams, then) {
	var newstate = formdata(id);
	delete newstate['_STACK_'];
	for (name in newstate) {
		if (name.substr(0,3)=='id_'
		    && (newparams==null || name.substr(3)!=newparams['part'])) {
			delete newstate[name];
		}
	}
	if (newparams != null) newstate = array_merge(newstate,newparams);

	var url = get(id+'_self');
	var postdata = queryencode(newstate);
	if (method.toUpperCase()!='POST') {
		if (url.indexOf('?')>=0) {
			url = url + '&' + postdata;
		} else {
			url = url + '?' + postdata;
		}
		postdata = null;
	}

	busy_on();
	disable(elem(id));
	request([url,['X-ID',id]], postdata, function(req,ok){
		enable(elem(id));
		busy_off();
		if (ok) {
			var partname = (newparams!=null) ? newparams['part'] : null;
			var part = (partname!=null ? elem(id+'_'+partname) : elem(id));
			replace(part,req.responseText);

			var ondom = get(id+'_ondom');
			if (ondom != null) eval(ondom);

			if (then!=null) then();
		} else {
			var pid = newstate['part'];
			if (pid==null) {
				pid = id;
			} else {
				pid = id+'_'+pid;
			}
			complain(req.responseText, pid);
		}
	});
}

function maketable_confirm_and_delete(id, key, that) {
	if (confirm( get(id+'_deletemsg').replace('{1}',that) )) {
		maketable_delete(id, key, null);
	}
	return false;
}

function maketable_insert(id, row, then) {
	maketable_request(id, 'POST', {
		'op':'insert',
		'row':row
	}, function(){
		if (then!=null) then();
	});
}

function maketable_delete(id, key, then) {
	var ondeleting = get(id+'_ondeleting');
	if (ondeleting!=null) eval('var key='+repr(key)+';'+ondeleting);

	maketable_request(id, 'POST', {
		'op':'delete',
		'key':key
	}, function(){
		if (then!=null) then();

		var ondelete = get(id+'_ondelete');
		if (ondelete!=null) eval('var key='+repr(key)+';'+ondelete);
	});
}

function maketable_reorder(id,reorderfrom,reorderto) {
	maketable_request(id, 'POST', {
		'op':'reorder',
		'reorderfrom':reorderfrom,
		'reorderto':reorderto
	});
}

function maketable_dd_ord(drop,event,id,row,key) {
	if (event==null || !event.dragdrop) return;

	if (event.data.type=='maketablerow' && id==event.data.id) {
		accept_dragdrop(event,id+'_'+row);
		if (drop) {
			var of = get(id+'_orderfield');
			maketable_reorder(id,event.data.values[of],key[of]);
		}
	} else {
		maketable_dd_row(drop,event,id,row,key);
	}
}

function maketable_dd_row(drop,event,id,row,key) {
	if (event==null || !event.dragdrop) return;

	var callback = get(id+(drop?'_rowondrop':'_rowondrag'));
	if (callback != null) {
		var data = event.data;
		var rowid = id+'_'+row;
		eval(callback);
	} else {
		maketable_dd(drop,event,id);
	}
}

function maketable_dd(drop,event,id) {
	if (event==null || !event.dragdrop) return;

	var callback = get(id+(drop?'_ondrop':'_ondrag'));
	if (callback != null) {
		var data = event.data;
		eval(callback);
	}
}

function maketable_begin_edit(id,rowid) {
	var x = {};
	x['edit_'+rowid] = '1';
	x['part'] = String(rowid);
	maketable_request(id,'GET',x,function(){
		focus_first(elem(id+'_'+rowid));
	});
	return false;
}
function maketable_finish_edit(id,rowid) {
	var x = formdata(elem(id+'_'+String(rowid)));
	x['ok_'+rowid] = '1';
	x['cancel_'+rowid] = null;
	x['part'] = String(rowid);
	maketable_request(id,'POST',x,function(){
		elem(id+'_edit'+'_'+rowid).focus();

		var onupdate = get(id+'_onupdate');
		if (onupdate!=null) eval('var key='+x['id_'+rowid]+';'+onupdate);
	});
	return false;
}
function maketable_cancel_edit(id,rowid) {
	var x = formdata(elem(id+'_'+String(rowid)));
	x['ok_'+rowid] = null;
	x['cancel_'+rowid] = '1';
	x['part'] = String(rowid);
	maketable_request(id,'GET',x,function(){
		elem(id+'_edit'+'_'+rowid).focus();
	});
	return false;
}

function maketable_button(id,btn,rowid) {
	var x = {};
	x[btn+'_'+rowid] = '1';
	x['part'] = String(rowid);
	maketable_request(id,'POST',x,function(){
		var el = elem(id+'_onsuccess_'+btn);
		if (el!=null) {
			eval(el.value);
		}
	});
	return false;
}
