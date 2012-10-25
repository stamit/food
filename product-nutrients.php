<? $AUTH=true;
	require_once 'app/init.php';

	function product_fooddb_import($prodid) {
		$pro = fetch('product.id',$prodid);
		if ($pro===null) return;

		$ndb_no = $pro['usda_source'];
		$fdb = row0('* FROM usda_food_des WHERE NDB_No='.sql($ndb_no));
		if ($fdb===null) throw new Exception('USDA food ID '.repr($ndb_no).' was not found in database');

		$product = array(
			'id'=>$prodid,
		);

		if ($pro['sample_weight']===null) {
			$product['sample_weight'] = 100.0;
			$sw  = 100.0;
		} else {
			$sw = $pro['sample_weight'];
		}

		$multiplier = $sw/100.0;

		$product['refuse_weight'] = $fdb['Refuse']*$multiplier;

		execute('UPDATE nutrient SET unit='.sql("\xc2\xb5g").' WHERE unit='.sql("\xce\xbcg"));  # this needs to stay here

		foreach (select('id,tag,unit,basetable,name FROM nutrient') as $nut) {
			$ns = select('d.Nutr_Val AS value, n.Units AS unit'
			             .' FROM usda_nutr_def n JOIN usda_nut_data d ON n.Nutr_No=d.Nutr_No'
			             .' WHERE n.Tagname='.sql($nut['tag']).' AND d.NDB_No='.sql($ndb_no));
			$n = null;
			foreach ($ns as $n1) {
				// XXX multiply/divide?
				if ($n1['unit']==$nut['unit']) {
					$n = $n1;
					break;
				//} else {
				//	error_log('UNITS DIFFER: '.repr($nut['tag']).' '.repr($n['unit']).' '.repr($nut['unit']));
				}
			}

			$pn = row0('* FROM product_nutrient'
				   .' WHERE product='.sql($prodid)
				   .' AND nutrient='.sql($nut['id']));
			if ($pn===null) {
				$pn = array(
					'product'=>$prodid,
					'nutrient'=>$nut['id'],
					'source'=>0,
					'value'=>null,
					'id2'=>$fdbid,
				);
			}
			if ($pn['value']===null && $pn['source']==0) {
				if ($n!==null) {
					$value = $n['value']*$multiplier;
				} else {
					$value = null;
				}

				$pn['source'] = 3;
				$pn['value'] = $value;
				store('product_nutrient.id',$pn);

				if ($nut['basetable']) {
					$product[$nut['name']] = $value;
				}

				product_nutrient_on_change($prodid,$nut['id'],$value);
			}
		}
		$product['default_source'] = 3;
		store('product.id',$product);
	}

	function product_parent_link($prodid,$parentid) {
		$row = array(
			'id'=>$prodid,
			'default_source'=>1,
		);

		$multiplier = product_nutrient_link_multiplier($prodid,$parentid);
		if ($multiplier===null) {
			$par = row0('* FROM product WHERE id='.sql($parentid));
			if ($par!==null) {
				$row['sample_weight'] = $par['sample_weight'];
				$row['sample_volume'] = $par['sample_volume'];
				$row['refuse_weight'] = $par['refuse_weight'];
				$row['refuse_volume'] = $par['refuse_volume'];
			} else {
				$row['sample_weight'] = 100;
				$row['sample_volume'] = null;
				$row['refuse_weight'] = 0;
				$row['refuse_volume'] = null;
			}
			$multiplier = 1.0;
		}

		store('product.id',$row);

		foreach (select('* FROM nutrient') as $nut) {
			$pn = fetch_product_nutrient($prodid,$nut['id']);
			if ( $pn===null || ($pn['value']===null && $pn['source']==0) ) {
				product_nutrient_link($prodid,$nut,$parentid,
						      $multiplier);
			}
		}
	}

	function product_children_link($prodid) {
		foreach (select('* FROM nutrient') as $nut) {
			$pn = fetch_product_nutrient($prodid,$nut['id']);
			if ( $pn===null || ($pn['value']===null && $pn['source']==0) ) {
				product_nutrient_link_to_children(
					$prodid, $nut['id'], $nut['name']
				);
			}
		}
		store('product.id',array('id'=>$prodid,'default_source'=>2));
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
		store('product.id',array('id'=>$prodid,'default_source'=>null));
	}


