<? $AUTH='admin';

	require_once 'app/init.php';
	require_once 'lib/validation.php';

	$row = given('nutrient.id', array(
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
			store('nutrient.id',$row);
			if (success($URL.'/nutrient')) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

?>
