<?php
require_once 'lib/util.php';
require_once 'lib/phpmailer/class.phpmailer.php';

function break_email_address($c) {
	if (preg_match('/^([^<>]*)<([^<>@]+@[^<>@]+)>$/', $c, $matches)) {
		return array($matches[2],$matches[1]);
	} else if (preg_match('/^[^@]+@[^@]+$/', $c, $matches)) {
		return array($c,null);
	} else {
		throw new Exception('address not in "email" or "name <email>" format: '.repr($c));
	}
}

function send_email($headers, $text, $html=null) {
	global $ENCODING, $SMTP_HOST, $SMTP_USERNAME, $SMTP_PASSWORD;

	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->Host = $SMTP_HOST;
	if (strlen($SMTP_USERNAME)) {
		$mail->SMTPAuth = true;
		$mail->Username = $SMTP_USERNAME;
		$mail->Password = $SMTP_PASSWORD;
	}
	$mail->CharSet = $ENCODING;

	foreach ($headers as $name=>$value) {
		if (is_int($name)) {
			list($name,$value) = explode(':',$value,2);
		}

		switch (strtoupper($name)) {

		case 'FROM':
			if (is_string($value)) {
				$x = break_email_address($value);
				$addr = $x[0];
				$name = $x[1];
			} else if (is_array($value)) {
				$x = value;
			} else if (value!==null) {
				throw new Exception('"From" is neither string nor array: '.repr($headers['To']));
			}
			$mail->From = $x[0];
			$mail->FromName = $x[1];
			break;

		case 'TO':
			if (is_string($value)) {
				$to = array($value);
			} else if (is_array($value)) {
				$to = $value;
			} else {
				throw new Exception('"To" is neither string nor array: '.repr($value));
			}
			foreach ($to as $b=>$c) {
				if (is_integer($b)) {
					$x = break_email_address($c);
					$addr = $x[0];
					$name = $x[1];
				} else if (strpos($b,'@') === false && strpos($c,'@') !== false) {
					$name = $b;
					$addr = $c;
				} else {
					$addr = $b;
					$name = $c;
				}
		
				$mail->AddAddress($addr,$name);
			}
			break;

		case 'SUBJECT':
			$mail->Subject = $value;
			break;

		default:
			$mail->AddCustomHeader($name,$value);
		}
	}

	if ($html!==null) {
		$mail->IsHTML(true);
		$mail->Body = $html;
		$mail->AltBody = $text;
	} else {
		$mail->IsHTML(false);
		$mail->Body = $text;
	}

	if (!$mail->send()) {
		throw new Exception('Could not send mail: '.$mail->ErrorInfo);
	}
}

?>
