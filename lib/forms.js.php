<? $CACHE=true; $NOLOG=true;
	require_once dirname(__FILE__).'/util.php';
	require_once dirname(__FILE__).'/borders.php';
?> 
// requires mouse.js
// optionally balloon/balloon.js

function keycode(e) {
	return (window.event ? window.event.keyCode : e.which);
}

var constraints = [];
function constrain(form,elem,rule,param) {
	var cns = {'form':form,'elem':element(elem),'rule':rule,'param':param};
	constraints.push(cns);
}
function validate(form, notify) {
	var mistakes=[];

	for (var i = 0 ; i < constraints.length ; ++i) {
		var cns = constraints[i];
		if (cns.form != form)
			continue;

		var elem = element(cns.elem);
		if (elem == null)
			continue;

		switch (cns.rule) {
		case 'int':
			{ var x = parseInt(str,10);
			if ( x != x ) {
				mistakes.push([elem, 'This is not a number.']);
			} }
			break;
		case 'real':
			{ var x = parseFloat(str);
			if (x!=x || x==Infinity) {
				mistakes.push([elem, 'This is not a number.']);
			} }
			break;
		case 'nz':
			if ( elem.value == 0 ) {
				mistakes.push([elem, 'This cannot be empty or zero.']);
			}
			break;
		case 'ne':
			if ( ! elem.value.length ) {
				mistakes.push([elem, 'You must enter something here.']);
			}
			break;
		case 'date':
			if ( parseDate(elem.value) == null ) {
				mistakes.push([elem, 'This is not a valid date.']);
			}
			break;
		case 'fun':
			var result = cns.param(elem);
			if ( result == null || result == '' ) {
				mistakes.push([elem, result]);
			}
			break;
		}
	}
	if (mistakes.length > 0) {
		if (notify) {
			complain(mistakes, form);
		}
		return false;
	}
	return true;
}
function validate_post(id,x,y,z) {
	var el = elem(id);
	var method = attribute(el,'method').toLowerCase();
	var url, postdata, then, fail;
	if (typeof x == 'string') {
		url = x;
		then = y;
		fail = z;
	} else {
		url = el.action;
		then = x;
		fail = y;
	}

	if ( ! validate(id, true) )
		return false;

	if (method == 'post') {
		postdata = queryencode(formdata(el));
	} else {
		url = url+'?'+queryencode(formdata(el));
		postdata = null;
	}

	disable(el);
	busy_on();
	request(url, postdata, function(req,ok) {
		busy_off();
		enable(el);
		if (ok) {
			if (then!=null) then(req,ok);
		} else {
			complain(req.responseText, id);
			if (fail!=null) fail(req,ok);
			return false;
		}
	});
}
function post_form(id,name,value) {
	if ( ! validate(id, true) )
		return false;

	var el = elem(id);
	var method = attribute(el,'method').toLowerCase();
	var url = el.action;
	var postdata = formdata(el);

	if (name!=null && value!=null) {
	       postdata[name] = value;
	}

	if (method == 'post') {
		postdata = queryencode(postdata);
	} else {
		url = url+'?'+queryencode(postdata);
		postdata = null;
	}

	disable(el);
	busy_on();
	request(url, postdata, function(req,ok) {
		busy_off();
		enable(el);
		if (ok) {
			if (req.responseText != "")
				window.location.href=eval(req.responseText)[0];
		} else {
			complain(req.responseText, id);
			return false;
		}
	});
	return false;
}

function complain(mistakes, id) {
	if (typeof mistakes == 'string') {
		mistakes = expr(mistakes);
	}

	foreach(mistakes,function(i,mist){
		if (typeof mist[0] == 'string') {
			var x = elem(id+'_'+mist[0]);
			if (x==null || style(x,'display')=='none') {
				// for fckeditor
				x = elem(id+'_'+mist[0]+"___Frame");
			}
			mist[0] = x;
		}
	});

	var tosay=[], topoint=null;

	foreach(mistakes,function(i,mist){
		if (mist.length >= 2) {
			if (mist[0]!=null && mist[0]!='') {
				if (topoint == null)
					topoint = mist;
			} else {
				tosay.push(mist[1]);
			}
		} else if (mist.length == 1) {
			tosay.push(mist[0]);
		}
	});

	var alerted = false;
	var something = false;

	//
	// show balloon over first mistake (or simply alert)
	//
	if (topoint!=null && topoint[0]!=null) {
		var el = topoint[0];
		try {
			new_balloon(topoint[1], el, 53*topoint[1].length+2000);
			something = true;
		} catch(x) {
			alert(topoint[1]);
			alerted = true;
		}
		el.focus();
	}

	//
	// write inside <div name="form_mistakes"> and show it
	//
	if (!alerted) {
		if (tosay.length > 1) {
			alert('* '+tosay.join("\n* "));
		} else if (tosay.length > 0) {
			alert(tosay[0]);
		}
	}
}


