<?
require_once 'lib/util.php';

function maketable($MAKETABLE) {
	maketable_spec($MAKETABLE);

	$NEWGET = array();
	if (posting()) try {
		$NEWGET = maketable_posted($MAKETABLE,$_POST);
	} catch (Exception $x) {
		if (failure($x)) return;
	}

	print_maketable($MAKETABLE, array_merge($_GET, $_POST));
}

function maketable_param($name,$def) {
	$x = post('p_'.$name);
	return ($x!==null) ? $x : $def;
}

################################################################################
#
# table description
#
# table: (unquoted token: `foo.b``ar` => ```foo.b````ar```)
# instance: (unquoted token)
# field: (unquoted token)
# identifier: ( quoted identifier: foo.bar => array('foo','bar') )
# condition: sql
# condition: array(sql, identifier=>sql, ...)
# joincondition: array(field=>sql, ...)
# jointype: {'inner'|'left'|'group'}
#
#
# 'db' => (database object),
#
# 'key' => {
#	(string common primary key field among all tables)
# |
#	array(
#		'tablename' => 'keyfieldname',
#		...
#	)
# },
#
# 'join' => array(
#	{
#		instance
#	|
#		instance=>joincondition
#	|
#		array(instance,joincondition,[jointype,[as,]])
#	},
#	...
# ),
#
# 'where' => condition,
#
# 'columns' => array(
#	{
#		sql,
#	|
#		sql=>(caption text),
#	|
#		array(
#			'expr'=>sql,
#			'title'=>(caption text),
#			'fun'=>(function taking whole row),
#			'search'=>{'left'|'mid'|'right'|'all'},
#			'search_in'=>sql,
#			'edit'=>{null|'text'|'number'},
#			'width'=>(width of column as CSS length),
#			'size'=>(size of input box),
#			'maxlength'=>(maxlength of input box),
#			'opts'=>(options of drop-down box),
#		),
#	|
#		sql=>array(
#			'title'=>(caption text),
#			'fun'=>(function taking whole row),
#			'search'=>{'left'|'mid'|'right'|'all'},
#			'search_in'=>sql,
#			'edit'=>{null|'text'|'number'},
#			'width'=>(width of column as CSS length),
#			'size'=>(size of input box),
#			'maxlength'=>(maxlength of input box),
#			'opts'=>(options of drop-down box),
#		),
#	}
#	...
# ),
#
# 'buttons' => array(
#	{
#		(string predefined button name such as 'edit' or 'delete'),
#	|
#		(name) => array(
#			'class'=>(CSS class; by default it is name+'button'),
#			'url'=>(if set, button will be link with query=ident),
#			'include'=>(string filename of .php to simulate a POST),
#			'extra'=>(array extra values to pass to included script;
#			          does not take precedence over non-null keys),
#			'onclick'=>(string javascript code; must return false),
#			'onsuccess'=>(string javascript to run if request was
#			              successful; only with default onclick),
#			'vars'=>array(
#				(onclick code can be prefixed with "var x=y;")
#				'id',  (maketable element id: $ID)
#				'rowid',  (zero-based index of rows on screen)
#				'ident',  (object with primary key values;
#				           not included by default)
#				'rowname',  (human-readable row identifier;
#				             not included by default)
#			),
#			'html'=>(string HTML content of button or link),
#		),
#	}
#	...
# ),
#
# 'sayrecord' => (),
# 
# 'orderfield' => identifier,
# 'orderby' => identifier,
# 'orderad' => identifier,
#
# 'dragdrop' => (ordered array of instances),
#
# 'insert' =>  (ordered array of instances),
# 'userinsert' =>  (ordered array of instances),
# 'insertinc' =>  (string include path),
#
# 'update' =>  {true|false},
# 'userupdate' =>  {true|false},
# 'updateinc' =>  (string include path),
# 'updateurl' =>  (string URL that allows user to edit a record),
#
# 'delete' =>  (ordered array of instances),
# 'userdelete' =>  (ordered array of instances),
# 'deleteinc' =>  (string include path),
# 'deleteurl' =>  (string URL that allows user to delete a record),
# 'deleteneg' =>  (true or false/null; whether to pass negative row IDs to
#                  `deleteinc' script),
# 'deletenoconfirm' => (bool if true, no confirmation is needed for deletions),
#
# 'loadopts' => (function ($uri) -> array(name=>value,...)),
# 'saveopts' => (function ($uri,array(name=>value)) -> null),
#
function maketable_spec(&$MAKETABLE) {
	$MAKETABLE['selfposting'] = true;

	$MAKETABLE['namescount'] = 0;
	$MAKETABLE['namestaken'] = array();
	$MAKETABLE['aliases'] = array();

	global $MAKETABLE_CLASS;
	if (!strlen($MAKETABLE['class'])) {
		if (strlen($MAKETABLE_CLASS)) {
			$MAKETABLE['class'] = $MAKETABLE_CLASS;
		} else {
			$MAKETABLE['class'] = 'listing';
		}
	}

	$oldcolumns = is_array($MAKETABLE['columns']) ? $MAKETABLE['columns'] : array();
	$MAKETABLE['columns'] = array();
	$oldhidden = is_array($MAKETABLE['hidden']) ? $MAKETABLE['hidden'] : array();
	$MAKETABLE['hidden'] = array();

	$oldjoin = (is_array($MAKETABLE['join']) ? $MAKETABLE['join'] : array());
	$MAKETABLE['join'] = array();
	foreach ($oldjoin as $a=>$b)
		maketable_add_table($MAKETABLE, $a, $b);
	if ( !count($MAKETABLE['join']) )
		throw new LoggedException('there are no source tables ("join"=>...)');

	$oldkey = $MAKETABLE['key'];
	$MAKETABLE['key'] = array();
	if (is_string($oldkey)) {
		foreach ($MAKETABLE['join'] as $join) {
			$MAKETABLE['key'][$join['table']] = $oldkey;
		}
	} else {
		if (!is_array($oldkey)) $oldkey = array($oldkey);
		foreach ($oldkey as $x=>$y) {
			if (is_integer($x)) {
				if (breaksqlid($y,$broken) == 1 && count($MAKETABLE['join'])==1) {
					$t = $MAKETABLE['join'][0]['table'];
					$k = $broken[0];
				} else if (count($broken) == 2) {
					$t = $broken[0];
					$k = $broken[1];
				} else {
					throw new LoggedException('please specify one key per table like this: "key"=>array("tablename"=>"keyfield", ...)');
				}
			} else {
				$t = $x;
				$k = $y;
			}

			if (!strlen($k)) throw new LoggedException('missing key for table '.repr($x));

			if ($MAKETABLE['key'][$t] != null)
				throw new LoggedException('duplicate key given for table '.repr($x));

			$MAKETABLE['key'][$t] = $k;
		}
	}

	if (is_string($MAKETABLE['where'])) {
		if (strlen($MAKETABLE['where'])) {
			$MAKETABLE['where'] = array($MAKETABLE['where']);
		} else {
			$MAKETABLE['where'] = null;
		}
	} else if ($MAKETABLE['where']!==null && !is_array($MAKETABLE['where'])) {
		throw new LoggedException('"where" is empty or not a string/array');
	}

	if (is_string($MAKETABLE['having']))
		$MAKETABLE['having'] = array($MAKETABLE['having']);
	else if ($MAKETABLE['having']!==null && !is_array($MAKETABLE['having']))
		throw new LoggedException('"having" is not string or array');

	foreach ($oldcolumns as $a => $b) if ($b!==null) {
		maketable_add_column($MAKETABLE, $a,$b);
	}
	foreach ($oldhidden as $a => $b) if ($b!==null) {
		maketable_add_column($MAKETABLE, $a,$b, 'hidden');
	}
	if ( !count($MAKETABLE['columns']))
		throw new LoggedException('no output columns specified ("columns"=>...)');

	if (!is_string($MAKETABLE['sayrecord']))
		$MAKETABLE['sayrecord'] = maketable_text('record');
	if (!is_string($MAKETABLE['sayrecords']))
		$MAKETABLE['sayrecords'] = maketable_text('records');

	if ($MAKETABLE['orderfield']!==null) {
		$MAKETABLE['orderfield'] = sqlid(unsqlid($MAKETABLE['orderfield']),1);
		maketable_add_field($MAKETABLE, $MAKETABLE['orderfield']);
	}
	if ($MAKETABLE['orderby']!==null) {
		$MAKETABLE['orderby'] = sqlid(unsqlid($MAKETABLE['orderby']),1);
		maketable_add_field($MAKETABLE, $MAKETABLE['orderby']);
	}
	if ($MAKETABLE['orderad']!==null) {
		if (strtoupper($MAKETABLE['orderad'])=='ASC')
			$MAKETABLE['orderad'] = 1;
		if (strtoupper($MAKETABLE['orderad'])=='DESC')
			$MAKETABLE['orderad'] = 2;
		if ($MAKETABLE['orderad']!=1 && $MAKETABLE['orderad']!=2)
			throw new LoggedException('"orderad" must be 1 (asc) or 2 (desc), not '
			                          .repr($MAKETABLE['orderad']));
	}

	maketable_tablesubset($MAKETABLE, 'insert');
	maketable_tablesubset($MAKETABLE, 'userinsert', 'insert');
	if ($MAKETABLE['insertinc']!==null && !is_string($MAKETABLE['insertinc']) )
		throw new LoggedException('"insertinc" is not a string');

	if ($MAKETABLE['deleteurl']!==null) {
		if (!is_string($MAKETABLE['deleteurl']) )
			throw new LoggedException('"deleteurl" is not a string');

		$MAKETABLE['delete'] = true;
	}
	maketable_tablesubset($MAKETABLE, 'delete');
	maketable_tablesubset($MAKETABLE, 'userdelete', 'delete');
	if ($MAKETABLE['deleteinc']!==null && !is_string($MAKETABLE['deleteinc']) )
		throw new LoggedException('"deleteinc" is not a string');

	if ($MAKETABLE['updateinc']!==null) {
		if (!is_string($MAKETABLE['updateinc']) )
			throw new LoggedException('"updateinc" is not a string');

		$MAKETABLE['update'] = true;
	}
	if ($MAKETABLE['updateurl']!==null) {
		if (!is_string($MAKETABLE['updateurl']) )
			throw new LoggedException('"updateurl" is not a string');

		$MAKETABLE['update'] = true;
	}
	if ($MAKETABLE['update']) $MAKETABLE['update'] = true;
	maketable_tablesubset($MAKETABLE, 'update');
	if ($MAKETABLE['userupdate']) $MAKETABLE['userupdate'] = true;
	maketable_tablesubset($MAKETABLE, 'userupdate', 'update');

	maketable_tablesubset($MAKETABLE, 'dragdrop');
	if (count($MAKETABLE['insert']) || count($MAKETABLE['delete']) || count($MAKETABLE['update']) ||
	    count($MAKETABLE['userinsert']) || count($MAKETABLE['userdelete']) || count($MAKETABLE['userupdate']) ||
	    count($MAKETABLE['dragdrop'])) {
		if (is_array($MAKETABLE['key'])) {
			foreach ($MAKETABLE['join'] as $join) {
				if ($MAKETABLE['key'][$join['table']] === null) {
					throw new LoggedException('unknown key for table '.repr($instance));
				}
			}
		}
	}

	if ($MAKETABLE['buttons'] === null) {
		$MAKETABLE['buttons'] = array();
		if (count($MAKETABLE['userupdate'])>0)
			$MAKETABLE['buttons'][] = 'edit';
		if (count($MAKETABLE['userdelete'])>0)
			$MAKETABLE['buttons'][] = 'delete';
	} else if (!is_array($MAKETABLE['buttons'])) {
		throw new LoggedException('expected array in "buttons"');
	}

	$newbuttons = array();
	foreach ($MAKETABLE['buttons'] as $name=>$button) {
		$newname = is_int($name)?$button:$name;
		if (!is_string($newname)) {
			throw new LoggedException('expected string for'
			                          .' button name; not '
			                          .repr($button));
		} else if (!preg_match('/^[a-zA-Z]+$/',$newname)) {
			throw new LoggedException('button names must consist'
			                          .' of letters only; not like '
			                          .repr($newname));
		}

		switch ($newname) {
		case 'edit':
			$newbutton = array(
				'url'=>$MAKETABLE['updateurl'],
				'onclick'=>'return maketable_begin_edit('
					.'id,rowid'
				.')',
				'vars'=>array('id','rowid'),
			);
			break;
		case 'delete':
			$newbutton = array(
				'url'=>$MAKETABLE['deleteurl'],
				'onclick'=>($MAKETABLE['deletenoconfirm']
				?	'return maketable_delete(id,ident)'
				:	'return maketable_confirm_and_delete('
						.'id,ident,rowname'
					.')'
				),
				'vars'=>array('id','ident','rowname'),
			);
			break;
		default:
			if (is_int($name)) {
			  throw new LoggedException('not a predefined button: '
			                            .repr($newname));
			}
			$newbutton = array();
		}

		if (!is_int($name)) {
			foreach ($button as $n=>$v) {
				$newbutton[$n] = $v;
			}
		}

		if ($newbutton['vars']===null ||
		    $newbutton['vars']===true) {
			$newbutton['vars'] = array('id','rowid');
		} else if ($newbutton['vars']===false) {
			$newbutton['vars'] = array();
		} else if (is_array($newbutton['vars'])) {
			$valid_jsvars = array(
				'id'=>true,
				'rowid'=>true,
				'ident'=>true,
				'rowname'=>true,
			);
			foreach ($newbutton['vars'] as $n=>$var) {
				if (!is_int($n)) {
					throw new LoggedException(
						'"vars" for button '
						.repr($newname)
						.' contains key: '
						.repr($n)
					);
				} else if (!is_string($var)) {
					throw new LoggedException(
						'"vars" for button '
						.repr($newname)
						.' contains non-string: '
						.repr($var)
					);
				} else if (!$valid_jsvars[$var]) {
					throw new LoggedException(
						'"vars" for button '
						.repr($newname)
						.' contain unrecognized'
						.' variable: '.repr($var)
					);
				}
			}
		} else {
			throw new LoggedException(
				'"vars" for button '.repr($newname)
				.' is not an array or boolean'
			);
		}

		if ($newbutton['class']===null) {
			$newbutton['class'] = $newname.'button';
		} else if (!is_string($newbutton['class'])) {
			throw new LoggedException(
				'"class" for button '.repr($newname)
				.' must be string, not '
				.repr($newbutton['class'])
			);
		}

		if ($newbutton['type']===null) {
			$newbutton['type'] = 'submit';
		} else if (!is_string($newbutton['type'])) {
			throw new LoggedException(
				'"type" for button '.repr($newname)
				.' must be string, not '
				.repr($newbutton['type'])
			);
		}

		if ($newbutton['onclick']===null) {
			$newbutton['onclick'] =
				'maketable_button(id,'.js($newname).',rowid)';
		} else if (!is_string($newbutton['type'])) {
			throw new LoggedException(
				'"type" for button '.repr($newname)
				.' must be string, not '
				.repr($newbutton['type'])
			);
		}

		$newbutton['name'] = $newname;
		$newbuttons[$newname] = $newbutton;
	}

	$MAKETABLE['buttons'] = $newbuttons;

	if ($MAKETABLE['showcount']===null)
		$MAKETABLE['showcount'] = ($MAKETABLE['having']===null);

	switch (intval($MAKETABLE['navigation'])) {
	case 3: case 2: case 1: case 0: case -1:
		$MAKETABLE['navigation'] = intval($MAKETABLE['navigation']);
		break;
	default:
		throw new LoggedException('"navigation" should be -1, 0, 1, 2 or 3; not '.repr($MAKETABLE['navigation']));
	}

	if ($MAKETABLE['offset']===null || intval($MAKETABLE['offset'])<0)
		$MAKETABLE['offset'] = 0;
	if (intval($MAKETABLE['limit'])<=0)
		$MAKETABLE['limit'] = 15;
}

