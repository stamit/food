<? $AUTH='admin';
	require_once 'app/init.php';
	$uri = '/admin/test?__1=%7B%22foo%22%3A%22bar%22%7D';
?>
<? include 'app/begin.php' ?>
<pre><?=html(mutate_deep(array('baz'=>'mau'),array(1,2),$uri))?></pre>
<? include 'app/end.php' ?>
