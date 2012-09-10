// should have no dependencies

function foreach(a,f) {
	if (a instanceof Array) {
		for (var i = 0 ; i < a.length ; ++i) {
			r = f.call(a[i],i,a[i]);
			if (r!=null && !r) return false;
		}
	} else {
		for (var i in a) {
			r = f.call(a[i],i,a[i]);
			if (r!=null && !r) return false;
		}
	}
	return true;
}

function max(a,b) { return a<b?b:a; }
function min(a,b) { return a<b?a:b; }

function array_merge(a,b) {  // more like `object_merge'
	var c = {};
	for (var n in a) c[n] = a[n];
	for (var n in b) c[n] = b[n];
	return c;
}

function expr(x) {
	return eval('('+x+')');
}

function js(x) {
	return repr(x);
}

// the opposite of `expr'
// - expr(repr(x)) should be equal to x for any constant expression x
function repr(x) {
	var y = '';
	if (x === undefined) {
		return 'null';  // XXX we have to satisfy everyone
	} else if (x === null) {
		return 'null';
	} else if (typeof x === 'boolean') {
		return x ? 'true' : 'false';
	} else if (typeof x === 'number') {
		return String(x);
	} else if (typeof x === 'string') {
		for (var i = 0 ; i < x.length ; ++i) {
			var c = x.charAt(i);
			switch (c) {
			case '\b': c = '\\b'; break;
			case '\t': c = '\\t'; break;
			case '\n': c = '\\n'; break;
			case '\r': c = '\\r'; break;
			case '\f': c = '\\f'; break;
			case '\u000B': c = '\\u000B'; break;
			case '"': c = '\\"'; break;
			case '\\': c = '\\\\'; break;
			}
			y += c;
		}
		return '"'+y+'"';
	} else if (x instanceof Array) {
		for (var i = 0 ; i < x.length ; ++i) {
			if (i) y += ',';
			y += repr(x[i]);
		}
		return '['+y+']';
	} else {
		var i = 0;
		for (var key in x) {
			if (i) y += ','; else ++i;
			y += repr(key) + ':' + repr(x[key]);
		}
		return '{'+y+'}';
	}
}

function trim(s) {
	return (/^\s*(.*?)\s*/.exec(s))[1];
}

function map(a,fun) {
	var b = [];
	for (var i = 0 ; i < a.length ; ++i)
		b.push(fun(a[i]));
	return b;
}

function range(a,b,c) {
	var x = [];
	if (b === undefined) {
		for (var i = 0 ; i < a ; ++i)
			x.push(i);
	} else if (c === undefined) {
		for (var i = a ; i < b ; ++i)
			x.push(i);
	} else if (a<b) {
		for (var i = a ; i < b ; i += c)
			x.push(i);
	} else {
		for (var i = a+c ; i >= b ; i += c)
			x.push(i);
	}
	return x;
}

function maxf(a,f) {
	var m = a[0];
	var fm = f(m);

	for (var i = 1 ; i < a.length ; ++i) {
		var ai = a[i];
		var fai = f(ai);
		if (fm < fai) {
			m = ai;
			fm = fai;
		}
	}

	return m;
}

function array_has(arr,x) {
	for (var i = 0 ; i < arr.length ; ++i) {
		if (arr[i] == x) {
			return i;
		}
	}
	return -1;
}

function array_merge(a,b) {  // more like `object_merge'
	var c = {};
	for (var n in a) c[n] = a[n];
	for (var n in b) c[n] = b[n];
	return c;
}

function html(str) {
	if (str == null)
		return '';

	if (typeof str != 'string')
		str = String(str);

	var y = '';
	for (var i = 0 ; i < str.length ; ++i) {
		var c = str.charAt(i);
		switch (c) {
		case '&': c = '&amp;'; break;
		case '<': c = '&lt;'; break;
		case '>': c = '&gt;'; break;
		case '"': c = '&quot;'; break;
		case "'": c = '&#039;'; break;
		}
		y = y + c;
	}
	return y;
}

