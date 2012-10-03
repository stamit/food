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
		'/lib/balloon/balloon.js',
		'/lib/cal/calendar.js',
		'/lib/cal/calendar-en.js',
		'/lib/cal/calendar-setup.js',
	);

	$DEFAULT_CSS = array(
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
	<link rel="stylesheet" type="text/css" href="<?=html($URL.'/app/style.css')?>">
	<link rel="stylesheet" type="text/css" href="<?=html($URL.'/app/print.css')?>" media="print">
<? foreach ($DEFAULT_CSS as $css) { ?>
	<link rel="stylesheet" type="text/css" href="<?=html($URL.$css)?>">
<? } ?>

	<title><?=$TITLE?$TITLE:strip_tags($HEADING?$HEADING:'')?></title>
</head>

<body onload="convert_links(document);<? if (strlen($ONLOAD)) echo html($ONLOAD); ?>">
<div class="wrapper">

<?
	$TABS = array(
		'/persons' => 'persons',
		'/stores' => 'stores',
		'/products' => 'products',
		'/foods' => 'foods',
		'/receipts' => 'receipts',
		'/purchases' => 'purchases',
		'/cart' => 'cart',
	);
	if (has_right('consumption')) {
		$TABS['/consumptions'] = 'consumption';
		$TABS['/stats'] = 'statistics';
	}
	$TABS['/user/profile'] = 'user';
	$TABS['/user/logout'] = 'exit';
	if (has_right('admin')) {
		$TABS['/admin/'] = 'ADMIN';
	}

	if ($_SESSION['user_id']) {
		echo '<ul class="tabs">';
		$i=0;
		$a=0;
		foreach ($TABS as $url1=>$label) {
			$url = $URL.$url1;

			$class=array();
			if ($i==0) $class[]='first';
			if ($i==count($TABS)-1) $class[]='last';
			if (!count($class)) $class[] = 'mid';

			if ($a) {
				$class[] = 'aactive';
				$a = 0;
			}
			if ($_SERVER['REQUEST_URI']==$url ||
			    startswith($_SERVER['REQUEST_URI'],$url.'/') ||
			    startswith($_SERVER['REQUEST_URI'],$url.'?')) {
				$class[] = 'active';
				$a = 1;
			}

			echo '<li class="'.implode(' ',$class).'">'
				.'<span class="tableft"></span>'
				.'<a href="'.html($url).'">'.$label.'</a>'
				.'<span class="tabright"></span>'
			.'</li>';
			++$i;
		}
		echo '</ul>';
	}
?>

<div class="wrapper2">
<div class="wrapper3">
<?
	if ($_SESSION['alert']) {
		echo '<div class="alert">'.str_replace('\n','<br/>',html($_SESSION['alert'])).'</div>';
		unset($_SESSION['alert']);
	}

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
