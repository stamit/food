<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'app/data.php';
	require_once 'lib/maketable/maketable.php';

	include 'app/begin.php';
	maketable(array(
		'self'=>$URL.'/persons-table',
		'key'=>'id',
		'join'=>array(
			'person'=>array(),
		),
		'columns'=>array(
			'person.name'=>array('Name', 'person_name_html',
				'search'=>'mid',
				'width'=>'250px',
			),
			'person.phone'=>array('Phone',
				'search'=>'left',
				'width'=>'90px',
			),
/*
			'person.afm'=>array('ΑΦΜ',
				'tip'=>'Αριθμός Φορολογικού Μητρώου',
				'search'=>'mid',
				'width'=>'70px',
			),
			'person.doy'=>array('ΔΟΥ',
				'search'=>'mid',
				'width'=>'140px',
			),
*/
			'person.website'=>array('Website', 'website_html',
				'search'=>'mid',
				'width'=>'120px',
			),
		),
		'orderby'=>'person.name',
		'userorder'=>true,

		'delete'=>1,
		'deleteinc'=>'person.php',
		'deleteneg'=>true,
	));
	return include 'app/end.php';
?>