// converts string to array of DOM nodes
function dom(str) {
	var nodes = [];

	var wrap = 0;
	if (/^\s*<(thead|tbody)(\s|>)/.exec(str) != null) {
		wrap = 1;
		str = '<table>'+str+'</table>';
	} else if (/^\s*<tr(\s|>)/.exec(str) != null) {
		wrap = 2;
		str = '<table><tbody>'+str+'</tbody></table>';
	} else if (/^\s*<(td|th)(\s|>)/.exec(str) != null) {
		wrap = 3;
		str = '<table><tbody><tr>'+str+'</tr></tbody></table>';
	}

	var div = document.createElement('div');
	div.innerHTML = str;

	var container = div;
	while (wrap) {
		container = container.childNodes[0];
		--wrap;
	}
	for (var i = 0 ; i < container.childNodes.length ; ++i) {
		nodes.push(container.childNodes[i]);
	}

	return nodes;
}

// (flags&1) : for all smaller unknowns (months days hours etc.) assume maximum (and not minimum) possible values
//             (larger unknowns are always decided as if we were looking for the minimums of the smaller unknowns, so with/without 1 gives continuous range)
// (flags&2) : interpret [0]0-[0]0 as `month-year' (and not as `day-month'/`month-day' depending on $PREF_DATE_FORMAT)
// (flags&4) : prefer recent past dates (and not simply nearest dates)
// (flags&8) : interpret [0]0 as year (and not as day)
// ____-__-DD !(flags&8)  FIXME!!!
// ____-MM-DD !(flags&2)
// ___Y-MM-DD
// __YY-MM-DD
// _YYY-MM-DD
// YYYY-MM-DD
// ____-MM-__ never
// ___Y-MM-__ (flags&2)
// __YY-MM-__ (flags&2)
// _YYY-MM-__
// YYYY-MM-__
// ___Y-__-__ (flags&8)
// __YY-__-__ (flags&8)
// _YYY-__-__
// YYYY-__-__
// __:__:__
// hh:__:__
// hh:mm:__
// hh:mm:ss
function parseDate(str, flags) {
	var x = null;

	if ((flags&8) && x == null) {  // optional
		// [y]y
		x = /^\s*(\d\d?)\s*$/.exec(str);
		if (x != null) {
			x = ['',x[1],'','','','',''];
		}
	}
	if ((flags&2) && x == null) {  // optional
		// [m]m-[y]y
		x = /^\s*(\d\d?)\s*[-\/]\s*(\d\d?)\s*$/.exec(str);
		if (x != null) {
			x = ['',x[2],x[1],'','','',''];
		}
	}
	if (x == null) {
		// [m]m-[y]yyy [hours[:minutes[:seconds]]]
		x = /^\s*(\d\d?)\s*[-\/]\s*(\d\d\d\d?)\s*(?:(?:\s|T)\s*(\d\d?)\s*(?::\s*(\d\d?)\s*(?::\s*(\d\d?)\s*)?)?)?$/.exec(str);
		if (x != null) {
			x = ['',x[2],x[1],'',x[3],x[4],x[5]];
		} else {
			// [hours[:minutes[:seconds]]] [m]m-[y]yyy
			x = /^\s*(?:(\d\d?)\s*(?::\s*(\d\d?)\s*(?::\s*(\d\d?)\s*)?)?(?:\s|T)\s*)?(\d\d?)\s*[-\/]\s*(\d\d\d\d?)\s*$/.exec(str);
			if (x != null) {
				x = ['',x[5],x[4],'',x[1],x[2],x[3]];
			}
		}
		if (x == null) {
			// [y]yyy[-[m]m[-[d]d]] [hours[:minutes[:seconds]]]
			x = /^\s*(\d\d\d\d?)\s*(?:[-\/]\s*(\d\d?)\s*(?:[-\/]\s*(\d\d?)\s*(?:(?:\s|T)\s*(\d\d?)\s*(?::\s*(\d\d?)\s*(?::\s*(\d\d?)\s*)?)?)?)?)?(.*?)\s*$/.exec(str);
		}
	}
	if (x == null) {
		// day[-month[-[[[y]y]y]y] [hours[:minutes[:seconds]]]
		// month[-day[-[[[y]y]y]y] [hours[:minutes[:seconds]]]
		x = /^\s*(\d\d?)(?:\s*[-\/]\s*(\d\d?)\s*(?:[-\/]\s*(\d{1,4})\s*?)?)?(?:(?:\s|T)\s*(\d\d?)\s*(?::\s*(\d\d?)\s*(?::\s*(\d\d?)\s*)?)?)?$/.exec(str);
		if (x != null) {
			x = ['',x[3],x[2],x[1],x[4],x[5],x[6]];
			if ( 0 /*<?php if ($PREF_DATE_FORMAT == 'MDY') print '*'.'/ + 1 /'.'*'; ?>*/) { var tmp = x[3]; x[3] = x[2]; x[2] = tmp; }
		} else {
			// [hours[:minutes[:seconds]]] day[-month[-[[[y]y]y]y] 
			// [hours[:minutes[:seconds]]] month[-day[-[[[y]y]y]y] 
			x = /^\s*(?:(\d\d?)\s*(?::\s*(\d\d?)\s*(?::\s*(\d\d?)\s*)?)?(?:\s|T)\s*)?(\d\d?)\s*[-\/]\s*(\d\d?)\s*(?:[-\/]\s*(\d{1,4})\s*?)?\s*$/.exec(str);
			if (x != null) {
				x = ['',x[6],x[5],x[4],x[1],x[2],x[3]];
				if ( 0 /*<?php if ($PREF_DATE_FORMAT == 'MDY') print '*'.'/ + 1 /'.'*'; ?>*/) { var tmp = x[3]; x[3] = x[2]; x[2] = tmp; }
			}
		}
	}

	var determine_year = function(x,flags) {  // FIXME again
		var cur = new Date();
		var curyear = cur.getFullYear();
		var cury = String(curyear);
		if (x[1]==null || x[1]=='') {
			return new Date().getFullYear();
		} else if (x[1].length<=cury.length) {
			var month = (x[2]!=null&&x[2]!='') ? (parseInt(x[2],10)||null) : null;
			var curmonth = cur.getMonth();
			var day = (x[3]!=null&&x[3]!='') ? (parseInt(x[3],10)||null) : null;
			var curday = cur.getDate();

			var unit = 1;
			for (var i = 0 ; i < x[1].length ; ++i) unit = unit*10;

			var curprefix = (x[1].length<cury.length) ? parseInt(cury.substring(0,cury.length-x[1].length), 10) : 0;
			var nearestyear = null;
			for (var prefix = Math.max(curprefix-1,0) ; prefix <= curprefix+1 ; ++prefix) {
				var year = unit*prefix+parseInt(x[1],10);
				if ( ( !(flags&4) || year<curyear || (year==curyear && (month==null || month<curmonth || (month==curmonth && (day==null || day<curday)))) )
				     && (nearestyear==null || Math.abs(curyear-year)<Math.abs(curyear-nearestyear)) ) {
					nearestyear = year;
				}
			}
			return nearestyear;
		} else {
			return x[1];
		}
	}

	var fill_unknowns = function(x,flags) {  // year is known
		x = map(x.slice(1,7),function(i) { return ((i!=null && i!='') ? (parseInt(i,10)||null) : null); });
		x[1] = (x[1]!=null && x[1]>0) ? x[1]-1 : ((flags&1) ? 11 : 0);
		if (x[3]==null) x[3] = ((flags&1) ? 23 : 0);
		if (x[4]==null) x[4] = ((flags&1) ? 59 : 0);
		if (x[5]==null) x[5] = ((flags&1) ? 59 : 0);

		if (x[2]==null || x[2]==0) {
			if (flags&1) {
				for (x[2]=31 ; x[2]>=28 ; --x[2]) {
					var d = new Date(x[0],x[1],x[2],x[3],x[4],x[5]);
					if (d!=null && d.getDate()==x[2]) {
						break;
					}
				}
			} else {
				x[2] = 1;
			}
		}
		return x;
	}

	if (x!=null && x[3]!='0' && x[3]!='00' && x[2]!='0' && x[2]!='00') {
		x[1] = determine_year(x,flags);
		x = fill_unknowns(x,flags);
		var d = new Date(x[0],x[1],x[2],x[3],x[4],x[5]);
		if (d.getFullYear()==x[0] && d.getMonth()==x[1] && d.getDate()==x[2]
		    && d.getHours()==x[3] && d.getMinutes()==x[4] && d.getSeconds()==x[5]) {
			return d;
		}
	}

	return null;
}
function unparseDate(d, withtime) {
	var Y = String(d.getFullYear());
	var M = String(d.getMonth()+1);
	while (M.length < 2) M = '0'+M;
	var D = String(d.getDate());
	while (D.length < 2) D = '0'+D;

	var h = String(d.getHours());
	while (h.length < 2) h = '0'+h;
	var m = String(d.getMinutes());
	while (m.length < 2) m = '0'+m;
	var s = String(d.getSeconds());
	while (s.length < 2) s = '0'+s;

	if ( 0 /*<?php if ($PREF_DATE_FORMAT == 'MDY') print '*'.'/ + 1 /'.'*'; ?>*/) {
		return M+'/'+D+'/'+Y + (withtime ? ' '+h+':'+m+':'+s : '');
	} else {
		return D+'/'+M+'/'+Y + (withtime ? ' '+h+':'+m+':'+s : '');
	}
}