function maketable_tablesubset(&$MAKETABLE, $which, $dfl=null) {
	$old = $MAKETABLE[$which];
	if ($old === null) {
		if ($dfl != null) {
			$MAKETABLE[$which] = $MAKETABLE[$dfl];
		} else {
			$MAKETABLE[$which] = array();
		}
	} else if ($old === false) {
		$MAKETABLE[$which] = array();
	} else if ($old === true) {
		if ($dfl === null) {
			$MAKETABLE[$which] = array();
			foreach ($MAKETABLE['join'] as $i=>$join) {
				$MAKETABLE[$which][] = $i;
			}
		} else {
			$MAKETABLE[$which] = $MAKETABLE[$dfl];
		}
	} else if (is_string($old)) {
		if ($MAKETABLE['aliases'][$old] !== null) {
			$MAKETABLE[$which] = array($old);
		} else {
			throw new LoggedException('the '.repr($old).' in parameter '.repr($which).' is not the name or alias of a table');
		}
	} else if (is_integer($old)) {
		if ($old > count($MAKETABLE['join']))
			throw new LoggedException('the '.repr($old).' in parameter '.repr($which).' is greater than the number of tables');

		$MAKETABLE[$which] = array();
		for ($i = 0 ; $i < abs($old) ; ++$i)
			$MAKETABLE[$which][] = $i;
	} else if (is_array($old)) {
		$MAKETABLE[$which] = array();
		foreach ($old as $x) {
			if (is_integer($x)) {
				if ($x>=0 && $x<count($MAKETABLE['join'])) {
					$MAKETABLE[$which][] = $x;
				} else {
					throw new LoggedException('the '.repr($x).' in parameter '.repr($which).' does not index a joined table [0,'.count($MAKETABLE['join']).')');
				}
			} else if (is_string($x)) {
				if ($MAKETABLE['aliases'][$x] !== null) {
					$MAKETABLE[$which][] = $MAKETABLE['aliases'][$x];
				} else {
					throw new LoggedException('the '.repr($x).' in parameter '.repr($which).' is not the name or alias of a table');
				}
			}
		}
	} else {
		throw new LoggedException('"delete" must be boolean, integer or array of integers');
	}
}

function maketable_add_table(&$MAKETABLE, $a, $b) {
	$join = maketable_positional_params(is_string($a)?$a:array(), array('table','on','type','as'), is_string($a)?array($b):$b);

	if ($join['on'] === null) {
		# ok - always true - cartesian product

	} else if (is_array($join['on'])) {  # array of "field"=>"sql"
		$newcond = array();
		foreach ($join['on'] as $field=>$expr) {
			if (!strlen($field))
				throw new LoggedException('empty or missing field name in join condition: '.repr($field).' => '.repr($expr));
			if ($expr==='')
				throw new LoggedException('empty field value in join condition: '.repr($field).' => '.repr($expr));

			if ($expr === null) {  # "a JOIN b ON x IS NULL"
				$newcond[$field] = $expr;
			} else if ($expr === strval(intval($expr))) {  # "a JOIN b ON x=123"
				$newcond[$field] = $expr;
			} else if (breaksqlid($expr,$broken) >= 2) {  # "a JOIN b ON x=a.y"
				$newcond[$field] = sqlid($broken,1);
				maketable_add_field($MAKETABLE, $newcond[$field]);
			} else if (count($broken)>=1) {  # "a JOIN b ON x=y"
				# FIXME this won't be returned with instancename even if unambiguous
				throw new LoggedException('please use full `instance`.`field` syntax for field backreference instead of '.repr($expr));
			} else {
				# MAYBE TODO do extract field references from expression
				# MAYBE TODO support string constants
				throw new LoggedException('cannot handle string constants/complex expressions in joins (use "where"): '.repr($field).' => '.repr($expr));
			}
		}
		$join['on'] = $newcond;

	} else {
		throw new LoggedException('invalid join condition (expected array of "field"=>"sql"): '.repr($join['on']));
	}

	if ($join['as'] === null) {
		if ($MAKETABLE['aliases'][$join['table']] !== null)
			throw new LoggedException('you need to give an alias name ("as"=>"...") for second use of table '.repr($join['table']));

		$join['as'] = $join['table'];
	} else {
		if ($MAKETABLE['aliases'][$join['as']] !== null)
			throw new LoggedException('alias '.repr($join['as']).' given to two or more instances');
	}

	$MAKETABLE['aliases'][$join['as']] = count($MAKETABLE['join']);

	$MAKETABLE['join'][] = $join;
}


