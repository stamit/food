<? $AUTH='admin';
	require_once 'app/init.php';

	$HEADING = 'Nutrients';
?>
<? include 'app/begin.php' ?>
<? $mtid = include 'nutrients-table.php' ?>

<h3>New nutrient</h3>

<? begin_form('nutrient') ?>
<table class="fields">
	<tr><th class="left">Row:</th><td><?=
		number_input('order',value('SELECT MAX(`order`)+10 FROM nutrient'))
	?></td></tr>
	<tr><th class="left">Column:</th><td><?=
		number_input('column','')
	?></td></tr>
	<tr><th class="left">Tag:</th><td><?=
		input('tag','',10)
	?></td></tr>
	<tr><th class="left">Code name:</th><td><?=
		input('name','',24)
	?></td></tr>
	<tr><th class="left">User-readable name:</th><td><?=
		input('description','',32)
	?></td></tr>
	<tr><th class="left">Unit:</th><td><?=
		input('unit','',8)
	?></td></tr>
	<tr><th class="left">Number of decimal digits:</th><td><?=
		number_input('decimals',1)
	?></td></tr>

	<tr><td colspan="2" class="buttons">
		<button class="button" onclick="<? print html(
			'validate_post('.js($ID).',function(){'
				.'maketable_display('.js($mtid).');'
				.'elem('.js($ID.'_order').').value = parseInt(elem('.js($ID.'_order').').value)+10;'
				.'elem('.js($ID.'_tag').').focus();'
			.'})'
		)?>">Save</button>
	</td></tr>
</table>
<? end_form() ?>

<? return include 'app/end.php' ?>
