<?
require_once 'app/init.php';
require_once 'lib/util.php';

if (!headers_sent()) {
	header('Content-type: '.$CONTENT_TYPE.($ENCODING ? '; charset='.$ENCODING : ''));
	if ( ! $CACHE ) {
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	}
}

if ( $DEPTH <= 0 && !$INLINE_REQUEST ) {
	$DEFAULT_JS = array(
		'/lib/util.js',
		'/lib/style.js',
		'/lib/mouse.js',
		'/lib/keyboard.js',
		'/lib/forms.js',
		'/lib/dragdrop.js',
		'/lib/maketable/maketable.js',
		'/lib/jquery/jquery-1.2.3.js',
		'/lib/balloon/balloon.js',
		'/lib/cal/calendar.js',
		'/lib/cal/calendar-en.js',
		'/lib/cal/calendar-setup.js',
	);

	$DEFAULT_CSS = array(
		'/app/style.css',
		'/lib/maketable/maketable.css',
		'/lib/cal/calendar-system.css',
	);

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head profile="http://www.w3.org/2005/10/profile">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
<? if (is_string($ICON)) { ?>
	<link rel="icon" type="image/png" href="<?=html($URL.'/'.$ICON)?>">
<? } ?>
	<script type="text/javascript">var the_url=<?=js(ifnull($URL,''))?></script>
<? foreach ($DEFAULT_JS as $js) { ?>
	<? include_script($URL.$js); ?>
<? } ?>
<? foreach ($DEFAULT_CSS as $css) { ?>
	<link rel="stylesheet" type="text/css" href="<?=html($URL.$css)?>">
<? } ?>

	<title><?=$TITLE?$TITLE:strip_tags($HEADING?$HEADING:'')?></title>
</head>

<body onload="convert_links(document);<? if (strlen($ONLOAD)) echo html($ONLOAD); ?>">

<table cellspacing="0" cellpadding="0" class="pagelayout"><tr class="pagerow"><td class="toplevel"><?

	function echo_tab($url,$label) {
		if ($_SERVER['REQUEST_URI']==$url ||
		    startswith($_SERVER['REQUEST_URI'],$url.'/') ||
		    startswith($_SERVER['REQUEST_URI'],$url.'?')) {
		    	$x = ' class="active"';
		} else {
			$x = '';
		}
		echo '<li'.$x.'><a href="'.html($url).'">'.$label.'</a></li>';
	}

	if ($_SESSION['user_id']) {
		echo '<ul class="tabs">';
		echo_tab($URL.'/persons', 'persons');
		echo_tab($URL.'/stores', 'stores');
		echo_tab($URL.'/products', 'products');
		echo_tab($URL.'/foods', 'foods');
		echo_tab($URL.'/receipts', 'receipts');
		echo_tab($URL.'/purchases', 'purchases');
		echo_tab($URL.'/cart', 'cart');
		if (has_right('consumption')) {
			echo_tab($URL.'/consumptions', 'consumption');
			echo_tab($URL.'/stats', 'statistics');
		}
		if ($_SESSION['user_id']) {
			echo_tab($URL.'/user/profile', 'user');
			if (has_right('admin')) {
				echo_tab($URL.'/admin', 'ADMIN');
			}
			echo_tab($URL.'/user/logout', 'exit');
		}
		echo '</ul>';
	}

	if ($_SESSION['alert']) {
		echo '<div class="alert">'.$_SESSION['alert'].'</div>';
		unset($_SESSION['alert']);
	}

	/*if ($BREAD) {
		echo '<div class="breadcrumbs">';
		$i = 0;
		foreach ($BREAD as $page => $name) {
			if ($i) echo '&nbsp;&raquo;&nbsp;';
			echo '<a href="'.html($page).'">'.html_stiff($name).'</a>';
			++$i;
		}
		unset($i);
		echo '</div>';
	}*/

	if ($HEADING) {
		if ($POPUP !== null) {
			/*echo '<div style="float:right">'
				.'<img'
					.' src="'.html("$URL/app/cross.gif").'"'
					.' alt="'.html('close').'"'
					.' onclick="'.html('close_popup('.js($POPUP).')').'"'
					.' style="cursor:pointer"'
				.' />'
			.'</div>';*/
			echo '<h2 onselectstart="return false"'
			        .' onmousedown="return drag_popup('.html(js($POPUP)).')"'
			        .' ondblclick="return close_popup('.html(js($POPUP)).')">';
			if (is_string($ICON)) {
				echo '<img src="'.html($URL.'/'.$ICON).'" class="icon" />';
			}
			echo $HEADING;
			echo '</h2>';
		} else {
			echo '<h2>'.$HEADING.'</h2>';
		}
	}

} else if ( $DEPTH > 1 || !$INLINE_REQUEST ) {
	push();
}

++$DEPTH;

?>
