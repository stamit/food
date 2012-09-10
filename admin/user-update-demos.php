<?
#
# updates demogrphic group and nutrient threshold information for aging users
#
ini_set('include_path',dirname(dirname(__FILE__)));
require_once 'app/init.php';
header('Content-type: text/plain');

foreach (query('SELECT * FROM users') as $user) {
	$olddemo = $user['demographic_group'];
	$demo = find_demographic_group($user);
	if ($olddemo != $demo) {
		echo 'user '.$user['id'].': demo '.$olddemo.'=>'.$demo."\n";
		put(array(
			'id'=>$user['id'],
			'demographic_group'=>$demo
		),'users');
		$user['demographic_group'] = $demo;
		execute('DELETE FROM threshold'
		        .' WHERE user='.sql($user['id'])
		        .' AND demographic_group IS NOT NULL');
		user_update_thresholds($user['id']);
	}
}

?>