/******************************************************************************/


function element(x) {
	var z = document.getElementById(x);
	if (typeof z == 'array') z = z[0];
	return z;
}
function elem(x) {
	var z = document.getElementById(x);
	if (typeof z == 'array') z = z[0];
	return z;
}

function attribute(el,y,z) {
	el = (typeof el == 'string') ? element(el) : el;
	var attr = (el.attributes==null?null:el.attributes.getNamedItem(y));
	var value = (attr==null?null:attr.value);

	switch (y) {
	case 'onload': case 'onunload':
	case 'onclick': case 'ondblclick':
	case 'onmousedown': case 'onmouseup': case 'onmouseover': case 'onmousemove': case 'onmouseout':
	case 'onfocus': case 'onblur':
	case 'onkeypress': case 'onkeydown': case 'onkeyup':
	case 'onsubmit': case 'onreset': case 'onselect': case 'onchange':
		if (navigator.userAgent.indexOf('MSIE') != -1) {
			value = String(value).replace(/\n/g,' ');
			var zz = /^\s*function\s+anonymous\s*\(\s*\)\s*{(.*)}\s*$/.exec(value);
			if (zz != null) value = zz[1];
		}
		break;
	}

	if (z !== undefined) {
		switch (y) {
		case 'onload': case 'onunload':
		case 'onclick': case 'ondblclick':
		case 'onmousedown': case 'onmouseup': case 'onmouseover': case 'onmousemove': case 'onmouseout':
		case 'onfocus': case 'onblur':
		case 'onkeypress': case 'onkeydown': case 'onkeyup':
		case 'onsubmit': case 'onreset': case 'onselect': case 'onchange':
			if (navigator.userAgent.indexOf('MSIE') != -1) {
				el.setAttribute(y,function(){return eval(z)});
			} else {
				attr = document.createAttribute(y);
				attr.value = z;
				el.attributes.setNamedItem(attr);
			}
			break;

		default:
			attr = document.createAttribute(y);
			attr.value = z;
			el.attributes.setNamedItem(attr);
		}
	}

	return value;
}

