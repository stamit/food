import sys
import MySQLdb
import MySQLdb.connections
import MySQLdb.cursors

def sqlid(s):
	return '`'+s.replace('`','``')+'`'

def get_tp_size(col):
	tp = str(col[1])
	p1 = tp.find('(')
	p2 = tp.find(')')
	if p1 and p2 and p1<p2:
		typename = tp[:p1].upper()
		size = tp[p1+1:p2].replace(',','.')
	else:
		typename = tp.upper()
		size = '-'
	size = ''.join(size.split()).split('.')
	return typename,size

def sql(x,db):
	if x==None:
		return 'NULL'
	elif type(x)==int:
		return str(x)
	elif type(x)==str:
		return "'"+db.escape_string(x)+"'"
	else:
		raise 'unimplemented'

def eatprefix(x,y):
	if not x.startswith(y):
		return None
	return x[len(y):]

if __name__=='__main__':
	if len(sys.argv)<2:
		sys.stderr.write('symtax: %s [//HOST/]DATABASE[/PREFIX]\n')
		sys.exit(1)

	host = 'localhost'
	charset = 'utf8'
	tableprefix = 'usda_'

	arg = sys.argv[1]
	if arg.startswith('//'):
		i = arg[2].find('/')
		if i>=0:
			host = arg[2:2+i]
			arg = arg[2+i+1]

	i = arg.find('/')
	if i>=0:
		tableprefix = arg[i+1:]
		arg = arg[:i]

	dbname = arg


	db = MySQLdb.connections.Connection(host=host, db=dbname, charset=charset)

	c = db.cursor()
	c.execute('SHOW TABLES')

	tables = []
	for row in c.fetchall():
		tables.append(str(row[0]))
	
	tables.sort()

	c.execute("SELECT table_name, column_name, referenced_table_name, referenced_column_name FROM information_schema.key_column_usage WHERE referenced_table_name IS NOT NULL AND table_schema="+sql(dbname,db))
	foreign_keys = c.fetchall()

	for table in tables:
		table = eatprefix(table,tableprefix)
		if not table:
			continue

		c.execute('SHOW FULL COLUMNS FROM '+sqlid(tableprefix+table))

		sys.stdout.write('\n'+table+'\n')

		cols = c.fetchall()

		maxlen = reduce(lambda a,b: max(a,b), map(lambda a:len(a[0]), cols))

		for col in cols:
			name = col[0].encode('utf8')

			typename,size = get_tp_size(col)
			if typename=='INT':
				size = '-'
			if typename=='TINYINT':
				typename = 'INT';
				size = '-';
			if typename=='DOUBLE':
				typename = 'FLOAT8'
				size = '-'

			if typename=='VARCHAR':
				typename = 'A'
			if typename=='DECIMAL':
				typename = 'N'
				if size[1]=='0':
					size = size[0]

			'_'.join(typename.split())

			if col[3]=='NO':
				nullable = 'N'
			else:
				nullable = 'Y'

			if col[4]=='PRI':
				pri = '*'
			else:
				pri = ''

			comment = col[8].encode('utf8')
			comment.replace('\n',' ')
			if comment:
				comment = '\t--'+comment

			refs = filter(lambda a: (a[0]==tableprefix+table and a[1]==col[0]), foreign_keys)
			if len(refs)>1 and not reduce(lambda a,b: (a==b) and a, refs):
				raise Exception('strange field references more than one foreign key: %s.%s: %s'%(table,name,repr(refs)))
			if refs:
				foreign_table = eatprefix(refs[0][2].encode('utf8'),tableprefix)
				if foreign_table<>None:
					ref = ' '+foreign_table+'.'+refs[0][3].encode('utf8')
				else:
					ref = ''
			else:
				ref = ''

			sys.stdout.write('%s%s %s %s%s %s%s%s\n'%(name, ' '*(maxlen-len(col[0])), typename, '.'.join(size),pri, nullable, ref, comment))

	c.close()

	db.close()