/******************************************************************************/


function open_window(url) {
	/*var digest = function(s) {
		var x = '';
		for (var i = 0 ; i < s.length ; ++i) {
			if (/[a-zA-Z]/.test(s.charAt(i))) {
				x += s.charAt(i);
			} else {
				x += '_'+s.charCodeAt(i)+'_';
			}
		}
		return x;
	}

	var win = window.open('', 'window_'+digest(url));
	if (win.location.href == 'about:blank') {
		win.location.href = url;
	}
	win.focus();*/

	window.open(url, 'rightpane');
}


/******************************************************************************/


var the_popups = {};
var the_popup_counter = 0;

function new_popup(pos, innerHTML) {
	var div = document.createElement('div');
	attribute(div,'class','popup');
	style(div,'position','absolute');
	style(div,'display','none');
	div.innerHTML = innerHTML;
	document.body.appendChild(div);

	style(div,'opacity','0');
	style(div,'display',null);

	var x = Math.min(Math.max(0,pos[0] - div.offsetWidth/2),
	                 document.body.offsetWidth-div.offsetWidth-1);
	var y = Math.max(0,pos[1] - div.offsetHeight/2);
	style(div,'left',x+'px');
	style(div,'top',y+'px');

	style(div,'display','none');
	style(div,'opacity',null);
	return div;
}

function open_popup(url, onok) {
	if (the_popups[url] != null) {
		focus_popup(the_popups[url]);
	} else {
		var id = 'POPUP_'+(the_popup_counter++);
		var zorder = the_popup_counter;

		the_popups[url] = id;
	
		if (/\?/.test(url))
			url2 = url + '&_POPUP_='+urlencode(id);
		else
			url2 = url + '?_POPUP_='+urlencode(id);

		busy_on();
		request(url2,null,function(req,ok) {
			busy_off();

			if (ok) {
				var xtra = ' onselectstart="return false"'+
				           ' onmousedown="return drag_popup('+html(repr(id))+')"'+
				           ' ondblclick="return close_popup('+html(repr(id))+')"';
				var before = <?php print js(begin_borders2_str('', dereference("balloon/baloon2"),'.png', 7,5,7,5, 'white', '<!--BORDERS-->', 'cursor:move')); ?>
					.replace(/<!--BORDERS-->/g,xtra);
				var after = <?php print js(end_borders2_str(dereference("balloon/baloon2"),'.png', 7,5,7,5, 'white', '<!--BORDERS-->', 'cursor:move')); ?>
					.replace(/<!--BORDERS-->/g,xtra);
				var div = new_popup(the_mouse_position,
					hidden(id+'_url', url)
					+hidden(id+'_onok', onok)
					+before
					+req.responseText
					+after
				);
				convert_links(div);
				div.setAttribute('id',id);
				div.setAttribute('zIndex',zorder);
				$(div).fadeIn(function(){
					focus_first(div);
				});
			} else {
				the_popups[url] = null;
				complain(req.responseText);
			}
		});

		return id;
	}
}

var the_old_mouse_position;
function drag_popup(id) {
	the_old_mouse_position = [the_mouse_position[0],the_mouse_position[1]];

	$(document.body).bind('mousemove',id,on_popup_drag);
	$(document.body).bind('mouseup',id,on_popup_focus);

	return false;
}
function on_popup_drag(e) {
	var div = elem(e.data);

	var x = Math.floor(div.style.left.substr(0,div.style.left.length-2)) + (e.pageX - the_old_mouse_position[0]);
	var y = Math.floor(div.style.top.substr(0,div.style.top.length-2)) + (e.pageY - the_old_mouse_position[1]);

	div.style.left = x + 'px';
	div.style.top = y + 'px';

	the_old_mouse_position = [e.pageX,e.pageY];

	$(document.body).unbind('mouseup',on_popup_focus);
	$(document.body).bind('mouseup',e.data,on_popup_enddrag);
}
function on_popup_enddrag(e) {
	$(document.body).unbind('mousemove',on_popup_drag);
	$(document.body).unbind('mouseup',on_popup_enddrag);
}
function on_popup_focus(e) {
	focus_popup(e.data);
	$(document.body).unbind('mousemove',on_popup_drag);
	$(document.body).unbind('mouseup',on_popup_focus);
}

function focus_popup(id) {
	var div = elem(id);
	if (div != null)
		focus_first(div);
}

