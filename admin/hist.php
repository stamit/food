<? $AUTH='admin';
require_once 'app/init.php';
require_once 'app/data.php';
require_once 'lib/maketable/fun.php';

include 'app/begin.php';
maketable(array(
	'self'=>$URL.'/receipt-children',
	'params'=>array(
		'table'=>v('table'),
		'table_id'=>v('table_id'),
	),
	'key'=>'id',
	'join'=>array(
		'log_database',
	),
	'where'=>array(
		'`table`='.sql(maketable_param('table',v('table'))),
		'table_id='.sql(maketable_param('table_id',v('table_id'))),
	),
	'columns'=>array(
		'log_database.time'=>array('Date/time',
			'width'=>'110px',
		),
		'log_database.changes'=>array('Changes',
			'log_database_changes_html',
			'width'=>'300px',
		),
	),
	'orderby'=>'log_database.time',
	'userorder'=>true,
));
return include 'app/end.php';
?>
