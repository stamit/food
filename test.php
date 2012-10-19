<?
	require_once 'app/init.php';
	$_SESSION['alert'] = 'This is an alert message.';
?>
<? include 'app/begin.php' ?>

<h1>This is a test</h1>

<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer dictum lectus a erat dignissim suscipit. Morbi purus ligula, tempus nec feugiat sed, ullamcorper placerat metus. Donec condimentum eleifend nulla, ac molestie massa porta semper. Morbi rhoncus nulla in ipsum consectetur suscipit. Cras pellentesque nulla vitae felis porta suscipit. Nunc varius, tortor id tincidunt posuere, elit lorem dapibus felis, quis faucibus lacus urna non dolor. Suspendisse potenti. Vestibulum pellentesque interdum velit ac viverra. Nullam facilisis sapien id urna tristique tempus vitae et magna. Praesent justo magna, posuere vitae blandit eu, tincidunt sed purus. Curabitur magna eros, aliquam a feugiat ac, ullamcorper in justo. Mauris in faucibus enim. Curabitur ut justo ipsum.</p>

<ul>
	<li><a href="http://www.w3.org/TR/html401/">HTML 4.01 specification</a></li>
	<ul>
		<li><a href="http://www.w3.org/TR/html401/">HTML 4.01 specification</a></li>
		<li><a href="http://www.w3.org/TR/CSS2/">CSS 2.1 specification</a></li>
	</ul>
	<li><a href="http://www.w3.org/TR/CSS2/">CSS 2.1 specification</a></li>
</ul>

<div class="side">
<table border="1"
       summary="This table gives some statistics about fruit
                flies: average height and weight, and percentage
                with red eyes (for both males and females).">
	<caption>A test table with merged cells</caption>

	<col />
	<col class="number" />
	<col class="number" />
	<col class="number" />

	<thead>
		<tr>
			<th rowspan="2"></th>
			<th colspan="2">Average</th>
			<th rowspan="2">Red<br />eyes</th>
		</tr>
		<tr>
			<th>height</th>
			<th>weight</th>
		</tr>
	</thead>

	<tfoot>
		<tr><th>All</th><td>1.8</td><td>0.0025</td><td>41.5%</td></tr>
	</tfoot>

	<tbody>
		<tr><th>Males</th>  <td>1.9</td><td>0.0030</td><td>40.0%</td></tr>
		<tr><th>Females</th><td>1.7</td><td>0.0020</td><td>43.0%</td></tr>
	</tbody>
</table>
</div>

<p>Vivamus tempus interdum mauris, venenatis aliquet eros aliquet non. Praesent tempor lobortis pharetra. Aliquam et nisl sed odio hendrerit venenatis ac at odio. Nunc suscipit ante eu velit molestie at consectetur risus varius. Fusce vitae nulla vitae arcu faucibus bibendum ac a libero. Suspendisse tortor magna, luctus at mollis posuere, fermentum sed erat. Aliquam vulputate sollicitudin felis, eu pulvinar dolor luctus ut. Cras adipiscing, nisi at cursus laoreet, arcu lacus ultrices nisi, sit amet ultricies metus nulla vel nisi. Maecenas a quam felis, ut accumsan diam. Nam id erat tortor. Phasellus vel mi non ipsum fringilla elementum in a velit.</p>

<p>Sed sed risus a magna sodales pulvinar. Nullam faucibus volutpat massa nec dapibus. Proin sit amet leo elit. Aenean sit amet nisl nisi, et molestie diam. Phasellus id magna et ante mollis viverra. Duis congue accumsan metus nec tincidunt. Cras tellus erat, porta vitae sollicitudin non, egestas a magna. Ut vestibulum sodales dui nec vulputate.</p>

<p>Vivamus tempus interdum mauris, venenatis aliquet eros aliquet non. Praesent tempor lobortis pharetra. Aliquam et nisl sed odio hendrerit venenatis ac at odio. Nunc suscipit ante eu velit molestie at consectetur risus varius. Fusce vitae nulla vitae arcu faucibus bibendum ac a libero. Suspendisse tortor magna, luctus at mollis posuere, fermentum sed erat. Aliquam vulputate sollicitudin felis, eu pulvinar dolor luctus ut. Cras adipiscing, nisi at cursus laoreet, arcu lacus ultrices nisi, sit amet ultricies metus nulla vel nisi. Maecenas a quam felis, ut accumsan diam. Nam id erat tortor. Phasellus vel mi non ipsum fringilla elementum in a velit.</p>