# $field must be normalized
function maketable_add_field(&$MAKETABLE, $field) {
	if ( $MAKETABLE['namestaken'][$field] === null ) {
		maketable_add_column($MAKETABLE, $field, null, 'hidden');
	}
}

function maketable_add_column(&$MAKETABLE, $a, $b=null, $cols='columns') {
	$out = maketable_positional_params(is_string($a)?$a:array(),array('expr','title','fun','search','search_in','edit','width','size','maxlength','opts','opts_sql','f','tip','nowrap','class','prefix','postfix'),$b);

	if ($out['expr']===null && $out['fun']===null && $out['f']===null)
		throw new LoggedException('column with no expression and no function: '.repr($out));

	if ($out['fun']!==null && !function_exists($out['fun']))
		throw new LoggedException('function '.repr($out['fun']).' does not exist');
	if ($out['f']!==null && !function_exists($out['f']))
		throw new LoggedException('function '.repr($out['f']).' does not exist');

	# is 'expr' a simple field reference? then we quote it! (now it's even
	# safe to put SQL keywords in 'expr'!)
	if (is_string($out['expr']) && breaksqlid($out['expr'],$broken)>=1) {
		$out['expr'] = sqlid($broken,4);
		$out['field'] = sqlid($broken,1);  # do not quote keywords internally
	} else {
		$out['field'] = null;
		if ($out['edit']!==null)
			throw new LoggedException('can only edit columns with simple field references: '.repr($out));
		$out['edit'] = 'none';
	}

	if ($out['as']===null) {
		if ($out['field']===null) {
			$out['as'] = maketable_new_name($MAKETABLE);
			#$out['as'] = strval($out['expr']);
		} else {
			$out['as'] = $out['field'];
		}
	} else {
		if (!is_string($out['as']))
			throw new LoggedException('field alias not a string: '.repr($out['as']));
		if ($MAKETABLE['namestaken'][$out['as']])
			throw new LoggedException('this name is already taken: '.repr($out['as']));
		$MAKETABLE['namestaken'][$out['as']] = true;
	}
	if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_:.-]*$/',$out['as']))
		throw new LoggedException('please use field aliases which can be used as HTML element identifiers (id="..."); not '.repr($out['as']));

	if (!is_string($out['head'])) {
		if (is_string($out['title'])) 
			$out['head'] = $out['title'];
		else if (is_string($out['label'])) 
			$out['head'] = $out['label'];
		else
			$out['head'] = $out['as'];

		unset($out['title']);
		unset($out['label']);
	}

	if ($out['search_in']!==null)
		maketable_add_field($MAKETABLE, $out['search_in']);

	if ($out['edit'] === null)
		$out['edit'] = 'text';

	if ($out['size'] === null) {
		$out['size'] = null;
	#} else {
	#	if (!is_int($out['size'])) {
	#		throw new LoggedException('size of input box not an integer: '.repr($out['size']));
	#	}
	}

	$MAKETABLE[$cols][] = $out;
}
function maketable_new_name(&$MAKETABLE) {
	for (;;) {
		$x = maketable_makename_rec($MAKETABLE['namescount']);
		if ($MAKETABLE['namestaken'][$x] === null) {
			$MAKETABLE['namestaken'][$x] = true;
			++$MAKETABLE['namescount'];
			return $x;
		}
		++$MAKETABLE['namescount'];
	}
}
$MAKETABLE_ALPHABET = 'abcdefghijklmnopqrstuvwxyz';
function maketable_makename_rec($n) {
	global $MAKETABLE_ALPHABET;
	if ($n >= strlen($MAKETABLE_ALPHABET)) {
		return maketable_makename_rec(intval($n/strlen($MAKETABLE_ALPHABET)-1))
		       .$MAKETABLE_ALPHABET[$n%strlen($MAKETABLE_ALPHABET)];
	} else {
		return $MAKETABLE_ALPHABET[$n%strlen($MAKETABLE_ALPHABET)];
	}
}

function maketable_positional_params($params, $names, $xtra=null) {
	if (!is_array($params)) $params = array($params);
	if (!is_array($names)) $names = array($names);
	if ($xtra !== null) {
		if (is_array($xtra)) {
			$params = array_merge($params,$xtra);
		} else {
			$params[] = $xtra;
		}
	}

	$out = array();

	$i = 0;
	while (array_key_exists($names[$i],$params)) ++$i;

	foreach ($params as $x => $y) {
		if (is_integer($x)) {
			if ($i < count($names)) {
				$out[$names[$i]] = $y;
			} else {
				$out[] = $y;
			}

			do ++$i; while (array_key_exists($names[$i],$params));
		} else {
			$out[$x] = $y;
		}
	}

	return $out;
}



################################################################################
#
# SQL generation
#
function maketable_sql(&$MAKETABLE, $PARAMS, $only_count=false) {
	$from = maketable_sql_join($MAKETABLE);
	$where = array();

	$cond = maketable_sql_selectall($MAKETABLE);
	if (strlen($cond)) $where[] = $cond;

	$mayorder = array();

	foreach (array_merge($MAKETABLE['hidden'],$MAKETABLE['columns']) as $col) {
		if ($col['userorder']) $mayorder[$col['as']] = true;

		$qsearch = $PARAMS['search_'.str_replace('.','_',$col['as'])];
		if (strlen($qsearch)) {
			$search_in = ($col['search_in']!==null ? $col['search_in'] : $col['expr']);
			switch ($col['search']) {
			case 'left':
				$where[] = 'LEFT('.$search_in.','.strlen($qsearch).')='.sql($qsearch);
				break;
			case 'middle': case 'mid':
				$where[] = 'LOCATE('.sql($qsearch).','.$search_in.')>0';
				break;
			case 'right':
				$where[] = 'RIGHT('.$search_in.','.strlen($qsearch).')='.sql($qsearch);
				break;
			case 'fulltext':
				$where[] = 'MATCH ('.$search_in.') AGAINST ('.sql($qsearch).' IN BOOLEAN MODE)';
				break;
			case 'natural':
				$where[] = 'MATCH ('.$search_in.') AGAINST ('.sql($qsearch).')';
				break;
			case 'all': case 'exact':
				$where[] = $search_in.'='.sql($qsearch);
				break;
			}
		}
	}

	$groupby = '';

	if ( $only_count ) {
		if (count($MAKETABLE['key'])) {
			$project = array();
			foreach ($MAKETABLE['join'] as $join) {
				if ($join['type']!='group') {
					$project[] = 'IFNULL('.sqlid(array($join['as'],$MAKETABLE['key'][$join['table']])).',0)';
					# XXX COUNT(DISTINCT ...) doesn't count combinations with any NULL values
					# XXX assumed IDs will never be zero
				}
			}
			$project = 'COUNT(DISTINCT '.implode(',',$project).')';  # XXX MySQL-ism
		} else {
			$project = 'COUNT(*)';
		}
		$order = '';
	} else {
		$project = maketable_sql_project($MAKETABLE);

		$groupby=array();
		foreach ($MAKETABLE['join'] as $join) {
			if ($join['type']!='group') {
				$ff = array($join['as'],$MAKETABLE['key'][$join['table']]);
				$groupby[] = sqlid($ff);
			}
		}
		$groupby = ' GROUP BY '.(count($groupby)?implode(',',$groupby):'1');

		if ( strlen($MAKETABLE['orderfield']) ) {
			$order = $MAKETABLE['orderfield'].' '
			         .($MAKETABLE['orderfieldad']==1?'ASC':'DESC');

		} else if ( ( $MAKETABLE['userorder'] ||
		              $mayorder[$PARAMS['orderby']] )
		            && $PARAMS['orderby']!==null
		            && $PARAMS['orderad']>0) {
			$order = sqlid($PARAMS['orderby']).' '
			         .($PARAMS['orderad']==1?'ASC':'DESC');

		} else if ( $MAKETABLE['orderby'] !== null ) {
			if ($MAKETABLE['orderad'] !== null) {
				$order = $MAKETABLE['orderby'].' '.($MAKETABLE['orderad']==1?'ASC':'DESC');
			} else {
				$order = $MAKETABLE['orderby'];
			}
		} else {
			$order = '';
		}
	}

	$having = $MAKETABLE['having'];

	$sql = "$project FROM $from"
		.(count($where)?" WHERE ".implode(' AND ',$where):'')
		.$groupby
		.((!$only_count && count($having))?" HAVING ".implode(' AND ',$having):'')
		.(strlen($order)?" ORDER BY $order":'');

	#error_log($sql);

	return $sql;
}
function maketable_sql_project(&$MAKETABLE) {
	$plist=array();

	# requested columns
	foreach (array_merge($MAKETABLE['hidden'],$MAKETABLE['columns']) as $col) {
		if (is_string($col['expr'])) {
			$plist['.'.$col['as']] = $col['expr'].' AS '.sqlid($col['as']);
		}
	}

	# primary keys
	foreach ($MAKETABLE['join'] as $join) {
		$ff = array($join['as'],$MAKETABLE['key'][$join['table']]);
		$plist['.'.sqlid($ff,1)] = sqlid($ff,4).' AS '.sqlid(sqlid($ff,1));
	}

	return implode(',',$plist);
}
function maketable_sql_join(&$MAKETABLE) {
	$from = array();
	foreach ($MAKETABLE['join'] as $join) {
		$p = '';

		if (count($from)) {
			if ($join['type']=='left' || $join['type']=='group') {
				$p .= ' LEFT JOIN ';
			} else {
				$p .= ' JOIN ';
			}
		}

		$p .= sqlid($join['table']);
		if (strlen($join['as']) && $join['as']!=$join['table'])
			$p .= ' '.sqlid($join['as']);

		if (count($from)) {
			$cond = maketable_sql_condition($join['on'],$join['as']);
			if (strlen($cond)) $p .= ' ON '.$cond;
		}

		$from[] = $p;
	}
	return implode('',$from);
}
function maketable_sql_selectall(&$MAKETABLE) {
	$where = array();

	$cond = maketable_sql_condition($MAKETABLE['join'][0]['on'],$MAKETABLE['join'][0]['as']);
	if (strlen($cond)) $where[] = $cond;

	$cond = maketable_sql_condition($MAKETABLE['where']);
	if (strlen($cond)) $where[] = $cond;

	return implode(' AND ',$where);
}
function maketable_sql_selectone(&$MAKETABLE, $keys) {
	$where = array();
	$cond = maketable_sql_selectall($MAKETABLE);
	if (strlen($cond)) $where[] = $cond;
	foreach ($MAKETABLE['join'] as $join) {
		if ($join['type'] != 'group') {
			$keyfield = $MAKETABLE['key'][$join['table']];
			$fullfield4 = sqlid(array($join['as'],$keyfield));
			$fullfield = sqlid(array($join['as'],$keyfield),1);
			$field = sqlid($keyfield,1);

			if (array_key_exists($fullfield,$keys)) {
				if ($keys[$fullfield]===null) {
					$where[] = $fullfield4.' IS NULL';
				} else {
					$where[] = $fullfield4.'='.sql($keys[$fullfield]);
				}

			} else if (array_key_exists($field,$keys)) {
				if ($keys[$field]===null) {
					$where[] = $fullfield4.' IS NULL';
				} else {
					$where[] = $fullfield4.'='.sql($keys[$field]);
				}

			} else {
				error_log('FULLFIELD '.repr($fullfield));
				error_log('FIELD '.repr($field));
				error_log('PARAMS '.repr($keys));
				error_log('JOIN '.repr($join));
				$where[] = 'FALSE';  # test for this
			}
		}
	}
	return implode(' AND ',$where);
}
function maketable_sql_groupby(&$MAKETABLE) {
	$groupby=array();
	foreach ($MAKETABLE['join'] as $join) {
		if ($join['type']!='group') {
			$fullfield = sqlid(array($join['as'],$MAKETABLE['key'][$join['table']]));
			$groupby[] = $fullfield;
		}
	}
	return implode(',',$groupby);
}

