<? $AUTH='admin';

	require_once 'app/init.php';
	require_once 'lib/validation.php';

	$row = given_record('nutrient.id', array(
		'order'=>0,
		'column'=>0,
		'tag'=>'',
		'name'=>'',
		'description'=>'',
		'unit'=>'',
		'decimals'=>0,
	));

	if (posting()) try {
		$row['basetable'] = 0;

		if (correct()) {
			put($row,'nutrient');
			if (success($URL.'/nutrient')) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

?>
