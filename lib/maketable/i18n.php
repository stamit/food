<?php
#
# simple (read: not very smart) internationalization for maketable
# - the "Accept-Language" HTTP header should have been used instead
# - gettext perhaps?
#
$MAKETABLE_TEXT = array(
/*
	'Page {1}'
	=> 'Σελίδα {1}',

	'Page {1} of {2}'
	=> 'Σελίδα {1} από {2}',

	'{1} per page'
	=> '{1} ανά σελίδα',

	'({1} {2} total)'
	=>'({1} {2} συνολικά)',

	'({1} total)'
	=>'({1} συνολικά)',

	' (out of {1})'
	=>' (από {1})',

	'First {1}'
	=>'Πρώτα {1}',

	'Last {1}'
	=>'Τελευταία {1}',

	'All {1}'
	=>'Όλα {1}',

	'{1} to {2}'
	=>'{1} έως {2}',

	'Are you sure you want to erase {1}?'
	=>'Θέλετε σίγουρα να σβηστεί {1}?',

	'refresh'
	=>'ανανέωση',

	'No {1} found.'
	=>'Δε βρέθηκαν {1}.',
	
	'record'
	=>'εγγραφή',

	'records'
	=>'εγγραφές',
*/
);

function maketable_text($text, $y=null) {
	global $MAKETABLE_TEXT;

	$z = $MAKETABLE_TEXT[$text];
	if ($z===null) $z = $text;

	if ($y!==null) {
		$x = array();
		foreach ($y as $i=>$a)
			$x[] = '{'.strval($i+1).'}';
		$z = str_replace($x,$y,$z);
	}

	return $z;
}
?>