function maketable_sql_condition($cond, $deftable=null) {
	if ($cond === null) {  # there is no condition (always true)
		return null;

	} else if (is_string($cond)) {  # string with SQL expression
		if (breaksqlid($cond,$broken) >= 2) {
			return sqlid($broken);
		} else if ($deftable!==null && count($broken) >= 1) {
			return sqlid(array($deftable,$broken[0]));
		} else if (count($broken) >= 1) {
			return sqlid($broken);
		} else {
			return "($cond)";
		}

	} else if (is_array($cond)) {  # expressions and/or "field"=>"sql" pairs
		$where = array();
		foreach ($cond as $name=>$value) if ($value!==null) {
			if (is_integer($name)) {  # string with SQL expression in array("sql","sql","sql")
				$where[] = "($value)";
			} else if (is_string($name)) {  # "field"=>"sql" pairs ("field" means "$deftable.field" by default)
				if ($value === null) {
					$post = ' IS NULL';
				} else if (is_string($value)) {
					$post = '=('.$value.')';
				}
				if (breaksqlid($name,$broken) >= 2) {
					$where[] = sqlid($broken).$post;
				} else if ($deftable!==null && count($broken) >= 1) {
					$where[] = sqlid(array($deftable,$broken[0])).$post;
				} else if (count($broken) >= 1) {
					$where[] = sqlid($broken[0]).$post;
				} else {
					throw new LoggedException('invalid SQL identifier: '.repr($name));
				}
			}
		}
		return implode(' AND ',$where);

	} else {
		throw new LoggedException('invalid condition: '.repr($cond));
	}
}


################################################################################
#
# HTML generation
#
function print_maketable(&$MAKETABLE, $PARAMS) {
	global $ID, $STACK;

	if ($MAKETABLE['loadopts']!==null) {
		$opts = $MAKETABLE['loadopts']($MAKETABLE['self']);
		foreach (array('offset','limit','orderby','orderad') as $n) {
			if ( $opts[$n]!==null && $PARAMS[$n]===null ) {
				$PARAMS[$n] = $opts[$n];
			}
		}
	}

	if ($PARAMS['part'] === null || $PARAMS['part'] === 'body') {
		if ($PARAMS['offset'] === null || intval($PARAMS['offset']) < 0) {
			$offset = $MAKETABLE['offset'];
		} else {
			$offset = intval($PARAMS['offset']);
		}

		if ($PARAMS['limit'] <= 0) {
			$limit = $MAKETABLE['limit'];
		} else {
			$limit = intval($PARAMS['limit']);
		}

		$sql = maketable_sql($MAKETABLE,$PARAMS);
		$rows = select($sql, intval($limit+1), intval($offset),
		               $MAKETABLE['db']);

		if (!count($rows) && $offset>0) {
			$offset -= $limit;
			$rows = select($sql, intval($limit+1), intval($offset),
			               $MAKETABLE['db']);

			if (!count($rows) && $offset>0) {
				$offset = 0;
				$rows = select($sql, intval($limit+1),
				               intval($offset),
				               $MAKETABLE['db']);
			}
		}

		if ($PARAMS['part']===null) {
			print '<form id="'.html($ID).'"';
			if (strlen($MAKETABLE['ondrag']))
		     		print ' onmousemove="'.html('maketable_dd(false,event,'.js($ID).');').'"';
			if (strlen($MAKETABLE['ondrop']))
				print ' onmouseup="'.html('maketable_dd(true,event,'.js($ID).');').'"';
			print ' action="'.html($_SERVER['REQUEST_URI']).'"';
			print ' method="post"';
			print ' autocomplete="off"';
			print ' onsubmit="return false"';
			print ' class="'.html($MAKETABLE['class']);
			print '">';

			//print '<input type="hidden" name="uri" value="'.html($_SERVER['REQUEST_URI']).'" />';

			if (is_array($STACK)) {
				echo hidden_stack();
			}

			if ($MAKETABLE['params'] !== null)
			foreach ($MAKETABLE['params'] as $name=>$def) {
				$value = maketable_param($name,$def);
				if ($value!==null) {
					print '<input type="hidden" name="p_'.html($name).'" value="'.html($value).'" />';
				}
			}

			print '<input type="hidden" id="'.html($ID.'_self').'"'
			           .' value="'.html(js($MAKETABLE['self'])).'" />';

			# onsuccess codes
			foreach ($MAKETABLE['buttons'] as $button) {
				if ($button['onsuccess']!==null) {
					echo '<input type="hidden"'
						.' id="'.html($ID.'_onsuccess_'.$button['name']).'"'
						.' value="'.html($button['onsuccess']).'"'
					.' />';
				}
			}

			if ($MAKETABLE['orderfield'] !== null)
				print '<input type="hidden" id="'.html($ID.'_orderfield').'" value="'.html(js($MAKETABLE['orderfield'])).'" />';

			foreach (array('ondelete','onupdate','ondom','ondrag','ondrop','rowondrag','rowondrop') as $ev)
				if ($MAKETABLE[$ev]!==null)
					print '<input type="hidden" id="'.html($ID.'_'.$ev).'" value="'.html(js($MAKETABLE[$ev])).'" />';

			if (count($MAKETABLE['userdelete'])>0)
				print '<input type="hidden" id="'.html($ID.'_deletemsg').'"'
				           .' value="'.html(js(maketable_text('Are you sure you want to erase {1}?'))).'" />';
		}

		print_maketable_inner($MAKETABLE, $PARAMS, $rows, $offset, $limit);

		if ($PARAMS['part']===null) {
			print '</form>';
		}

		if ($MAKETABLE['saveopts']!==null) {
			$MAKETABLE['saveopts']($MAKETABLE['self'],array(
				'offset'=>$offset,
				'limit'=>$limit,
				'orderby'=>$PARAMS['orderby'],
				'orderad'=>$PARAMS['orderad'],
			));
		}

	} else if ( $PARAMS['part'] == 'head' ) {
		print maketable_head($MAKETABLE, $PARAMS);

	} else if ( strval(intval($PARAMS['part'])) === $PARAMS['part'] ) {
		$keys = jsdecode($PARAMS['id_'.$PARAMS['part']]);
		$join = maketable_sql_join($MAKETABLE);
		$cond = maketable_sql_selectone($MAKETABLE, $keys);
		$project = maketable_sql_project($MAKETABLE);
		$groupby = maketable_sql_groupby($MAKETABLE);
		$row = row("$project FROM $join WHERE $cond"
		           .(strlen($groupby)?' GROUP BY '.$groupby:''),
		           $MAKETABLE['db']);
		print_maketable_row($MAKETABLE, $PARAMS, intval($PARAMS['part']), $row);
	}
}