################################################################################	


	$row = given('product.id', array(
		'usda_source'=>array('zeropad()',array(),5,  ''=>null),
		'sample_weight'=>'float',
		'sample_volume'=>'float',
		'refuse_weight'=>'float',
		'refuse_volume'=>'float',
	));

	$nutids=array();
	$nuts=array();
	$basetables=array();
	foreach (select('id,name,basetable FROM nutrient') as $x) {
		if ($_POST[$x['name']]!==null) {
			$nutids[$x['name']] = $x['id'];
			$nuts[$x['name']] = ($_POST[$x['name']]!='') ? floatval($_POST[$x['name']]) : null;
			if ($x['basetable']) $basetables[$x['name']] = true;
		}
	}

	$old = fetch('product.id', $row);
	$own_record = ($row['id']===null) ||
	              ( $old['user_id']!==null
	                && $old['user_id']==$_SESSION['user_id'] ) || 
	              has_right('admin');

	if (posting()) try {
		if ($old===null)
			throw Exception('product does not exist');
		if (!has_right('register-products'))
			throw Exception('you are not allowed to register products');
		if (!$own_record)
			throw Exception('you are not allowed to edit this record');

		if ($_POST['fooddb_import']) {
			product_fooddb_import($row['id']);
			if (success('')) return true;

		} else if ($_POST['fooddb_clear']) {
			product_clearlink($row['id'],3);
			if (success('')) return true;

		} else if ($_POST['parent_link']) {
			product_parent_link($row['id'],$old['parent']);
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
				foreach ($nuts as $name => $value) {
					$pn = row0('* FROM product_nutrient'
						   .' WHERE product='.sql($row['id'])
						   .' AND nutrient='.sql($nutids[$name]));
					if ($pn==null) {
						$pn = array(
							'product'=>$row['id'],
							'nutrient'=>$nutids[$name],
							'source'=>0,
						);
					}

					if ($pn['source']==0) {
						if ($basetables[$name]) $row[$name] = $value;
						$pn['value'] = $value;
						#if ($pn['nutrient']==60)
						store('product_nutrient.id',$pn);
						product_nutrient_on_change($row['id'],$nutids[$name],$value);
					}
				}

				$row['id'] = (int)store('product.id',$row);

				if (success('?id='.urlencode($row['id']))) return true;
			}
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	$MODE = $row['id'];
	$row = fetch('product.id',$row);

	if ($row['usda_source']===null) {
		$usda_sources = col('id2 FROM product_nutrient WHERE product='.sql($row['id']).' AND source=3 GROUP BY id2');
		if (count($usda_sources)==1) {
			$row['usda_source'] = zeropad($usda_sources[0],5);
		}
	}

	if ($MODE[0]=='+') {
		$HEADING = 'Nutritional information for '.html($row['name']);
		$RO = 0;
	} else {
		$HEADING = 'Nutritional information for <a href="'.html('product?id='.urlencode($row['id'])).'">'.html($row['name']).'</a>';
		$RO = 1;

		if ($row['usda_source']!==null) {
			$usda_text = ifnull(value0('Long_Desc FROM usda_food_des WHERE NDB_No='.sql($row['usda_source'])),'???');
		} else {
			$usda_text = implode(', ', $usda_sources);
		}
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
	<tr<?=$row['default_source']==3?' class="ndb"':''?>><th>USDA ID:</th><td><?=input('usda_source',$row,5,$RO).($RO ? ' '.html($usda_text) :'')?></td>

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
				$value = $pn['value'];

				$class=array();

				if ($pn['source']==3) {
					$class[]='ndb';
				} else if ($pn['source']>0) {
					$class[]='linked';
				} else if (!$RO) {
					$class[]='editing';
				}
				if ($value===null) $class[]='empty';
				if (count($class)) {
					echo '<tr class="'.html(implode(' ',$class)).'">';
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
