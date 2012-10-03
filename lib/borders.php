<?php
require_once dirname(__FILE__).'/util.php';

#
# This code
#
#   $bb = begin_borders($img_pre, $img_ext);
#   print "your content";
#   end_borders($bb);
#
# gives
#
#   +--+--------+--+
#   |  |        |  |  "$img_pre-topright$img_ext"
#   +--+--------+--+
#   |  |your    |  |  "$img_pre-right$img_ext"
#   |  |content |  |
#   |  |        |  |
#   |  |        |  |
#   +--+--------+--+
#   |  |        |  |  "$img_pre-bottomright$img_ext"
#   +--+--------+--+
#
# ($id is optional id of center cell)
#
function begin_borders_str($id, $img_pre, $img_ext, $cssborder='', $cssbg='', $xtra='', $xs='') {
	$xs = html($xs);
	if (strlen($xs)) $sxs = ' style="'.$xs.'"';

	return '<table class="layout" cellpadding="0" cellspacing="0">'
		.'<tr class="layout">'
			.'<td class="layout"'.$sxs.$xtra.'><img alt="" src="'.$img_pre.'-topleft'.$img_ext.'"/></td>'
			.'<td class="layout" style="'
				.( strlen($cssborder)
					? "border-top: ".html($cssborder)."; background: ".html($cssbg)
					: 'background-image: url(\''.$img_pre.'-top'.$img_ext.'\'); background-repeat: repeat-x;'
				)
			.$xs.'"'.$xtra.'></td>'
			.'<td class="layout"'.$sxs.$xtra.'><img alt="" src="'.$img_pre.'-topright'.$img_ext.'"/></td>'
		.'</tr><tr class="layout">'
			.'<td class="layout" style="'
				.( strlen($cssborder)
					? "border-left: ".html($cssborder)."; background: ".html($cssbg)
					: 'background-image: url(\''.$img_pre.'-left'.$img_ext.'\'); background-repeat: repeat-y;'
				)
			.$xs.'"'.$xtra.'></td>'
			.'<td'.($id?' id="'.html($id).'"':'').' class="layout" style="'
				.( strlen($cssbg)
					? "background: $cssbg"
					: 'background-image: url(\''.$img_pre.'-center'.$img_ext.'\');'
				)
			.$xs.'"'.$xtra.'>';
}
function end_borders_str($img_pre, $img_ext, $cssborder='', $cssbg='', $xtra='', $xs='') {
	$xs = html($xs);
	if (strlen($xs)) $sxs = ' style="'.$xs.'"';

	return '</td>'
			.'<td class="layout" style="'
				.( strlen($cssborder)
					? "border-right: ".html($cssborder)."; background: ".html($cssbg)
					: 'background-image: url(\''.$img_pre.'-right'.$img_ext.'\'); background-repeat: repeat-y;'
				)
			.$xs.'"'.$xtra.'></td>'
		.'</tr><tr class="layout">'
			.'<td class="layout"'.$sxs.$xtra.'><img alt="" src="'.$img_pre.'-bottomleft'.$img_ext.'"/></td>'
			.'<td class="layout" style="'
				.( strlen($cssborder)
					? "border-bottom: ".html($cssborder)."; background: ".html($cssbg)
					: 'background-image: url(\''.$img_pre.'-bottom'.$img_ext.'\'); background-repeat: repeat-x;'
				)
			.$xs.'"'.$xtra.'></td>'
			.'<td class="layout"'.$sxs.$xtra.'><img alt="" src="'.$img_pre.'-bottomright'.$img_ext.'"/></td>'
		.'</tr>'
	.'</table>';
}

function begin_borders($id='', $img_pre, $img_ext, $cssborder='', $cssbg='', $cssborder2=null, $cssbg2=null) {
	if ($cssborder2 === null) $cssborder2 = $cssborder;
	if ($cssbg2 === null) $cssbg2 = $cssbg;
	print begin_borders_str($id, $img_pre, $img_ext, $cssborder, $cssbg);
	return array($img_pre,$img_ext,$cssborder2,$cssbg2);
}
function end_borders(&$bb) {
	$img_pre=$bb[0];
	$img_ext=$bb[1];
	$cssborder=$bb[2];
	$cssbg=$bb[3];
	print end_borders_str($img_pre, $img_ext, $cssborder, $cssbg);
}

