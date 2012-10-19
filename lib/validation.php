<?php

require_once 'lib/util.php';

function validate_email($email,$id) {
	if (!preg_match('/^[a-zA-Z_.0-9-]+@[^.]+(\.[^.]+)+$/',$email))
		mistake($id, 'Incorrect email address');
}

function to_phone($phone) {
	$phone = trim($phone);
	$phone = str_replace('.','',$phone);
	$phone = str_replace('-','',$phone);
	$phone = preg_replace('/\s+/','',$phone);
	$phone = preg_replace('/^00/','+',$phone);
	if ($phone==='') return null;
	return $phone;
}
function validate_telephone(&$phone,$id,$country_code=null) {
	$phone = to_phone($phone);

	if ($country_code!==null
	    && strlen($phone) && substr($phone,0,1)!=='+')
		$phone = $country_code.$phone;

	if (!preg_match('/[0-9 ]+/',$phone) || strlen($phone)<6)
		mistake($id,'You did not enter a phone number');

	if (!preg_match('/^\+?[0-9 ]*$/',$phone))
		mistake($id,'Only digits are allowed in phone numbers');
}

function check_afm($afm) {
	if ( ! preg_match('/^[0123456789]{9}$/',$afm) )
		return 'Ο ΑΦΜ πρέπει να αποτελείται από 9 ψηφία.';
	$sum = 0;
	$pow = 2;
	for ($i = 0 ; $i < 8 ; ++$i) {
		$sum += $pow*intval($afm[7-$i]);
		$pow *= 2;
	}
	if ((($sum%11)%10) != intval($afm[8]))
		return 'Ο ΑΦΜ δεν είναι σωστός. '.(($sum%11)%10);
	return null;
}

function validate_afm(&$afm,$id) {
	$err = check_afm($afm);
	if ($err!==null) fail($id.':'.html($err));
}

?>
