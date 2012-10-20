<?

function website_html($row) {
	return $row['person.website']!=null ? '<a href="'.html('http://'.$row['person.website']).'">'.html($row['person.website']).'</a>' : '';
}

function person_or_store_name_html($row) {
	if ($row['person.id']!==null) {
		return '<a href="person?id='.html($row['person.id']).'">'
			.html($row['person.name'])
		.'</a>';
	} else if ($row['store.id']!==null) {
		return '<a href="store?id='.html($row['store.id']).'">'
			.html($row['store.name'])
		.'</a>';
	} else {
		return '';
	}
}
function person_name_html($row) {
	return $row['person.id']===null?'':'<a href="person?id='.html($row['person.id']).'">'
		.html($row['person.name'])
	.'</a>';
}
function person_select_html($field,$row,$RO,$nulltext) {
	return $RO
		? ( $row[$field]
			? '<a href="'.html('person?id='.urlencode($row[$field])).'">'
				.html(value0('name FROM person WHERE id='.sql($row[$field])))
			  .'</a>'
			: ''
		)
		: dropdown($field,$row,
			   select('id AS value, name AS text'
				  .' FROM person ORDER BY name'),
			   $RO, array('',ifnull($nulltext,'(unknown)')));
}

function store_name_html($row) {
	return $row['store.id']===null?'':'<a href="store?id='.html($row['store.id']).'">'
		.html($row['store.name'])
	.'</a>';
}

function product_name_html($row) {
	global $URL;
	return $row['product.id']===null?'':'<a href="'.html($URL.'/product?id='.$row['product.id']).'">'
		.html($row['product.name'])
	.'</a>';
}

function product_default_quantity_html($row) {
	if ($row['product.net_weight']!==null) {
		return rtrim(rtrim(number_format($row['product.net_weight'],1),'0'),'.').'g';
	} else if ($row['product.net_volume']!==null) {
		return rtrim(rtrim(number_format($row['product.net_volume'],1),'0'),'.').'ml';
	} else if ($row['product.typical_units']!==null) {
		return 'x'.number_format($row['product.typical_units'],0);
	} else {
		return '';
	}
}

function cart_quantity_html($row) {
	switch ($row['cart_item.unit']) {
	case 0: return 'x'.number_format($row['cart_item.quantity'],0);
	case 1: return rtrim(rtrim(number_format($row['cart_item.quantity'],1),'0'),'.').'g';
	case 2: return rtrim(rtrim(number_format($row['cart_item.quantity'],1),'0'),'.').'ml';
	}
}

function cart_price_html($row) {
	return html(currency_decode($row['cart_item.price']));
}

function quantity_html($x) {
	return ($x!==null)?number_format($x,1):'';
}

function quantity2_html($x) {
	return ($x!==null)?number_format($x,2):'';
}

function consumption_quantity_html($row) {
	if ($row['consumption.weight'] !== null) {
		$s = html($row['consumption.weight'].'g');
		if ($row['consumption.units'] !== null)
			$s = '×'.html($row['consumption.units']).'='.$s;
	} else if ($row['consumption.volume'] !== null) {
		$s = html($row['consumption.volume'].'ml');
		if ($row['consumption.units'] !== null)
			$s = '×'.html($row['consumption.units']).'='.$s;
	} else if ($row['consumption.units'] !== null) {
		$s = '×'.html($row['consumption.units']);
	}
	return $s;
}

function consumption_time_html($row) {
	return $row['consumption.consumed']===null?'':'<a href="consumption?id='.html($row['consumption.id']).'">'
		.html($row['consumption.consumed'])
	.'</a>';
}

function get_timezone_opts() {
	$tz_opts = array();
	$continent = null;
	foreach(timezone_identifiers_list() as $value) {
		if (preg_match('/^(America|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific)\//', $value)) {
			$ex = explode("/", $value,2); //obtain continent,city   
			$tz_opts[] = array(
				'value'=>$value,
				'grp'=>$ex[0],
				'text'=>$ex[1],
			);
		}
	}
	return $tz_opts;
}

function authenticate_user($user,$password) {
	return sha1($password) == $user['password'];
}

