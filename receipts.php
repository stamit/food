<? $AUTH=true;
	require_once 'app/init.php';
	require_once 'lib/regional.php';

	$HEADING = 'Receipts';
?>
<? include 'app/begin.php' ?>

<? $mtid = include 'receipts-table.php' ?>

<h3 class="noprint">New receipt</h3>

<? begin_form('receipt') ?>
<table class="fields noprint">
	<tr><th class="left">Date/time:</th><td><?
		print datetime_input('issued',today())
	?></td></tr>
	<tr><th class="left">Amount:</th><td><?
		print number_input('amount','').'€';
	?></td></tr>
	<tr><th class="left">Store:</th><td><?
		print dropdown('store','',query(
			"SELECT id AS value, CONCAT(name,' - ',IF(LENGTH(address)>40,CONCAT(SUBSTRING(address,1,40),'[...]'),address)) AS text"
			.' FROM store ORDER BY name'
		),'',array('','(unknown store or no store)'))
	?></td></tr>
	<tr><th class="left">Seller:</th><td><?
		print dropdown('person','',query(
			"SELECT id AS value, name AS text"
			.' FROM person ORDER BY name'
		),'',array('','(owner of above store)'))
	?></td></tr>
	<tr><td colspan="2" class="buttons">
		<button class="button" onclick="<? print html(
			'validate_post('.js($ID).',function(){'
				.'maketable_display('.js($mtid).');'
				.'elem('.js($ID.'_issued').').focus();'
			.'})'
		)?>">Save</button>
	</td></tr>
</table>
<? end_form() ?>

<? include 'app/end.php' ?>
