<? $AUTH=true;
	require_once 'app/init.php';

	$row = given('product.id', array(
		'maker'=>'int',
		'packager'=>'int',
		'importer'=>'int',
		'distributor'=>'int',
		'parent'=>'int',
		'name'=>'str',
		'type'=>'int',
		'barcode'=>'str1',
		'typical_price'=>'float',
		'price_to_parent'=>'bool',
		'price_no_children'=>'bool',
		'price_no_recalc'=>'bool',
		'typical_units'=>'str1',
		'units_avoid_filling'=>'bool',
		'units_near_kg'=>'bool',
		'ingredients'=>'str1',
		'store_duration'=>'float',
		'store_temp_min'=>'float',
		'store_temp_max'=>'float',
		'packaging_weight'=>'float',
		'glaze_weight'=>'float',
		'net_weight'=>'float',
		'net_volume'=>'float',
	));

	//
	// if creating new food, merge nutrient values from parent
	//
	if ($row['id']===null && $row['parent']!==null) {
		$row2 = row0('* FROM product'
		             .' WHERE id='.sql($row['parent']));
		if ($row2!==null) {
			foreach (select('* FROM nutrient') as $nut) {
				$pn = row0(
					'* FROM product_nutrient'
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

	$old = fetch('product.id', abs($row['id']));
	$own_record = ($row['id']===null) ||
	              ( $old['user_id']!==null
	                && $old['user_id']==$_SESSION['user_id'] ) || 
	              has_right('admin');

	if (posting('calc_typicals')) {
		try {
			product_calc_typicals($row['id']);
			if (success('?id='.urlencode($row['id']))) return true;
		} catch (Exception $x) {
			if (failure($x)) return false;
		}

	} else if (posting()) try {
		if (!has_right('register-products'))
			throw Exception('you are not allowed to register products');
		if (!$own_record)
			throw Exception('you are not allowed to edit this record');

		$row['user_id'] = $_SESSION['user_id'];

		if (array_key_exists('barcode',$row) || !$row['id']) {
			if ($row['barcode']!==null)
				$row['barcode'] = trim($row['barcode']);
			$oldrow = row0(
				'* FROM product'
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
			if ( value('COUNT(*) FROM product'
			           .' WHERE name='.sql($row['name'])
			           .($row['id']!==null?
			           	' AND id<>'.sql($row['id'])
			           :'')) ) {
				mistake('name','There is already a product with this name.');
			}
		}

		if ($row['typical_price']!==null && $row['typical_price']<0)
			mistake('typical_price', 'Must not be negative.');
		if ($row['typical_units']!==null && $row['typical_units']<0)
			mistake('typical_units', 'Must not be negative.');
		if ($row['store_duration']!==null && $row['store_duration']<0)
			mistake('store_duration', 'Must not be negative.');
		if ($row['store_temp_min']!==null && ($row['store_temp_min']<-99 || $row['store_temp_min']>99))
			mistake('store_temp_min', 'Must not be negative.');
		if ($row['store_temp_max']!==null && ($row['store_temp_max']<-99 || $row['store_temp_max']>99))
			mistake('store_temp_max', 'Must not be negative.');
		if ($row['packaging_weight']!==null && $row['packaging_weight']<0)
			mistake('packaging_weight', 'Must not be negative.');
		if ($row['glaze_weight']!==null && $row['glaze_weight']<0)
			mistake('glaze_weight', 'Must not be negative.');
		if ($row['net_weight']!==null && $row['net_weight']<0)
			mistake('net_weight', 'Must not be negative.');
		if ($row['net_volume']!==null && $row['net_volume']<0)
			mistake('net_volume', 'Must not be negative.');

		$sdm = ( $_POST['store_duration_months']!==null
		         && $_POST['store_duration_months']!=='' )
			? intval($_POST['store_duration_months'])
			: null;
		if ($sdm!==null) {
			$row['store_duration'] += 30*$sdm;
		}

		if ($row['id']!==null) {
			$par = $row['parent'];
			while ($par!==null) {
				if ($par==$row['id']) {
					mistake('parent','Forms a circle.');
				}
				$par = value('parent FROM product'
				             .' WHERE id='.sql($par));
			}
		}

		if (correct()) {
			if ($row['id']<0 && $old['parent']!==null) {
				$addit = '&parent='.urlencode($old['parent']);
			} else {
				$addit = '';
			}

			$row['id'] = (int)store('product.id',$row);
			if (success('?id='.urlencode($row['id']).$addit)) return true;
		}
	} catch (Exception $x) {
		if (failure($x)) return false;
	}

	if ($row['type']==1) {
		$type = 'food';
	} else if ($row['type']==2) {
		$type = 'consumable';
	} else {
		$type = 'product';
	}

	$MODE = $row['id'];
	if (!$MODE) {
		$HEADING = 'New '.$type;
		$RO = 0;
	} else if ($MODE>0) {
		$row = fetch('product.id',$row);
		if ($row) {
			$owner = row0('* FROM person WHERE id='.sql($row['owner']));
			$HEADING = 'Product '.html($row['name'])
			           .($owner ? ' ('.$owner['name'].')' : '');
			$RO = ($MODE[0]!='+');
		} else {
			$STATUS = 404;
			$HEADING = 'Product does not exist';
			$RO = -1;
		}
	} else {
		$parent = $row['parent'];
		$row = fetch('product.id',-$row['id']);
		if ($row) {
			$HEADING = 'Delete '.$type.' '.html($row['name']).'?';
			$RO = 1;
		} else {
			$HEADING = 'Product deleted';
			$RO = -1;
			if ($parent!==null) {
				$HEADING .= ' - <a href="?'.html('id='.urlencode($parent)).'">Parent: '.html(value0('name FROM product WHERE id='.sql($parent))).'</a>';
			}
		}
	}

	if ($row['store_duration']!==null && $row['store_duration']!=='') {
		$row['store_duration_months'] =
			intval($row['store_duration']/30);
		$row['store_duration'] -= 30*$row['store_duration_months'];
	}


	if ($RO && $MODE>0) {
		$np=value('COUNT(*) FROM receipt'
			  .' WHERE product='
			  .sql($row['id'])
			  .' AND user_id='
			  .$_SESSION['user_id']);
		if ($np>0) {
			$HEADING .= '<span class="noprint">';
			$HEADING .= ' - <a href="'.html('purchases?prodid='.$row['id']).'">'.($np==1?$np.' purchase':$np.' purchases').'</a>';
			$HEADING .= '</span>';
		}
	}
?>
<? include 'app/begin.php' ?>
<? if ($RO>=0) { ?>
<? begin_form() ?>
<table class="fields">
	<? if ($RO) { ?>
	<tr><th>B/C:</th><td>
		<? if ($row['barcode']!==null) { ?>
			<?=input('barcode',$row,13,$RO)?>
		<? } else { ?>
			(unknown / unregistered)
		<? } ?>
	</td></tr>
	<? } else { ?>
	<tr><th>Name:</th><td>
		<?=input('name',$row,array(40,64),$RO)?>

		<? if ($row['type']!==null) {
			print hidden('type',$row);
		} else {
			print ' ';
			print dropdown('type',$row,array(
				array('1','Foods'),
				array('2','Consumables'),
				array('3','Miscellaneous'),
			),$RO);
		}?>

		<? if (!$RO || $row['barcode']!==null) { ?>
		<b>B/C:</b>
		<?=input('barcode',$row,13,$RO)?>
		<? } ?>
	</td></tr>
	<? } ?>

	<tr<?=$row['parent']?'':' class="noprint"'?>><th>Parent:</th><td>
		<?= $RO
			? ( $row['parent']
				?'<a href="'.html('product?id='.urlencode($row['parent'])).'">'
					.value0('name FROM product WHERE id='.sql($row['parent']))
				.'</a>'
				: '(no parent)'
			)
			: dropdown('parent',$row,
			           select('id AS value, name AS text FROM product'
			                  .($row['id']?' WHERE id<>'.sql($row['id']):'')
			                  .' ORDER BY name'),
			           $RO, array('','(root product)'))
		?>
	</td></tr>

	<tr><th>Variations:</th><td><?
		$j = 0;
		foreach (select('id,name FROM product WHERE parent='.sql($row['id'])) as $i=>$x) {
			if ($j) echo '<br />';
			$j = 1;
			if ($RO) {
				echo '<a href="product?id='.html(urlencode($x['id'])).'">'
					.html($x['name'])
				.'</a>';
			} else {
				echo '<strong>'.html($x['name']).'</strong>';
			}
		}
		if (!$j) {
			echo '(none)';
			if ($RO) {
				echo '<span class="noprint">';
				echo ' <a href="'.html('?parent='.$row['id']).'">[add new]</a>';
				echo '</span>';
			}
		}
	?></td></tr>
</table>

<table class="fields">
	<tr><th>Manufacturer:</th><td><?= person_select_html('maker',$row,$RO,'(anyone or unknown)') ?></td></tr>

	<tr><th>Packager:</th><td><?= person_select_html('packager',$row,$RO,'(anyone or unknown)') ?></td></tr>

	<? /*if (!$RO || $row['importer']) { ?>
	<tr><th>Importer:</th><td><?= person_select_html('importer',$row,$RO,'(anyone or unknown)') ?></td></tr>
	<? }*/ ?>

	<? /*if (!$RO || $row['distributor']) { ?>
	<tr><th>Distributor:</th><td><?= person_select_html('distributor',$row,$RO,'(anyone or unknown)') ?></td></tr>
	<? }*/ ?>

	<? if (!$RO || $row['typical_price'] || !$row['price_no_recalc']) { ?>
	<tr<?=$row['typical_price']?'':' class="noprint"'?>><th>Ex.price:</th><td>
		<? if (!$RO) { ?>
			<?=number_input('typical_price',$row,null,$RO).'€'?>
			&nbsp;
			<?=checkbox('price_to_parent',$row,
				    'updates parent',$RO)?>
			&nbsp;
			<?=checkbox('price_no_children',$row,
				    'does not update children',$RO)?>
			&nbsp;
			<?=checkbox('price_no_recalc',$row,
				    'do not auto-update',$RO)?>
		<? } else { ?>
			<?=($row['typical_price']!==null) ? html($row['typical_price']).'€&nbsp;' : ''?>
			<button id="<?=html($ID.'_calc_typicals')?>"
				type="submit"
				onclick="<?=html('return post_form('
					.js($ID).','
					.js($ID.'_calc_typicals')
				.')')?>"
				name="calc_typicals"
				value="1"
				class="refreshbutton"
			></button>
		<? } ?>
	</td></tr>
	<? } ?>

	<? if (!$RO || ($row['typical_units']!==null && ($row['type']!=3 || $row['typical_units']!=1))) { ?>
	<tr><th>Units:</th><td>
		<? if ($RO) { ?>
			<?=html($row['typical_units'])?>
		<? } else { ?>
			<?=number_input('typical_units',$row,null,$RO)?>
		<? } ?>

		<? if ($row['type']!=3 || $row['type']===null) { ?>
			units, packages, rations or pairs
		<? } else if (!$RO) { ?>
			units, packages or pairs
		<? } ?>
		&nbsp;
		<? if (!$RO && ($row['type']!=3 || $row['type']===null)) { ?>
			<?=checkbox('units_avoid_filling',$row,
				    'avoid filling units field',$RO)?>
			<?=checkbox('units_near_kg',$row,
				    'prefer round kg quantities',$RO)?>
		<? } ?>
	</td></tr>
	<? } ?>

	<? if (!$RO || $row['net_weight']!==null) { ?>
	<tr><th><?
		if ($row['type']!=3 || $row['type']===null) {
			echo 'Net weight:';
		} else {
			echo 'Weight:';
		}
	?></th><td><?
		if ($row['type']!=3 || $row['type']===null) {
			echo '℮ ';
		}
		if ($RO) {
			echo html($row['net_weight']);
		} else {
			echo number_input('net_weight',$row,null,$RO).'g';
		}
		if ($row['type']!=3 || $row['type']===null) {

			if (!$RO || $row['glaze_weight']!==null) {
				echo ' &nbsp; &nbsp; &nbsp; <strong>Glaze:</strong> ';
				if ($RO) {
					echo html($row['glaze_weight']);
				} else {
					echo number_input('glaze_weight',$row,null,$RO);
				}
				echo 'g';
			}

			if (!$RO || $row['packaging_weight']!==null) {
				echo ' &nbsp; &nbsp; &nbsp; <strong>Tare weight:</strong> ';
				if ($RO) {
					echo html($row['packaging_weight']);
				} else {
					echo number_input('packaging_weight',
							  $row,null,$RO);
				}
				echo 'g';
			}
		}
	?></td></tr>
	<? } ?>

	<? if ($row['type']!=3 || $row['type']===null) { ?>

	<? if (!$RO || $row['net_volume']!==null) { ?>
	<tr><th>Net.volume:</th><td><?
		print '℮ '.number_input('net_volume',$row,null,$RO).'ml';
	?></td></tr>
	<? } ?>

	<? if (!$RO || $row['store_duration_months'] || $row['store_duration'] || $row['store_temp_min'] || $row['store_temp_max'] || $row['store_conditions']) { ?>
	<tr><th>Storage:</th><td>
		<? if (!$RO || $row['store_duration_months'] || $row['store_duration']) { ?>
			up to

			<? if (!$RO || $row['store_duration_months']) { ?>
			<?=number_input('store_duration_months',$row,2,$RO)?> months
			<? } ?>

			<? if (!$RO || $row['store_duration']) { ?>
			<? if (!$RO || $row['store_duration_months']) { ?>
				and
			<? } ?>
			<?=number_input('store_duration',$row,2,$RO)?> days
			<? } ?>
		<? } ?>

		<? if (!$RO || $row['store_temp_min'] || $row['store_temp_max']) { ?>

		&nbsp;
		temperature

		<? if (!$RO || $row['store_temp_min']) { ?>
		min
		<?=number_input('store_temp_min',$row,3,$RO)?>
		<? } ?>

		<? if (!$RO || $row['store_temp_max']) { ?>
		max
		<?=number_input('store_temp_max',$row,3,$RO)?>°C,
		<? } ?>

		<? } ?>

		<? if (!$RO || $row['store_conditions']) { ?>
		&nbsp;
		in 
		<?=dropdown('store_conditions',$row,select(
			'id AS value,description AS text'
			.' FROM storage_conditions'
		),$RO,array('','(unknown)'))?>
		<? } ?>
	</td></tr>
	<? } ?>
	<? } ?>

	<? if (!$RO || $row['ingredients']!==null || $row['type']==1) { ?>
	<tr><th>Description:</th><td>
		<?=textarea('ingredients',$row,64,5,$RO)?>

		<? if ($RO && $row['type']==1) {
			echo '<div class="noprint">';
			echo '<a href="'.html('product-nutrients?id='.urlencode($row['id'])).'">Nutritional information</a>';
			echo '</div>';
		} ?>
	</td></tr>
	<? } ?>

<? if (has_right('register-products') && $own_record) { ?>
	<tr><td colspan="2" class="buttons">
		<? if ($MODE<0) { ?>
			<?=submit_button('Delete')?>
			<?=link_button('Keep',array('id'=>$row['id']),'cancel')?>
		<? } else if ($RO) { ?>
			<?=link_button('Edit',array('id'=>'+'.$row['id']),'edit')?>
			<?=link_button('Delete',array('id'=>-$row['id']),'delete')?>
		<? } else if ($MODE>0) { ?>
			<?=submit_button('Save')?>
			<?=link_button('Cancel',array('id'=>(int)$row['id']),'cancel')?>
		<? } else { ?>
			<?=submit_button('Save')?>
		<? } ?>
	</td></tr>
<? } ?>
</td></tr></table>
<? end_form() ?>
<? } ?>
<? include 'app/end.php' ?>
