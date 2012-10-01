#
# * reads schema.txt as standard input and UPPERCASE.txt files in working directory
# * produces SQL statements for importing database into MySQL or PostgreSQL
#
# any table reference cycles might not be handled properly
#
import sys
import re

# for MySQL
droptables = 1
writeschema = 1
fieldquote = '`'
commentsyntax = 0
writedata = 1
recordgroup = 16  # to avoid MySQL "packet too large" errors
tableprefix = ''

# for PostreSQL
#droptables = 1
#writeschema = 1
#fieldquote = '"'
#commentsyntax = 1
#writedata = 1
#recordgroup = 16
#tableprefix = ''

if len(sys.argv)>=2:
	tableprefix = sys.argv[1]

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

				if words[1]<>'A' and words[1]<>'N':
					raise Exception("expected 'A' or 'N' as second word: %s"%line)

				if words[2].find('.')==-1:
					words[2] = int(words[2])
				else:
					words[2] = tuple(map(int,words[2].split('.')))

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

	if words[1]=='A':
		t.append('VARCHAR(%d)'%words[2])
	else:
		if type(words[2])==tuple:
			s = '%d,%d'%(words[2][0],words[2][1])
		else:
			s = '%d'%words[2]
		t.append('DECIMAL(%s)'%s)

	if words[3]=='N':
		t.append('NOT NULL')

	if singlekeyfield and words[5]:
		t.append('PRIMARY KEY')

	if commentsyntax==0 and words[6]<>None:
		t.append('COMMENT '+sqlstring(words[6]))

	if words[4]:
		t.append('REFERENCES %s.%s'%(sqlfield(tableprefix+words[4][0]),sqlfield(words[4][1])))

	return ' '.join(t)

def field_def_pkey(s):
	return field_def(s,1)

def typed_value(value,field):
	return sqlstring(value)

def print_inserts(tablename,fields):
	filename = tablename.upper()+'.txt'
	f = open(filename,'r')

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

def print_creates_inserts(tablename,tables,written):
	fields = tables[tablename]

	if written.get(tablename)==1:
		return

	written[tablename] = 1

	#
	# dump prerequisites
	#
	for f in fields:
		if f[4]:
			print_creates_inserts(f[4][0],tables,written)

	#
	# dump table
	#
	if writeschema:
		keyfields = filter(iskey,fields)

		if len(keyfields)>1:
			commalist = map(field_def,fields)
			commalist.append('PRIMARY KEY (%s)'%','.join(map(lambda a:sqlfield(a[0]),keyfields)))
		else:
			commalist = map(field_def_pkey,fields)

		sys.stdout.write('CREATE TABLE %s (\n\t%s\n);\n'%(sqlfield(tableprefix+tablename),',\n\t'.join(commalist)))

		if commentsyntax==1:
			for field in fields:
				if field[5]<>None:
					sys.stdout.write('COMMENT ON %s.%s IS %s;\n'%(sqlfield(tablename),sqlfield(field[1]),sqlstring(field[5])));

	if writedata:
		print_inserts(tablename,fields)

	sys.stdout.write('\n');

def print_drops(tablename,tables,dropped):
	if dropped.get(tablename):
		return

	dropped[tablename] = 1

	hasref = False
	for tablename2,fields2 in tables.items():
		for f2 in fields2:
			if f2[4] and f2[4][0]==tablename:
				print_drops(tablename2,tables,dropped)

	sys.stdout.write('DROP TABLE IF EXISTS %s;\n'%sqlfield(tableprefix+tablename))

if __name__ == '__main__':
	written = {}
	tables = readschema(sys.stdin)

	if droptables:
		dropped={}
		for tablename in tables:
			print_drops(tablename,tables,dropped)
		sys.stdout.write('\n')

	if writeschema or writedata:
		for tablename,fields in tables.items():
			print_creates_inserts(tablename,tables,written)
