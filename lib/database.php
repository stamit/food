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
		throw new LoggedException('cannot represent value as SQL: '.repr($x));
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
		throw new LoggedException($db->ErrorMsg());

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
		throw new LoggedException("query gives more than one result: ".$sql);
	if (!count($results))
		return null;

	$row = $results[0];
	if (count($row) < 1)
		throw new LoggedException("query returned record with no fields: ".$sql);
	if (count($row) > 1)
		throw new LoggedException("query returned record with more than one field: ".$sql);
	foreach ($row as $a)
		return $a;

	return null;
}

# the only field of the first row
function value1($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$row = row1($sql, $db);
	if (count($row) < 1)
		throw new LoggedException("query returned record with no fields: ".$sql);
	if (count($row) > 1)
		throw new LoggedException("query returned record with more than one field: ".$sql);
	foreach ($row as $a)
		return $a;
	return null;
}

# the single field of the single resulting row
function value($sql,$db=null) {
	global $DB; if ($db === null) $db = $DB;

	$x = row($sql,$db);
	if (count($x) < 1)
		throw new LoggedException("query returned record with no fields: ".$sql);
	if (count($x) > 1)
		throw new LoggedException("query returned record with more than one field: ".$sql);
	foreach ($x as $a) $y = $a;
	return $y;
}

# the only row or NULL (expects at most one row)
function row0($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$results = select($sql, 2, 0, $db);
	if (count($results) > 1)
		throw new LoggedException("query gives more than one result: ".$sql);
	return (count($results)>0) ? $results[0] : null;
}

# the first row of possibly many rows
function row1($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$results = select($sql, 1, 0, $db);
	if (!count($results))
		throw new LoggedException("query gives no results: ".$sql);
	return $results[0];
}

# the single resulting row
function row($sql,$db=null) {
	global $DB; if ($db === null) $db = $DB;

	$results = select($sql, 2, 0, $db);
	if (!count($results))
		throw new LoggedException("query gives no results: ".$sql);
	if (count($results) > 1)
		throw new LoggedException("query gives more than one result: ".$sql);
	return $results[0];
}

# an array with the only value of each of the zero or more rows returned by query $sql
function col($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$xs = select($sql,null,null,$db);
	for ($i = 0 ; $i < count($xs) ; ++$i) {
		$row = $xs[$i];
		if (count($row) < 1)
			throw new LoggedException("query returned record with no fields: ".$sql);
		if (count($row) > 1)
			throw new LoggedException("query returned record with more than one field: ".$sql);
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
		throw new LoggedException($db->ErrorMsg());

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
	foreach ($row as $field=>$value) if ($field!=$id_field) {
		$assignments[] = sqlid($field).'='.sql($value,$db);
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

# works with the result of `given', after validation and possible corrections
# by you
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
			update($table.'.'.$key, $row, $db);
		} else {
			$id = -$row[$key];
			delete($table.'.'.$key, $id, $db);
		}
	} else {
		throw new Exception('not implemented');
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


#
# return a record which is created from user-supplied data, and is prepared for
# INSERTing, UPDATEing or DELETEing with store()
#
# - suppose postdata is "a=123" ( no 'key' field, meaning INSERT )
#     (array('a'=>1.23, 'b'=>'foo'),'key')
#         => array('a'=>123.0)
#
# - suppose postdata is "key=9999&a=123" ( positive 'key' field, meaning UPDATE )
#     (array('a'=>1.23, 'b'=>'foo'),'key')
#         => array('key'=>9999, 'a'=>123.0)
#
# - suppose postdata is "key=-9999&a=123" ( negative 'key' field, meaning DELETE )
#     (array('a'=>1.23, 'b'=>'foo'),'key')
#         => array('key'=>-9999)
#
function given($a, $b, $ins_defaults=false) {
	if (is_array($a)) {
		$prototype = $a;
		$a = $b;
	} else {
		$prototype = $b;
	}

	if (is_string($a)) {
		$i = strpos($a,'.');
		if ($i===false) {
			$table_name=$a;
			$id_field=null;
		} else {
			$table_name=substr($a,0,$i);
			$id_field=substr($a,$i+1);
		}
	} else {
		$table_name=null;
		$id_field=null;
	}

	$record = array();
	if ($id_field !== null) {
		$id = given_field($id_field,$table_name);
		if ($id > 0) {
			$record[$id_field] = $id;
			$do_put_defaults = false;
		} else if ($id == 0) {
			$do_put_defaults = $ins_defaults;
		} else {
			return array($id_field=>$id);
		}
	}
	foreach ($prototype as $field => $decl) {
		#
		# 'foo' => def,
		#     all values are converted to type of `def'
		#     unset if they cannot be converted
		#
		# 'foo' => array('str1'=>value1, 'str2'=>value2, ...),
		#     other values unset
		#     YOU WILL HAVE TO PREFIX ANY INTEGER VALUES WITH '0'
		#         (so they are suitable as array keys)
		#
		# 'foo' => array(def, 'str1'=>value1, 'str2'=>value2, ...),
		#     other values are converted to type of `def'
		#     unset if they cannot be converted
		#
		# 'foo' => array(def,other, 'str1'=>value1, 'str2'=>value2, ...),
		#     if `other' is true, other values become `def'
		#      otherwise converted to type of `def'
		#
		# 'foo' => array(def,other,unset, 'str1'=>value1, 'str2'=>value2, ...),
		#     specific values (mapped to type of `def')
		#     if `other' is true, other values become `def'
		#      otherwise converted to type of `def'
		#     if `unset' is true, unset values become `def'
		#      otherwise they remain unset
		#

		$x = given_field($field,$table_name);
		if (!is_array($decl)) {
			$type = $decl;
		} else {
			if ($x===null) {
				if ($decl[2]) {
					$x = $decl[0];
					$type = null;
				} else {
					continue;
				}

			} else {
				# XXX  I hate this in PHP!  XXX
				if (strval(intval($x)) === strval($x))
					$y = '0'.$x;
				else
					$y = $x;

				if (array_key_exists($y,$decl)) {
					$x = $decl[$y];
					$type = null;
	
				} else if ($decl[1]) {
					$x = $decl[0];
					$type = null;
	
				} else if (array_key_exists(0,$decl)) {
					$type = $decl[0];
	
				} else {
					continue;
				}
			}
		}

		switch (gettype($type)) {
		case 'boolean':
			if ($x===null) {
				$x = given_field($field.'___OFF__',$table_name);
			}
			if (trim($x)!=='')
				$record[$field] = intval($x) ? 1 : 0;
			break;
		case 'int': case 'integer':
			if (trim($x)!=='')
				$record[$field] = intval($x);
			break;
		case 'double': case 'float': case 'real':
			if (trim($x)!=='')
				$record[$field] = floatval(str_replace(',','.',$x));
			break;
		case 'string':
			$record[$field] = strval($x);
			break;
		case 'NULL':
			$record[$field] = $x;
			break;
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

?>