function close_popup(id,isok) {
	if (id instanceof Array) {
		id = the_popups[id];
		if (id == null) {
			return;
		}
	}

	if (isok) {
		var x = get(id+'_onok');
		if (x != null) {
			eval(x);
		}
	}
	the_popups[get(id+'_url')] = null;
	var div = elem(id);
	$(div).fadeOut(function(){
		document.body.removeChild(div);
	});
}

function convert_links(el) {
	if (el != null) {
		if ( el.nodeName == 'A' ) {
			var cls = attribute(el,'class');
			if (cls != null) {
				var clss = cls.split(/\s+/);
				if ( array_has(clss,'popup') >= 0 ) {
					attribute(el,'onclick','open_popup('
						+repr(attribute(el,'href'))+','
						+repr(attribute(el,'onclick'))
					+'); return false;');
					attribute(el,'href','#');

				} else if ( array_has(clss,'window') >= 0 ) {
					attribute(el,'onclick','open_window('+repr(attribute(el,'href'))+');return false;');
					attribute(el,'href','#');
				}
			}

		} else for (var i = 0 ; i < el.childNodes.length ; ++i) {
			convert_links(el.childNodes[i]);
		}
	}
}

/******************************************************************************/

function remove(x) {
	var xx = (typeof x == 'string') ? element(x) : x;
	xx.parentNode.removeChild(xx);
}

function toid(x) {
	if (x instanceof Array) {
		return x.join('_');
	} else {
		return String(x);
	}
}

function checkbox(name,checked,label,disabled,onchange) {
	if (checked === undefined) checked=false;
	if (disabled === undefined) disabled=false;
	if (onchange === undefined) onchange='';

	return '<span><input id="'+html(name)+'" type="checkbox" name="'+html(name)+'" value="1"'
		+(checked?' checked="checked"':'')
		+(disabled?' disabled="disabled"':'')
		+(onchange?' onclick="'+html(onchange)+'"':'')
		+' class="tickbox checkbox"'
	+'/>'
		+(label.length?'<label for="'+html(name)+'">'+label+'</label>':'')
	+'</span>';
}

function radio(name,value,label,checked,disabled,onchange) {
	if (checked === undefined) checked=false;
	if (disabled === undefined) disabled=false;
	if (onchange === undefined) onchange='';

	return '<span><input type="radio" id="'+html(name)+html(value)+'" name="'+html(name)+'" value="'+html(value)+'"'
		+(checked?' checked="checked"':'')
		+(disabled?' disabled="disabled"':'')
		+(onchange?' onclick="'+html(onchange)+'"':'')
		+' class="tickbox radio"'
	+'/>'+ (label?'<label for="'+html(name)+html(value)+'">'+label+'</label>':'')+'</span>';
}

function dropdown_opt_text(opt) {
	if (typeof opt == 'string') {
		return opt;
	} else if (opt instanceof Array) {
		return opt[1];
	} else if (opt['text'] != undefined) {
		return opt.text;
	} else if (opt['name'] != undefined) {
		return opt.name;
	} else if (opt['value'] != undefined) {
		return opt.value;
	}
}

function dropdown_opt(opt) {
	if (typeof opt == 'string') {
		return {'value':opt, 'text':dropdown_opt_text(opt)};
	} else if (opt instanceof Array) {
		return {'value':String(opt[0]), 'text':dropdown_opt_text(opt[1])};
	} else if (opt['value'] != undefined) {
		return {'value':opt.value, 'text':dropdown_opt_text(opt)};
	} else if (opt['name'] != undefined) {
		return {'value':opt.name, 'text':dropdown_opt_text(opt)};
	} else if (opt['text'] != undefined) {
		return opt.text;
	}
}

function dropdown_opts(obj) {
	var opts = [];
	if ( ! (obj instanceof Array) ) {
		for (var prop in obj) {
			var x = obj[prop];
			if (typeof x != 'string') {
				x = prop;
			}
			opts.push(dropdown_opt([prop,x]))
		}
	} else {
		for (var i = 0 ; i < obj.length ; ++i)
			opts.push(dropdown_opt(obj[i]));
	}
	return opts;
}

function dropdown(id, value, opts, onchange, extra, onblur) {
	opts = dropdown_opts(opts,extra);
	if (extra !== undefined)
		opts = dropdown_opts(extra).concat(opts);

	var str = '<select id="'+html(id)+'" ';
	if (onchange != null) str += ' onchange="'+html(onchange)+'"';
	if (onblur != null) str += ' onblur="'+html(onblur)+'"';
	str += '>';
	if (extra === undefined) {
		str += '<option value=""></option>';
	}
	for (var i = 0 ; i < opts.length ; ++i) {
		var opt = opts[i];
		str += '<option value="'+html(opt.value)+'"';
		if (value == opt.value) str += ' selected="selected"';
		str += '>'+html(opt.text)+'</option>';
	}
	return str+'</select>';
}

