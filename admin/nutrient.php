<? $AUTH='admin';

	require_once 'app/init.php';
	require_once 'lib/validation.php';

	$row = given('nutrient.id', array(
		'order'=>'int',
		'column'=>'int',
		'tag'=>'str',
		'name'=>'str',
		'description'=>'str',
		'unit'=>'str',
		'decimals'=>'int',
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
