<?
require_once dirname(__FILE__).'/util.php';
require_once dirname(__FILE__).'/adodb5/adodb.inc.php';
require_once dirname(__FILE__).'/adodb5/adodb-exceptions.inc.php';

# function query($sql, $limit=null, $offset=null, $db=null)
# function value0($sql, $db=null)
# function value1($sql, $db=null)
# function value($sql,$db=null)
# function row0($sql, $db=null)
# function row1($sql, $db=null)
# function row($sql,$db=null)
# function col($sql, $db=null)
# function get_tables($db=null)
# function get_fields($table, $db=null)

# function execute($sql, $db=null)
# function insert($table, $row, $db=null)
# function update($table, $id_field, $row, $db=null)
# function delete($table, $id_field, $row_or_id, $db=null)

# function commit($db=null)
# function rollback($db=null)

# function place_record(&$row, $id_field, $table, $db=null)
# function get($id,$table)
# function put($row, $table, $db=null)

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

# general SQL escaping function
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

# shorthand query function that returns rows as name=>value arrays 
# - foreach iterators are not supported here!
# - only read-only operations should be performed with this function
function query($sql, $limit=null, $offset=null, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$results = array();
	if ($limit!==null) {
		$set = $db->SelectLimit($sql,
			intval($limit), max(0,intval($offset))
		);
	} else {
		$set = $db->Execute($sql);
	}

	if ($set === FALSE)
		throw new LoggedException($db->ErrorMsg());

	while (!$set->EOF) {
		$results[] = $set->fields;
		$set->MoveNext();
	}

	return $results;
}

# returns the only field of the only row or NULL if there are no resulting rows
function value0($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$results = query($sql, 2, 0, $db);
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

# returns the only field of the first row
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

# returns the single field of the single resulting row
function value($sql,$db=null) {
	global $DB; if ($db === null) $db = $DB;

	if (strtoupper(substr($sql,0,7))!='SELECT ')
		$sql = 'SELECT '.$sql;

	$x = row($sql,$db);
	if (count($x) < 1)
		throw new LoggedException("query returned record with no fields: ".$sql);
	if (count($x) > 1)
		throw new LoggedException("query returned record with more than one field: ".$sql);
	foreach ($x as $a) $y = $a;
	return $y;
}

# returns the only row or NULL (expects at most one row)
function row0($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$results = query($sql, 2, 0, $db);
	if (count($results) > 1)
		throw new LoggedException("query gives more than one result: ".$sql);
	return (count($results)>0) ? $results[0] : null;
}

# returns the first row
function row1($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$results = query($sql, 1, 0, $db);
	if (!count($results))
		throw new LoggedException("query gives no results: ".$sql);
	return $results[0];
}

# returns the single resulting row
function row($sql,$db=null) {
	global $DB; if ($db === null) $db = $DB;

	$results = query($sql, 2, 0, $db);
	if (!count($results))
		throw new LoggedException("query gives no results: ".$sql);
	if (count($results) > 1)
		throw new LoggedException("query gives more than one result: ".$sql);
	return $results[0];
}

# returns array with the only value of each of the zero or more rows returned by query $sql
function col($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$xs = query($sql,null,null,$db);
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


function get_tables($db=null) {
	global $DB; if ($db === null) $db = $DB;

	$y = array();
	foreach (query('SHOW TABLES',null,null,$db) as $table) {
		$keys = array_keys($table);
		$y[] = array('name'=>$table[$keys[0]]);
	}
	return $y;
}

function get_fields($table, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$table = sqlid($table);
	$fields = array();
	foreach (query('SHOW COLUMNS FROM '.$table,null,null,$db) as $col) {
		$fields[] = array(
			'name'=>$col['Field'],
			'type'=>$col['Type']
		);
	}
	return $fields;
}


################################################################################


function execute($sql, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$set = $db->Execute($sql);
	if ($set === FALSE)
		throw new LoggedException($db->ErrorMsg());

	return $db->Affected_Rows();
}

# returns the ID field of the newly INSERTed row
# - $id_field is unquoted SQL identifier
# - $row is indexed by unquoted SQL identifiers
function insert($table, $row, $db=null) {
	global $DB; if ($db === null) $db = $DB;

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

# $row must contain a field named $id_field, and only the fields that are to be modified
# - $id_field is unquoted SQL identifier
# - $row is indexed by unquoted SQL identifiers
function update($table, $id_field, $row, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$assignments = array();
	foreach ($row as $field=>$value) if ($field!=$id_field) {
		$assignments[] = sqlid($field).'='.sql($value,$db);
	}

	if (!$assignments) return 0;

	$sql = 'UPDATE '.sqlid($table)
	       .' SET '.implode(',',$assignments)
	       .' WHERE '.sqlid($id_field).'='.sql($row[$id_field],$db);

	return execute($sql,$db);
}

# deletes a record by ID
# - $id_field is unquoted SQL identifier
# - if $row_or_id is an array, is is indexed by unquoted SQL identifiers
# - if $row_or_id is not an array, it is the row's ID
function delete($table, $id_field, $row_or_id, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	$key = is_array($row_or_id) ? $row_or_id[$id_field] : $row_or_id;
	$sql = 'DELETE FROM '.sqlid($table).' WHERE '.sqlid($id_field).'='.sql($key);

	return execute($sql,$db);
}


################################################################################


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
# INSERTing, UPDATEing or DELETEing with place_record()
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
function given_record($a, $b, $ins_defaults=false) {
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
	if ($value === null && $table_name!==null) $value = post($table_name.'_'.$field_name);  # XXX XXX XXX
	return $value;
}

# works with the result of `given_record', after validation and possible
# corrections by you
function place_record(&$row, $id_field, $table, $db=null) {
	global $DB; if ($db === null) $db = $DB;

	if ( ! $row[$id_field] ) {
		$id = insert($table, $row, $db);
	} else if ( $row[$id_field] > 0) {
		update($table, $id_field, $row, $db);
		$id = $row[$id_field];
	} else {
		$row[$id_field] = -$row[$id_field];
		delete($table, $id_field, $row[$id_field], $db);
		$id = $row[$id_field];
	}

	return $id;
}


# fetches a row by id
function get($id,$table,$db=null) {
	global $DB; if ($db === null) $db = $DB;

	return ($id !== null)
		? row0('SELECT * FROM '.sqlid($table).' WHERE id='.sql($id))
		: null
	;
}

# like place_record, but also logs record into "log_database"
# - $id_field is always 'id'
function put($row, $table, $db=null) {
	global $REQUEST_ID;

	$id = place_record($row,'id',$table,$db);

	insert('log_database',array(
		'request_id'=>$REQUEST_ID,
		'user_id'=>$_SESSION['user_id'],
		'table'=>$table,
		'table_id'=>abs($id),
		'changes'=>js($row),
	),$db);

	return $id;
}

?>
