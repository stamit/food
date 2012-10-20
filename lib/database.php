<?
require_once 'lib/util.php';
require_once 'lib/adodb5/adodb.inc.php';
require_once 'lib/adodb5/adodb-exceptions.inc.php';

#
# fetching functons:
#
# select($sql, $limit=null, $offset=null, $db=null)
# value0($sql, $db=null)
# value1($sql, $db=null)
# value($sql, $db=null)
# row0($sql, $db=null)
# row1($sql, $db=null)
# row($sql, $db=null)
# col($sql, $db=null)
# fetch($tablekeys,$row, $db=null)

#
# storing functions:
#
# execute($sql, $db=null)
# insert($table, $row, $db=null)
# update($tablekey, $row, $db=null)
# delete($tablekey, $row, $db=null)
# store($tablekeys,$row, $db=null)

#
# database connections:
#
# connect($con,$encoding=null)
# commit($db=null)
# rollback($db=null)


# SQL escaping function
function sql($x, $db=null) {
	if ($x === null) {
		return 'NULL';
	} else if (is_bool($x)) {
		return ($x ? 'TRUE' : 'FALSE');
	} else if (is_integer($x)) {
		return strval($x);
	} else if (is_real($x)) {
		return number_format($x,12,'.','');
	} else if (is_string($x)) {
		return qstr($x, $db);
	} else if (is_array($x)) {
		$z = array();
		foreach ($x as $y) {
			$z[] = sql($y, $db);
		}
		return '('.implode(',',$z).')';
	} else {
		throw new Exception('cannot represent value as SQL: '.repr($x));
	}
}
function qstrr(&$x, $db=null) {  # for strings only (takes reference)
	global $DB; if ($db === null) $db = $DB;

	return $db->qstr($x);
}
function qstr($x, $db=null) {  # for strings only
	global $DB; if ($db === null) $db = $DB;

	return $db->qstr($x);
}

#
# makes an SQL identifier, escaping with backticks where necessary
#
# !(mode&2): 'foo.b&&a`r' => '`foo.b&&a``r`'
#   mode&2 : 'foo.b&&a`r' => 'foo`.`b`&&`a````r'
#            quote only the parts of the identifier with special characters
#   mode&4 : 'anything' => '`anything`'
#
$SQL_IDCHARS='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
function sqlid($string, $mode=4) {
	global $SQL_IDCHARS;
	$unquoted = "[$SQL_IDCHARS][".$SQL_IDCHARS."0123456789]*";
	if (is_string($string)) {

		if ( ($mode&4) || (!preg_match("/^$unquoted$/",$string) && !($mode&2)) ) {
			return '`'.str_replace('`','``',$string).'`';
		} else if ($mode&2) {
			preg_match_all("/([$SQL_IDCHARS]+|[^$SQL_IDCHARS]+)/",$string,$pieces);
			$a = array();
			foreach ($pieces[0] as $s) $a[] = sqlid($s,$mode&(~2));
			return implode('',$a);
		} else {
			return $string;
		}

	} else if (is_array($string)) {
		$a = array();
		foreach ($string as $s) $a[] = sqlid($s,$mode);
		return implode('.',$a);

	} else {
		return null;
	}
}
function breaksqlid($string, &$broken) {
	global $SQL_IDCHARS;
	$unquoted = "[$SQL_IDCHARS][".$SQL_IDCHARS."0123456789]*";
	$quoted = "`(([^`]|``)*)`";
	if ($string == '') {
		$broken = array('');
		return 1;
	} else if (preg_match("/^$unquoted$/",$string)) {
		$broken = array($string);
		return 1;
	} else if (preg_match("/^$quoted$/",$string,$regs)) {
		$broken = array(str_replace('``','`',$regs[1]));
		return 1;
	} else if (preg_match("/^($unquoted|$quoted)+(\.($unquoted|$quoted)+)*$/",$string)) {
		preg_match_all("/($unquoted|$quoted)+/x",$string,$tokens);
		$broken = array();
		foreach ($tokens[0] as $token) {
			$identifier = '';
			preg_match_all("/($unquoted|$quoted)/x",$token,$parts);
			foreach ($parts[0] as $part) {
				if (preg_match("/^$quoted$/",$part,$matches)) {
					$identifier .= str_replace('``','`',$matches[1]);
				} else {
					$identifier .= $part;
				}
			}
			$broken[] = $identifier;
		}
		return count($broken);
	} else {
		$broken = array();
		return 0;
	}
}
function unsqlid($string) {
	if (breaksqlid($string, $broken) >= 1) {
		if (count($broken)>=2) {
			return $broken;
		} else {
			return $broken[0];
		}
	} else {
		return null;
	}
}