function maketable_head(&$MAKETABLE, &$PARAMS) {
	global $ID, $URL;

	$thead = '<tr class="'.html($MAKETABLE['class']).' top">';

	$i = 0;
	foreach ($MAKETABLE['columns'] as $col) {
		$thead .= '<th class="'.html($MAKETABLE['class']).' top';
		if (!$i)
			$thead .= ' first';
		if ($i==count($MAKETABLE['columns'])-1
		    && !$MAKETABLE['buttons'])
			$thead .= ' last';
		if ($col['class']!==null)
			$thead .= html(' '.$col['class']);
		$thead .= '"';
		if (strlen($col['tip']))
			$thead .= ' title="'.html($col['tip']).'"';
		if ($col['nowrap'])
			$thead .= ' nowrap';
		if ($col['width']!==null)
			$thead .= ' style="width:'.html($col['width']).'"';
		$thead .= '>';

		if ($PARAMS['orderby']==$col['as']) {
			$thead .= hidden('orderby',$PARAMS['orderby']);
			$thead .= hidden('orderad',$PARAMS['orderad']);
		}

		$sortable = (!strlen($MAKETABLE['orderfield']))
		            && $MAKETABLE['userorder'] || $col['userorder'];

		if (!strlen($MAKETABLE['orderfield'])
		    && $PARAMS['orderby']==$col['as'] && $PARAMS['orderad']>0) {
			$sortbythis = ($PARAMS['orderad']==1) ? "asc" : "desc";
		} else {
			$sortbythis = null;
		}

		if (strlen($col['head'])) {
			if ($sortable) {
				$thead .= '<a '.maketable_nav($ID,$PARAMS,
					($PARAMS['orderby']!=$col['as'] || $PARAMS['orderad']!=2) ? array(
						'orderby'=>$col['as'],
						'orderad'=>(($PARAMS['orderby']===null||$PARAMS['orderby']==$col['as'])
						           ? (($PARAMS['orderad']+1)%3) : 1)
					) : array(
						'orderby'=>null,
						'orderad'=>0,
					)
				)
				.' class="orderlink'.html($sortbythis!==null ?
					' '.$sortbythis
				:'').'"'
				.'>';

				$thead .= '<span class="prefix"></span>';
			}

			$thead .= html($col['head']);

			if ($sortable) {
				$thead .= '<span class="postfix"></span>';
			}

			if ($sortbythis!==null && $MAKETABLE['orderimg']) {
				$thead .= img($URL.'/lib/maketable/'
				              .$sortbythis.'.png');
			}

			if ($sortable) {
				$thead .= '</a>';
			}
		} else {
			$thead .= '&nbsp;';
		}

		if ($col['search'] !== null) {
			$qparam = 'search_'.$col['as'];
			$search_string = $PARAMS[$qparam];
			if ($search_string === null) {
				$qparam = str_replace('.','_',$qparam);  # FIXME somehow
				$search_string = $PARAMS[$qparam];
			}

			$qname = substr($qparam,7);  # FIXME somehow

			if ($search_string === null) {
				$thead .= '<a class="'.html($MAKETABLE['class']).' findbutton" href="?'.html(
					maketable_mutate(array($qparam=>''),$PARAMS)
				).'" onclick="'.html(
					'maketable_display('.js($ID).','
						.'{'.js(urlencode($qparam)).':""},'
						.'function(){elem('.js($ID.'_search_'.$qname).').focus();}'
					.');return false;'
				).'"></a>';

			} else {
				$thead .= '<a class="'.html($MAKETABLE['class']).' nofindbutton" href="?'.html(
					maketable_mutate(array($qparam=>null),$PARAMS)
				).'" onclick="'.html(
					'maketable_display('.js($ID).',{'.js(urlencode($qparam)).':null});return false;'
				).'"></a>';

				$thead .= '<br/>';

				$thead .= '<div style="width:100%;position:relative;">';

				$thead .= '<input type="text" size="8" style="width:100%;"'
					.' id="'.html($ID.'_search_'.$qname).'"'
					.' name="'.html('search_'.$qname).'"'
					.' value="'.html($search_string).'"'
					.' onchange="'.html(
						'maketable_display('.js($ID).',{'
							.js($qparam).':'.'elem('.js($ID.'_search_'.$qname).').value,'
							.js('offset').':0,'
							.'"part":"body"'
						.'})'
					).'"'
				.' />';

				$thead .= '<noscript>';
				$thead .= '<button type="submit"'
					.' name="foo" value="bar"'
					.' class="'
						.html($MAKETABLE['class'])
						.' okbutton'
					.'"'
				.'></button>';
				$thead .= '</noscript>';

				$thead .= '</div>';
			}
		}

		$thead .= '</th>';

		++$i;
	}
	if ($MAKETABLE['buttons']) {
		$thead .= '<th class="'.html($MAKETABLE['class']).' top last buttons">'
			.'&nbsp;'
		.'</th>';
	}

	$thead .= '</tr>';

	return $thead;
}

function print_maketable_inner(&$MAKETABLE, &$PARAMS, &$rows, $offset, $limit) {
	global $ID;

	$thead = maketable_head($MAKETABLE, $PARAMS);

	if ($PARAMS['part']===null) {
		print '<table class="'.html($MAKETABLE['class']).'" cellspacing="0" cellpadding="0">';
		if ( ! $MAKETABLE['nohead'] )
			print '<thead id="'.html($ID.'_head').'">'.$thead."</thead>\n";
	}
	if ($PARAMS['part']===null || $PARAMS['part']=='body') {
		print '<tbody id="'.html($ID.'_body').'">';
	}

	$i = 0;
	foreach ($rows as $row) {
		if (count($MAKETABLE['dragdrop'])==1) {
			$dragsingle = array($MAKETABLE['join'][0]['as'], $MAKETABLE['key'][$MAKETABLE['join'][0]['table']]);
		} else {
			$dragsingle = null;
		}
		if ($i < $limit) {
			print_maketable_row($MAKETABLE, $PARAMS, $i, $row);
		}

		++$i;
	}

	if ($i > 0) {
		if ($MAKETABLE['navigation_always'] || $offset || $i>$limit || $limit!=$MAKETABLE['limit']) {
			print_maketable_footer($MAKETABLE,$PARAMS,$i,$offset,$limit);
		}

	} else if ( ! $MAKETABLE['noempty'] ) {
		print '<tr class="'.html($MAKETABLE['class']).' first last">';
		print '<td colspan="'.( count($MAKETABLE['columns'])
			+ ((count($MAKETABLE['userupdate'])>0)?1:0)
			+ ((count($MAKETABLE['userdelete'])>0)?1:0)
		).'"';
		if (strlen($MAKETABLE['ondrag'])) {
			print ' onmousemove="'.html('maketable_dd(false,event,'.js($ID).')').'"';
		}
		if (strlen($MAKETABLE['ondrop'])) {
			print ' onmouseup="'.html('maketable_dd(true,event,'.js($ID).')').'"';
		}
		print ' class="'.html($MAKETABLE['class']).' first last">'
			.maketable_text('No {1} found.',array($MAKETABLE['sayrecords']))
		.'</td>';
		print '</tr>';
	}

	if ($PARAMS['part']===null || $PARAMS['part']=='body') {
		print '</tbody>';
	}
	if ($PARAMS['part']===null) {
		print '</table>';
	}
}

