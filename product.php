<? $AUTH=true;
	require_once 'app/init.php';

	$proto = array(
		'maker'=>array(0,''=>null),
		'packager'=>array(0,''=>null),
		'importer'=>array(0,''=>null),
		'distributor'=>array(0,''=>null),
		'parent'=>array(0,''=>null),
		'name'=>array('',''=>null),
		'type'=>array(0,''=>null),
		'barcode'=>array('',''=>null),
		'typical_price'=>array('',''=>null),
		'price_to_parent'=>false,
		'price_no_children'=>false,
		'price_no_recalc'=>false,
		'typical_units'=>array('',''=>null),
		'units_avoid_filling'=>false,
		'units_near_kg'=>false,
		'ingredients'=>array('',''=>null),
		'store_duration'=>array(0.0,''=>null),
		'store_temp_min'=>array(0.0,''=>null),
		'store_temp_max'=>array(0.0,''=>null),
		'packaging_weight'=>array(0.0,''=>null),
		'glaze_weight'=>array(0.0,''=>null),
		'net_weight'=>array(0.0,''=>null),
		'net_volume'=>array(0.0,''=>null),
		'sample_weight'=>array(0.0,''=>null),
		'sample_volume'=>array(0.0,''=>null),
		'refuse_weight'=>array(0.0,''=>null),
		'refuse_volume'=>array(0.0,''=>null),
	);
	foreach (col('SELECT name FROM nutrient WHERE basetable') as $name)
		$proto[$name] = array(0.0,''=>null);
	$row = given_record($proto,'id','product');

	$old = get($row['id'],'product');

	if ($row['id']!==null) {
		$disabled = !($old['user_id']===null ||
		              $old['user_id']==$_SESSION['user_id'] ||
		              has_right('admin') );
	} else {
		$disabled = false;
	}

	if ($row['id']===null && $row['parent']!==null) {
		$row2 = row0('SELECT * FROM product'
		             .' WHERE id='.sql($row['parent']));
		if ($row2!==null) {
			foreach (query('SELECT * FROM nutrient') as $nut) {
				$pn = row0(
					'SELECT * FROM product_nutrient'
					.' WHERE product='.sql($row['parent'])
					.' AND nutrient='.sql($nut['id'])
				);
				if ($pn && $pn['source']==0) {
					$row2[$nut['name']] = $pn['value'];
				} else {
					unset($row2[$nut['name']]);
				}
			}
			$row = array_merge($row2,$row);
			unset($row['id']);
			unset($row['barcode']);
		}
	}

	if (posting()) try {
		if (!has_right('register-products'))
			mistake('You are not allowed to register products.');

		if ($disabled)
			mistake('You are not allowed to edit to this record.');

		$row['user_id'] = $_SESSION['user_id'];

		if (array_key_exists('barcode',$row) || !$row['id']) {
			if ($row['barcode']!==null)
				$row['barcode'] = trim($row['barcode']);
			$oldrow = row0(
				'SELECT * FROM product'
				.' WHERE barcode='.sql($row['barcode'])
				.($row['id']!==null ?
					' AND id<>'.sql($row['id'])
				:'')
			);
			if ($oldrow !== null) {
				mistake('barcode','There is already a product with this barcode ('.html($oldrow['name']).').');
			}
		}

		if (array_key_exists('name',$row) || !$row['id']) {
			$row['name'] = trim($row['name']);
			if (mb_strlen($row['name'])<4)
				mistake('name','At least 4 characters.');
			if ( value('SELECT COUNT(*) FROM product'
			           .' WHERE name='.sql($row['name'])
			           .($row['id']!==null?
			           	' AND id<>'.sql($row['id'])
			           :'')) ) {
				mistake('name','There is already a product with this name.');
			}
		}

		if ($row['store_duration']!==null) {
			if ($row['store_duration']<0) {
				mistake('store_duration', 'Must not be negative.');
			}
		}
		$sdm = ( $_POST['store_duration_months']!==null
		         && $_POST['store_duration_months']!=='' )
			? intval($_POST['store_duration_months'])
			: null;
		if ($sdm!==null) {
			$row['store_duration'] += 30*$sdm;
		}

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

		if ($row['id']!==null) {
			$par = $row['parent'];
			while ($par!==null) {
				if ($par==$row['id']) {
					mistake('parent','Forms a circle.');
				}
				$par = value('SELECT parent FROM product'
				             .' WHERE id='.sql($par));
			}
		}

		foreach (query('SELECT * FROM nutrient') as $nut)
		if ($POST[$nut['name']]!==null) {
			if ($row['sample_weight']===null
			    && $row['sample_volume']===null) {
			    	mistake('sample_weight',
			    	        'You gave nutrient information without a sample weight.');
				break;
			}
			break;
		}

		if (correct()) {
			$row['id'] = put($row,'product');

			foreach (query('SELECT * FROM nutrient') as $nut)
			if (array_key_exists($nut['name'],$_POST)) {
				$pn = row0('SELECT * FROM product_nutrient'
				           .' WHERE product='.sql($row['id'])
				           .' AND nutrient='.sql($nut['id']));
				$value = $_POST[$nut['name']]=='' ? null
				         : floatval($_POST[$nut['name']]);
				if ($pn!==null) {
					if ($value===null) {
						execute('DELETE FROM product_nutrient'
						        .' WHERE id='.sql($pn['id']));
					} else if ($pn['value']!=$value) {
						execute('UPDATE product_nutrient'
						        .' SET value='.sql($value)
						           .', source=0, id2=NULL'
						        .' WHERE id='.$pn['id']);
					}
				} else if ($value !== null) {
					insert('product_nutrient',array(
						'product'=>$row['id'],
						'nutrient'=>$nut['id'],
						'value'=>$value,
					));
				}
				product_nutrient_on_change(
					$row['id'],
					$nut['id'],
					$value
				);
			}

			$s = null;
			if ($_POST['calc_typicals']) {
				product_calc_typicals($row['id']);
			} else if ($_POST['fooddb_import']) {
				product_fooddb_import($row['id'],
					intval($_POST['fooddb_source'])
				);
			} else if ($_POST['fooddb_clear']) {
				product_clearlink($row['id'],3);
			} else if ($_POST['parent_link']) {
				product_parent_link($row['id'],
					$row['parent']
				);
			} else if ($_POST['parent_clear']) {
				product_clearlink($row['id'],1);
			} else if ($_POST['children_link']) {
				product_children_link($row['id']);
			} else if ($_POST['children_clearlink']) {
				product_clearlink($row['id'],2);
			} else if ($row['type']==1) {
				$s = success($URL.'/foods',$row['id']);
			} else {
				$s = success($URL.'/products',$row['id']);
			}
			if ($s === null) {
				$s = success($URL.'/product?id='.$row['id'],$row['id']);
			}
			if ($s) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	if ($row['id']>0) {
		$row = row('SELECT * FROM product WHERE id='.sql($row['id']));
		$owner = row0('SELECT * FROM person WHERE id='.sql($row['owner']));
		$HEADING = 'Product "'.html($row['name']).($owner ? ' ('.$owner['name'].')' : '').'"';
	} else {
		$HEADING = 'New product';
	}
	if ($row['type']==1) {
		$HEADING .= ' (foods)';
	} else if ($row['type']==2) {
		$HEADING .= ' (consumables)';
	} else {
		$HEADING .= ' (miscellaneous)';
	}

	if ($row['type']==1) {
		$BREAD = array($URL=>'home', 'foods'=>'foods');
	} else {
		$BREAD = array($URL=>'home', 'products'=>'products');
	}

	if ($row['store_duration']!==null && $row['store_duration']!=='') {
		$row['store_duration_months'] =
			intval($row['store_duration']/30);
		$row['store_duration'] -= 30*$row['store_duration_months'];
	}
?>
<? include 'app/begin.php' ?>
<? include_script('product-script.js') ?>
<? begin_form($URL.'/product'.($row['id']?'?id='.$row['id']:'')) ?>
<?=hidden('id',$row['id'])?>

<table>
	<tr><td>

		<table>
			<tr><th class="left">Barcode:</th><td><?
				print input('barcode',$row,13,$disabled);

				if ($row['id']) {
					$np=value('SELECT COUNT(*) FROM receipt'
					          .' WHERE product='
					          .sql($row['id'])
					          .' AND user_id='
					          .$_SESSION['user_id']);
					if ($np>0) {
						echo ' <a href="'.html('purchases?prodid='.$row['id']).'">'.($np==1?$np.' purchase':$np.' purchases').'</a>';
					}
				}
			?></td></tr>

			<tr><th class="left">Name:</th><td><?
				print input('name',$row,array(40,64),$disabled);
				if ($row['type']!==null) {
					print hidden('type',$row);
				} else {
					print ' ';
					print dropdown('type',$row,array(
						array('1','Foods'),
						array('2','Expendables'),
						array('3','Miscellaneous'),
					),$disabled);
				}
				if ($row['id']) {?>
					<a href="<?=html('?parent='.$row['id'])?>">New variation</a>
				<?}
			?></td></tr>

			<tr><th class="left">Manufacturer:</th><td><?
				print dropdown('maker',$row,
					query('SELECT id AS value, name AS text'
					      .' FROM person ORDER BY name'),
				$disabled, array('','(anyone or unknown)'));
				if ($row['maker']) {
					print ' <a href="'.html('person?id='.urlencode($row['maker'])).'"><img src="app/link.png" alt="go" class="icon"></a>';
				}
			?></td></tr>

			<tr><th class="left">Packager:</th><td><?
				print dropdown('packager',$row,
					query('SELECT id AS value, name AS text'
					      .' FROM person ORDER BY name'),
				$disabled, array('','(anyone or unknown)'));
				if ($row['packager']) {
					print ' <a href="'.html('person?id='.urlencode($row['packager'])).'"><img src="app/link.png" alt="go" class="icon"></a>';
				}
			?></td></tr>

			<tr><th class="left">Importer:</th><td><?
				print dropdown('importer',$row,
					query('SELECT id AS value, name AS text'
					      .' FROM person ORDER BY name'),
				$disabled, array('',
					'(anyone or unknown or local product)'
				));
				if ($row['importer']) {
					print ' <a href="'.html('person?id='.urlencode($row['importer'])).'"><img src="app/link.png" alt="go" class="icon"></a>';
				}
			?></td></tr>

			<tr><th class="left">Distributor:</th><td><?
				print dropdown('distributor',$row,
					query('SELECT id AS value, name AS text'
					      .' FROM person ORDER BY name'),
				$disabled, array('','(anyone or unknown)'));
				if ($row['distributor']) {
					print ' <a href="'.html('person?id='.urlencode($row['distributor'])).'"><img src="app/link.png" alt="go" class="icon"></a>';
				}
			?></td></tr>

			<tr><th class="left">Typical price:</th><td>
				<?=number_input('typical_price',$row,null,
				                $disabled).'€'?>
				&nbsp;
				<?=checkbox('price_to_parent',$row,
				            'affects parent',$disabled)?>
				&nbsp;
				<?=checkbox('price_no_children',$row,
				            'does not affect children',$disabled)?>
				&nbsp;
				<?=checkbox('price_no_recalc',$row,
				            'do not recalculate price',$disabled)?>
				&nbsp;
				<button id="<?=html($ID.'_calc_typicals')?>"
					type="submit"
					onclick="<?=html('return post_form('
						.js($ID).','
						.js($ID.'_calc_typicals')
					.')')?>"
					name="calc_typicals" value="1"
					<?=$disabled?' disabled="disabled"':''?>
				>Recalculate</button>
			</td></tr>

			<tr><th class="left">Units:</th><td>
				<?=number_input('typical_units',$row,null,
				                $disabled)?>
				<? if ($row['type']!=3 || $row['type']===null) { ?>
					units, packages, rations or pairs
				<? } else { ?>
					units, packages or pairs
				<? } ?>
				&nbsp;
				<? if ($row['type']!=3 || $row['type']===null) { ?>
					<?=checkbox('units_avoid_filling',$row,
					            'avoid filling units field',$disabled)?>
					<?=checkbox('units_near_kg',$row,
					            'prefer round kg quantities',$disabled)?>
				<? } ?>
			</td></tr>

			<tr><th class="left"><?
				if ($row['type']!=3 || $row['type']===null)
					echo 'Net weight:';
				else
					echo 'Weight:';
			?></th><td><?
				if ($row['type']!=3 || $row['type']===null) {
					echo '℮ ';
				}
				echo number_input('net_weight',$row,null,
				                  $disabled).'g';
				if ($row['type']!=3 || $row['type']===null) {
					print ' + <strong>glaze</strong> ';
					print number_input('glaze_weight',
					                   $row,null,
					                   $disabled).'g';
					print ' &nbsp; ( + packaging weight';
					print number_input('packaging_weight',
					                   $row,null,
					                   $disabled).'g )';
				}
			?></td></tr>

			<? if ($row['type']!=3 || $row['type']===null) { ?>
			<tr><th class="left">Net.volume:</th><td><?
				print '℮ '.number_input('net_volume',$row,null,
				                        $disabled).'ml';
			?></td></tr>

			<tr><th class="left">Storage:</th><td><?
				print number_input('store_duration_months',
				                   $row,2,$disabled).' months and ';
				print number_input('store_duration',$row,2,
				                   $disabled).' days';
				print ', temperature';
				print number_input('store_temp_min',$row,3,
				                   $disabled).'-';
				print number_input('store_temp_max',$row,3,
				                   $disabled).'°C';
				print ', in ';
				print dropdown('store_conditions',$row,query(
					'SELECT id AS value,description AS text'
					.' FROM storage_conditions'
				),null,array('','(unknown)'));
			?></td></tr>
			<? } ?>

			<? if ($row['type']!=3 || $row['type']===null) { ?>
			<tr><th class="left top">Ingredients:</th><td><?
				print textarea('ingredients',$row,64,5,
				               $disabled);
			?></td></tr>
			<? } else { ?>
			<tr><th class="left top">Specifications:</th><td><?
				print textarea('ingredients',$row,64,5,
				               $disabled);
			?></td></tr>
			<? } ?>

			<tr><th class="left">Variation of:</th><td><?
				print dropdown('parent',$row,
					query('SELECT id AS value, name AS text FROM product'
						.($row['id']?' WHERE id<>'.sql($row['id']):'')
					.' ORDER BY name'),
				$disabled, array('','(root product)'));
				if ($row['parent']) {
					print ' <a href="'.html('product?id='.urlencode($row['parent'])).'"><img src="app/link.png" alt="go" class="icon"></a>';
				}
			?></td></tr>
			<tr><td colspan="2"><?
				foreach (query('SELECT id,name FROM product WHERE parent='.sql($row['id'])) as $i=>$x) {
					if ($i) print '<br />';
					print '<a href="product?id='.html(urlencode($x['id'])).'">'
						.html($x['name'])
					.'</a>';
				}
			?>&nbsp;</td></tr>
		</table>

		<? if ($row['type']==1 || $row['type']===null) { ?>
			<div id="<?=html($ID.'_nutrients')?>">
			<? include 'product-nutrients.php' ?>
			</div>
		<? } ?>
	</td></tr>
<? if (has_right('register-products')) { ?>
	<tr><td colspan="3" class="buttons">
		<?=ok_button('Save')?>
	</td></tr>
<? } ?>
</td></tr></table>
<? end_form() ?>

<? include 'app/end.php' ?>