function fetch_product_nutrient($prodid,$nutid) {
	return row0('* FROM product_nutrient'
	            .' WHERE product='.sql($prodid)
	            .' AND nutrient='.sql($nutid));
}

function product_nutrient_on_change($prodid,$nutid,$value,$nutname=null) {
	if ($nutname===null) {
		$nutname = value('name FROM nutrient'
		                 .' WHERE id='.sql($nutid));
	}

	foreach (select('id, product FROM product_nutrient'
	                .' WHERE nutrient='.sql($nutid)
	                .' AND source=1 AND id2='.sql($prodid)) as $pn) {
		$mult=product_nutrient_link_multiplier($pn['product'],$prodid);
		if ($mult===null) {
			$value2 = null;
		} else if ($value!==null) {
			$value2 = $value*$mult;
		} else {
			$value2 = null;
		}

		store('product_nutrient.id',array(
			'id'=>$pn['id'],
			'value'=>$value2,
		));

		if (value('basetable FROM nutrient'
		          .' WHERE id='.sql($nutid))) {
		        store('product.id',array(
		        	'id'=>$pn['product'],
		        	$nutname=>$value2,
		        ));
		}
		product_nutrient_on_change($pn['product'],$nutid,$value2,
		                               $nutname);
	}

	$par = value0('parent FROM product WHERE id='.sql($prodid));
	if ($par!==null) {
		$parsrc = value0('source FROM product_nutrient'
		                 .' WHERE product='.sql($par)
		                 .' AND nutrient='.sql($nutid));
		if ($parsrc==2) {
			product_nutrient_link_to_children($par,$nutid,$nutname);
		}
	}
}

function product_nutrient_link_to_children($prodid,$nutid,$nutname=null) {
	if ($nutname===null) {
		$nutname = value('name FROM nutrient'
		                 .' WHERE id='.sql($nutid));
	}

	foreach (col('id FROM product'
	             .' WHERE parent='.sql($prodid)) as $cid) {
		if (product_nutrient_check_for_loops($cid,$nutid,$prodid)) {
			throw new Exception('Nutrient '.$nutid.' links form a loop.');
		}
	}

	$prod = row('* FROM product WHERE id='.sql($prodid));

	if ($prod['sample_weight']===null) {
		$value = null;
	} else {
		$r = row('SUM(pn.value*p.market_weight/(p.sample_weight-IFNULL(p.refuse_weight,0)))/SUM(p.market_weight) AS value, SUM(pn.value IS NULL) AS empty, COUNT(*) AS total FROM product p LEFT JOIN product_nutrient pn ON (pn.product=p.id AND pn.nutrient='.sql($nutid).') WHERE p.parent='.sql($prodid).' AND p.price_to_parent AND p.market_weight>0');
		if ($r['empty'] || !$r['total']) {
			$value = null;
		} else {
			$value = $r['value'];
			$value *= $prod['sample_weight']-$prod['refuse_weight'];
		}
	}

	store('product_nutrient.id',array(
		'id'=>value0('id FROM product_nutrient'
		             .' WHERE product='.sql($prodid)
		             .' AND nutrient='.sql($nutid)),
		'product'=>$prodid,
		'nutrient'=>$nutid,
		'value'=>$value,
		'source'=>2,
		'id2'=>$prodid,
	));

	if (value('basetable FROM nutrient WHERE id='.sql($nutid))) {
		store('product.id',array(
			'id'=>$prodid,
			$nutname=>$value,
		));
	}

	product_nutrient_on_change($prodid,$nutid,$value,$nutname);
}

function product_nutrient_link_multiplier($prodid,$parentid) {
	$par = row0('sample_weight,sample_volume,refuse_weight,refuse_volume'
	            .' FROM product WHERE id='.sql($parentid));
	$prod = row0('sample_weight,sample_volume,refuse_weight,refuse_volume'
	             .' FROM product WHERE id='.sql($prodid));
	if ($par!==null && $par['sample_weight']!==null && $prod['sample_weight']!==null) {
		$multiplier = ($prod['sample_weight']-ifnull($prod['refuse_weight'],0))
		              / ($par['sample_weight']-ifnull($par['refuse_weight'],0));
	} else if ($prod!==null && $par['sample_volume']!==null && $prod['sample_volume']!==null) {
		$multiplier = ($prod['sample_volume']-ifnull($prod['refuse_volume'],0))
		              / ($par['sample_volume']-ifnull($par['refuse_volume'],0));
	} else {
		$multiplier = null;
	}
	return $multiplier;
}