function print_maketable_row(&$MAKETABLE, &$PARAMS, $i, &$row) {
	global $ID, $MISTAKES;

	$mistakes_list = array();

	print '<tr id="'.html($ID.'_'.$i).'" class="'.html($MAKETABLE['class']);
	if (!$MAKETABLE['noalt'])
		print ($i%2)?' odd':' even';
	if (!$i)
		print ' first';
	if ($i==count($rows)-1 || $i==($limit-1))
		print ' last';
	if ($MAKETABLE['rowclassfun']!==null) {
		$rowclass = $MAKETABLE['rowclassfun']($row);
		if (strlen($rowclass)>0) {
			print ' '.html($rowclass);
		}
	}
	print '"';

	$id_values = maketable_identifying_values($MAKETABLE,$row,'dragdrop');

	if (count($MAKETABLE['dragdrop'])) {
		print ' onmousedown="'.html('return begin_drag('.js($ID.'_'.$i).',{'
			.'"type":"maketablerow",'
			.'"id":'.js($ID).','
			.'"values":'.js($id_values)
		.'})').'"';
	}

	if (strlen($MAKETABLE['orderfield'])) {
		print ' onmousemove="'.html('maketable_dd_ord(false,event,'
			.js($ID).','.js($i).','.js($id_values)
		.')').'"';
	} else if (strlen($MAKETABLE['rowondrag'])) {
		print ' onmousemove="'.html('maketable_dd_row(false,event,'
			.js($ID).','.js($i).','.js($id_values)
		.')').'"';
	} else if (strlen($MAKETABLE['ondrag'])) {
		print ' onmousemove="'.html('maketable_dd(event,'.js($ID).')').'"';
	}

	if (strlen($MAKETABLE['orderfield'])) {
		print ' onmouseup="'.html('maketable_dd_ord(true,event,'
			.js($ID).','.js($i).','.js($id_values)
		.')').'"';
	} else if (strlen($MAKETABLE['rowondrop'])) {
		print ' onmouseup="'.html('maketable_dd_row(true,event,'
			.js($ID).','.js($i).','.js($id_values)
		.')').'"';
	} else if (strlen($MAKETABLE['ondrop'])) {
		print ' onmouseup="'.html('maketable_dd(event,'.js($ID).')').'"';
	}

	print '>';


	$editing = ($PARAMS['edit_'.$i]!==null && $PARAMS['cancel_'.$i]===null);
	$ident = maketable_identifying_values($MAKETABLE,$row,'');

	$j = 0;
	foreach ($MAKETABLE['columns'] as $colname => $col) {
		$mistaken = null;
		if ($PARAMS['ok_'.$i]!==null) {
			if ($MISTAKES[$col['as']]!==null) {
				$mistakes_list[] = $MISTAKES[$col['as']];
				$mistaken = count($mistakes_list);
			} else {
				$exp = explode('.',$col['as']);
				if (count($exp)==2 && $MISTAKES[$exp[1]]!==null) {
					$mistakes_list[] = $MISTAKES[$exp[1]];
					$mistaken = count($mistakes_list);
				}
			}
		}

		print '<td class="'.html($MAKETABLE['class']);
		if (count($MAKETABLE['dragdrop'])>0)
			print ' draggable';
		if (!$j)
			print ' first';
		if ($j==count($MAKETABLE['columns'])-1
		    && !$MAKETABLE['buttons'])
			print ' last';
		if ($col['class']!==null)
			print html(' '.$col['class']);
		if ($mistaken!==null)
			print ' mistake';
		print '"';

		if ($col['nowrap'])
			print ' nowrap="nowrap"';

		if (count($MAKETABLE['dragdrop'])>0)
			print ' onselectstart="return false"';
		print '>';

		if (!$j) {
			print hidden('id_'.$i,js($ident));
			if ($editing) {
				print hidden('edit_'.$i,'');
			}
		}

		if ($editing && $PARAMS[$i.'_'.$col['as']]!==null) {
			$value = $PARAMS[$i.'_'.$col['as']];
		} else {
			$value = $row[$col['as']];
			if ($value!==null && $col['prefix']!==null)
				print html($col['prefix']);
		}

		if ($editing && $col['edit']!='none') {
			switch ($col['edit']) {
			case 'text': default:
				print input(
					$i.'_'.$col['as'], $value,
					array($col['size'],$col['maxlength'])
				);
				break;
			case 'number':
				print number_input(
					$i.'_'.$col['as'], $value,
					array($col['size'],$col['maxlength'])
				);
				break;
			case 'date':
				print date_input(
					$i.'_'.$col['as'], $value
				);
				break;
			case 'datetime':
				print datetime_input(
					$i.'_'.$col['as'], $value
				);
				break;
			case 'dropdown':
				$opts = $col['opts'];
				if ($opts===null && $col['opts_sql'])
					$opts=select($col['opts_sql'],null,null,
					             $MAKETABLE['db']);
				if ($opts===null)
					$opts = array();
				print dropdown(
					$i.'_'.$col['as'], $value, $opts,
					' style="width:'.html($col['size']).'"'
				);
				break;
			case 'checkbox':
				print checkbox($i.'_'.$col['as'], $value, null);
				break;
			}
		} else if ($col['fun']) {
			print $col['fun']($row);
		} else if ($col['f']) {
			if (is_string($col['expr'])) {
				print $col['f']($row[$col['as']]);
			} else {
				print $col['f'](null);
			}
		} else if ($value!==null) {
			print html($value);
		}

		if ( ( ($editing && $col['edit']!='none')  ||
		       $value!==null ) && $col['postfix']!==null )
			print html($col['postfix']);

		if ($mistaken!==null) {
			print '<sup class="mistake">'.$mistaken.'</sup>';
		}

		print '</td>';

		++$j;
	}

	if ($MAKETABLE['buttons']) {
		print '<td class="'.html($MAKETABLE['class']).' last buttons">';
	}

	if ($editing) {
		print '<button type="submit" name="'.html('ok_'.$i).'" value=""'
		.' onclick="'.html(
			'return maketable_finish_edit('.js($ID).','.js($i).')'
		).'" class="'.html($MAKETABLE['class']).' okbutton"></button>';

		print '<span class="separator"></span>';

		print '<button type="submit" name="'.html('cancel_'.$i).'" value=""'
		.' onclick="'.html(
			'maketable_cancel_edit('.js($ID).','.js($i).');return false;'
		).'" class="'.html($MAKETABLE['class']).' cancelbutton"></button>';
	} else {
		$rowvars = array(
			'id'=>$ID,
			'rowid'=>$i,
			'ident'=>$ident,
		);
	
		if (is_string($MAKETABLE['sayrecordx'])) {
			$rowvars['rowname'] = $MAKETABLE['sayrecordx']($row);
		} else {
			$rowvars['rowname'] = $MAKETABLE['sayrecord'].' '.$row[
				sqlid( array(
					$MAKETABLE['join'][0]['as'],
					$MAKETABLE['key'][$MAKETABLE['join'][0]['table']]
				) ,1)
			];
			# NOTE this only talks about the record
			# from the first table, so if you have
			# many tables you may want to write a
			# callback
		}

		$sp = false;
		foreach ($MAKETABLE['buttons'] as $name=>$button) {
			if ($sp) {
				echo '<span class="separator"></span>';
			} else {
				$sp = true;
			}
			print_maketable_button($MAKETABLE,$button,$rowvars);
		}
	}

	if ($MAKETABLE['buttons']) {
		print '</td>';
	}

	print "</tr>\n";

	if ($MISTAKES && $PARAMS['ok_'.$i]!==null) {
		print '<tr class="'.html($MAKETABLE['class']).' mistakes';
		if (!$MAKETABLE['noalt'])
			print ($i%2)?' odd':' even';
		if (!$i)
			print ' first';
		if ($i==count($rows)-1 || $i==($limit-1))
			print ' last';
		if ($MAKETABLE['rowclassfun']!==null) {
			$rowclass = $MAKETABLE['rowclassfun']($row);
			if (strlen($rowclass)>0) {
				print ' '.html($rowclass);
			}
		}
		print '">';
		print '<td';
		print ' colspan="'
			.( count($MAKETABLE['columns'])
			   + ($MAKETABLE['buttons']?1:0) )
		.'"';
		print ' class="'.html($MAKETABLE['class']).' mistakes first last"';
		print '>';

		print '<ol>';
		foreach ($mistakes_list as $mist) {
			print '<li>'.html($mist).'</li>';
		}
		print '</ol>';

		print '</td>';
		print '</tr>';
	}
}

function print_maketable_button(&$MAKETABLE,$button,$rowvars) {
	global $ID;
	if ($button['url']!==null) {
		print '<a href="'.html($button['url'].'?'.queryencode($rowvars['ident'])).'"'
			.' id="'.html($ID.'_'.$button['name']).'_'.$rowvars['rowid'].'"'
			.' class="'.html($MAKETABLE['class'].' '.$button['class']).'"'
		.'>'.$button['html'].'</a>';
	} else {
		$vnames = ifnull($button['vars'],array('id','rowid','ident'));

		$jsv = array();
		foreach ($vnames as $v)
			$jsv[] = $v.'='.js($rowvars[$v]);

		$js = $jsv ? 'var '.implode(',',$jsv).';' : '';
		$js .= $button['onclick'];

		print '<button'
			.' type="'.html($button['type']).'"'
			.' id="'.html($ID.'_'.$button['name']).'_'.$rowvars['rowid'].'"'
			.' name="'.html($button['name'].'_'.$rowvars['rowid']).'"'
			.' value="'.html(js($rowvars['ident'])).'"'
			.' class="'.html($MAKETABLE['class'].' '.$button['class']).'"'
			.' onclick="'.html($js).'"'
		.'>'.$button['html'].'</button>';
	}
}

