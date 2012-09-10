var the_keyboard_handlers = {'down':{},'up':{}};

document.onkeydown = function(ev) {
	for (var k in the_keyboard_handlers['down']) {
		eval(k);
	}
}

document.onkeyup = function(ev) {
	for (var k in the_keyboard_handlers['up']) {
		eval(k);
	}
}

function add_keyboard_handler(ev,script) {
	var x = the_keyboard_handlers[ev][script];
	if (x == null) x = 0;
	the_keyboard_handlers[ev][script] = (x+1);
}

function remove_keyboard_handler(ev,script) {
	var x = the_keyboard_handlers[ev][script];
	if (x != null) {
		x = x-1;
		if (x==0) {
			delete the_keyboard_handlers[ev][script];
		} else {
			the_keyboard_handlers[ev][script] = x;
		}
	}
}
