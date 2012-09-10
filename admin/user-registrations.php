<?
#
# deletes user registrations that have not been confirmed within a specified
# amount of time
#
ini_set('include_path',dirname(dirname(__FILE__)));
require_once 'app/init.php';
header('Content-type: text/plain; charset='.$ENCODING);

foreach (query('SELECT * FROM users WHERE'
               .' registered<SUBDATE(NOW(),'.sql($REGISTRATION_TIMEOUT_DAYS).')'
               .' AND confirmed IS NULL AND NOT active') as $user) {
	echo 'registration timed out for user "'.$user['username'].'"'
	     .' with email address "'.$user['email'].'"; deleting user';

	execute('DELETE FROM users WHERE id='.sql($user['id']));

	flush();
	ob_flush();
}

?>