function print_maketable_footer(&$MAKETABLE,&$PARAMS,$i,$offset,$limit) {
	global $ID;

	print '<tr class="'.html($MAKETABLE['class']).' bottom">';
	print '<td colspan="'.(
		count($MAKETABLE['columns'])
		+ ($MAKETABLE['buttons'] ? 1 : 0)
	).'" class="'.html($MAKETABLE['class']).' bottom first last">';

	print hidden('offset',$offset);

	if ($offset > 0) {
		print '<div class="'.html($MAKETABLE['class']).' pagingbuttons left" style="float:left">';

		print '<a class="'.html($MAKETABLE['class']).' firstbutton" '.maketable_nav($ID,$PARAMS,array(
			'offset' => 0,
		)).'></a>';
		$pp = max(0,$offset-$limit);

		print '<a class="'.html($MAKETABLE['class']).' prevbutton" '.maketable_nav($ID,$PARAMS,array(
			'offset' => $pp,
		)).'></a>';
		print '</div>';
	}

	if ($limit >= $i) {
		# we hit the end of the table
		$num_all_records = $offset + $i;

	} else {
		# we don't know where is the end of the table yet
		if ($MAKETABLE['showcount']) {
			$num_all_records = value(
				maketable_sql($MAKETABLE, $PARAMS, true),
				$MAKETABLE['db']
			);
		} else {
			$num_all_records = null;
		}
		print '<div class="'.html($MAKETABLE['class']).' pagingbuttons right" style="float:right;">';
		print '<a class="'.html($MAKETABLE['class']).' nextbutton" '.maketable_nav($ID,$PARAMS,array(
			'offset' => $offset+$limit
		)).'></a>';
		if ($MAKETABLE['showcount']) {  # FIXME trouble with `having' clause
			if ($MAKETABLE['navigation']==3) {
				$last_page_offset = intval((intval($num_all_records)-1)/$limit)*$limit;
			} else {
				$last_page_offset = (intval($num_all_records)-$limit);
			}
			print '<a class="'.html($MAKETABLE['class']).' lastbutton" '.maketable_nav($ID,$PARAMS,array(
				'offset' => $last_page_offset
			)).'></a>';
		}
		print '</div>';
	}

	#
	# make drop-down
	#
	$limitopts = array();
	$maxpagesize = ( ($num_all_records!==null) ? ($num_all_records-$offset) : null );

	$pagesizeoptions = array(10,15,20,50,100,200,500);
	$pagesizeoptions[] = $MAKETABLE['limit'];
	$pagesizeoptions[] = $limit;
	sort($pagesizeoptions);
	$selectedseen = false;
	foreach (array_unique($pagesizeoptions) as $pagesize) {
		$n = ($maxpagesize!==null ? min($pagesize,$maxpagesize) : $pagesize);

		switch ($MAKETABLE['navigation']) {
		case 3:
			$text = strval($pagesize);
			break;
		case 2:
			if ($offset == 0) {
				if ($maxpagesize!==null && $maxpagesize<=$pagesize) {
					$text = maketable_text('All {1}',array($maxpagesize));
				} else {
					$text = maketable_text('First {1}',array($pagesize));
				}
			} else if ($maxpagesize!==null && $maxpagesize<=$pagesize) {
				$text = maketable_text('Last {1}',array($maxpagesize));
			} else {
				$text = maketable_text('{1} to {2}', array($offset+1,$offset+$n));
			}
			break;
		case 1:
		case 0:
			if ($n>1) {
				$text = maketable_text('{1} to {2}', array($offset+1,$offset+$n));
			} else {
				$text = ($offset+1);
			}
			break;
		}

		if ($pagesize == $limit)
			$selectedseen = true;

		if ($maxpagesize===null || $pagesize<$maxpagesize || $selectedseen)
			$limitopts[] = array($pagesize, $text);

		if ($maxpagesize!==null && $pagesize>=$maxpagesize && $selectedseen)
			break;
	}

	$limitsel = dropdown('limit', $limit, $limitopts,null,null,
		'maketable_display('.js($ID).',{"limit":elem('.js($ID.'_limit').').value})'
	);

	#
	# make the text
	#
	switch ($MAKETABLE['navigation']) {
	case 3:
		$cur_page = intval($offset/$limit);

		if ($num_all_records!==null) {
			$num_all_pages = intval(($num_all_records+$limit-1)/$limit);
			$num_opt_pages = $num_all_pages;
		} else {
			$num_all_pages = null;
			$num_opt_pages = $cur_page+1;
		}

		$offsetopts = array();
		for ($j = 0 ; $j < $num_opt_pages ; ++$j)
			$offsetopts[] = array($j*$limit, $j+1);
		if ($num_all_pages === null)  # extra "next" page added to dropdown
			$offsetopts[] = array($j*$limit, $j+1);
		$offsetsel = dropdown('offset', $cur_page*$limit, $offsetopts,null,null,
			'maketable_display('.js($ID).',{"offset":elem('.js($ID.'_offset').').value})'
		);

		if ($num_all_pages !== null) {
			print str_replace(
				array('{1}','{2}'),
				array($offsetsel, strval($num_all_pages)),
				html_stiff( maketable_text('Page {1} of {2}') )
			);
		} else {
			print str_replace('{1}', $offsetsel,
				html_stiff( maketable_text('Page {1}') )
			);
		}
		if ($num_all_records!==null && $MAKETABLE['showcount']) {
			print ' ';
			print str_replace(
				array('{1}','{2}'),
				array( $num_all_records, (($num_all_records==1) ? $MAKETABLE['sayrecord'] : $MAKETABLE['sayrecords']) ),
				html_stiff( maketable_text('({1} {2} total)') )
			);
		}
		if (count($limitopts)>1) {
			print ' - ';
			print str_replace('{1}', $limitsel,
				html_stiff( maketable_text('{1} per page') )
			);
		}
		break;
	case 2:
		if ($offset == 0) {
			print $limitsel.html_stiff(' '.$MAKETABLE['sayrecords']);
		} else if ($limit < $i) {
			print html_stiff(mb_strtoupper(mb_substr($MAKETABLE['sayrecords'],0,1)).mb_substr($MAKETABLE['sayrecords'],1).' ').$limitsel;
		} else if ($i > 1) {
			print $limitsel.html_stiff(' '.$MAKETABLE['sayrecords']);
		} else {
			print $limitsel.html_stiff(' '.$MAKETABLE['sayrecord']);
		}
		if ($num_all_records!==null && ($offset > 0 || $limit<$i))
			print html_stiff(maketable_text(' (out of {1})',array($num_all_records)));
		break;
	case 1:
	case 0:
		if ($i > 1) {
			print html_stiff(mb_strtoupper(mb_substr($MAKETABLE['sayrecords'],0,1)).mb_substr($MAKETABLE['sayrecords'],1).' ').$limitsel;
		} else {
			print html_stiff(mb_strtoupper(mb_substr($MAKETABLE['sayrecord'],0,1)).mb_substr($MAKETABLE['sayrecord'],1).' ').$limitsel;
		}
		#if ($num_all_records!==null && ($offset > 0 || $limit<$i))
		if ($num_all_records!==null && $MAKETABLE['showcount'])
			print ' '.html_stiff(maketable_text('({1} total)',array($num_all_records)));
		break;
	default:
		print hidden('limit',$limit);
	}

	if ($MAKETABLE['navigation']>=0) {
		print '<button type="submit" onclick="'.html(
		 	'maketable_display('.js($ID).');return false;'
		).'" class="'.html($MAKETABLE['class']).' refreshbutton"></button>';
	}

	print '</td>';
	print '</tr>';
}

function maketable_nav($ID, $PARAMS, $newstuff=array()) {
	global $ID;

	return 'href="?'.html(maketable_mutate($newstuff,$PARAMS)).'"'
	.' onclick="'.html(
		 'maketable_display('.js($ID).','.js($newstuff).');'
		 .'return false;'
	).'"';
}

function maketable_mutate($newstuff,$PARAMS) {
	# remove row editing
	foreach ($PARAMS as $name=>$value) {
		if (startswith($name,'edit_') || startswith($name,'cancel_') || startswith($name,'ok_') || startswith($name,'id_')) {
			unset($PARAMS[$name]);

			$i = substr($name,strpos($name,'_')+1);
			foreach ($PARAMS as $name2=>$value2) {
				if (startswith($name2,$i.'_')) 
				unset($PARAMS[$name2]);
			}
		}
	}
	return mutate($newstuff,$PARAMS);
}

function maketable_identifying_values(&$MAKETABLE,$row,$set=null) {
	$values = array();
	if (strlen($set)) {
		foreach ($MAKETABLE[$set] as $instance) {
			$join = $MAKETABLE['join'][$instance];
			$keyfield = $MAKETABLE['key'][$join['table']];
			$f = sqlid(array($join['as'],$keyfield),1);
			$v = maketable_specified_value($f, $row);
			$values[$f] = $v;
		}
	} else {
		foreach ($MAKETABLE['join'] as $join) {
			$keyfield = $MAKETABLE['key'][$join['table']];
			$f = sqlid(array($join['as'],$keyfield),1);
			$v = maketable_specified_value($f, $row);
			$values[$f] = $v;
		}
	}
	if (strlen($MAKETABLE['orderfield'])) {
		$f = $MAKETABLE['orderfield'];
		$values[$f] = maketable_specified_value($f, $row);
	}
	return $values;
}


################################################################################
#
# HTTP POST handling
#
function maketable_posted(&$MAKETABLE,&$PARAMS) {
	$op = $PARAMS['op'];
	unset($PARAMS['op']);
	switch ($op) {

	case 'reorder':
		if ( !count($MAKETABLE['dragdrop']) ||
		     !strlen($MAKETABLE['orderfield']) )
			throw new LoggedException('Not an ordered table.');

		$of = $MAKETABLE['orderfield'];
		$updjoin = maketable_sql_join($MAKETABLE);
		$updrange = maketable_sql_selectall($MAKETABLE);
		$from = intval($PARAMS['reorderfrom']);
		$to = intval($PARAMS['reorderto']);
		unset($PARAMS['reorderfrom']);
		unset($PARAMS['reorderto']);

		execute( "UPDATE $updjoin"
		         ." SET $of=IF($of=$from,$to,"
		                       .($from<$to?"$of-1":"$of+1").")"
		         ." WHERE $of>=".min($from,$to)
		           ." AND $of<=".max($from,$to)
		         .(strlen($updrange)?' AND '.$updrange:''),
		         $MAKETABLE['db'] );

		commit($MAKETABLE['db']);
		break;

	case 'insert':
		if ( ! $MAKETABLE['insert'] )
			throw new LoggedException('No insertions permitted.');

		$row = jsdecode($PARAMS['row']);
		unset($PARAMS['row']);

		if ($MAKETABLE['insertinc'] !== null) {
			if (!maketable_include($MAKETABLE['insertinc'], $row))
				throw new Exception('');
		} else {
			maketable_insert($MAKETABLE, $row);
		}
		break;

	case 'delete':
		if ( count($MAKETABLE['delete'])<=0 )
			throw new LoggedException('No deletions permitted.');

		$keys = jsdecode($PARAMS['key']);
		unset($PARAMS['key']);

		maketable_delete($MAKETABLE, $keys);
		break;

	default:
		foreach ($PARAMS as $name=>$value) {
			if (startswith($name,'delete_')) {
				$i = substr($name,7);

				unset($PARAMS[$name]);

				maketable_delete(
					$MAKETABLE,
					jsdecode($PARAMS['id_'.$i])
				);

			} else if (startswith($name,'ok_')) {
				$i = substr($name,3);

				unset($PARAMS[$name]);
				unset($PARAMS['cancel_'.$i]);

				maketable_update(
					$MAKETABLE,
					$i,
					jsdecode($PARAMS['id_'.$i]),
					$PARAMS
				);

				unset($PARAMS['edit_'.$i]);
			} else if (startswith($name,'cancel_')) {
				$i = substr($name,7);

				unset($PARAMS[$name]);
				unset($PARAMS['ok_'.$i]);
				unset($PARAMS['edit_'.$i]);
			} else {
				$nex = explode('_',$name,2);
				$i = intval($nex[1]);
				if ($MAKETABLE['buttons'][$nex[0]]!==null
				    && strval($i)===$nex[1]) {
					maketable_posted_button(
						$MAKETABLE,
						$nex[0],
						jsdecode($PARAMS['id_'.$i])
					);
				}
			}
		}
	}
}