#
# This code
#
#   $bb = begin_borders2($id, $img_pre, $img_ext, $w1,$w2,$h1,$h2, $cssbg);
#   print "your content";
#   end_borders2($bb);
#
# supposedly gives
#
#       v "$img_pre-topleftr$img_ext"
#   +--+--+--------+--+--+  "$img_pre-toprightl$img_ext"
#   |  |  |        |  |  |  "$img_pre-topright$img_ext"
#   +--+--+--------+--+--+
#   |  |your content  |  |  "$img_pre-toprightd$img_ext"
#   +--+              +--+
#   |  |              |  |
#   |  |              |  |  "$img_pre-right$img_ext"
#   |  |              |  |
#   +--+              +--+
#   |  |              |  |  "$img_pre-bottomrightu$img_ext"
#   +--+--+--------+--+--+
#   |  |  |        |  |  |  "$img_pre-bottomright$img_ext"
#   +--+--+--------+--+--+  "$img_pre-bottomrightl$img_ext"
#       ^ "$img_pre-bottomleftr$img_ext"
# ($id is optional id of center cell)
#
function begin_borders2_str($id, $img_pre,$img_ext, $w1,$w2,$h1,$h2, $cssbg, $xtra='', $xs='') {
	$xs = html($xs);
	if (strlen($xs)) {
		$sxs = ' style="'.$xs.'"';
	} else {
		$sxs = '';
	}

	$bg = strlen($cssbg) ? 'background:'.$cssbg.';'
	                     : "background-image:url('".$img_pre."-center".$img_ext."');" ;

	if (detect_msie())
		return '<table class="layout" cellpadding="0" cellspacing="0">'
			.'<tr class="layout">'
				."<td class=\"layout\"$sxs$xtra><img alt=\"\" src=\"$img_pre-topleft$img_ext\"/></td>"
				."<td class=\"layout\" style=\"width: {$w1}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-topleftr$img_ext\"/></td>"
				."<td class=\"layout\" style=\"background-image: url('$img_pre-top$img_ext'); background-repeat: repeat-x;$xs\"$xtra>"
					.'<div style="width:1px;height:1px;"></div>'
				."</td>"
				."<td class=\"layout\" style=\"width: {$w2}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-toprightl$img_ext\"/></td>"
				."<td class=\"layout\"$sxs$xtra><img alt=\"\" src=\"$img_pre-topright$img_ext\"/></td>"
			.'</tr><tr class="layout">'
				."<td class=\"layout\" style=\"height: {$h1}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-topleftd$img_ext\"/></td>"
				.'<td class="layout" style="'.html($bg).$xs.'"'.$xtra.'></td>'
				.'<td class="layout" style="'.html($bg).$xs.'"'.$xtra.'></td>'
				.'<td class="layout" style="'.html($bg).$xs.'"'.$xtra.'></td>'
				."<td class=\"layout\" style=\"height: {$h1}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-toprightd$img_ext\"/></td>"
			.'</tr><tr class="layout">'
				."<td class=\"layout\" style=\"background-image: url('$img_pre-left$img_ext'); background-repeat: repeat-y;$xs\"$xtra>"
					#."<img alt=\"\" src=\"$img_pre-left$img_ext\"/>"
					.'<div style="width:1px;height:1px;"></div>'
				."</td>"
				.'<td class="layout" style="'.html($bg).$xs.'"'.$xtra.'></td>'
				.'<td'.($id?' id="'.html($id).'"':'').' class="layout" style="'.html($bg).'">';

	return '<table class="layout" cellpadding="0" cellspacing="0">'
		.'<tr class="layout">'
			."<td class=\"layout\"$sxs$xtra><img alt=\"\" src=\"$img_pre-topleft$img_ext\"></td>"
			."<td class=\"layout\" style=\"width: {$w1}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-topleftr$img_ext\"></td>"
			."<td class=\"layout\" style=\"background-image: url('$img_pre-top$img_ext'); background-repeat: repeat-x;$xs\"$xtra>"
				#."<img alt=\"\" src=\"$img_pre-top$img_ext\">"
				.'<div style="width:1px;height:1px;"></div>'
			."</td>"
			."<td class=\"layout\" style=\"width: {$w2}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-toprightl$img_ext\"/></td>"
			."<td class=\"layout\"$sxs$xtra><img alt=\"\" src=\"$img_pre-topright$img_ext\"/></td>"
		.'</tr><tr class="layout">'
			."<td class=\"layout\" style=\"height: {$h1}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-topleftd$img_ext\"/></td>"
			.'<td colspan="3" rowspan="3" '.($id?'id="'.html($id).'" ':'').' class="layout" style="'.html($bg).'">';
}
function end_borders2_str($img_pre, $img_ext, $w1, $w2, $h1, $h2, $cssbg='', $xtra='', $xs='') {
	$xs = html($xs);
	if (strlen($xs)) {
		$sxs = ' style="'.$xs.'"';
	} else {
		$sxs = '';
	}

	$bg = strlen($cssbg) ? 'background:'.$cssbg.';'
	                     : "background-image:url('".$img_pre."-center".$img_ext."');" ;

	if (detect_msie())
		return '</td>'
				.'<td class="layout" style="'.html($bg).$xs.'"'.$xtra.'></td>'
				."<td class=\"layout\" style=\"background-image: url('$img_pre-right$img_ext'); background-repeat: repeat-y;$xs\"$xtra>"
					#."<img alt=\"\" src=\"$img_pre-right$img_ext\">"
					.'<div style="width:1px;height:1px;"></div>'
				."</td>"
			.'</tr><tr class="layout">'
				."<td class=\"layout\" style=\"height: {$h2}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-bottomleftu$img_ext\"></td>"
				.'<td class="layout" style="'.html($bg).$xs.'"'.$xtra.'></td>'
				.'<td class="layout" style="'.html($bg).$xs.'"'.$xtra.'></td>'
				.'<td class="layout" style="'.html($bg).$xs.'"'.$xtra.'></td>'
				."<td class=\"layout\" style=\"height: {$h2}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-bottomrightu$img_ext\"></td>"
			.'</tr><tr class="layout">'
				."<td class=\"layout\"$sxs$xtra><img alt=\"\" src=\"$img_pre-bottomleft$img_ext\"></td>"
				."<td class=\"layout\" style=\"width: {$w1}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-bottomleftr$img_ext\"></td>"
				."<td class=\"layout\" style=\"background-image: url('$img_pre-bottom$img_ext'); background-repeat: repeat-x;$xs\"$xtra>"
					#."<img alt=\"\" src=\"$img_pre-bottom$img_ext\">"
					.'<div style="width:1px;height:1px;"></div>'
				."</td>"
				."<td class=\"layout\" style=\"width: {$w2}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-bottomrightl$img_ext\"></td>"
				."<td class=\"layout\"$sxs$xtra><img alt=\"\" src=\"$img_pre-bottomright$img_ext\"></td>"
			.'</tr>'
		.'</table>';

	return '</td>'
			."<td class=\"layout\" style=\"height: {$h1}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-toprightd$img_ext\"></td>"
		.'</tr><tr class="layout">'
			."<td class=\"layout\" style=\"background-image: url('$img_pre-left$img_ext'); background-repeat: repeat-y;$xs\"$xtra>"
				#."<img alt=\"\" src=\"$img_pre-left$img_ext\">"
				.'<div style="width:1px;height:1px;"></div>'
			."</td>"
			."<td class=\"layout\" style=\"background-image: url('$img_pre-right$img_ext'); background-repeat: repeat-y;$xs\"$xtra>"
				#."<img alt=\"\" src=\"$img_pre-right$img_ext\">"
				.'<div style="width:1px;height:1px;"></div>'
			."</td>"
		.'</tr><tr class="layout">'
			."<td class=\"layout\" style=\"height: {$h2}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-bottomleftu$img_ext\"></td>"
			."<td class=\"layout\" style=\"height: {$h2}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-bottomrightu$img_ext\"></td>"
		.'</tr><tr class="layout">'
			."<td class=\"layout\"$sxs$xtra><img alt=\"\" src=\"$img_pre-bottomleft$img_ext\"></td>"
			."<td class=\"layout\" style=\"width: {$w1}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-bottomleftr$img_ext\"></td>"
			."<td class=\"layout\" style=\"background-image: url('$img_pre-bottom$img_ext'); background-repeat: repeat-x;$xs\"$xtra>"
				#."<img alt=\"\" src=\"$img_pre-bottom$img_ext\">"
				.'<div style="width:1px;height:1px;"></div>'
			."</td>"
			."<td class=\"layout\" style=\"width: {$w2}px;$xs\"$xtra><img alt=\"\" src=\"$img_pre-bottomrightl$img_ext\"></td>"
			."<td class=\"layout\"$sxs$xtra><img alt=\"\" src=\"$img_pre-bottomright$img_ext\"></td>"
		.'</tr>'
	.'</table>';
}

function begin_borders2($id, $img_pre, $img_ext, $w1,$w2,$h1,$h2, $cssbg='') {
	print begin_borders2_str($id, $img_pre, $img_ext, $w1,$w2,$h1,$h2, $cssbg);
	return array($img_pre,$img_ext,$w1,$w2,$h1,$h2,$cssbg);
}

function end_borders2(&$bb) {
	$img_pre=$bb[0];
	$img_ext=$bb[1];
	$w1=$bb[2];
	$w2=$bb[3];
	$h1=$bb[4];
	$h2=$bb[5];
	$cssbg=$bb[6];
	print end_borders2_str($img_pre, $img_ext, $w1, $w2, $h1, $h2, $cssbg);
}
?>