function has_class(el,c) {
	var a = attribute(el,'class');
	var classes = (a!=null ? a.split(/\s+/) : []);
	var j = 0;
	for (var i = 0 ; i < classes.length ; ++i)
		if (classes[i]==c)
			++j;
	return j;
}
function add_class(el,c,nostack) {
	var a = attribute(el,'class');
	var classes = (a!=null ? a.split(/\s+/) : []);

	if (nostack) {
		for (var i = 0 ; i < classes.length ; ++i) {
			if (classes[i]==c) {
				return;
			}
		}
	}

	classes.push(c);
	attribute(el, 'class', classes.join(' '));
}
function remove_class(el,c,nostack) {
	var a = attribute(el,'class');
	if (a != null) {
		var classes = a.split(/\s+/);
		for (var i = classes.length ; i > 0 ;) { --i;
			if (classes[i] == c) {
				classes.splice(i,1);
				if (!nostack) break;
			}
		}
		attribute(el, 'class', classes.join(' '));
	}
}

function get(x) {
	var e = elem(x);
	if (e == null) return undefined;
	if (e.value == null) return undefined;
	return expr(e.value);
}
function set(x,y) {
	elem(x).value = repr(y);
}

function get_rect(obj) {
	if (typeof obj == 'string')
		obj = elem(obj);

	var curleft = 0, curtop = 0, obj2 = obj;
	while (obj2) {
		curleft += obj2.offsetLeft;
		curtop += obj2.offsetTop;
		obj2 = obj2.offsetParent;
	}
	return [curleft,curtop,obj.offsetWidth,obj.offsetHeight];
}