function maketable_posted_button($MAKETABLE, $btn, $keys) {
	$inc = $MAKETABLE['buttons'][$btn]['include'];
	if ($inc!==null) {
		$extra = $MAKETABLE['buttons'][$btn]['extra'];
		if ($extra!==null) foreach ($extra as $n=>$v) {
			if ($keys[$n]===null) {
				$keys[$n] = $v;
			}
		}
		if (!maketable_include($inc, $keys))
			throw new Exception('');
	}
}

# simulates a POST to the included page
function maketable_include($script, $postdata=null) {
	global $_GET,$_POST,$INLINE_REQUEST;
	$OLD_GET = $_GET;
	$OLD_POST = $_POST;
	$OLD_INLINE_REQUEST = $INLINE_REQUEST;

	$_GET = array();
	$_POST = (is_array($postdata)?$postdata:array());
	$INLINE_REQUEST = 2;

	$status = include $script;

	$_GET = $OLD_GET;
	$_POST = $OLD_POST;
	$INLINE_REQUEST = $OLD_INLINE_REQUEST;

	return $status;
}

function maketable_insert(&$MAKETABLE, $data) {
	$history = array();

	foreach ($MAKETABLE['join'] as $instance=>$join) {
		$instname = $join['as'];
		$instvalues = array();

		# join conditions become assignments
		foreach ($join['on'] as $field=>$sql) if (is_string($field)) {
			if (breaksqlid($field,$broken)>=2 && $broken[0]==$instname) {
				$history[sqlid($broken,1)] = $instvalues[$broken[1]] = $sql;
			} else if (count($broken)>=1) {
				$history[sqlid(array($instname,$broken[0]),1)] =
					$instvalues[$broken[0]] =
						maketable_sql_substitution($sql, $history);
			}
		}

		# get any extra values specified by the client (only for fields declared in 'columns' or in 'hidden')
		foreach (array_merge($MAKETABLE['hidden'],$MAKETABLE['columns']) as $col) if (strlen($col['field'])) {
			$field = $col['field'];

			if (breaksqlid($field,$broken)>=2 && $broken[0]==$instname) {
				$value = maketable_specified_value($field,$data);
				if ($value !== null) {
					$history[$field] = $instvalues[$broken[1]] = $value;
				} else {
					$value = $data[sqlid($broken[1],1)];
					if ($value !== null) {
						$history[$field] = $instvalues[$broken[1]] = $value;
					}
				}
			}
		}

		# if the orderfield is in this instance and its value is not specified, then insert at end
		# otherwise make room for new record
		if ( strlen($MAKETABLE['orderfield']) && breaksqlid($MAKETABLE['orderfield'],$broken)>=2 && $broken[0]==$instname) {
			$ordfrom = maketable_sql_join($MAKETABLE);
			$ordselect = maketable_sql_selectall($MAKETABLE);
			$spec_order = maketable_specified_value($MAKETABLE['orderfield'], $data);
			if ($spec_order === null) {
				$history[$MAKETABLE['orderfield']] =
				$instvalues[$broken[1]] = value(
					"IFNULL(MAX("
						.$MAKETABLE['orderfield'].
					"),0)+1 FROM $ordfrom WHERE $ordselect",
					$MAKETABLE['db']
				);
			} else {
				$of = $MAKETABLE['orderfield'];
				execute( "UPDATE $ordfrom SET $of=$of+1 WHERE $of>=$spec_order".(strlen($ordselect)?' AND '.$ordselect:'') );
			}
		}

		$table = $MAKETABLE['join'][$instance]['table'];
		$pkey = $MAKETABLE['key'][$table];
		if (array_search($instance,$MAKETABLE['insert']) !== false) {
			$history[sqlid(array($instname,$pkey),1)] =
				insert($table, $instvalues,
				       $MAKETABLE['db']);
		}
	}

	commit($MAKETABLE['db']);
}

function maketable_delete(&$MAKETABLE, $keys) {
	if ($MAKETABLE['deleteinc'] !== null) {
		if ($MAKETABLE['deleteneg']) {
			foreach ($keys as $name=>$value) {
				$keys[$name] = -$keys[$name];
			}
		}
		if (!maketable_include($MAKETABLE['deleteinc'], $keys))
			throw new Exception('');

		return;
	}

	$tables = implode(',',maketable_instance_names($MAKETABLE,'delete'));
	$join = maketable_sql_join($MAKETABLE);
	$select = maketable_sql_selectone($MAKETABLE, $keys);
	execute("DELETE $tables FROM $join WHERE $select");

	if ( strlen($MAKETABLE['orderfield']) && breaksqlid($MAKETABLE['orderfield'],$broken)>=2) {
		$spec_order = maketable_specified_value($MAKETABLE['orderfield'], $keys);
		if ($spec_order !== null) {
			$ordfrom = maketable_sql_join($MAKETABLE);
			$ordselect = maketable_sql_selectall($MAKETABLE);
			$of = $MAKETABLE['orderfield'];
			execute( "UPDATE $ordfrom SET $of=$of-1 WHERE $of>".sql($spec_order).(strlen($ordselect)?' AND '.$ordselect:'') );
		}
	}

	commit($MAKETABLE['db']);
}

function maketable_update(&$MAKETABLE, $trid, $keys, $formdata) {
	global $ID;
	$trid = strval($trid);

	if ($MAKETABLE['updateinc'] !== null) {
		$unwrap = array();
		foreach ($formdata as $name=>$value) {
			if (substr($name,0,strlen($trid)+1) == $trid.'_') {
				$unwrap[substr($name,strlen($trid)+1)] = $value;
			}
		}
		if (!maketable_include($MAKETABLE['updateinc'], array_merge($unwrap,$keys)))
			throw new Exception('');
	} else {
		$join = maketable_sql_join($MAKETABLE);
		$select = maketable_sql_selectone($MAKETABLE, $keys);

		$assignments = array();
		foreach ($MAKETABLE['columns'] as $col) {
			if ($col['edit'] != 'none' && $col['field']!==null) {
				$y = $trid.'_'.$col['as'];

				$x = $formdata[$y];
				if ($x===null) $x = $formdata[str_replace('.','_',$y)];
				if ($col['edit']=='checkbox' && $x===null) {
					$x = $formdata[$y.'___OFF__'];
					if ($x===null) $x = $formdata[str_replace('.','_',$y).'___OFF__'];
				}

				$assignments[] = $col['field'].'='.sql($x);
			}
		}

		execute('UPDATE '.$join.' SET '.implode(',',$assignments).' WHERE '.$select);
	}

	commit($MAKETABLE['db']);
}

function maketable_instance_names(&$MAKETABLE, $indices) {
	$out = array();
	foreach ($MAKETABLE[$indices] as $i) {
		$out[] = $MAKETABLE['join'][$i]['as'];
	}
	return $out;
}

# either 'foo.bar' or 'bar' in $params
# assumes $sqlid is normalized ( sqlid(unsqlid($id),1) )
function maketable_specified_value($sqlid, &$params) {
	if ($params[$sqlid]!==null) {
		return $params[$sqlid];
	} else if (breaksqlid($sqlid,$broken)>=2) {
		return $params[sqlid($broken[count($broken)-1],1)];
	} else {
		return null;
	}
}

function maketable_sql_substitution($sql, $history) {
	# XXX FIXME XXX FIXME XXX FIXME XXX cannot substitute inside SQL expressions!!!
	if (breaksqlid($sql,$broken)==2) {
		$v = $history[sqlid($broken,1)];
		if ($v !== null) {
			return $v;
		} else {
			return $sql;
		}
	} else {
		return $sql;
	}
}


################################################################################


$MAKETABLE_TEXT = array(
/*
	'Page {1}'
	=> 'Σελίδα {1}',

	'Page {1} of {2}'
	=> 'Σελίδα {1} από {2}',

	'{1} per page'
	=> '{1} ανά σελίδα',

	'({1} {2} total)'
	=>'({1} {2} συνολικά)',

	'({1} total)'
	=>'({1} συνολικά)',

	' (out of {1})'
	=>' (από {1})',

	'First {1}'
	=>'Πρώτα {1}',

	'Last {1}'
	=>'Τελευταία {1}',

	'All {1}'
	=>'Όλα {1}',

	'{1} to {2}'
	=>'{1} έως {2}',

	'Are you sure you want to erase {1}?'
	=>'Θέλετε σίγουρα να σβηστεί {1}?',

	'refresh'
	=>'ανανέωση',

	'No {1} found.'
	=>'Δε βρέθηκαν {1}.',
	
	'record'
	=>'εγγραφή',

	'records'
	=>'εγγραφές',
*/
);

function maketable_text($text, $y=null) {
	global $MAKETABLE_TEXT;

	$z = $MAKETABLE_TEXT[$text];
	if ($z===null) $z = $text;

	if ($y!==null) {
		$x = array();
		foreach ($y as $i=>$a)
			$x[] = '{'.strval($i+1).'}';
		$z = str_replace($x,$y,$z);
	}

	return $z;
}


function maketable_first_key(&$array) {
	foreach ($array as $x => $y)
		return $x;
	
	throw new LoggedException('array is empty and therefore has no first key');
}

function maketable_first_value(&$array) {
	foreach ($array as $x => $y)
		return $y;
	
	throw new LoggedException('array is empty and therefore has no first value');
}

function maketable_array_map(&$array,&$mapping) {
	$out = array();
	foreach ($array as $x => $y)
		$out[$x] = $mapping[$y];
	return $out;
}

?>
