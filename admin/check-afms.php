<? $AUTH='admin';
ini_set('include_path',dirname(dirname(__FILE__)));
require_once 'app/init.php';
require_once 'lib/validation.php';
header('Content-type: text/plain; charset='.$ENCODING);

foreach (query('SELECT * FROM person') as $person) {
	if ($person['afm']!==null) {
		$mistake = check_afm($person['afm']);
		if ( $mistake !== null ) {
			$user = fetch('users.id',$person['user_id']);
			echo 'http://efood.stamit.gr/person?id='.$person['id']
				.' : '
				.' by user '.$user['username'].','
				.' afm '.repr($person['afm']).','
				.' name '.repr($person['name']).','
				.' '.$mistake
				."\n";
			flush();
			ob_flush();
		}
	}
}

?>