# generates the text of a "WHERE" clause, after the "WHERE"
function sql_where_conditions($names,$values,$db,$from=0) {
	$conditions = array();
	if (is_array($values)) {
		for ($i = $from ; $i < count($names) ; ++$i) {
			$name = $names[$i];
			if ($i===0) {
				$table = $name;
			} else if (array_key_exists($name,$values)) {
				$conditions[] = sqlid($name).'='.sql($values[$name],$db);
			} else if ($from==1 && array_key_exists($names[0].'.'.$name,$values)) {
				$conditions[] = sqlid($name).'='.sql($values[$names[0].'.'.$name],$db);
			} else {
				throw new Exception('not all key values given');
			}
		}
	} else {
		if (count($names)!=2)
			throw new Exception('not enough key values given');

		$conditions[] = sqlid($names[1]).'='.sql($values,$db);
	}
	if (!$conditions)
		throw new Exception('no keys were specified');
	return implode(' AND ',$conditions);
}


################################################################################


# the result of performing SQL query "SELECT $sql" on database $db (or global
# $DB, if null), from row offset $offset (or beginning, if null) and with
# maximum number of returned rows $limit (or all rows, if null)
function select($sql, $limit=null, $offset=null, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$results = array();
	if ($limit!==null) {
		$set = $db->SelectLimit('SELECT '.$sql,
			intval($limit), max(0,intval($offset))
		);
	} else {
		$set = $db->Execute('SELECT '.$sql);
	}

	if ($set === FALSE)
		throw new Exception($db->ErrorMsg());

	while (!$set->EOF) {
		$results[] = $set->fields;
		$set->MoveNext();
	}

	return $results;
}

# the only field of the only row or NULL if there are no resulting rows
function value0($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$results = select($sql, 2, 0, $db);
	if (count($results) > 1)
		throw new Exception("query gives more than one result: ".$sql);
	if (!count($results))
		return null;

	$row = $results[0];
	if (count($row) < 1)
		throw new Exception("query returned record with no fields: ".$sql);
	if (count($row) > 1)
		throw new Exception("query returned record with more than one field: ".$sql);
	foreach ($row as $a)
		return $a;

	return null;
}

# the only field of the first row
function value1($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$row = row1($sql, $db);
	if (count($row) < 1)
		throw new Exception("query returned record with no fields: ".$sql);
	if (count($row) > 1)
		throw new Exception("query returned record with more than one field: ".$sql);
	foreach ($row as $a)
		return $a;
	return null;
}

# the single field of the single resulting row
function value($sql,$db=null) {
	global $DB; if ($db === null) $db = $DB;

	$x = row($sql,$db);
	if (count($x) < 1)
		throw new Exception("query returned record with no fields: ".$sql);
	if (count($x) > 1)
		throw new Exception("query returned record with more than one field: ".$sql);
	foreach ($x as $a) $y = $a;
	return $y;
}

# the only row or NULL (expects at most one row)
function row0($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$results = select($sql, 2, 0, $db);
	if (count($results) > 1)
		throw new Exception("query gives more than one result: ".$sql);
	return (count($results)>0) ? $results[0] : null;
}

# the first row of possibly many rows
function row1($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$results = select($sql, 1, 0, $db);
	if (!count($results))
		throw new Exception("query gives no results: ".$sql);
	return $results[0];
}

# the single resulting row
function row($sql,$db=null) {
	global $DB; if ($db === null) $db = $DB;

	$results = select($sql, 2, 0, $db);
	if (!count($results))
		throw new Exception("query gives no results: ".$sql);
	if (count($results) > 1)
		throw new Exception("query gives more than one result: ".$sql);
	return $results[0];
}

# an array with the only value of each of the zero or more rows returned by query $sql
function col($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$xs = select($sql,null,null,$db);
	for ($i = 0 ; $i < count($xs) ; ++$i) {
		$row = $xs[$i];
		if (count($row) < 1)
			throw new Exception("query returned record with no fields: ".$sql);
		if (count($row) > 1)
			throw new Exception("query returned record with more than one field: ".$sql);
		foreach ($row as $a) $xs[$i] = $a;
	}
	return $xs;
}

# the row from table.keyfield $tablekey which has key with value $id
function fetch($tablekey,$id,$db=null) {
	global $DB; if ($db === null) $db = $DB;

	$words = explode('.',$tablekey);
	$table = $words[0];

	return ($id !== null)
		? row0('* FROM '.sqlid($table)
		       .' WHERE '.sql_where_conditions($words,$id,$db,1))
		: null
	;
}

################################################################################

