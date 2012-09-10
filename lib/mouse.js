var the_busy_count = 0;
var the_mouse_position = [0,0];
var the_mouse_handlers = {'move':{},'down':{},'up':{}};

document.onmousemove = function(ev) {
	the_mouse_position = get_cursor_position(ev);
	if (the_busy_count > 0) {
		var busy = elem('BUSY');
		busy.style.left = (the_mouse_position[0]+8) + 'px';
		busy.style.top = (the_mouse_position[1]+8) + 'px';
	}
	for (var k in the_mouse_handlers['move']) {
		eval(k);
	}
}

document.onmousedown = function(ev) {
	for (var k in the_mouse_handlers['down']) {
		eval(k);
	}
}

document.onmouseup = function(ev) {
	for (var k in the_mouse_handlers['up']) {
		eval(k);
	}
}

function add_mouse_handler(ev,script) {
	var x = the_mouse_handlers[ev][script];
	if (x == null) x = 0;
	the_mouse_handlers[ev][script] = (x+1);
}

function remove_mouse_handler(ev,script) {
	var x = the_mouse_handlers[ev][script];
	if (x != null) {
		x = x-1;
		if (x==0) {
			delete the_mouse_handlers[ev][script];
		} else {
			the_mouse_handlers[ev][script] = x;
		}
	}
}

function get_cursor_position(ev) {
	if (ev==null && window!=null && window.event!=null) {
		ev = window.event;
	}
	if (ev == null) {
		return the_mouse_position;
	} else if (ev.pageX!=null && ev.pageY!=null) {
		return [ev.pageX,ev.pageY];
	} else if (ev.x!=null && ev.y!=null) {
		var pos = [ev.x,ev.y];
		var de = document.documentElement;
		var bo = document.body;
		if (de != null && bo != null) {
			pos = [pos[0] + (de.scrollLeft || bo.scrollLeft) - (de.clientLeft || 0),
			       pos[1] + (de.scrollTop || bo.scrollTop) - (de.clientTop || 0)];
		}
		return pos;
	} else {
		return the_mouse_position;
	}
}

function busy_on() {
	if (the_busy_count <= 0) {
		var busy = document.createElement('div');
		busy.setAttribute('id','BUSY');
		busy.setAttribute('class','busy');
		busy.style.position = 'absolute';
		busy.style.left = (the_mouse_position[0]+8) + 'px';
		busy.style.top = (the_mouse_position[1]+8) + 'px';
		document.body.appendChild(busy);
	}
	++the_busy_count;
}
function busy_off() {
	--the_busy_count;
	if (the_busy_count <= 0) {
		document.body.removeChild(elem('BUSY'));
	}
}
