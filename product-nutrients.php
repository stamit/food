<? $AUTH=true;
	require_once 'app/init.php';

	$proto = array(
		'sample_weight'=>array(0.0,''=>null),
		'sample_volume'=>array(0.0,''=>null),
		'refuse_weight'=>array(0.0,''=>null),
		'refuse_volume'=>array(0.0,''=>null),
	);
	foreach (col('name FROM nutrient WHERE basetable') as $name)
		$proto[$name] = array(0.0,''=>null);
	$row = given('product.id', $proto);

	$old = fetch('product.id', $row);
	$own_record = ($row['id']===null) ||
	              ( $old['user_id']!==null
	                && $old['user_id']==$_SESSION['user_id'] ) || 
	              has_right('admin');

	function product_fooddb_import($prodid,$fdbid) {
		global $fooddb_mapping;
		global $fooddb_multipliers;

		$pro = row('* FROM product WHERE id='.sql($prodid));

		$fdb = row0('* FROM fd_nutrients WHERE NDB_No='.sql($fdbid));
		if ($fdb===null) throw new Exception('FOODDB ID does not exist: '.intval($fdbid));

		$product = array(
			'id'=>$prodid,
		);

		if ($pro['sample_weight']===null)
			$product['sample_weight'] = 100.0;
		else
			$product['sample_weight'] = $pro['sample_weight'];

		$multiplier = $product['sample_weight']/100.0;

		$product['refuse_weight'] = $fdb['Refuse_Pct']*$multiplier;

		foreach (select('* FROM nutrient') as $nut) {
			$mapping = $fooddb_mapping[$nut['name']];
			$pn = row0('* FROM product_nutrient'
				   .' WHERE product='.sql($prodid)
				   .' AND nutrient='.sql($nut['id']));
			if ($mapping!==null && $fdb[$mapping]!==null && $pn===null) {
				$value = $fdb[$mapping]*$multiplier;
				if ($fooddb_multipliers[$nut['name']]!==null) {
					$value *= $fooddb_multipliers[$nut['name']];
				}
				store('product_nutrient.id',array(
					'product'=>$prodid,
					'nutrient'=>$nut['id'],
					'value'=>$value,
					'source'=>3,
					'id2'=>$fdbid,
				));
				if ($nut['basetable']) {
					$product[$nut['name']] = $value;
				}
				product_nutrient_on_change($prodid,$nut['id'],
							       $value);
			}
		}

		update('product.id',$product);
	}

	function product_parent_link($prodid,$parentid) {
		$multiplier = product_nutrient_link_multiplier($prodid,$parentid);

		if ($multiplier===null) {
			$par = row('* FROM product WHERE id='.sql($parentid));
			put('product.id',array(
				'id'=>$prodid,
				'sample_weight'=>$par['sample_weight'],
				'sample_volume'=>$par['sample_volume'],
				'refuse_weight'=>$par['refuse_weight'],
				'refuse_volume'=>$par['refuse_volume'],
			));
			$multiplier = 1.0;
		}

		foreach (select('* FROM nutrient') as $nut) {
			$pn = row0('* FROM product_nutrient'
				   .' WHERE product='.sql($prodid)
				   .' AND nutrient='.sql($nut['id']));
			if ( $pn===null || ($pn['value']===null && !$pn['source']) ) {
				product_nutrient_link($prodid,$nut,$parentid,
						      $multiplier);
			}
		}
	}

	function product_children_link($prodid) {
		foreach (select('* FROM nutrient') as $nut) {
			$pn = row0('* FROM product_nutrient'
				   .' WHERE product='.sql($prodid)
				   .' AND nutrient='.sql($nut['id']));
			if ( $pn===null || ($pn['value']===null && !$pn['source']) ) {
				product_nutrient_link_to_children(
					$prodid, $nut['id'], $nut['name']
				);
			}
		}
	}

	function product_clearlink($prodid,$source) {
		foreach (select('* FROM product_nutrient'
				.' WHERE product='.sql($prodid)
				.' AND source='.sql($source)) as $pn) {
			$nut = fetch('nutrient.id',$pn['nutrient']);
			if ($nut['basetable']) {
				store('product.id',array(
					'id'=>$prodid,
					$nut['name']=>null,
				));
			}
			store('product_nutrient.id',array('id'=> - $pn['id']));

			product_nutrient_on_change(
				$prodid,
				$nut['id'],
				null
			);
		}
	}

	if (posting()) try {
		if ($old===null)
			throw Exception('product does not exist');
		if (!has_right('register-products'))
			throw Exception('you are not allowed to register products');
		if (!$own_record)
			throw Exception('you are not allowed to edit this record');

		if ($_POST['fooddb_import']) {
			product_fooddb_import($row['id'], $old['usda_source']);
			if (success('')) return true;

		} else if ($_POST['fooddb_clear']) {
			product_clearlink($row['id'],3);
			if (success('')) return true;

		} else if ($_POST['parent_link']) {
			product_parent_link($row['id'], $old['parent']);
			if (success('')) return true;

		} else if ($_POST['parent_clear']) {
			product_clearlink($row['id'],1);
			if (success('')) return true;

		} else if ($_POST['children_link']) {
			product_children_link($row['id']);
			if (success('')) return true;

		} else if ($_POST['children_clearlink']) {
			product_clearlink($row['id'],2);
			if (success('')) return true;

		} else {
			foreach (select('* FROM nutrient') as $nut)
			if ($POST[$nut['name']]!==null) {
				if ($row['sample_weight']===null
				    && $row['sample_volume']===null) {
					mistake('sample_weight',
						'You gave nutrient information without a sample weight.');
					break;
				}
				break;
			}

			if ($row['sample_weight']!==null && $row['sample_weight']<0)
				mistake('sample_weight', 'Must not be negative.');
			if ($row['sample_volume']!==null && $row['sample_volume']<0)
				mistake('sample_volume', 'Must not be negative.');
			if ($row['refuse_weight']!==null && $row['refuse_weight']<0)
				mistake('refuse_weight', 'Must not be negative.');
			if ($row['refuse_volume']!==null && $row['refuse_volume']<0)
				mistake('refuse_volume', 'Must not be negative.');

			if ($row['fats_saturated'] + $row['fats_monounsaturated']
			    + $row['fats_polyunsaturated'] > $row['fats']) {
				mistake('fats','Sum of saturated, monounsaturated and polyunsaturated fats is larger than '.$row['fats'].'g.');
			} else if ($row['fats']===0.0) {
				$row['fats_saturated'] = 0;
				$row['fats_monounsaturated'] = 0;
				$row['fats_polyunsaturated'] = 0;
				$row['fats_polyunsaturated_n9'] = 0;
				$row['fats_polyunsaturated_n6'] = 0;
				$row['fats_polyunsaturated_n3'] = 0;
				$row['fats_trans'] = 0;
			}

			if (correct()) {
				$row['id'] = (int)store('product.id',$row);
				foreach (select('* FROM nutrient') as $nut) {
					if (array_key_exists($nut['name'],$row)) {
						$pn = row0('* FROM product_nutrient'
							   .' WHERE product='.sql($row['id'])
							   .' AND nutrient='.sql($nut['id']));
						if ($pn===null) {
							$pn = array(
								'product'=>$row['id'],
								'nutrient'=>$nut['id'],
							);
						}
						$pn['value'] = $row[$nut['name']];

						store('product_nutrient.product.nutrient',$pn);
						product_nutrient_on_change($row['id'],$nut['id'],$value);
					}
				}

				if (success('?id='.urlencode($row['id']))) return true;
			}
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	$MODE = $row['id'];
	$row = fetch('product.id',$row);
	if ($MODE[0]=='+') {
		$HEADING = 'Nutritional information for '.html($row['name']);
		$RO = 0;
	} else {
		$HEADING = 'Nutritional information for <a href="'.html('product?id='.urlencode($row['id'])).'">'.html($row['name']).'</a>';
		$RO = 1;
	}
?>
<? include 'app/begin.php' ?>

<? begin_form() ?>
<table class="fields"><tr>
	<tr><th>Parent:</th><td>
		<?= $row['parent']
			? ( $RO
				? '<a href="'.html('product-nutrients?id='.urlencode($row['parent'])).'">'
					.value0('name FROM product WHERE id='.sql($row['parent']))
				.'</a>'
				: value0('name FROM product WHERE id='.sql($row['parent']))
			)
			: '(no parent)'
		?>
	</td></tr>

	<?
		$variations = select('id,name FROM product WHERE parent='.sql($row['id']));
	?>

	<tr><th>Variations:</th><td><?
		$j = 0;
		foreach ($variations as $i=>$x) {
			if ($j) echo '<br />';
			$j = 1;
			if ($RO) {
				echo '<a href="product-nutrients?id='.html(urlencode($x['id'])).'">'
					.html($x['name'])
				.'</a>';
			} else {
				echo html($x['name']);
			}
		}
		if (!$j) {
			echo '(none)';
		}
	?></td></tr>
</tr></table>

<table class="fields">
	<th>USDA ID:</th><td><?=number_input('usda_source',null,null,$RO)?></td>

	<tr><th>Sample:</th><td>
		<? if (!$RO || $row['sample_weight']!==null) { ?>
		<?=number_input('sample_weight',$row,null,$RO)?>g
		<? } ?>
		<? if (!$RO || $row['sample_volume']!==null) { ?>
		<?=number_input('sample_volume',$row,null,$RO)?>ml
		<? } ?>
	</td></tr>
	<tr><th>Refuse:</th><td>
		<? if (!$RO || $row['refuse_weight']!==null) { ?>
		<?=number_input('refuse_weight',$row,null,$RO)?>g
		<? } ?>
		<? if (!$RO || $row['refuse_volume']!==null) { ?>
		<?=number_input('refuse_volume',$row,null,$RO)?>ml
		<? } ?>
	</td></tr>
</table>

<table class="fields layout">
	<tr>
	<? foreach (array(1,2,3,4) as $col) { ?>
		<td class="<?=$col==4?' last':''?>">
			<table class="fields">
			<? foreach (select('* FROM nutrient WHERE `column`='.$col
					   .' ORDER BY `order`') as $nut) {
				$pn = row0('* FROM product_nutrient'
					   .' WHERE product='.sql($row['id'])
					   .' AND nutrient='.sql($nut['id']));
				$name = $nut['name'];
				$value = $row[$nut['name']];

				if ($pn['source']>0) {
					echo '<tr class="linked">';
				} else if ($value===null) {
					echo '<tr class="empty">';
				} else {
					echo '<tr>';
				}
				echo '<th>'.html($nut['description']).'</th><td>';
				echo number_input(
					$name,
					ifnull($value,$pn['value']),
					6,
					($pn!==null && $pn['source']!=0) ||
					$RO
				).html($nut['unit']);
				echo '</td></tr>';
			} ?>
			</table>
		</td>
	<? } ?>
	</tr>

<? if (has_right('register-products') && $own_record) { ?>
	<tr><td colspan="4" class="buttons">
		<? if ($RO) { ?>
			<? if ($row['usda_source']) { ?>
			<?=submit_button('Link USDA','fooddb_import','1','left')?>
			<? } ?>
			<?=submit_button('Unlink USDA','fooddb_clear','1','left')?>
			<?=submit_button('Link parent','parent_link','1','left')?>
			<?=submit_button('Unlink parent','parent_clear','1','left')?>
			<?=submit_button('Link children','children_link','1','left')?>
			<?=submit_button('Unlink children','children_clearlink','1','left')?>
		<? } ?>

		<? if ($RO) { ?>
			<?=link_button('Edit',array('id'=>'+'.$row['id']),'edit')?>
		<? } else { ?>
			<?=submit_button('Save')?>
			<?=link_button('Cancel',array('id'=>(int)$row['id']),'cancel')?>
		<? } ?>
	</td></tr>
<? } ?>

</table>
<? end_form() ?>

<? include 'app/end.php' ?>
