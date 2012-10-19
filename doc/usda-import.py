#
# * reads schema.txt as standard input and UPPERCASE.txt files in working directory
# * produces SQL statements for importing database into MySQL or PostgreSQL
#
# with one argument it outputs only CREATE TABLE statements
#
# with two arguments it outputs ALTER TABLE statements for modifying the first
# schema to match the second
#
# any table reference cycles might not be handled properly
#
# cannot drop foreign keys
#
# XXX renaming a column will cause it to be dropped; KEEP NAMES UNCHANGED
#
# XXX changing primary keys is not implemented
#
import sys
import re

droptables = 1
writeschema = 1
writedata = 1
tableprefix = 'usda_'

# for MySQL
fieldquote = '`'
commentsyntax = 0
recordgroup = 16  # to avoid MySQL "packet too large" errors
mysql = 1
typemap = {
	'A':'VARCHAR',
	'VARCHAR':'VARCHAR',
	'TEXT':'TEXT',

	'BOOL':'TINYINT',
	'BOOLEAN':'TINYINT',
	'INT':'INT',
	'N':'DECIMAL',

	'FLOAT':'FLOAT',
	'FLOAT8':'DOUBLE',
	'DOUBLE':'DOUBLE',

	'DATE':'DATE',
	'TIME':'TIME',
	'TIMESTAMP':'TIMESTAMP',
}

# for PostreSQL
#fieldquote = '"'
#commentsyntax = 1
#recordgroup = 16
#typemap = {
#	'A':'VARCHAR',
#	'VARCHAR':'VARCHAR',
#	'TEXT':'TEXT',
#
#	'BOOL:'BOOLEAN',
#	'BOOLEAN':'BOOLEAN',
#	'INT':'INT',
#	'N':'DECIMAL',
#
#	'FLOAT':'FLOAT',
#	'FLOAT8':'FLOAT8',
#	'DOUBLE':'FLOAT8',
#
#	'DATE':'DATE',
#	'TIME':'TIME',
#	'TIMESTAMP':'TIMESTAMP WITH TIME ZONE',
#}

def readschema(f):
	lineno = 1
	tables = {}
	curtable = None

	try:
		while True:
			line = f.readline()
			if not line: break

			line = line.strip('\r\n')
			if not line: continue

			i = line.find('--')
			if i<>-1:
				line,comment = line[:i],line[i+2:]
			else:
				comment = None

			line = line.strip()
			if not line: continue

			words = line.split()

			if len(words)==1 or words[1]=='T':
				tablename = words[0]
				if not re.match('[A-Za-z_][A-Za-z0-9_]*$', tablename):
					raise Exception('can only handle alphanumeric characters and underscores in table names: %s'%line)

				curtable = tables[tablename] = []
			else:
				if len(words)<4 or len(words)>5:
					raise Exception('expected: {K|F} sqlfield {N|A} {len|digits.digits} {Y|N} [reftable.refkey]')

				if len(words[1])==2 and words[1][-1:]=='*':
					keyfield = True
					words[1] = words[1][:1]
				elif len(words[2])==2 and words[2][-1:]=='*':
					keyfield = True
					words[2] = words[2][:1]
				else:
					keyfield = False

				if typemap.get(words[1])==None:
					raise Exception("unrecognized type (second word): %s"%line)
				words[1] = typemap[words[1]]

				if words[2] == '-':
					words[2] = None
				else:
					words[2] = tuple(map(int,words[2].split('.')))
					while len(words[2]) and words[2][-1]==0:
						words[2] = words[2][:-1]
					if len(words[2])==1:
						words[2] = words[2][0]

				if words[3]=='Y':
					words[3] = True
				elif words[3]=='N':
					words[3] = False
				else:
					raise Exception("expected 'Y' or 'N' as fourth word: %s"%line)

				if len(words)==4:
					words.append(None)
				else:
					words[4] = words[4].split('.')
					if len(words[4])<>2:
						raise Exception("expected REFTABLE.REFKEY as fifth word")

				words.append(keyfield)

				words.append(comment)

				fname = words[0].split('.')
				if len(fname)==1:
					curtable.append(words)
				else:
					words[0] = fname[1]
					tables[fname[0]].append(words)

			lineno=lineno+1
	except Exception, x:
		sys.stderr.write('error in line %d: %s'%(lineno,repr(x)))
		raise

	return tables

