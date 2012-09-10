CREATE TABLE demographic_group (
	id SERIAL PRIMARY KEY,
	min_age float,
	max_age float,
	gender int,
	pregnancy int
);
COMMENT ON COLUMN demographic_group.gender IS '0:male;1:female';
COMMENT ON COLUMN demographic_group.pregnancy IS '0:no;1:pregnant;2:lactating';

CREATE TABLE users (
	id SERIAL PRIMARY KEY,
	username varchar(12) NOT NULL,
	password varchar(40) NOT NULL,
	email varchar(128) NOT NULL,
	registered timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	confirmation varchar(40),
	confirmed timestamp NULL,
	active int NOT NULL DEFAULT '0',
	timezone varchar(32),
	birth date,
	gender int,
	pregnancy int NOT NULL DEFAULT '0',
	demographic_group int REFERENCES demographic_group(id),
	UNIQUE (username,email,confirmation)
);
CREATE INDEX users_by_demographic_group ON users(demographic_group);
CREATE INDEX users_by_registered ON users(registered);
CREATE INDEX users_by_confirmed ON users(confirmed);
CREATE INDEX users_by_active ON users(active);
COMMENT ON COLUMN users.gender IS 'female?';
COMMENT ON COLUMN users.pregnancy IS '0:no;1:pregnant;2:lactating';

CREATE TABLE person (
	id SERIAL PRIMARY KEY,
	user_id int NOT NULL DEFAULT '35',
	name varchar(64) NOT NULL UNIQUE,
	address varchar(127),
	postcode varchar(6),
	postbox varchar(6),
	phone varchar(16),
	phone2 varchar(16),
	fax varchar(16),
	email varchar(127) UNIQUE,
	website varchar(127),
	afm varchar(9) UNIQUE,
	doy varchar(48),
	notes text 
);
CREATE INDEX person_by_phone ON person(phone);
CREATE INDEX person_by_fax ON person(fax);
CREATE INDEX person_by_website ON person(website);
CREATE INDEX person_by_postcode ON person(postcode);

CREATE TABLE product (
	id SERIAL PRIMARY KEY,
	user_id int,
	parent int REFERENCES product(id),
	maker int REFERENCES person(id),
	packager int REFERENCES person(id),
	importer int REFERENCES person(id),
	distributor int REFERENCES person(id),
	name varchar(64) NOT NULL UNIQUE,
	"type" int,
	barcode varchar(13) UNIQUE,
	typical_price decimal(6,2), -- COMMENT '€ (total price - not per unit)',
	price_to_parent int NOT NULL DEFAULT '0',
	price_no_children int NOT NULL DEFAULT '0',
	price_no_recalc int NOT NULL DEFAULT '0',
	typical_units int,
	units_avoid_filling int NOT NULL DEFAULT '0',
	units_near_kg int NOT NULL DEFAULT '0',
	ingredients text,
	store_temp_min float, -- celsius
	store_temp_max float, -- celsius
	store_duration float, -- days
	store_conditions int,
	packaging_weight int, -- g
	recyclable_packaging int,
	glaze_weight float, -- g
	net_weight float, -- g
	net_volume float, -- ml
	market_weight float,
	sample_weight float, -- g
	sample_volume float, -- ml
	refuse_weight float, -- g
	refuse_volume float, -- ml
	water float,
	energy float,
	proteins float,
	carbohydrates float,
	sugars float,
	fats float,
	fats_saturated float,
	fats_monounsaturated float,
	fats_polyunsaturated float,
	fats_polyunsaturated_n9 float,
	fats_polyunsaturated_n6 float,
	fats_polyunsaturated_n3 float,
	fats_trans float,
	total_fiber float,
	potassium float,
	sodium float,
	chloride float,
	ash float,
	calcium float,
	phosphorus float,
	iron float,
	fluoride float,
	a float,
	retinol float,
	alpha_carotene float,
	beta_carotene float,
	beta_cryptoxanthin float,
	lycopene float,
	lutein_zeaxanthin float,
	c float,
	d float,
	e float,
	b1 float,
	b2 float,
	b3 float,
	b5 float,
	b6 float,
	b7 float,
	b9 float,
	folic_acid float,
	folate float,
	b12 float,
	k float,
	choline float,
	cholesterol float,
	magnesium float,
	zinc float,
	manganese float,
	copper float,
	iodine float,
	selenium float,
	molybdenium float,
	chromium float,
	boron float,
	nickel float,
	silicon float,
	vanadium float,
	sulfate float
);
CREATE INDEX product_by_energy ON product(energy);
CREATE INDEX product_by_sugars ON product(sugars);
CREATE INDEX product_by_fats ON product(fats);
CREATE INDEX product_by_fats_saturated ON product(fats_saturated);
CREATE INDEX product_by_proteins ON product(proteins);
CREATE INDEX product_by_carbohydrates ON product(carbohydrates);
CREATE INDEX product_by_water ON product(water);
CREATE INDEX product_by_total_fiber ON product(total_fiber);
CREATE INDEX product_by_calcium ON product(calcium);
CREATE INDEX product_by_phosphorus ON product(phosphorus);
CREATE INDEX product_by_fats_polyunsaturated ON product(fats_polyunsaturated);
CREATE INDEX product_by_fats_monounsaturated ON product(fats_monounsaturated);
CREATE INDEX product_by_cholesterol ON product(cholesterol);
CREATE INDEX product_by_sodium ON product(sodium);
CREATE INDEX product_by_net_weight ON product(net_weight);
CREATE INDEX product_by_iron ON product(iron);
CREATE INDEX product_by_glaze_weight ON product(glaze_weight);
CREATE INDEX product_by_store_temp_max ON product(store_temp_max);
CREATE INDEX product_by_store_duration ON product(store_duration);
CREATE INDEX product_by_typical_price ON product(typical_price);
CREATE INDEX product_by_magnesium ON product(magnesium);
CREATE INDEX product_by_zinc ON product(zinc);
CREATE INDEX product_by_manganese ON product(manganese);
CREATE INDEX product_by_copper ON product(copper);
CREATE INDEX product_by_selenium ON product(selenium);
CREATE INDEX product_by_ash ON product(ash);
CREATE INDEX product_by_fats_trans ON product(fats_trans);
CREATE INDEX product_by_parent ON product(parent);
CREATE INDEX product_by_distributor ON product(distributor);
CREATE INDEX product_by_importer ON product(importer);
CREATE INDEX product_by_maker ON product(maker);
CREATE INDEX product_by_packager ON product(packager);