function product_nutrient_link($prodid,$nut,$linkid,$multiplier=null) {
	if (!is_array($nut)) $nut = fetch('nutrient.id',$nut);
	if ($multiplier===null)
		$multiplier=product_nutrient_link_multiplier($prodid,$linkid);

	$pn = row0('* FROM product_nutrient'
	           .' WHERE product='.sql($prodid)
	           .' AND nutrient='.sql($nut['id']));

	if (product_nutrient_check_for_loops($linkid,$nut['id'],$prodid)) {
		throw new Exception('Nutrient '.$nut['tag'].' links form a loop.');
	}

	$value = value0('value FROM product_nutrient'
	                 .' WHERE product='.sql($linkid)
	                 .' AND nutrient='.sql($nut['id']));
	if ($multiplier===null) {
		$value = null;
	} else if ($value!==null) {
		$value *= $multiplier;
	}

	store('product_nutrient.id',array(
		'id'=>$pn['id'],
		'product'=>$prodid,
		'nutrient'=>$nut['id'],
		'value'=>$value,
		'source'=>1,
		'id2'=>$linkid,
	));
	if ($nut['basetable']) {
		store('product.id',array(
			'id'=>$prodid,
			$nut['name']=>$value,
		));
	}
	product_nutrient_on_change($prodid,$nut['id'],$value);
}

function product_nutrient_check_for_loops($prodid,$nutid,$oid) {
	if ($prodid == $oid)
		return true;

	$pn = row0('* FROM product_nutrient'
	           .' WHERE product='.sql($prodid)
	           .' AND nutrient='.sql($nutid));

	if ($pn['source']==1) {
		if (product_nutrient_check_for_loops($pn['id2'],$nutid,$oid)) {
			return true;
		}

	} else if ($pn['source']==2) {
		foreach (col('id FROM product'
		             .' WHERE parent='.sql($prodid)) as $cid) {
			if (product_nutrient_check_for_loops($cid,$nutid,$oid)){
				return true;
			}
		}
	}

	return false;
}

function get_right($right) {
	if (is_int($right)) {
		return row('* FROM `right` WHERE id='.sql($right));
	} else if (is_array($right)) {
		return $right;
	} else {
		return row('* FROM `right` WHERE name='.sql($right));
	}
}

function set_right($right_id) {
	unset_right($right_id);
	insert('user_right',array('user'=>$_SESSION['user_id'],'right'=>$right['id']));
}
function unset_right($right_id) {
	execute('DELETE FROM user_right WHERE user='.sql($_SESSION['user_id']).' AND `right`='.sql($right['id']));
}

function has_right($right, $user_id=0) {
	$right = get_right($right);

	if ($user_id===0) {
		$user_id = $_SESSION['user_id'];
	} else if (is_array($user_id)) {
		$user_id = $user_id['id'];
	}

	if (!$user_id)
		return 0;

	return value('COUNT(*) FROM user_right WHERE user='.sql($user_id).' AND `right`='.sql($right['id']));
}

function product_equivalents_for_stats($prod_id,&$equivs) {
	$equivs['0'.$prod_id] = true;
	foreach (col('id FROM product WHERE parent='.sql($prod_id)
	             .' AND price_to_parent') as $child_id) {
		if (!$equivs['0'.$child_id]) {
			product_equivalents_for_stats($child_id,$equivs);
		}
	}
}

