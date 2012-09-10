<? $AUTH='admin';
	require_once 'app/init.php';

	$table = $_GET['table'];
	$id = $_GET['id'];

	if ($table!==null && $table!=='' && $id!==null && $id!=='') try {
		$row = get($id,$table);

		$history = query('SELECT * FROM log_database'
			.' WHERE `table`='.sql($table)
			.' AND table_id='.sql($id)
			.' ORDER BY time'
		);

		if ($row===null && !$history) {
			throw new Exception('No such record exists or has existed.');
		}
	
		$users = array();
	
		$state = array();
		for ($i = 0 ; $i < count($history) ; ++$i) {
			$actual_changes = array();
			$changes = jsdecode($history[$i]['changes']);
			if ($changes!==null) {
				foreach ($changes as $field=>$value) {
					if ($state[$field]!==$value || $_GET['all']) {
						$actual_changes[$field] = $value;
						$state[$field] = $value;
					}
				}
			} else {
				$_SESSION['alert'] = html('Invalid JSON?');
			}
	
			$uid = $history[$i]['user_id'];
			$u = ifnull($users[$uid], get($uid,'users'));
			$users[$uid] = $u;
			$history[$i]['user'] = $u;
			$history[$i]['actual'] = $actual_changes;
		}
	} catch (Exception $x) {
		$_SESSION['alert'] = $x->getMessage();
	} else {
		$history = null;
	}

	$HEADING = 'Record history';
?>
<? include 'app/begin.php' ?>

<form action="<?=html($URL.'/admin/history')?>" method="get">
	<table class="fields">
		<tr>
			<th class="left">Table:</th>
			<td><?=dropdown('table',$_GET,query('SHOW TABLES'))?></td>
		</tr>
		<tr>
			<th class="left">Record ID:</th>
			<td><?=input('id',$_GET)?></td>
		</tr>
		<tr>
			<td class="buttons" colspan="2">
				<input class="button ok" type="submit" value="Lookup" />
			</td>
		</tr>
	</table>
</form>


<? if ($history!==null) { ?>

<h3>History of record <?=html($_GET['id'])?> from table
"<?=html($_GET['table'])?>"</h3>

<table class="listing">
	<thead class="listing">
		<tr class="listing">
			<th class="listing" style="width:110px;">Date</th>
			<th class="listing" style="width:75px;">User</th>
			<th class="listing" style="width:300px;">Changes</th>
		</tr>
	</thead>
	<tbody class="listing">
<? foreach ($history as $i=>$row) { ?>
		<tr class="listing <?=($i%2)?'odd':'even'?>">
			<td class="listing first"><?=html($row['time'])?></td>
			<td class="listing"><?=html($row['user']['username'])?></td>
			<td class="listing last">
			<? foreach ($row['actual'] as $field=>$value)
			if ($field!='id') { ?>
				<b><?=html($field)?></b>: <?=html(sql($value))?><br />
			<? } ?>
			<? if (!$row['actual']) { ?>
				(no changes)
			<? } ?>
			</td>
		</tr>
<? } ?>
	</tbody>
</table>
<? } ?>

<? include 'app/end.php' ?>