CREATE TABLE cart (
	id SERIAL PRIMARY KEY,
	user_id int NOT NULL REFERENCES users(id),
	name varchar(64) NOT NULL,
	days float NOT NULL DEFAULT '7',
	UNIQUE (user_id,name)
);

CREATE TABLE cart_item (
	id SERIAL PRIMARY KEY,
	cart int NOT NULL REFERENCES cart(id),
	product int NOT NULL REFERENCES product(id),
	quantity float,
	price float,
	unit int NOT NULL DEFAULT '0',
	multiplier float
);
COMMENT ON COLUMN cart_item.unit IS '0:units;1:grams;2:mililitres';

CREATE TABLE consumption (
	id SERIAL PRIMARY KEY,
	user_id int NOT NULL REFERENCES users(id),
	consumed timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	product int NOT NULL REFERENCES product(id),
	units int,
	weight float,
	volume float
);
COMMENT ON COLUMN consumption.weight is 'g';
COMMENT ON COLUMN consumption.volume is 'ml';
CREATE INDEX consumption_by_consumed_product ON consumption(consumed,product);
CREATE INDEX consumption_by_product ON consumption(product);
CREATE INDEX consumption_by_user_id ON consumption(user_id);

CREATE TABLE email_templates (
	id SERIAL PRIMARY KEY,
	name varchar(16) NOT NULL UNIQUE,
	description varchar(256),
	subject text NOT NULL,
	text text NOT NULL,
	html text,
	queue_minutes int,
	queue_tod time
);

CREATE TABLE email_queue (
	id SERIAL PRIMARY KEY,
	created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	template_id int REFERENCES email_templates(id),
	headers text NOT NULL,
	recipient varchar(64),
	subject text,
	text text NOT NULL,
	html text,
	to_send timestamp NULL,
	sent timestamp NULL,
	errormsg text,
	active int NOT NULL DEFAULT '1'
);
CREATE INDEX email_queue_by_created ON email_queue(created);
CREATE INDEX email_queue_by_recipient ON email_queue(recipient);
CREATE INDEX email_queue_by_sent_active ON email_queue(sent,active);
CREATE INDEX email_queue_by_template_id ON email_queue(template_id);