function product_calc_typicals($prod) {
	if (!is_array($prod)) $prod = fetch('product.id',$prod);

	if ($prod['price_no_children'] || $prod['barcode']!==null) {
		$where = ' WHERE product='.sql($prod['id']);
	} else {
		$equivs = array();
		product_equivalents_for_stats($prod['id'],$equivs);
		$ids = array();
		foreach ($equivs as $id=>$true) $ids[] = intval($id);
		$where = ' WHERE product IN ('
			.implode(',',array_map('sql',$ids))
		.')';
	}

	$sums = array(
		'units'=>0, 'units_count'=>0,
		'gross_weight'=>0.0, 'gross_weight_count'=>0,
		'tare_weight'=>0.0, 'tare_weight_count'=>0,
		'weight'=>0.0, 'weight_count'=>0,
		'volume'=>0.0, 'volume_count'=>0,
		'price'=>0.0, 'price_count'=>0,
		'density'=>0.0, 'density_count'=>0,
	);
	$per_unit = array(
		'gross_weight'=>0.0,'gross_weight_count'=>0,'gross_weight_n'=>0,
		'tare_weight'=>0.0,'tare_weight_count'=>0,'tare_weight_n'=>0,
		'weight'=>0.0, 'weight_count'=>0, 'weight_n'=>0,
		'volume'=>0.0, 'volume_count'=>0, 'volume_n'=>0,
		'price'=>0.0, 'price_count'=>0, 'price_n'=>0,
	);
	$price_per = array(
		'weight'=>0.0, 'weight_count'=>0,
		'weight_sum'=>0.0, 'weight_price_sum'=>0.0,
		'volume'=>0.0, 'volume_count'=>0,
		'volume_sum'=>0.0, 'volume_price_sum'=>0.0,
	);
	foreach (select('* FROM receipt'.$where) as $rec) {
		if ($rec['units']!==null) {
			$sums['units'] += $rec['units'];
			++$sums['units_count'];

			if ($rec['weight']!==null) {  # gross weight
				$per_unit['gross_weight'] += $rec['weight'];
				$per_unit['gross_weight_count']+=$rec['units'];
				++$per_unit['gross_weight_n'];

			}
			if ($rec['weight']!==null && $rec['net_weight']!==null){
				$per_unit['tare_weight'] +=
					$rec['weight'] - $rec['net_weight'];
				$per_unit['tare_weight_count'] += $rec['units'];
				++$per_unit['tare_weight_n'];
			}
			if ($rec['net_weight']!==null) {
				$per_unit['weight'] += $rec['net_weight'];
				$per_unit['weight_count'] += $rec['units'];
				++$per_unit['weight_n'];
			}
			if ($rec['net_volume']!==null) {
				$per_unit['volume'] += $rec['net_volume'];
				$per_unit['volume_count'] += $rec['units'];
				++$per_unit['volume_n'];
			}
			if ($rec['amount']!==null && $rec['amount']>0) {
				$per_unit['price'] += $rec['amount'];
				$per_unit['price_count'] += $rec['units'];
				++$per_unit['price_n'];
			}
		}
		if ($rec['weight']!==null) {  # gross weight
			$sums['gross_weight'] += $rec['weight'];
			++$sums['gross_weight_count'];
		}
		if ($rec['weight']!==null && $rec['net_weight']!==null) {
			$sums['tare_weight'] += $rec['weight']
			                        - $rec['net_weight'];
			++$sums['tare_weight_count'];
		}
		if ($rec['net_weight']!==null) {
			$sums['weight'] += $rec['net_weight'];
			++$sums['weight_count'];

			if ($rec['net_volume']!==null) {
				$sums['density'] += $rec['net_weight']
				                    / $rec['net_volume'];
				++$sums['density_count'];
			}
		}
		if ($rec['net_volume']!==null) {
			$sums['volume'] += $rec['net_volume'];
			++$sums['volume_count'];
		}
		if ($rec['amount']!==null && $rec['amount']>0) {
			$sums['price'] += $rec['amount'];
			++$sums['price_count'];

			if ($rec['net_weight']!==null) {
				$price_per['weight'] += $rec['amount']
					/ $rec['net_weight'];
				$price_per['weight_sum'] += $rec['net_weight'];
				$price_per['weight_price_sum']+=$rec['amount'];
				++$price_per['weight_count'];
			}
			if ($rec['net_volume']!==null) {
				$price_per['volume'] += $rec['amount']
					/ $rec['net_volume'];
				$price_per['volume_sum'] += $rec['net_volume'];
				$price_per['volume_price_sum']+=$rec['amount'];
				++$price_per['volume_count'];
			}
		}
	}

	$row = array('id'=>$prod['id']);

	$row['market_weight'] = $sums['weight'];

	if ($sums['units_count']>0) {
		$typical_units = max(1,
			round($sums['units']/$sums['units_count'])
		);
	}

	# product is more often priced by weight or by volume?
	if ( ( $price_per['weight_count'] > $price_per['volume_count'] ||
	       ( $price_per['weight_count'] == $price_per['volume_count']
	         && ($prod['net_weight']!==null||$prod['net_volume']===null) ) )
	     && $price_per['weight_count']>0 ) {
		$row['net_volume'] = null;

		if ( $per_unit['weight_n']>0.2*$sums['weight_count']
		     && $prod['units_avoid_filling'] ) {
			$wpu = $per_unit['weight']
			       / $per_unit['weight_count'];
		} else {
			$wpu = null;
		}

		# more often priced by weight or by number of units?
		if ( $price_per['weight_count'] >= $per_unit['price_n'] ||
		     $prod['units_avoid_filling'] ) {

			# do we have weight per unit?
			if ($wpu!==null) {
				if (!$prod['units_near_kg'] || $wpu<=0) {
					$row['typical_units'] = $typical_units;
				} else {
					$row['typical_units'] =round(1000/$wpu);
				}

				$row['net_weight'] = $row['typical_units']*$wpu;
				if ($per_unit['tare_weight_count']>0) {
					$row['packaging_weight'] =
						$row['typical_units']
						*$per_unit['tare_weight']
						/$per_unit['tare_weight_count'];
				}

			} else {
				$row['typical_units'] = null;
				if (!$prod['units_near_kg']) {
					$row['net_weight'] = $sums['weight']
						/ $sums['weight_count'];
					if ($sums['tare_weight_count']>0) {
						$row['packaging_weight'] =
						   $sums['tare_weight']
						   / $sums['tare_weight_count'];
					}
				} else {
					$row['net_weight'] = 1000;
				}
			}

			$row['net_weight'] -= ifnull($prod['glaze_weight'],0);

			$ppw = $price_per['weight_price_sum']
			       / $price_per['weight_sum'];
			$row['typical_price'] = $ppw*$row['net_weight'];
		} else {
			if (!$prod['units_near_kg'] || $wpu<=0) {
				$row['typical_units'] = $typical_units;
			} else {
				$row['typical_units'] = round(1000/$wpu);
			}

			if ($row['typical_units']<1)
				$row['typical_units'] = 1;

			$ppu = $per_unit['price']/$per_unit['price_count'];
			$row['typical_price'] = $row['typical_units']*$ppu;

			$row['net_weight'] = $row['typical_units']*$wpu
				- ifnull($prod['glaze_weight'],0);
			if ($per_unit['tare_weight_count']>0) {
				$row['packaging_weight'] =
					$row['typical_units']
					* $per_unit['tare_weight']
					/ $per_unit['tare_weight_count'];
			}
		}

	} else if ( $price_per['volume_count']>0 ) {
		$row['net_volume'] = null;

		if ( $per_unit['volume_n']>0.2*$sums['volume_count']
		     && $prod['units_avoid_filling'] ) {
			$vpu = $per_unit['volume']
			       / $per_unit['volume_count'];
		} else {
			$vpu = null;
		}

		# more often priced by volume or by number of units?
		if ( $price_per['volume_count'] >= $per_unit['price_n'] ||
		     $prod['units_avoid_filling'] ) {

			# do we have volume per unit?
			if ($vpu!==null) {
				if (!$prod['units_near_kg'] || $vpu<=0) {
					$row['typical_units'] = $typical_units;
				} else {
					$row['typical_units'] =round(1000/$vpu);
				}

				$row['net_volume'] = $row['typical_units']*$vpu;
				if ($per_unit['tare_weight_count']>0) {
					$row['packaging_weight'] =
						$row['typical_units']
						*$per_unit['tare_weight']
						/$per_unit['tare_weight_count'];
				}

			} else {
				$row['typical_units'] = null;
				if (!$prod['units_near_kg']) {
					$row['net_volume'] = $sums['volume']
						/ $sums['volume_count'];
					if ($sums['tare_weight_count']>0) {
						$row['packaging_weight'] =
						   $sums['tare_weight']
						   / $sums['tare_weight_count'];
					}
				} else {
					$row['net_volume'] = 1000;
				}
			}

			$row['net_volume'] -=
				ifnull(1.087*$prod['glaze_weight'],0);

			$ppv = $price_per['volume_price_sum']
			       / $price_per['volume_sum'];
			$row['typical_price'] = $ppv*$row['net_volume'];
		} else {
			$vpu = $per_unit['volume'] / $per_unit['volume_count'];

			if (!$prod['units_near_kg'] || $vpu<=0) {
				$row['typical_units'] = $typical_units;
			} else {
				$row['typical_units'] = round(1000/$vpu);
			}

			if ($row['typical_units']<1)
				$row['typical_units'] = 1;

			$ppu = $per_unit['price']/$per_unit['price_count'];
			$row['typical_price'] = $row['typical_units']*$ppu;

			$row['net_volume'] = $row['typical_units']*$vpu
				- ifnull(1.087*$prod['glaze_weight'],0);
			if ($per_unit['tare_weight_count']>0) {
				$row['packaging_weight'] =
					$row['typical_units']
					* $per_unit['tare_weight']
					/ $per_unit['tare_weight_count'];
			}
		}
	} else if ($per_unit['price_n']>0) {
		$row['typical_units'] = $typical_units;
		$row['typical_price'] = $row['typical_units']
			* $per_unit['price']
			/ $per_unit['price_count'];
	}

	update('product.id',$row);
}