function hidden(id, value) {
	return '<input type="hidden" id="'+html(toid(id))+'" value="'+html(repr(value))+'" />';
}

function input(id, value, length, disabled, onchange, onfocus, onblur, onclick) {
	if (id instanceof Array)
		id = id[0]+'_'+id[1];
	return '<input id="'+html(id)+'"'
		+' value="'+html(value)+'"'
		+(length<0?' size="'+html(-length)+'"':'')
		+(length>0?' maxlength="'+html(length)+'" size="'+html(length)+'"':'')
		+(disabled?' disabled="disabled"':'')
		+(onchange?' onchange="'+html(onchange)+'"':'')
		+(onfocus?' onfocus="'+html(onfocus)+'"':'')
		+(onblur?' onblur="'+html(onblur)+'"':'')
		+(onclick?' onclick="'+html(onclick)+'"':'')
		+' class="textarea"'
	+' />';
}

var __the_calendar__ = null;
function dateinput(id, value, disabled, onchange) {
	if (id instanceof Array)
		id = id[0]+'_'+id[1];
	return input(id, value, -10, disabled, onchange, 'on_dateinput_focus('+repr(id)+')', 'on_dateinput_blur('+repr(id)+')');
}
function on_dateinput_focus(id) {
	var el = element(id);

	if (__the_calendar__ != null) {
		__the_calendar__.destroy();
		__the_calendar__ = null;
	}
	__the_calendar__ = new Calendar(1, null,
		function(c, value) {
			element(id).value = value;
			/*if (__the_calendar__ != null) {
				__the_calendar__.destroy();
				__the_calendar__ = null;
			}*/
		},
		function(c) {
			if (__the_calendar__ != null) {
				__the_calendar__.destroy();
				__the_calendar__ = null;
			}
		}
	);
	__the_calendar__.weekNumbers = false;
	__the_calendar__.showsTime = false;
	__the_calendar__.create();
	__the_calendar__.parseDate(el.value)
	__the_calendar__.showAtElement(el);
}
function on_dateinput_blur(id) {
	if (__the_calendar__ != null) {
		__the_calendar__.destroy();
		__the_calendar__ = null;
	}
}

function datetimeinput(id, value, disabled, onchange) {
	if (id instanceof Array)
		id = id[0]+'_'+id[1];
	return input(id, value, -16, disabled, onchange, 'on_datetimeinput_focus('+repr(id)+')', 'on_datetimeinput_blur('+repr(id)+')');
}
function on_datetimeinput_blur(id) {
	if (__the_calendar__ != null) {
		__the_calendar__.destroy();
		__the_calendar__ = null;
	}
}
function on_datetimeinput_focus(id) {
	var el = element(id);

	if (__the_calendar__ != null) {
		__the_calendar__.destroy();
		__the_calendar__ = null;
	}
	__the_calendar__ = new Calendar(1, null,
		function(c, value) {
			element(id).value = value;
			/*if (__the_calendar__ != null) {
				__the_calendar__.destroy();
				__the_calendar__ = null;
			}*/
		},
		function(c) {
			if (__the_calendar__ != null) {
				__the_calendar__.destroy();
				__the_calendar__ = null;
			}
		}
	);
	__the_calendar__.weekNumbers = false;
	__the_calendar__.showsTime = true;
	__the_calendar__.setDateFormat('%Y-%m-%d %H:%M');
	__the_calendar__.create();
	__the_calendar__.parseDate(el.value)
	__the_calendar__.showAtElement(el);
}

function textarea(name, value, cols, rows, disabled, onchange) {
	var prefix=null;
	if (name instanceof Array) {
		prefix = name[0];
		name = name[1];
	}
	return '<textarea name="'+html(name)+'"'
		+(prefix!==null ? ' id="'+html(prefix+'_'+name)+'"' : '')
		+(cols>0?' cols="'+html(cols)+'"':'')
		+(rows>0?' rows="'+html(rows)+'"':'')
		+(disabled?' disabled="disabled"':'')
		+(onchange?' onchange="'+html(onchange)+'"':'')
		+' class="textarea"'
	+'>'
		+html(value)
	+'</textarea>';
}

function icon(name, filename,alt, onclick) {
	var prefix=null;
	if (name instanceof Array) {
		prefix = name[0];
		name = name[1];
	}
	return '<button name="'+html(name)+'"'
		+(prefix!==null ? ' id="'+html(prefix+'_'+name)+'"' : '')
		+' type="button"'
		+' class="button icon"'
		+(onclick?' onclick="'+html(onclick)+'"':'')
	+'>'
		+img(filename,alt)
	+'</button>';
}

function img(filename,alt) {
	return '<img src="'+html(filename)+'" alt="'+html(alt)+'" />'
}