CREATE TABLE fd_descriptions (
	NDB_No int,
	FdGrp_Cd int,
	Long_Desc varchar(256),
	Shrt_Desc varchar(64),
	ComName varchar(128),
	ManufacName varchar(64),
	Survey varchar(1),
	Ref_desc varchar(256),
	Refuse int,
	SciName varchar(64),
	N_Factor double precision,
	Pro_Factor double precision,
	Fat_Factor double precision,
	X double precision
);

CREATE TABLE fd_groups (
	FdGrp_Cd int,
	FdGrp_Desc varchar(64)
);

CREATE TABLE fd_nutrients (
	NDB_No int,
	Shrt_Desv varchar(64),
	Water double precision,
	Energ_Kcal double precision,
	Protein double precision,
	Lipid_Tot double precision,
	Ash double precision,
	Carbohydrt double precision,
	Fiber_TD double precision,
	Sugar_Tot double precision,
	Calcium double precision,
	Iron double precision,
	Magnesium double precision,
	Phosphorus double precision,
	Potassium double precision,
	Sodium double precision,
	Zinc double precision,
	Copper double precision,
	Manganese double precision,
	Selenium double precision,
	Vit_C double precision,
	Thiamin double precision,
	Riboflavin double precision,
	Niacin double precision,
	Panto_acid double precision,
	Vit_B6 double precision,
	Folate_Tot double precision,
	Folic_acid double precision,
	Food_Folate double precision,
	Folate_DFE double precision,
	Vit_B12 double precision,
	Vit_A_IU double precision,
	Vit_A_RAE double precision,
	Retinol double precision,
	Vit_E double precision,
	Vit_K double precision,
	Alpha_Carot double precision,
	Beta_Carot double precision,
	Beta_Crypt double precision,
	Lycopene double precision,
	"Lut+Zea" double precision,
	FA_Sat double precision,
	FA_Mono double precision,
	FA_Poly double precision,
	Cholestrl double precision,
	GmWt_1 double precision,
	GmWt_Desc1 varchar(64),
	GmWt_2 double precision,
	GmWt_Desc2 varchar(64),
	Refuse_Pct double precision
);
CREATE INDEX fd_nutrients_Shrt_Desv_index ON fd_nutrients USING gin(to_tsvector('english', Shrt_Desv));

CREATE TABLE fd_weights (
	NDB_No int,
	Seq int,
	Amount double precision,
	Msre_Desc varchar(128),
	Gm_Wgt double precision,
	Num_Data_Pts int,
	Std_Dev double precision
);

CREATE TABLE log_database (
	id SERIAL PRIMARY KEY,
	time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	request_id int NOT NULL,
	user_id int,
	"table" varchar(32) NOT NULL,
	table_id int NOT NULL,
	changes text NOT NULL
);
CREATE INDEX log_database_by_user_id_time ON log_database(user_id,time);
CREATE INDEX log_database_by_table_table_id_time ON log_database("table",table_id,time);

CREATE TABLE log_hosts (
	id SERIAL PRIMARY KEY,
	host varchar(64) NOT NULL UNIQUE
);

CREATE TABLE log_urls (
	id SERIAL PRIMARY KEY,
	host_id int NOT NULL,
	uri varchar(255) NOT NULL
);
CREATE INDEX log_urls_by_host_id_uri ON log_urls(host_id,uri);

CREATE TABLE log_user_agents (
	id SERIAL PRIMARY KEY,
	name varchar(255) NOT NULL
);
CREATE INDEX log_user_agents_by_name ON log_user_agents(name);

CREATE TABLE log_sessions (
	id SERIAL PRIMARY KEY,
	phpid varchar(32) UNIQUE,
	data text NOT NULL,
	user_id int REFERENCES users(id),
	started timestamp NULL DEFAULT CURRENT_TIMESTAMP,
	ended timestamp NULL,
	expiry timestamp NULL
);
CREATE INDEX log_sessions_by_started ON log_sessions(started);
CREATE INDEX log_sessions_by_user_id ON log_sessions(user_id);
CREATE INDEX log_sessions_by_user_id_ended_expiry ON log_sessions(user_id,ended,expiry);

