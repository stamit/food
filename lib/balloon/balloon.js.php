<?php
	require_once dirname(__FILE__).'/../util.php';
	require_once dirname(__FILE__).'/../borders.php';
?> 
//
// requires keyboard.js, mouse.js and style.js
//

var the_balloon_count=0;

function new_balloon(html_content, where_to_point, dt, direction) {
	var pelem;
	var rect;
	if (where_to_point instanceof Array) {
		pelem = null;
		rect = where_to_point;
	} else if (typeof(where_to_point) == 'string') {
		pelem = element(where_to_point);
		rect = get_rect(pelem);
	} else {
		pelem = where_to_point;
		rect = get_rect(pelem);
	}
	var x = Math.floor(rect[0]);
	var y = Math.floor(rect[1]);

	var cnt = <?php
		print javascript_string(begin_borders2_str('', dereference('baloon2'),'.png', 7,5,7,5, 'white'));
	?>+html_content+<?php
		print javascript_string(end_borders2_str(dereference('baloon2'),'.png', 7,5,7,5, 'white'));
	?>;

	var div = document.createElement('div');
	div.id = 'BALLOON_'+(the_balloon_count++);
	style(div,'position','absolute');
	style(div,'display','none');
	style(div,'width','24em');
	style(div,'cursor','default');
	style(div,'textAlign','left');
	document.body.appendChild(div);  // early

	var inneroffs = (dt>=0?4:0);
	switch (direction) {
	case 1:
		var dx = 10;
		var dy = 24;
		cnt = '<img alt="" src="<?php print html(dereference('baloon-arrow1.png')) ?>" style="position:relative; left:-'+dx+'px; top:'+dy+'px;"/>'+cnt;
		if (rect.length==4) {
			x = rect[0]+rect[2]-inneroffs;
			y = rect[1]+rect[3]*3/5;
		}
		x += dx;
		y -= dy;
		break;
	case 2:
		var dx = 15;
		var dy = 3;
		cnt = cnt+'<img alt="" src="<?php print html(dereference('baloon-arrow2.png')) ?>" style="position:relative; left:'+dx+'px; top:-'+dy+'px;"/>';
		div.innerHTML = cnt;
		style(div,'display','');
		style(div,'opacity','0');
		if (rect.length==4) {
			x = rect[0]+rect[2]*3/5;
			y = rect[1]+inneroffs;
		}
		x -= dx;
		y -= div.offsetHeight-dy-2;
		style(div,'display','none');
		style(div,'opacity','');
		break;
	default:
		cnt = '<img alt="" src="<?php print html(dereference('baloon-arrow.png')) ?>" style="position:relative; left:12px; top:1px;"/>'+cnt;
		x-=12;
		y-=1;
		if (rect.length==4) {
			x = rect[0]+rect[2]*3/5;
			y = rect[1]+rect[3]-inneroffs;
		}
		if (pelem.type == 'text') {
			x = Math.min(x, rect[0] + pelem.value.length*rect[3]*0.41*3/5);
		}
		break;
	}

	style(div,'left',x+'px');
	style(div,'top',y+'px');
	div.innerHTML = cnt;

	if (dt > 0) {
		style(div,'opacity',0);
		style(div,'display','');
		animate(250,function(x){
			style(div,'opacity',x);
		},function(x){
			style(div,'opacity','');
			timeout(dt,function(){
				animate(500,function(x){
					style(div,'opacity',1-x);
				},function(){
					del_balloon(div);
				});
			});
		});
	}

	add_keyboard_handler('down','del_balloon(elem('+repr(div.id)+'))');
	add_mouse_handler('down','del_balloon(elem('+repr(div.id)+'))');

	return div;
}

function del_balloon(balloon) {
	if (balloon != null) {
		document.body.removeChild(balloon);
		add_keyboard_handler('down','del_balloon(elem('+repr(balloon.id)+'))');
		add_mouse_handler('down','del_balloon(elem('+repr(balloon.id)+'))');
	}
}