# number of rows affected by running query $sql on the database $db or on the global database $DB
function execute($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$set = $db->Execute($sql);
	if ($set === FALSE)
		throw new Exception($db->ErrorMsg());

	return $db->Affected_Rows();
}

# id of the row inserted into table $table based on $row
function insert($tablekeys, $row, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$words = explode('.',$tablekeys);
	$table = $words[0];

	# FIXME what does ADODB return in the case of multiple key fields?

	$fields = array();
	$values = array();
	foreach ($row as $field=>$value) {
		$fields[] = sqlid($field);
		$values[] = sql($value,$db);
	}

	$sql = 'INSERT INTO '.sqlid($table)
	       .' ('.implode(',',$fields).')'
	       .' VALUES ('.implode(',',$values).')';

	execute($sql,$db);

	return $db->Insert_ID();
}

# Update the table.keyfields $tablekeys row identified by (and as described by) $row .
# - array $row contains values for all key fields as well as update data
function update($tablekeys, $row, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$words = explode('.',$tablekeys);
	$table = $words[0];
	if ($table === null)
		throw new Exception('no table specified');

	$assignments = array();
	foreach ($row as $field=>$value) {
		$i = array_search($field,$words);
		if ($i===FALSE || $i<1) {
			$assignments[] = sqlid($field).'='.sql($value,$db);
		}
	}
	if (!$assignments) return 0;

	$sql = 'UPDATE '.sqlid($table)
	       .' SET '.implode(',',$assignments)
	       .' WHERE '.sql_where_conditions($words,$row,$db,1);

	execute($sql,$db);
}

# Delete the table.keyfields $tablekeys row identified by $row .
# - array $row contains values for all key fields
function delete($tablekeys, $row, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$words = explode('.',$tablekeys);
	$table = $words[0];
	if ($table === null)
		throw new Exception('no table specified');

	$sql = 'DELETE FROM '.sqlid($table)
	       .' WHERE '.sql_where_conditions($words,$row,$db,1);

	execute($sql,$db);
}

# ID of inserted, updated or deleted table.id $tablekeys row, from operation
# described by $row (zero ID value means insert, positive ID value means update
# and negative ID value means delete)
function store($tablekeys, $row, $db=null) {
	global $DB; if ($db === null) $db = $DB;
	global $REQUEST_ID;

	$words = explode('.',$tablekeys);
	$table = $words[0];

	if ( count($words)==1 ) {
		$id = insert($table, $row, $db);

	} else if ( count($words)==2 ) {
		$key = $words[1];
		if ( ! $row[$key] ) {
			$id = insert($table, $row, $db);
		} else if ( $row[$key] > 0) {
			$id = $row[$key];
			update($tablekeys, $row, $db);
		} else {
			$id = -$row[$key];
			delete($tablekeys, $id, $db);
			$id = $row[$key];
		}
	} else {
		update($tablekeys, $row, $db);
	}

	insert('log_database',array(
		'request_id'=>$REQUEST_ID,
		'user_id'=>$_SESSION['user_id'],
		'table'=>$table,
		'table_id'=>abs($id),
		'changes'=>js($row),
	),$db);

	return $id;
}


################################################################################


$MYSQL_ENCODING_MAPPING = array(
	'UTF8'=>'utf8',
	'UTF-8'=>'utf8',
	'ISO8859-7'=>'greek',
	'ISO-8859-7'=>'greek',
);

function map_database_charset($dbtype, $encoding) {
	global $MYSQL_ENCODING_MAPPING;
	if (substr($dbtype,0,5)=='mysql') {
		return ifnull($MYSQL_ENCODING_MAPPING[strtoupper($encoding)],
		              strtoupper($encoding));
	} else {
		return null;
	}
}

function connect($con,$encoding=null) {
	global $ENCODING;

	if (is_string($con)) {
		$db = ADONewConnection($con);
		$dbtype = $con;
	} else {
		$db = ADONewConnection($con['dbtype']);
		$db->PConnect($con['dbserver'], $con['dbuser'], $con['dbpass'],
		              $con['dbname']);
		$dbtype = $con['dbtype'];
	}

	if (substr($dbtype,0,5)=='mysql') {
		$names = map_database_charset($dbtype,ifnull($encoding,
		                                             $ENCODING));
		$db->Execute('SET NAMES '.$db->qstr($names));
	}

	if (substr($dbtype,0,6)=='mysqlt') {
		$db->BeginTrans();
	}

	$db->SetFetchMode(ADODB_FETCH_ASSOC);

	return $db;
}

function commit($db=null) {
	global $DB; if ($db === null) $db = $DB;

	$db->CommitTrans();
	$db->BeginTrans();
}

