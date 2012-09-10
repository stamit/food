var the_drag_op = null;

function drag_operation(data, el, bounds, offset) {
	this.data = data;
	this.element = elem(el);
	this.container = null;
	this.bounds = bounds;
	this.offcur = null;
	this.offtarget = offset;
	this.oldaccepting = null;
	this.accepting = null;

	this.begin = function() {
		var rect = get_rect(this.element);
		var container = this.container = document.createElement('DIV');
		this.container.style['position'] = 'absolute';
		this.container.style['padding'] = '0';
		this.container.style['left'] = rect[0] + 'px';
		this.container.style['top'] = rect[1] + 'px';
		this.container.style['width'] = rect[2] + 'px';
		this.container.style['height'] = rect[3] + 'px';
		this.container.appendChild(drag_clone(this.element,rect));
		document.body.appendChild(this.container);
		this.element.style['visibility'] = 'hidden';

		var offcur = this.offcur = [rect[0]-the_mouse_position[0], rect[1]-the_mouse_position[1]];
		var offorigin = [this.offcur[0],this.offcur[1]];
		var offtarget = this.offtarget;
		this.animate(100,function(t){
			offcur[0] = offorigin[0] + (offtarget[0]-offorigin[0])*t;
			offcur[1] = offorigin[1] + (offtarget[1]-offorigin[1])*t;
			container.style['left'] = (the_mouse_position[0]+offcur[0]) + 'px';
			container.style['top'] = (the_mouse_position[1]+offcur[1]) + 'px';
		},function(){
			offcur[0] = offtarget[0];
			offcur[1] = offtarget[1];
			container.style['left'] = (the_mouse_position[0]+offtarget[0]) + 'px';
			container.style['top'] = (the_mouse_position[1]+offtarget[1]) + 'px';
		});
	}

	this.animate = function(t,f,g) {
		if (this.animation!=null) {
			clearInterval(this.animation);
			this.animation = null;
		}

		if (t) {
			var thing = this;
			this.animation = animate(t,f,function(){
				thing.animation = null;
				if (g!=null) return g();
			});
		} else {
			if (g!=null) g();
		}
	}

	this.onmousemove = function(e) {
		if (this.container != null) {
			this.container.style['left'] = (the_mouse_position[0]+this.offcur[0]) + 'px';
			this.container.style['top'] = (the_mouse_position[1]+this.offcur[1]) + 'px';

			this.accepting = null;
			var target = elem_at(the_mouse_position);
			while (target!=null) {
				var code = attribute(target,'onmousemove');
				if (code!=null) {
					var event = {'dragdrop':true, 'data':this.data, 'drag_op':this};
					if (eval(code)) {
						this.accepting = target;
						break;
					}

					if (this.accepting != null) {
						break;
					}
				}

				target = target.parentNode;
			}

			if (this.oldaccepting != this.accepting) {
				if (this.oldaccepting != null)
					remove_class(this.oldaccepting,'draghighlight');
				if (this.accepting != null)
					add_class(this.accepting,'draghighlight');
			}

			this.oldaccepting = this.accepting;

		} else if (the_mouse_position[0]<this.bounds[0] || the_mouse_position[0]>(this.bounds[0]+this.bounds[2]) ||
		           the_mouse_position[1]<this.bounds[1] || the_mouse_position[1]>(this.bounds[1]+this.bounds[3])) {
			this.begin();
		}
	}

	this.end = function() {
		if (this.container == null) return;

		var target;
		var event = {'dragdrop':true, 'data':this.data, 'drag_op':this};
		if (this.accepting == null) {
			target = elem_at(the_mouse_position);
			while (target!=null) {
				var code = attribute(target,'onmouseup');
				if (code!=null) {
					if (eval(code)) {
						this.accepting = target;
						break;
					}
	
					if (this.accepting != null) {
						break;
					}
				}
	
				target = target.parentNode;
			}
		} else {
			target = this.accepting;
			this.accepting = null;
			var code = attribute(target,'onmouseup');
			if (code!=null && eval(code)) {
				this.accepting = target;
			}
		}

		if ( this.accepting!=null && this.accepting!=document ) {
			this.drop(this.accepting);
		} else {
			this.cancel();
		}

		if (this.oldaccepting!=null) {
			remove_class(this.oldaccepting,'draghighlight');
			this.oldaccepting = null;
		}
	}

	this.cancel = function() {
		var element = this.element;
		var container = this.container;
		var old_place = get_rect(this.element);
		this.animate(0);
		this.animation = drag_animate_move(container, old_place, 200, function(){
			element.style['visibility'] = '';
			document.body.removeChild(container);
		});
	}

	this.drop = function(target) {
		var container = this.container;
		if (target!=null && target!='') {
			this.animate(0);
			this.animation = drag_animate_move(container,get_rect(target),200,function(){
				document.body.removeChild(container);
			});
		} else {
			this.animate(0);
			this.animation = drag_animate_fadeout(container,200,function(){
				document.body.removeChild(container);
			});
		}
	}
}

function begin_drag(id,data) {
	if (the_drag_op!=null) return;

	the_drag_op = new drag_operation(data, id,
		[the_mouse_position[0]-3, the_mouse_position[1]-3, 6, 6],
		[6, 6]
	);

	add_mouse_handler('move','on_drag()');
	add_mouse_handler('up','end_drag()');

	return false;
}

function end_drag(e) {
	remove_mouse_handler('move','on_drag()');
	remove_mouse_handler('up','end_drag()');

	the_drag_op.end();
	the_drag_op = null;
}

function on_drag(e) {
	the_drag_op.onmousemove(e);
}
function accept_drag(event,id) { event.drag_op.accepting = (id!=null?id:''); }
function accept_drop(event,id) { event.drag_op.accepting = (id!=null?id:''); }
function accept_dragdrop(event,id) { event.drag_op.accepting = (id!=null?id:''); }

function drag_clone(el,rect) {
	var clone = el.cloneNode(true);
	if (el.nodeName == 'TR') {
		var a=el.firstChild, b=clone.firstChild;
		while (a!=null && b!=null) {
			var r = get_rect(a);
			b.style['width'] = (r[2]-getStyle(a,'padding-left',1)-getStyle(a,'padding-right',1)) + 'px';
			a = a.nextSibling;
			b = b.nextSibling;
		}

		var tbl = document.createElement('TABLE');
		if (rect != null) tbl.style['width'] = rect[2] + 'px';
		var tbo = document.createElement('TBODY');
		tbo.appendChild(clone);
		tbl.appendChild(tbo);
		return tbl;
	} else {
		return clone;
	}
}

function drag_animate_move(el,target,time,callback) {
	var origin = get_rect(el);
	return animate(time,function(t){
		el.style['left'] = ( origin[0] + (target[0]-origin[0])*t ) + 'px';
		el.style['top'] = ( origin[1] + (target[1]-origin[1])*t ) + 'px';
		el.style['width'] = ( origin[2] + (target[2]-origin[2])*t ) + 'px';
		el.style['height'] = ( origin[3] + (target[3]-origin[3])*t ) + 'px';
	},callback);
}

function drag_animate_fadeout(el,time,callback) {
	return animate(time,function(t){
		el.style['opacity'] = String(1 - t);
	},callback);
}