CREATE TABLE log_requests (
	id SERIAL PRIMARY KEY,
	"date" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	client_ip varchar(15) NOT NULL,
	method varchar(8) NOT NULL,
	host_id int NOT NULL,
	url_id int NOT NULL REFERENCES log_urls(id),
	referer_host_id int REFERENCES log_hosts(id),
	referer_url_id int REFERENCES log_urls(id),
	user_agent_id int REFERENCES log_user_agents(id),
	session_id int REFERENCES log_sessions(id),
	user_id int REFERENCES users(id)
);
CREATE INDEX log_requests_by_host_id ON log_requests(host_id);
CREATE INDEX log_requests_by_referer_host_id ON log_requests(referer_host_id);
CREATE INDEX log_requests_by_session_id ON log_requests(session_id);
CREATE INDEX log_requests_by_url_id ON log_requests(url_id);
CREATE INDEX log_requests_by_referer_url_id ON log_requests(referer_url_id);
CREATE INDEX log_requests_by_user_agent_id ON log_requests(user_agent_id);
CREATE INDEX log_requests_by_user_id ON log_requests(user_id);

CREATE TABLE nutrient (
	id SERIAL PRIMARY KEY,
	"order" int NOT NULL,
	"column" int NOT NULL DEFAULT '1',
	tag varchar(10),
	name varchar(24) NOT NULL,
	description varchar(32) NOT NULL,
	unit varchar(8) NOT NULL,
	decimals int NOT NULL DEFAULT '0',
	basetable int NOT NULL DEFAULT '1'
);
CREATE INDEX "nutrient_by_order" ON nutrient("order");

CREATE TABLE product_nutrient (
	id SERIAL PRIMARY KEY,
	product int NOT NULL REFERENCES product(id),
	nutrient int NOT NULL REFERENCES nutrient(id),
	value float,
	source int NOT NULL DEFAULT '0',
	id2 int,
	UNIQUE (product,nutrient)
);
CREATE INDEX product_nutrient_by_nutrient_source_id2 ON product_nutrient(nutrient,source,id2);
CREATE INDEX product_nutrient_by_nutrient ON product_nutrient(nutrient);
CREATE INDEX product_nutrient_by_product ON product_nutrient(product);
COMMENT ON COLUMN product_nutrient.source is '0:manual;1:product;2:children;3:fooddb';

CREATE TABLE store (
	id SERIAL PRIMARY KEY,
	owner int REFERENCES person(id),
	name varchar(127) NOT NULL,
	address varchar(127),
	postcode varchar(6),
	phone varchar(16),
	phone2 varchar(16),
	fax varchar(16),
	notes text
);
CREATE INDEX store_by_postcode ON store(postcode);
CREATE INDEX store_by_owner ON store(owner);

CREATE TABLE receipt (
	id SERIAL PRIMARY KEY,
	user_id int NOT NULL REFERENCES users(id),
	parent int,
	issued timestamp NULL,
	person int REFERENCES person(id),
	store int REFERENCES store(id),
	amount decimal(6,2),
	product int REFERENCES product(id),
	units int,
	length float,
	area float,
	weight float,
	net_weight float,
	net_volume float,
	notes text
);
CREATE INDEX receipt_by_date ON receipt(issued,store);
CREATE INDEX receipt_by_store ON receipt(store);
CREATE INDEX receipt_by_user_id ON receipt(user_id);
CREATE INDEX receipt_by_parent ON receipt(parent);
CREATE INDEX receipt_by_person ON receipt(person);
CREATE INDEX receipt_by_product ON receipt(product);
COMMENT ON COLUMN receipt.amount IS 'total price - not price per unit';
COMMENT ON COLUMN receipt.weight IS 'g (gross weight)';
COMMENT ON COLUMN receipt.net_weight IS 'g';
COMMENT ON COLUMN receipt.net_volume IS 'ml';

CREATE TABLE "right" (
	id SERIAL PRIMARY KEY,
	name varchar(20) NOT NULL UNIQUE,
	expression varchar(64) NOT NULL,
	description text
);

CREATE TABLE storage_conditions (
	id SERIAL PRIMARY KEY,
	description varchar(64) NOT NULL
);

CREATE TABLE threshold (
	id SERIAL PRIMARY KEY,
	demographic_group int REFERENCES demographic_group(id),
	"user" int REFERENCES users(id),
	nutrient int NOT NULL REFERENCES nutrient(id),
	min float,
	best float,
	max float,
	UNIQUE (demographic_group,"user",nutrient)
);
CREATE INDEX threshold_by_nutrient ON threshold(nutrient);
CREATE INDEX threshold_by_user ON threshold("user");
CREATE INDEX threshold_by_demographic_group ON threshold("demographic_group");

CREATE TABLE user_right (
	"user" int NOT NULL REFERENCES users(id),
	"right" int NOT NULL REFERENCES "right"(id),
	UNIQUE ("user","right")
);
CREATE INDEX user_right_by_right ON user_right("right");
CREATE INDEX user_right_by_user ON user_right("user");