def sqlfield(name):
	return fieldquote+name.replace(fieldquote,fieldquote+fieldquote)+fieldquote

def sqlstring(s):
	return "'"+s.replace("'","''").replace('\\','\\\\')+"'"

def iskey(words):
	return words[5]

def field_def(words,singlekeyfield=0):
	t = [sqlfield(words[0])]

	if words[1]=='VARCHAR':
		t.append('VARCHAR(%d)'%words[2])
	else:
		if type(words[2])==tuple:
			s = ','.join(map(str,words[2]))
		elif words[2]<>None:
			s = str(words[2])
		else:
			s = None

		if s<>None:
			t.append('%s(%s)'%(words[1],s))
		else:
			t.append(words[1])

	if not words[3]:
		t.append('NOT NULL')

	if singlekeyfield and words[5]:
		t.append('PRIMARY KEY')

	if commentsyntax==0 and words[6]<>None:
		t.append('COMMENT '+sqlstring(words[6]))

	return ' '.join(t)

def field_def_pkey(s):
	return field_def(s,1)

def typed_value(value,field):
	return sqlstring(value)

def print_table(tablename,fields):
	filename = tablename.upper()+'.txt'
	try:
		f = open(filename,'r')
	except IOError, x:
		return

	lineno = 0
	outlineno = 0
	while True:
		lineno = lineno+1
		line = f.readline()
		if not line: break
		line = line.rstrip('\r\n')

		state = 0
		value = []
		values = []
		for i in range(len(line)):
			c = line[i]
			if state==0:
				if c=='^':
					values.append(''.join(value))
					value = []
				elif c=='~':
					state = 1
				else:
					value.append(c)
			elif state==1:
				if c=='~':
					state = 0
				else:
					value.append(c)

		if state==0:
			values.append(''.join(value))
			value = []
		elif state==1:
			sys.stderr.write('%s:%d: end of line after single ~ quote (assuming end of line closes quote): %s\n'%(filename,lineno, repr(line)))
			values.append(''.join(value))
			value = []

		if len(fields)<>len(values):
			sys.stderr.write('%s:%d: incorrect number of data values (expected %d, got %d; ignoring line): %s\n'%(filename,lineno, len(fields), len(values), repr(line)))
			continue

		if outlineno==0 or (recordgroup>0 and (outlineno%recordgroup)==0):
			if outlineno>0:
				sys.stdout.write(';\n')
			sys.stdout.write('INSERT INTO %s (%s) VALUES\n'%(sqlfield(tableprefix+tablename), ','.join(map(lambda a: sqlfield(a[0]),fields))))
		else:
			sys.stdout.write(',\n')

		sys.stdout.write('\t(%s)'%','.join(map(lambda i: typed_value(values[i],fields[i]), range(len(fields)))))

		outlineno = outlineno + 1

	if outlineno>0:
		sys.stdout.write(';\n')

	f.close()
	sys.stdout.write('\n');

def print_drops(tablename,prevtables,tables,dropped):
	if dropped.get(tablename):
		return
	dropped[tablename] = 1

	hasref = False
	for tablename2,fields2 in tables.items():
		for f2 in fields2:
			if f2[4] and f2[4][0]==tablename:
				print_drops(tablename2,prevtables,tables,dropped)

	if prevtables==None:
		sys.stdout.write('DROP TABLE IF EXISTS %s;\n'%sqlfield(tableprefix+tablename))
		sys.stdout.write('\n')