function find_demographic_group($user) {
	if (!is_array($user)) $user = fetch('users.id',$user);

	$conds = array();

	if ($user['birth']!==null) {
		$age = value('DATEDIFF(NOW(),'.sql($user['birth']).')')/365.0;
		$conds[] = '(min_age<='.sql($age).' OR min_age IS NULL)';
		$conds[] = '('.sql($age).'<max_age OR max_age IS NULL)';
	} else {
		$conds[] = 'min_age IS NULL AND max_age IS NULL';
	}

	if ($user['gender']!==null) {
		$conds[] ='(gender IS NULL OR gender='.sql($user['gender']).')';
	} else {
		$conds[] = 'gender IS NULL';
	}

	if ($user['pregnancy']!==null) {
		$conds[] = '(pregnancy IS NULL OR '
		           .'pregnancy='.sql($user['pregnancy']).')';
	} else {
		$conds[] = 'pregnancy IS NULL';
	}

	$groups = col('id FROM demographic_group'
	              .' WHERE '.implode(' AND ',$conds));
	if ($groups) {
		return $groups[0];
	} else {
		return null;
	}
}

function user_update_thresholds($user) {
	if (!is_array($user)) $user = fetch('users.id',$user);

	if ($user['demographic_group']===null) {
		$demo = find_demographic_group($user);
		store('users.id',array(
			'id'=>$user['id'],
			'demographic_group'=>$demo,
		));
	} else {
		$demo = $user['demographic_group'];
	}

	foreach (select('* FROM nutrient') as $nut) {
		$thr = row0('* FROM threshold'
		            .' WHERE user='.sql($user['id'])
		            .' AND nutrient='.sql($nut['id']));

		if ($thr===null) {
			$def_thr = row0('* FROM threshold WHERE '
				.($demo===null
					? 'demographic_group IS NULL'
					: 'demographic_group='.sql($demo)
				)
				.' AND user IS NULL'
				.' AND nutrient='.sql($nut['id'])
			);
			if ($def_thr!==null) {
				unset($def_thr['id']);
				$def_thr['user'] = $user['id'];
				store('threshold.id',$def_thr);
			}

		} else if ($thr['demographic_group']!==null) {
			$def_thr = row0('* FROM threshold'
				.' WHERE demographic_group='
					.sql($user['demographic_group'])
				.' AND user IS NULL'
				.' AND nutrient='.sql($nut['id'])
			);
			if ($def_thr!==null) {
				$def_thr['id'] = $thr['id'];
				$def_thr['user'] = $user['id'];
				store('threshold.id',$def_thr);
			} else {
				store('threshold.id',array('id'=>-$thr['id']));
			}
		}
	}
}

function log_database_changes_html($row) {
	$changes = jsdecode($row['log_database.changes']);
	$s = array();
	foreach ($changes as $field=>$value) {
		$s[] = html($field).' = '.html(sql($value)).'<br />';
	}
	return implode('',$s);
}

?>
