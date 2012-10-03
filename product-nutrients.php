<? $AUTH=true;
	require_once 'app/init.php';
	if ($row===null) {
		$row = fetch('product.id',intval($_GET['id']));
	}
?>
<h3>Nutritional information</h3>

<table class="layout"><tr>
	<td>
		<table class="fields">
			<tr><th>Sample:</th><td>
				<?=number_input('sample_weight',$row,null,$disabled)?>g
				<?=number_input('sample_volume',$row,null,$disabled)?>ml
			</td></tr>
			<tr><th>Refuse:</th><td>
				<?=number_input('refuse_weight',$row,null,$disabled)?>g
				<?=number_input('refuse_volume',$row,null,$disabled)?>ml
			</td></tr>
		</table>
	</td>
	<td>
		<strong>FOODDB ID:</strong>
		<?=number_input('fooddb_source',null,null,$disabled)?>
		<button type="submit" onclick="<?=html('return product_fooddb_import('.js($ID).')')?>" name="fooddb_import" value="1" <?=$disabled?' disabled="disabled"':''?>>Import</button>
		<button type="submit" onclick="<?=html('return product_fooddb_clear('.js($ID).')')?>" name="fooddb_clear" value="1" <?=$disabled?' disabled="disabled"':''?>>Clear</button>
	</td>
	<td>
		<button type="submit" onclick="<?=html('return product_parent_link('.js($ID).')')?>" name="parent_link" value="1" <?=$disabled?' disabled="disabled"':''?>>Link parent</button>
		<button type="submit" onclick="<?=html('return product_parent_clear('.js($ID).')')?>" name="parent_clear" value="1" <?=$disabled?' disabled="disabled"':''?>>Clear</button>
	</td>
	<td>
		<button type="submit" onclick="<?=html('return product_children_link('.js($ID).')')?>" name="children_link" value="1" <?=$disabled?' disabled="disabled"':''?>>Link children</button>
		<button type="submit" onclick="<?=html('return product_children_clearlink('.js($ID).')')?>" name="children_clearlink" value="1" <?=$disabled?' disabled="disabled"':''?>>Clear</button>
	</td>
</tr></table>

<table class="layout"><tr>
<? foreach (array(1,2,3,4) as $col) { ?>
<td class="<?=$col==4?' last':''?>">
	<table class="fields">
	<? foreach (select('* FROM nutrient WHERE `column`='.$col
	                   .' ORDER BY `order`') as $nut) { ?>
		<tr><th><?=html($nut['description'])?>:</th><td><?
			$pn = row0('* FROM product_nutrient'
			           .' WHERE product='.sql($row['id'])
			           .' AND nutrient='.sql($nut['id']));
			print number_input(
				$nut['name'],
				ifnull($row[$nut['name']],$pn['value']),
				6,
				($pn!==null && $pn['source']!=0) ||
				$disabled
			).$nut['unit'];
		?></td></tr>
	<? } ?>
	</table>
</td>
<? } ?>
</tr></table>
