<? $AUTH=true;
	require_once 'app/init.php';

	if (v('demographic_group')!==null) {
		authif(has_right('admin') || has_right('nutrient-thresholds'));
		$group_cond = 'demographic_group='
			.sql(intval(v('demographic_group')))
			.' AND user IS NULL';
	} else {
		$group_cond = 'user='.sql($_SESSION['user_id']);
		$user = get($_SESSION['user_id'],'users');
	}

	if (v('demographic_group')!==null) {
		$demo = intval(v('demographic_group'));
	} else {
		$user = get($_SESSION['user_id'],'users');
		$demo = $user['demographic_group'];
	}

	if (posting()) try {

		$thrs = array();
		foreach (query('SELECT id,name FROM nutrient') as $nutrient) {
			$name = $nutrient['name'];
			$thr = array(
				'nutrient'=>$nutrient['id'],
				'min'=>$_POST['min_'.$name],
				'best'=>$_POST['best_'.$name],
				'max'=>$_POST['max_'.$name],
				'demographic_group'=>$demo,
			);

			if (v('demographic_group')===null) {
				$thr['user'] = $_SESSION['user_id'];
				if ( ! $_POST['auto_'.$name] ) {
					$thr['demographic_group'] = null;
				}
			}

			if (!strlen($thr['min'])) $thr['min'] = null;
			if (!strlen($thr['best'])) $thr['best'] = null;
			if (!strlen($thr['max'])) $thr['max'] = null;

			if ($thr['min'] < 0) mistake('min_'.$name,'No negative values.');
			if ($thr['best'] < 0) mistake('best_'.$name,'No negative values.');
			if ($thr['max'] < 0) mistake('max_'.$name,'No negative values.');

			if ($thr['best']!==null && $thr['min']!==null && $thr['best'] < $thr['min'])
				mistake('best_'.$name,'"Best" value must not be smaller than "min".');
			if ($thr['max']!==null && $thr['best']!==null && $thr['max'] < $thr['best'])
				mistake('max_'.$name,'"Max" value must not be smaller than "best".');

			if ($thr['min']!==null) $thr['min'] = floatval($thr['min']);
			if ($thr['best']!==null) $thr['best'] = floatval($thr['best']);
			if ($thr['max']!==null) $thr['max'] = floatval($thr['max']);

			$thrs[$name] = $thr;
		}

		if (correct()) {
			foreach ($thrs as $thr) {
				execute('DELETE FROM threshold WHERE '
					.$group_cond.' AND '
					.'nutrient='.sql($thr['nutrient'])
				);
				if ($thr['min']!==null || $thr['best']!==null || $thr['max']!==null)
					insert('threshold',$thr);
			}

			if (v('demographic_group')!==null) {
				if (success()) return true;
			} else {
				if (success($URL.'/cart/')) return true;
			}
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	$HEADING = 'Goal quantities';
?>
<? include 'app/begin.php' ?>
<? begin_form($URL.'/cart/thresholds') ?>
<? if (v('demographic_group')!==null) {
	echo hidden('demographic_group',v('demographic_group'));
	$dg = get($demo,'demographic_group');
	$ss = array();
	if ($dg['gender']!==null) {
		if (!$dg['gender']) {
			$ss[] = 'male';
		} else switch ($dg['pregnancy']) {
		case 1: $ss[] = 'pregnant'; break;
		case 2: $ss[] = 'lactating'; break;
		case 0: default: $ss[] = 'female';
		}
	}
	if ($dg['min_age']!==null)
		if ($dg['min_age']>1) {
			$ss[] = 'from '.$dg['min_age'].' years';
		} else {
			$ss[] = 'from '.round(12*$dg['min_age']).' months';
		}
	if ($dg['max_age']!==null) {
		if ($dg['max_age']>1) {
			$ss[] = 'to '.($dg['max_age']-1).' years';
		} else {
			$ss[] = 'to '.round(12*$dg['max_age']-1).' months';
		}
	}
	echo html(capitalize(implode(', ',$ss)));
} ?>
<table class="fields">
<? foreach (query('SELECT * FROM nutrient ORDER BY `order`') as $nutrient) {
	$threshold = row0('SELECT * FROM threshold'
		.' WHERE '.$group_cond
		.' AND nutrient='.sql($nutrient['id'])
	);
	if (v('demographic_group')===null) {
		$auto_threshold = row0('SELECT * FROM threshold'
			.' WHERE user IS NULL'
			.' AND demographic_group='
				.sql($user['demographic_group'])
			.' AND nutrient='.sql($nutrient['id'])
		);

		$onchange = 'elem('.js($ID.'_auto_'.$nutrient['name']).').checked=false';
		$onauto = 'if (elem('.js($ID.'_auto_'.$nutrient['name']).').checked) {'
			.'elem('.js($ID.'_min_'.$nutrient['name']).').value = '
				.js($auto_threshold['min']).';'
			.'elem('.js($ID.'_best_'.$nutrient['name']).').value = '
				.js($auto_threshold['best']).';'
			.'elem('.js($ID.'_max_'.$nutrient['name']).').value = '
				.js($auto_threshold['max']).';'
		.'}';
	} else {
		$onchange = null;
		$onauto = null;
	}
	?>
	<tr>
		<th class="left"><?=html($nutrient['description'])?></th>
		<td>minimum <?=
			number_input('min_'.$nutrient['name'],
				$threshold['min'],
				null,false,$onchange
			)
			#.html($nutrient['unit'])
		?></td>
		<td>goal <?=
			number_input('best_'.$nutrient['name'],
				$threshold['best'],
				null,false,$onchange
			)
			#.html($nutrient['unit'])
		?></td>
		<td>maximum <?=
			number_input('max_'.$nutrient['name'],
				$threshold['max'],
				null,false,$onchange
			)
			.html($nutrient['unit'])
			.' '
			.mistake_label('min_'.$nutrient['name'])
			.' '
			.mistake_label('best_'.$nutrient['name'])
			.' '
			.mistake_label('max_'.$nutrient['name'])
		?></td>
		<? if (v('demographic_group')===null) { ?>
		<td><?=
			checkbox('auto_'.$nutrient['name'],
				( $threshold===null || 
				  $threshold['demographic_group']!==null ),
				'auto',
				false,$onauto
			)
		?></td>
		<? } ?>
	</tr>
<? } ?>
	<tr>
		<? if (v('demographic_group')===null) { ?>
		<td colspan="5" class="buttons">
		<? } else { ?>
		<td colspan="4" class="buttons">
		<? } ?>
			<?=ok_button('Save')?>
		</td>
	</tr>
</table>
<? end_form() ?>
<? include 'app/end.php' ?>