if (navigator.product == "Gecko") {
	Document.prototype.elementFromPoint = function(x, y) {
		this.addEventListener("mousemove", this.elementFromPoint__handler, false);
		var event = this.createEvent("MouseEvents");
		var box = this.getBoxObjectFor(this.documentElement);
		var screenDelta = { x: box.screenX, y: box.screenY };
		event.initMouseEvent("mousemove", true, false, this.defaultView, 0,
		                     x + screenDelta.x, y + screenDelta.y, x, y,
		                     false, false, false, false, 0, null);
		this.dispatchEvent(event);
		this.removeEventListener("mousemove", this.elementFromPoint__handler, false);
		return this.elementFromPoint__target;
	}
	Document.prototype.elementFromPoint__handler = function (event) {
		this.elementFromPoint__target = event.explicitOriginalTarget;

		// reparent target if it is a text node to emulate IE's behavior
		if (this.elementFromPoint__target.nodeType == Node.TEXT_NODE)
			this.elementFromPoint__target = this.elementFromPoint__target.parentNode;

			// change an HTML target to a BODY target to emulate IE's behavior (if we are in an HTML document)
			if (this.elementFromPoint__target.nodeName.toUpperCase() == "HTML" && this.documentElement.nodeName.toUpperCase() == "HTML")
				this.elementFromPoint__target = this.getElementsByTagName("BODY").item(0);

				event.preventDefault();
				event.stopPropagation();
	}
	Document.prototype.elementFromPoint__target = null;
}
function elem_at(x,y) {
	if (x instanceof Array) { y=x[1]; x=x[0]; }

	var sp = scrollpos();
	return document.elementFromPoint(x-sp[0],y-sp[1]);
}

function timeout(t,f) {
	return setTimeout(f,t);
}
function interval(t,f) {
	return setInterval(f,t);
}
function animate(time,callback,finish_callback) {
	var i = 0;
	var timer = interval(15,function(){
		if (i < time) {
			if (callback!=null)
				callback(i/time);
			i += 15;
		} else {
			clearInterval(timer);
			if (finish_callback!=null)
				finish_callback();
		}
	});
	return timer;
}
function rgb_hex(r,g,b) {
	var f = function(n) {
		var s = Math.floor(n).toString(16);
		return ((s.length==1?'0':'')+s.toLowerCase());
	}
	if (g===undefined) g = r;
	if (b===undefined) b = g;
	return ('#'+f(r)+f(g)+f(b));
}


/******************************************************************************/


function urlencode(x) {
	if (typeof x == 'string') {
		return encodeURIComponent(x);
	} else {
		return queryencode(x);
	}
}

