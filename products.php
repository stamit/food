<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'app/data.php';

	$HEADING = 'Products';
?>
<? include 'app/begin.php' ?>
<? include 'products-table.php' ?>
<? if (has_right('register-products')) { ?>
<p class="noprint"><a href="product?type=2">New food</a></p>
<p class="noprint"><a href="product?type=3">New product</a></p>
<? } ?>
<? return include 'app/end.php' ?>