function rollback($db=null) {
	global $DB; if ($db === null) $db = $DB;

	$db->RollbackTrans();
	$db->BeginTrans();
}


################################################################################


# record which is created from GET and POST data (POST has precedence)
function given($a, $b, $ins_defaults=false) {
	if (is_array($a)) {
		$prototype = $a;
		$a = $b;
	} else {
		$prototype = $b;
	}

	if (is_string($a)) {
		$words = explode('.',$a);
		if (count($words)) {
			$tablename = $words[0];
			for ($i = 1 ; $i < count($words) ; ++$i) {
				if (!array_key_exists($words[$i],$prototype)) {
					$prototype[] = $words[$i];
				}
			}
		} else {
			$tablename = null;
		}
	}

	$record = array();
	foreach ($prototype as $a => $b) {
		if (!is_string($a)) {
			$fieldname = $b;
			$x = given_field($fieldname,$tablename);
			if ($x!==null) {
				$record[$fieldname] = $x;
			}

		} else {
			$fieldname = $a;
			$fun = $b;
			$x = given_field($fieldname,$tablename);
			if (field_fun($fun,&$x,$fieldname,$tablename)) {
				$record[$fieldname] = $x;
			}
		}
	}
	return $record;
}

function given_field($field_name,$table_name) {
	$value = null;
	if ($table_name!==null) $value = post($table_name.'.'.$field_name);
	if ($value === null) $value = post($field_name);
	if ($value === null && $table_name!==null) $value = post($table_name.'_'.$field_name);  # FIXME ?
	return $value;
}

function field_fun($fun,&$x,$fieldname,$tablename) {
	switch (gettype($fun)) {
	case 'array':
		#
		# numbers and strings starting with digits are to be prefixed with an extra '0' in mapping
		#
		if ( is_int($x) || (is_string($x) && strval(intval($x[0]))==$x[0]) ) {
			$xx = '0'.strval($x);
		} else {
			$xx = strval($x);
		}

		if (array_key_exists($xx,$fun)) {
			$x = $fun[$xx];
			return true;
		} else {
			$i = 0;
			$z = null;
			while (array_key_exists($i,$fun)) {
				if (is_string($fun[$i]) && substr($fun[$i], strlen($fun[$i])-2, 2)==='()') {
					$newfun = substr($fun[$i], 0, strlen($fun[$i])-2);
					break;
				} else if (!field_fun($fun[$i],$x,$fieldname,$tablename)) {
					return false;
				}
				++$i;
			}
			if ($newfun===null) {
				return true;
			}

			# rest of items are function parameters
			$args = array();
			$j = $i+1;
			while (array_key_exists($j,$fun)) {
				if (is_array($fun[$j])) {
					if (!count($fun[$j])) {
						$args[] = $x;
					} else {
						$y = $x;
						field_fun($fun[$j],&$y,$fieldname,$tablename);
						$args[] = $y;
					}
				} else {
					$args[] = $fun[$j];
				}
				++$j;
			}

			$fun = $newfun;

			if (count($args)>1) {
				if ($fun===null) {
					$x = $args;
				} else {
					$x = call_user_func_array($fun, $args);
				}
				return true;
			} else if (count($args)==1) {
				$x = $args[0];
			}
		}
		break;
	case 'string':
		break;
	default:
		$fun = gettype($fun);
	}

	switch ($fun) {
	case null:  # identity
		return true;
	case 'nonull':
		return $x!==null;

	case 'bool': case 'boolean':
		if ($x===null) {  # XXX what else can be done here?
			$x = (given_field($fieldname.'___OFF__',$tablename)!==null)?'0':null;
		}
		if ($x!==null) {
			$x = trim($x);
			$x = ($x!=0) ? true : false;
			return true;
		}
		break;
	case 'int': case 'integer':
		if ($x!==null) {
			$x = trim($x);
			if ($x!=='') {
				$x = intval($x);
			} else {
				$x = null;
			}
			return true;
		}
		break;
	case 'double': case 'float': case 'real':
		if ($x!==null) {
			$x = trim($x);
			if ($x!=='') {  # XXX user's locale settings?
				$x = floatval(str_replace(',','.',$x));
			} else {
				$x = null;
			}
			return true;
		}
		break;
	case '': case 'str': case 'string':
		if ($x!==null) {
			$x = strval($x);
			return true;
		}
		break;
	case 'str1':
		if ($x!==null) {
			$x = strval($x);
			if ($x==='') $x = null;
			return true;
		}
		break;
	default:
		if ($x!==null) {
			$x = $fun($x);
			return true;
		}
	}
	$x = null;
	return false;
}

?>