function queryencode(stuff) {
	var str = "";
	if (typeof stuff == 'array') {
		for (var i = 0 ; i < stuff.length ; ++i) {
			if (i) str += "&";
			str += encodeURIComponent(stuff[i][0])+"="+encodeURIComponent(stuff[i][1]);
		}

	} else for (var name in stuff) {
		var val = stuff[name];
		if (val != null) {
			if (typeof val != 'string')
				val = repr(val);
			if (str) str += "&";
			str += encodeURIComponent(name)+"="+encodeURIComponent(val);
		}
	}
	return str;
}

function formdata(el,toarray) {
	if (typeof el == 'string')
		el = element(el);

	var data = [];
	formdata_gather(el,data);
	if (!toarray) {
		var result = {};
		for (var i = 0 ; i < data.length ; ++i)
			result[data[i][0]] = data[i][1];
		return result;
	} else {
		return data;
	}
}
function formdata_gather(el,out) {
	var name = attribute(el,'name');
	if (el.nodeName == 'SELECT' ||
	    el.nodeName == 'TEXTAREA') {
		if (!el.disabled) {
			var value = el.value;
			if (name!=null && value!=null) out.push([name,value]);
		}
	} else if (el.nodeName == 'INPUT') {
		if (!el.disabled) {
			var value = null;

			var type = attribute(el,'type');
			if (type==null || type=='text' || type=='password' || type=='hidden' || type=='submit' || type=='button' || type=='image') {
				value = el.value;
				// XXX <image> should give some coordinates: 'name.x' and 'name.y'
			} else if (type=='checkbox' || type=='radio') {
				if (el.checked) {
					value = el.value;
					if (value==null) value = '1';
				}
			}

			// XXX reset, button, file

			if (name!=null && value!=null) out.push([name,value]);
		}

	} else for (var i = 0 ; i < el.childNodes.length ; ++i) {
		formdata_gather(el.childNodes[i],out);
	}
}

function xhr_make(method, url, async) {
	method = method.toLowerCase();

	var req = null;
	if (window.ActiveXObject) {
		try {
			req = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			req = new ActiveXObject("Microsoft.XMLHTTP");
		}
	} else {
		req = new XMLHttpRequest();
	}
	req.open(method, url, async);
	if (method == "post") {
		req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	}
	req.setRequestHeader("X-Requested-With", "XMLHttpRequest");
	return req;
}

function xhr_success(req, timeout) {
	return (timeout != "timeout") ? (req.status>=200&&req.status<300) || req.status==304 || req.status==1223 : 0;
}

function request(query, postdata, callback) {
	var url, method, headers;

	if (postdata != null) {
		if (typeof postdata == 'object') {
			postdata = queryencode(postdata);
		} else if (typeof postdata == 'function') {
			callback = postdata;
			postdata = null;
		} else if (typeof postdata != 'string') {
			throw 'expected object, string or function';
		}
	}

	if (typeof query == 'string') {
		url = query;
		method = (postdata!=null) ? "post" : "get";
		if (postdata == undefined) {
			postdata = null;
		} else if (typeof postdata != 'string' && postdata!=null) {
			postdata = queryencode(postdata);
		}
		headers = [];

	} else if (query instanceof Array) {
		url = query[0];
		method = (postdata!=null) ? "post" : "get";
		headers = query.slice(1);

	} else {
		method = query.method.toLowerCase();
		if (method == 'post') {
			postdata = queryencode(formdata(query));
			url = query.action;
		} else {
			postdata = null;
			url = query.action+'?'+queryencode(formdata(query));
		}
		headers = [];
	}

	var async = (callback!=null);
	var req = xhr_make(method, url, async);
	if (callback != null) {
		req.onreadystatechange = function(timeout) {
			if ( req.readyState == 4 || timeout == "timeout" ) {
				/* this is supposedly a workaround for an IE
				 * bug, but I haven't really seen this bug! */
				/*if (!req.getResponseHeader("Date")) {
					var req2 = xhr_make(method, url, true);

					var ifModifiedSince = req.getResponseHeader("Last-Modified");
					ifModifiedSince = (ifModifiedSince) ? ifModifiedSince : new Date(0);	// January 1, 1970
					req2.setRequestHeader("If-Modified-Since", ifModifiedSince);

					req2.send(postdata);
					if (req2.status != 304) {
						req2.onreadystatechange = function(timeout) {
							if ( req2 != null && (req2.readyState == 4 || timeout == "timeout") ) {
								callback(req2, xhr_success(req, timeout));
								req2.onreadystatechange = null;
								req2 = null;
							}
						}
					}
				} else {*/
					callback(req, xhr_success(req, timeout));
					req = null;
				/*}*/
			}
		}
	}

	req.setRequestHeader('X-Requested-With','XMLHttpRequest');
	for (var i = 0 ; i < headers.length ; ++i)
		req.setRequestHeader(headers[i][0], headers[i][1]);

	req.send(postdata);
	return req;
}