def print_schema(tablename,prevtables,tables,written):
	if written.get(tablename)==1:
		return
	written[tablename] = 1

	#
	# dump prerequisites
	#
	fields = tables[tablename]
	for f in fields:
		if f[4]:
			print_schema(f[4][0],prevtables,tables,written)

	#
	# dump table
	#
	keyfields = filter(iskey,fields)

	if prevtables==None or prevtables.get(tablename)==None:
		if len(keyfields)>1:
			commalist = map(field_def,fields)
			commalist.append('PRIMARY KEY (%s)'%','.join(map(lambda a:sqlfield(a[0]),keyfields)))
		else:
			commalist = map(field_def_pkey,fields)

		for f in fields:
			if f[4]:
				commalist.append('FOREIGN KEY (%s) REFERENCES %s(%s)'%(f[0],tableprefix+f[4][0],f[4][1]))

		if mysql:
			extra = ' ENGINE = InnoDB';

		if commalist:
			sys.stdout.write('CREATE TABLE %s (\n\t%s\n)%s;\n'%(sqlfield(tableprefix+tablename),',\n\t'.join(commalist),extra))
			if commentsyntax==1:
				for field in fields:
					if field[5]<>None:
						sys.stdout.write('COMMENT ON %s.%s IS %s;\n'%(sqlfield(tablename),sqlfield(field[1]),sqlstring(field[5])));
			sys.stdout.write('\n');
	else:
		prevfields = prevtables[tablename]
		extra = None
		commalist = []
		fa = None
		for f in fields:
			f2 = f[:4]+[None]+f[5:]
			x = zip(prevfields,range(len(prevfields)))
			pfs = filter(lambda pf: pf[0][0]==f[0], x)
			if pfs:
				if pfs[0][1]:
					pfa = prevfields[pfs[0][1]-1][0]
				else:
					pfa = None
				pf = pfs[0][0]
				pf2 = pf[:4]+[None]+pf[5:]

				if (f2,fa)<>(pf2,pfa):
					s = 'MODIFY COLUMN '+field_def(f)
					if fa==None:
						s = s + ' FIRST'
					else:
						s = s + ' AFTER ' + sqlfield(fa)
					commalist.append(s)

				if pf[4]==None and f[4]<>None:
					commalist.append('ADD FOREIGN KEY (%s) REFERENCES %s(%s)'%(f[0],tableprefix+f[4][0],f[4][1]))
					if mysql:
						extra = 'ENGINE = InnoDB'
			else:
				s = 'ADD COLUMN '+field_def(f)
				if fa==None:
					s = s + ' FIRST'
				else:
					s = s + ' AFTER ' + sqlfield(fa)
				commalist.append(s)
			fa = f[0]
		for pf in prevfields:
			fs = filter(lambda f: pf[0]==f[0], fields)
			if not fs:
				commalist.append('DROP COLUMN '+sqlfield(pf[0]))

		if extra<>None:
			commalist.append(extra)

		if commalist:
			sys.stdout.write('ALTER TABLE %s \n\t%s\n;\n'%(sqlfield(tableprefix+tablename),',\n\t'.join(commalist)))
			sys.stdout.write('\n');

def print_data(tablename,tables,written):
	if written.get(tablename)==1:
		return
	written[tablename] = 1

	#
	# dump prerequisites
	#
	for f in tables[tablename]:
		if f[4]:
			print_data(f[4][0],tables,written)

	#
	# dump table
	#
	print_table(tablename,tables[tablename])

if __name__ == '__main__':
	if len(sys.argv)<2:
		prevtables = None
		tables = readschema(sys.stdin)

	elif len(sys.argv)==2:
		prevtables = None
		f = open(sys.argv[1],'r')
		tables = readschema(f)
		f.close()

	elif len(sys.argv)==3:
		if sys.argv[1]=='-':
			prevtables = readschema(sys.stdin)
		else:
			f = open(sys.argv[1],'r')
			prevtables = readschema(f)
			f.close()
		f = open(sys.argv[2],'r')
		tables = readschema(f)
		f.close()

	else:
		sys.stderr.write('syntax: %s [[PREVSCHEMA|-] SCHEMA]\n'%sys.argv[0])
		sys.exit(1)

	if droptables:
		dropped={}
		for tablename in tables:
			print_drops(tablename,prevtables,tables,dropped)

	if writeschema:
		written = {}
		for tablename,fields in tables.items():
			print_schema(tablename,prevtables,tables,written)

	if writedata:
		written = {}
		for tablename,fields in tables.items():
			print_data(tablename,tables,written)
