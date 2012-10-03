<?
require_once 'lib/database.php';
require_once 'lib/email.php';

function send_email_template($name, $headers, $vars) {
	global $EMAIL_FROM;

	$tpl = row('* FROM email_templates WHERE name='.sql($name));

	$a=array();
	$b=array();
	foreach ($vars as $name=>$value) {
		$a[] = "%$name%";
		$b[] = $value;
	}

	$subject = str_replace($a,$b,$tpl['subject']);

	$text = str_replace($a,$b,$tpl['text']);

	# escape HTML special characters
	$c=array();
	foreach ($b as $value)
		$c[] = html($value);

	$html = ($tpl['html']!==null ? str_replace($a,$c,$tpl['html']) : null);

	$headers = array_merge(array(
		'From'=>$EMAIL_FROM,
		'To'=>$EMAIL_FROM,
		'Subject'=>$subject,
	),$headers);

	$eq = array(
		'template_id'=>$tpl['id'],
		'headers'=>js($headers),
		'subject'=>$subject,
		'text'=>$text,
		'html'=>$html,
	);

	if (is_string($headers['To'])) {
		$email_name = break_email_address($headers['To']);
		$eq['recipient'] = $email_name[0];
	}

	$eq['id'] = insert('email_queue',$eq);
	if ($tpl['queue_tod']!==null) {
		$now = value('NOW()');
		$x = substr($now,0,10).' '.$tpl['queue_tod'];
		if (strcmp($x,$now)<0) {
			$x = date_adjust($x,'+1 day');
		}

		execute('UPDATE email_queue'
		        .' SET to_send='.sql($x)
		        .' WHERE id='.sql($eq['id']));

	} else if ($tpl['queue_minutes']!==null) {
		execute('UPDATE email_queue'
		        .' SET to_send=ADDTIME(created,SEC_TO_TIME('
		        	.sql(60*$tpl['queue_minutes'])
		        .'))'
		        .' WHERE id='.sql($eq['id']));

	} else {
		send_email($headers, $text, $html);
		execute('UPDATE email_queue'
		        .' SET to_send=NOW(), sent=NOW()'
		        .' WHERE id='.sql($eq['id']));
	}
}
?>