function scrollpos() {
	var f_filterResults = function(n_win, n_docel, n_body) {
		var n_result = n_win ? n_win : 0;
		if (n_docel && (!n_result || (n_result > n_docel)))
			n_result = n_docel;
		return n_body && (!n_result || (n_result > n_body)) ? n_body : n_result;
	}

	return [
		f_filterResults(
			window.pageXOffset ? window.pageXOffset : 0,
			document.documentElement ? document.documentElement.scrollLeft : 0,
			document.body ? document.body.scrollLeft : 0
		),
		f_filterResults(
			window.pageYOffset ? window.pageYOffset : 0,
			document.documentElement ? document.documentElement.scrollTop : 0,
			document.body ? document.body.scrollTop : 0
		),
	];
}

function focus_first(el,selall) {
	if (el == null) {
		return null;
	} else if ( ( el.nodeName == 'INPUT' && el.attributes != null
	              && el.attributes['type'] != null && el.attributes['type'].nodeValue != 'hidden' ) ||
	            el.nodeName == 'TEXTAREA' || el.nodeName == 'BUTTON' ) {
	     	el.focus();
	     	el.select();
		return el;
	} else {
		for (var i = 0 ; i < el.childNodes.length ; ++i) {
			var x = focus_first(el.childNodes[i]);
			if (x != null) return x;
		}
		return null;
	}
}

function disable(el) {
	if (el == null) return;
	switch (el.nodeName) {
	case 'INPUT': case 'SELECT': case 'BUTTON': case 'TEXTAREA':
		if (el.disableCount==null) {
			el.disableCount = (el.disabled ? 2 : 1);
			el.disabled = true;
		} else {
			el.disableCount = el.disableCount+1;
		}
		break;
	default:
		if (el.childNodes!=null) {
			for (var i = 0 ; i < el.childNodes.length ; ++i) {
				disable(el.childNodes[i]);
			}
		}
	}
}

function enable(el) {
	if (el == null) return;
	switch (el.nodeName) {
	case 'INPUT': case 'SELECT': case 'BUTTON': case 'TEXTAREA':
		if (el.disableCount>1) {
			el.disableCount = el.disableCount-1;
		} else {
			el.disableCount = null;
			el.disabled = false;
		}
		break;
	default:
		if (el.childNodes!=null) {
			for (var i = 0 ; i < el.childNodes.length ; ++i) {
				enable(el.childNodes[i]);
			}
		}
	}
}

function fill(el,markup) {
	while (el.childNodes.length)
		el.removeChild(el.childNodes[0]);
	append(el,markup);
}

function replace(el,markup) {
	var nodes = dom(markup);
	for (var i = 0 ; i < nodes.length ; ++i)
		el.parentNode.insertBefore(nodes[i],el);
	el.parentNode.removeChild(el);
}

function append(el,markup) {
	var nodes = dom(markup);
	for (var i = 0 ; i < nodes.length ; ++i)
		el.appendChild(nodes[i]);
}

function remove(el) {
	if (el!=null) el.parentNode.removeChild(el);
}