<h2>Second level heading</h2>

<p>Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Aenean sit amet tristique urna. Praesent quam massa, adipiscing non rutrum a, mollis egestas nibh. Nam dictum quam et quam tincidunt imperdiet. Pellentesque at ante urna. Pellentesque facilisis nunc quis leo condimentum euismod varius purus scelerisque. Suspendisse potenti. Vestibulum egestas tempor condimentum. Sed eu convallis ligula.</p>

<p>Quisque feugiat porttitor leo, at pharetra urna interdum non. Ut at purus mi, eget varius orci. Cras at interdum risus. Aenean commodo accumsan sagittis. Nam tempor euismod libero vel tristique. Suspendisse id felis sed tellus lobortis vehicula. Integer vulputate, ligula nec congue laoreet, tortor libero pretium leo, eget dapibus magna libero et nisl. Praesent non odio at turpis accumsan interdum. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Integer suscipit condimentum laoreet. Quisque erat orci, sollicitudin ut interdum et, auctor non elit. Proin in sapien in orci elementum porttitor. Cras metus enim, ornare ac tempor ut, luctus sit amet lacus. In in diam non mi porttitor consequat.</p>

<h2>Another heading</h2>

<p>Morbi nec erat consectetur odio convallis tempus. Fusce sit amet augue eget nisi ullamcorper venenatis. Etiam vitae lectus hendrerit dolor volutpat scelerisque. Nam posuere nulla sodales ante gravida fringilla quis vel turpis. Sed in egestas magna. In nec orci nec eros pretium posuere. Nulla luctus, ante nec congue convallis, quam arcu fermentum nisl, vitae ullamcorper mauris odio nec magna. Mauris a mi metus, sagittis sagittis enim. Nullam quis turpis sem, sit amet adipiscing dui. Sed sagittis euismod mauris ut lacinia.</p>

<? begin_form() ?>
<table class="fields">
	<tr><th>Name Sn:</th><td><?=input('name',$row,array(48,64))?></td></tr>
	<tr><th>Address:</th><td><?=textarea('address',$row,48,3)?></td></tr>
	<tr><th>Zip code:</th><td><?=input('postcode',$row,6)?></td></tr>
	<tr><th>P.O. box:</th><td><?=input('postbox',$row,6)?></td></tr>
	<tr><th>Phone:</th><td><?=input('phone',$row,16)?></td></tr>
	<tr><th>Phone:</th><td><?=input('phone2',$row,16)?></td></tr>
	<tr><th>FAX:</th><td><?=input('fax',$row,16)?></td></tr>
	<tr><th>Email:</th><td><?=input('email',$row,array(24,127))?></td></tr>
	<tr><th>Website:</th><td><?=input('website',$row,array(32,127))?></td></tr>
<?/*
	<tr><th>ΑΦΜ:</th><td><?=input('afm',$row,9)?></td></tr>
	<tr><th>ΔΟΥ:</th><td><?=input('doy',$row,array(24,48))?></td></tr>
*/?>
	<? if (!$OPTION) { ?>
	<tr><th>Notes:</th><td><?=textarea('notes',$row,64,10)?></td></tr>
	<? } ?>

<? if (has_right('register-persons')) { ?>
	<tr><td colspan="2" class="buttons">
		<?=submit_button('Save')?>
	</td></tr>
<? } ?>
</table>
<? end_form() ?>

<p>Donec eu sollicitudin mi. Vestibulum sed neque tortor. Aenean interdum pharetra nunc, eget sollicitudin nunc ullamcorper a. Nullam luctus facilisis lectus quis consectetur. Sed congue feugiat massa vitae pulvinar. Vivamus at mauris urna, at malesuada urna. Nam ultrices urna sit amet erat auctor elementum. Aenean sed libero ut est sagittis ultrices in eu leo. Aliquam erat volutpat. Maecenas commodo congue ante at laoreet. Nam ligula arcu, ultricies non pharetra et, molestie pharetra tellus. Curabitur euismod, nisl non elementum posuere, odio orci molestie augue, vitae feugiat purus tellus et orci. Ut ut leo augue.</p>

<? include 'app/end.php' ?>
