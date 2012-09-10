/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `days` float NOT NULL DEFAULT '7',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`name`),
  KEY `fk_cart_users1` (`user_id`),
  CONSTRAINT `fk_cart_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `cart_item`
--

DROP TABLE IF EXISTS `cart_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cart` int(11) NOT NULL COMMENT 'cart.id',
  `product` int(11) NOT NULL,
  `quantity` float DEFAULT NULL,
  `price` float DEFAULT NULL,
  `unit` int(11) NOT NULL DEFAULT '0' COMMENT '0:units;1:grams;2:mililitres',
  `multiplier` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_cart_item_cart1` (`cart`),
  KEY `fk_cart_item_product1` (`product`),
  CONSTRAINT `fk_cart_item_cart1` FOREIGN KEY (`cart`) REFERENCES `cart` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_cart_item_product1` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=351 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `consumption`
--

DROP TABLE IF EXISTS `consumption`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consumption` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `consumed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `product` int(11) NOT NULL,
  `units` int(11) DEFAULT NULL,
  `weight` float DEFAULT NULL COMMENT 'g',
  `volume` float DEFAULT NULL COMMENT 'ml',
  PRIMARY KEY (`id`),
  KEY `timestamp` (`consumed`,`product`),
  KEY `fk_consumption_product1` (`product`),
  KEY `fk_consumption_users1` (`user_id`),
  CONSTRAINT `fk_consumption_product1` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_consumption_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1601 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `demographic_group`
--

DROP TABLE IF EXISTS `demographic_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `demographic_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `min_age` float DEFAULT NULL,
  `max_age` float DEFAULT NULL,
  `gender` int(11) DEFAULT NULL COMMENT '0:male;1:female',
  `pregnancy` int(11) DEFAULT NULL COMMENT '0:no;1:pregnant;2:lactating',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `email_queue`
--

DROP TABLE IF EXISTS `email_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `template_id` int(11) DEFAULT NULL,
  `headers` text NOT NULL,
  `recipient` varchar(64) DEFAULT NULL,
  `subject` text,
  `text` text NOT NULL,
  `html` text,
  `to_send` timestamp NULL DEFAULT NULL,
  `sent` timestamp NULL DEFAULT NULL,
  `errormsg` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `created` (`created`),
  KEY `recipient` (`recipient`),
  KEY `sent` (`sent`,`active`),
  KEY `fk_email_queue_email_templates1` (`template_id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `fk_email_queue_email_templates1` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(16) NOT NULL,
  `description` varchar(256) DEFAULT NULL,
  `subject` text NOT NULL,
  `text` text NOT NULL,
  `html` text,
  `queue_minutes` tinyint(1) DEFAULT NULL,
  `queue_tod` time DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `fd_descriptions`
--

DROP TABLE IF EXISTS `fd_descriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fd_descriptions` (
  `NDB_No` int(11) DEFAULT NULL,
  `FdGrp_Cd` int(11) DEFAULT NULL,
  `Long_Desc` varchar(256) DEFAULT NULL,
  `Shrt_Desc` varchar(64) DEFAULT NULL,
  `ComName` varchar(128) DEFAULT NULL,
  `ManufacName` varchar(64) DEFAULT NULL,
  `Survey` varchar(1) DEFAULT NULL,
  `Ref_desc` varchar(256) DEFAULT NULL,
  `Refuse` int(11) DEFAULT NULL,
  `SciName` varchar(64) DEFAULT NULL,
  `N_Factor` double DEFAULT NULL,
  `Pro_Factor` double DEFAULT NULL,
  `Fat_Factor` double DEFAULT NULL,
  `X` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `fd_groups`
--

DROP TABLE IF EXISTS `fd_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fd_groups` (
  `FdGrp_Cd` int(11) DEFAULT NULL,
  `FdGrp_Desc` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `fd_nutrients`
--

DROP TABLE IF EXISTS `fd_nutrients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fd_nutrients` (
  `NDB_No` int(11) DEFAULT NULL,
  `Shrt_Desv` varchar(64) DEFAULT NULL,
  `Water` double DEFAULT NULL COMMENT 'Water (g)',
  `Energ_Kcal` double DEFAULT NULL COMMENT 'Energy (kcal)',
  `Protein` double DEFAULT NULL COMMENT 'Protein (g)',
  `Lipid_Tot` double DEFAULT NULL COMMENT 'Total fat (g)',
  `Ash` double DEFAULT NULL COMMENT 'Ash (g)',
  `Carbohydrt` double DEFAULT NULL COMMENT 'Carbohydrate (g)',
  `Fiber_TD` double DEFAULT NULL COMMENT 'Total Dietary Fiber (g)',
  `Sugar_Tot` double DEFAULT NULL COMMENT 'Sugar (g)',
  `Calcium` double DEFAULT NULL COMMENT 'Calcium (mg)',
  `Iron` double DEFAULT NULL COMMENT 'Iron (mg)',
  `Magnesium` double DEFAULT NULL COMMENT 'Magnesium (mg)',
  `Phosphorus` double DEFAULT NULL COMMENT 'Phosphorus (mg)',
  `Potassium` double DEFAULT NULL COMMENT 'Potassium (mg)',
  `Sodium` double DEFAULT NULL COMMENT 'Sodium (mg)',
  `Zinc` double DEFAULT NULL COMMENT 'Zinc (mg)',
  `Copper` double DEFAULT NULL COMMENT 'Copper (mg)',
  `Manganese` double DEFAULT NULL COMMENT 'Manganese (mg)',
  `Selenium` double DEFAULT NULL COMMENT 'Selenium (ug)',
  `Vit_C` double DEFAULT NULL COMMENT 'Vitamin C (mg)',
  `Thiamin` double DEFAULT NULL COMMENT 'Thiamin (mg)',
  `Riboflavin` double DEFAULT NULL COMMENT 'Riboflavin (mg)',
  `Niacin` double DEFAULT NULL COMMENT 'Niacin (mg)',
  `Panto_acid` double DEFAULT NULL COMMENT 'Pantothenic acid (mg)',
  `Vit_B6` double DEFAULT NULL COMMENT 'Vitamin B6 (mg)',
  `Folate_Tot` double DEFAULT NULL COMMENT 'Folate (ug)',
  `Folic_acid` double DEFAULT NULL COMMENT 'Folic acid (ug)',
  `Food_Folate` double DEFAULT NULL COMMENT 'Food Folate (ug)',
  `Folate_DFE` double DEFAULT NULL COMMENT 'Folate (dietary folate equivalents) (ug)',
  `Vit_B12` double DEFAULT NULL COMMENT 'Vitamin B12 (ug)',
  `Vit_A_IU` double DEFAULT NULL COMMENT 'Vitamin A (ui)',
  `Vit_A_RAE` double DEFAULT NULL COMMENT 'Vitamin A (retinol activity equivalent) (ug)',
  `Retinol` double DEFAULT NULL COMMENT 'Retinol (ug)',
  `Vit_E` double DEFAULT NULL COMMENT 'Vitamin E (alpha-tocopherol) (ug)',
  `Vit_K` double DEFAULT NULL COMMENT 'Vitamin K (phylloquinone) (ug)',
  `Alpha_Carot` double DEFAULT NULL COMMENT 'Alpha-carotene (ug)',
  `Beta_Carot` double DEFAULT NULL COMMENT 'Beta-carotene (ug)',
  `Beta_Crypt` double DEFAULT NULL COMMENT 'beta-cryptoxanthin (ug)',
  `Lycopene` double DEFAULT NULL COMMENT 'Lycophene (ug)',
  `Lut+Zea` double DEFAULT NULL COMMENT 'Lutein+zeazanthin (ug)',
  `FA_Sat` double DEFAULT NULL COMMENT '*Saturated fatty acid (g)',
  `FA_Mono` double DEFAULT NULL COMMENT '*Monosaturated fatty acids (g)',
  `FA_Poly` double DEFAULT NULL COMMENT '*Polyunsaturated fatty acids (g)',
  `Cholestrl` double DEFAULT NULL COMMENT '*Cholesterol (mg)',
  `GmWt_1` double DEFAULT NULL,
  `GmWt_Desc1` varchar(64) DEFAULT NULL,
  `GmWt_2` double DEFAULT NULL,
  `GmWt_Desc2` varchar(64) DEFAULT NULL,
  `Refuse_Pct` double DEFAULT NULL COMMENT 'Refuse (%)',
  FULLTEXT KEY `Shrt_Desv` (`Shrt_Desv`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `fd_weights`
--

DROP TABLE IF EXISTS `fd_weights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fd_weights` (
  `NDB_No` int(11) DEFAULT NULL,
  `Seq` int(11) DEFAULT NULL,
  `Amount` double DEFAULT NULL,
  `Msre_Desc` varchar(128) DEFAULT NULL,
  `Gm_Wgt` double DEFAULT NULL,
  `Num_Data_Pts` int(11) DEFAULT NULL,
  `Std_Dev` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `log_database`
--

DROP TABLE IF EXISTS `log_database`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_database` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `request_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `table` varchar(32) NOT NULL,
  `table_id` int(11) NOT NULL,
  `changes` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `table` (`table`,`table_id`,`time`),
  KEY `user_id` (`user_id`,`time`)
) ENGINE=InnoDB AUTO_INCREMENT=5796 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `log_hosts`
--

DROP TABLE IF EXISTS `log_hosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_hosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `host` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `host` (`host`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `log_requests`
--

DROP TABLE IF EXISTS `log_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `client_ip` varchar(15) NOT NULL,
  `method` varchar(8) NOT NULL,
  `host_id` int(11) NOT NULL,
  `url_id` int(11) NOT NULL,
  `referer_host_id` int(11) DEFAULT NULL,
  `referer_url_id` int(11) DEFAULT NULL,
  `user_agent_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_log_requests_log_hosts1` (`host_id`),
  KEY `fk_log_requests_log_hosts2` (`referer_host_id`),
  KEY `fk_log_requests_log_sessions1` (`session_id`),
  KEY `fk_log_requests_log_urls1` (`url_id`),
  KEY `fk_log_requests_log_urls2` (`referer_url_id`),
  KEY `fk_log_requests_log_user_agents1` (`user_agent_id`),
  KEY `fk_log_requests_users1` (`user_id`),
  CONSTRAINT `fk_log_requests_log_hosts1` FOREIGN KEY (`host_id`) REFERENCES `log_hosts` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_log_requests_log_hosts2` FOREIGN KEY (`referer_host_id`) REFERENCES `log_hosts` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_log_requests_log_sessions1` FOREIGN KEY (`session_id`) REFERENCES `log_sessions` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_log_requests_log_urls1` FOREIGN KEY (`url_id`) REFERENCES `log_urls` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_log_requests_log_urls2` FOREIGN KEY (`referer_url_id`) REFERENCES `log_urls` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_log_requests_log_user_agents1` FOREIGN KEY (`user_agent_id`) REFERENCES `log_user_agents` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_log_requests_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=18345 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `log_sessions`
--

DROP TABLE IF EXISTS `log_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phpid` varchar(32) DEFAULT NULL,
  `data` text NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `started` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ended` timestamp NULL DEFAULT NULL,
  `expiry` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phpid` (`phpid`),
  KEY `started` (`started`),
  KEY `user_id` (`user_id`,`ended`,`expiry`),
  KEY `fk_log_sessions_users1` (`user_id`),
  CONSTRAINT `fk_log_sessions_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=407 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `log_urls`
--

DROP TABLE IF EXISTS `log_urls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_urls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `host_id` int(11) NOT NULL,
  `uri` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `host_id` (`host_id`,`uri`)
) ENGINE=InnoDB AUTO_INCREMENT=5730 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `log_user_agents`
--

DROP TABLE IF EXISTS `log_user_agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_user_agents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `nutrient`
--

DROP TABLE IF EXISTS `nutrient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nutrient` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(11) NOT NULL,
  `column` int(11) NOT NULL DEFAULT '1',
  `tag` varchar(10) DEFAULT NULL,
  `name` varchar(24) NOT NULL,
  `description` varchar(32) NOT NULL,
  `unit` varchar(8) NOT NULL,
  `decimals` int(11) NOT NULL DEFAULT '0',
  `basetable` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `order` (`order`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `person`
--

DROP TABLE IF EXISTS `person`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `person` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '35',
  `name` varchar(64) NOT NULL,
  `address` varchar(127) DEFAULT NULL,
  `postcode` varchar(6) DEFAULT NULL,
  `postbox` varchar(6) DEFAULT NULL,
  `phone` varchar(16) DEFAULT NULL,
  `phone2` varchar(16) DEFAULT NULL,
  `fax` varchar(16) DEFAULT NULL,
  `email` varchar(127) DEFAULT NULL,
  `website` varchar(127) DEFAULT NULL,
  `afm` varchar(9) DEFAULT NULL,
  `doy` varchar(48) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `afm` (`afm`),
  UNIQUE KEY `email` (`email`),
  KEY `phone` (`phone`),
  KEY `fax` (`fax`),
  KEY `website` (`website`),
  KEY `postcode` (`postcode`)
) ENGINE=InnoDB AUTO_INCREMENT=327 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `parent` int(11) DEFAULT NULL COMMENT 'product',
  `maker` int(11) DEFAULT NULL COMMENT 'person',
  `packager` int(11) DEFAULT NULL COMMENT 'person',
  `importer` int(11) DEFAULT NULL COMMENT 'person',
  `distributor` int(11) DEFAULT NULL COMMENT 'person',
  `name` varchar(64) NOT NULL,
  `type` int(4) DEFAULT NULL,
  `barcode` varchar(13) DEFAULT NULL,
  `typical_price` decimal(6,2) DEFAULT NULL COMMENT '€ (total price - not per unit)',
  `price_to_parent` tinyint(1) NOT NULL DEFAULT '0',
  `price_no_children` tinyint(1) NOT NULL DEFAULT '0',
  `price_no_recalc` tinyint(1) NOT NULL DEFAULT '0',
  `typical_units` int(11) DEFAULT NULL,
  `units_avoid_filling` tinyint(1) NOT NULL DEFAULT '0',
  `units_near_kg` tinyint(1) NOT NULL DEFAULT '0',
  `ingredients` text,
  `store_temp_min` float DEFAULT NULL COMMENT '°C',
  `store_temp_max` float DEFAULT NULL COMMENT '°C',
  `store_duration` float DEFAULT NULL COMMENT 'days',
  `store_conditions` int(11) DEFAULT NULL,
  `packaging_weight` int(11) DEFAULT NULL COMMENT 'g',
  `recyclable_packaging` tinyint(1) DEFAULT NULL,
  `glaze_weight` float DEFAULT NULL COMMENT 'g',
  `net_weight` float DEFAULT NULL COMMENT 'g',
  `net_volume` float DEFAULT NULL COMMENT 'ml',
  `market_weight` float DEFAULT NULL,
  `sample_weight` float DEFAULT NULL COMMENT 'g',
  `sample_volume` float DEFAULT NULL COMMENT 'ml',
  `refuse_weight` float DEFAULT NULL COMMENT 'g',
  `refuse_volume` float DEFAULT NULL COMMENT 'ml',
  `water` float DEFAULT NULL COMMENT 'g',
  `energy` float DEFAULT NULL COMMENT 'kcal',
  `proteins` float DEFAULT NULL COMMENT 'g',
  `carbohydrates` float DEFAULT NULL COMMENT 'g',
  `sugars` float DEFAULT NULL COMMENT 'g',
  `fats` float DEFAULT NULL COMMENT 'g',
  `fats_saturated` float DEFAULT NULL COMMENT 'g',
  `fats_monounsaturated` float DEFAULT NULL COMMENT 'g',
  `fats_polyunsaturated` float DEFAULT NULL COMMENT 'g',
  `fats_polyunsaturated_n9` float DEFAULT NULL COMMENT 'g',
  `fats_polyunsaturated_n6` float DEFAULT NULL COMMENT 'g',
  `fats_polyunsaturated_n3` float DEFAULT NULL COMMENT 'g',
  `fats_trans` float DEFAULT NULL COMMENT 'g',
  `total_fiber` float DEFAULT NULL COMMENT 'g',
  `potassium` float DEFAULT NULL COMMENT 'mg',
  `sodium` float DEFAULT NULL COMMENT 'mg',
  `chloride` float DEFAULT NULL COMMENT 'mg',
  `ash` float DEFAULT NULL COMMENT 'g',
  `calcium` float DEFAULT NULL COMMENT 'mg',
  `phosphorus` float DEFAULT NULL COMMENT 'mg',
  `iron` float DEFAULT NULL COMMENT 'mg',
  `fluoride` float DEFAULT NULL COMMENT 'μg',
  `a` float DEFAULT NULL COMMENT 'μg RAE',
  `retinol` float DEFAULT NULL COMMENT 'μg',
  `alpha_carotene` float DEFAULT NULL COMMENT 'μg',
  `beta_carotene` float DEFAULT NULL COMMENT 'μg',
  `beta_cryptoxanthin` float DEFAULT NULL COMMENT 'μg',
  `lycopene` float DEFAULT NULL COMMENT 'μg',
  `lutein_zeaxanthin` float DEFAULT NULL COMMENT 'μg',
  `c` float DEFAULT NULL COMMENT 'mg',
  `d` float DEFAULT NULL COMMENT 'μg',
  `e` float DEFAULT NULL COMMENT 'mg',
  `b1` float DEFAULT NULL COMMENT 'mg',
  `b2` float DEFAULT NULL COMMENT 'mg',
  `b3` float DEFAULT NULL COMMENT 'mg',
  `b5` float DEFAULT NULL COMMENT 'mg (pantothenic acid)',
  `b6` float DEFAULT NULL COMMENT 'mg',
  `b7` float DEFAULT NULL COMMENT 'μg (biotin)',
  `b9` float DEFAULT NULL COMMENT 'μg DFE (folate)',
  `folic_acid` float DEFAULT NULL COMMENT 'μg',
  `folate` float DEFAULT NULL COMMENT 'μg',
  `b12` float DEFAULT NULL COMMENT 'μg',
  `k` float DEFAULT NULL,
  `choline` float DEFAULT NULL COMMENT 'mg',
  `cholesterol` float DEFAULT NULL COMMENT 'mg',
  `magnesium` float DEFAULT NULL COMMENT 'mg',
  `zinc` float DEFAULT NULL COMMENT 'mg',
  `manganese` float DEFAULT NULL COMMENT 'μg',
  `copper` float DEFAULT NULL COMMENT 'mg',
  `iodine` float DEFAULT NULL COMMENT 'μg',
  `selenium` float DEFAULT NULL COMMENT 'μg',
  `molybdenium` float DEFAULT NULL COMMENT 'μg',
  `chromium` float DEFAULT NULL COMMENT 'μg',
  `boron` float DEFAULT NULL COMMENT 'μg',
  `nickel` float DEFAULT NULL COMMENT 'μg',
  `silicon` float DEFAULT NULL COMMENT 'μg',
  `vanadium` float DEFAULT NULL COMMENT 'μg',
  `sulfate` float DEFAULT NULL COMMENT 'mg',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `energy` (`energy`),
  KEY `sugars` (`sugars`),
  KEY `fats` (`fats`),
  KEY `saturated_fats` (`fats_saturated`),
  KEY `proteins` (`proteins`),
  KEY `carbohydrates` (`carbohydrates`),
  KEY `water_grams` (`water`),
  KEY `total_fiber` (`total_fiber`),
  KEY `calcium` (`calcium`),
  KEY `phosphorus` (`phosphorus`),
  KEY `fats_polyunsaturated` (`fats_polyunsaturated`),
  KEY `fats_monounsaturated` (`fats_monounsaturated`),
  KEY `cholesterol` (`cholesterol`),
  KEY `sodium` (`sodium`),
  KEY `net_weight` (`net_weight`),
  KEY `iron` (`iron`),
  KEY `ice_weight` (`glaze_weight`),
  KEY `store_temperature` (`store_temp_max`),
  KEY `store_duration` (`store_duration`),
  KEY `example_price` (`typical_price`),
  KEY `magnesium` (`magnesium`,`zinc`,`manganese`,`copper`,`selenium`),
  KEY `ash` (`ash`),
  KEY `fats_trans` (`fats_trans`),
  KEY `fk_product_product1` (`parent`),
  KEY `fk_product_person1` (`distributor`),
  KEY `fk_product_person2` (`importer`),
  KEY `fk_product_person3` (`maker`),
  KEY `packager` (`packager`),
  CONSTRAINT `fk_product_person1` FOREIGN KEY (`distributor`) REFERENCES `person` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_product_person2` FOREIGN KEY (`importer`) REFERENCES `person` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_product_person3` FOREIGN KEY (`maker`) REFERENCES `person` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_product_product1` FOREIGN KEY (`parent`) REFERENCES `product` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=777 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `product_nutrient`
--

DROP TABLE IF EXISTS `product_nutrient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_nutrient` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product` int(11) NOT NULL,
  `nutrient` int(11) NOT NULL,
  `value` float DEFAULT NULL,
  `source` int(11) NOT NULL DEFAULT '0' COMMENT '0:manual;1:product;2:children;3:fooddb',
  `id2` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product` (`product`,`nutrient`),
  KEY `nutrient` (`nutrient`,`source`,`id2`),
  KEY `fk_product_nutrient_nutrient1` (`nutrient`),
  KEY `fk_product_nutrient_product1` (`product`),
  CONSTRAINT `fk_product_nutrient_nutrient1` FOREIGN KEY (`nutrient`) REFERENCES `nutrient` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_product_nutrient_product1` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=14206 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `receipt`
--

DROP TABLE IF EXISTS `receipt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receipt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `parent` int(11) DEFAULT NULL COMMENT 'receipt',
  `issued` timestamp NULL DEFAULT NULL,
  `person` int(11) DEFAULT NULL,
  `store` int(11) DEFAULT NULL,
  `amount` decimal(6,2) DEFAULT NULL COMMENT 'total price - not price per unit',
  `product` int(11) DEFAULT NULL,
  `units` int(11) DEFAULT NULL,
  `length` float DEFAULT NULL,
  `area` float DEFAULT NULL,
  `weight` float DEFAULT NULL COMMENT 'g (gross weight)',
  `net_weight` float DEFAULT NULL COMMENT 'g',
  `net_volume` float DEFAULT NULL COMMENT 'ml',
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `date` (`issued`,`store`),
  KEY `fk_receipt_person1` (`person`),
  KEY `fk_receipt_product1` (`product`),
  KEY `fk_receipt_receipt` (`parent`),
  KEY `fk_receipt_store1` (`store`),
  KEY `fk_receipt_users1` (`user_id`),
  KEY `parent_id` (`parent`),
  KEY `person_id` (`person`),
  KEY `product` (`product`),
  CONSTRAINT `fk_receipt_person1` FOREIGN KEY (`person`) REFERENCES `person` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_receipt_product1` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_receipt_receipt` FOREIGN KEY (`parent`) REFERENCES `receipt` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_receipt_store1` FOREIGN KEY (`store`) REFERENCES `store` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_receipt_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1589 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `right`
--

DROP TABLE IF EXISTS `right`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `right` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `expression` varchar(64) NOT NULL,
  `description` text CHARACTER SET latin1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `storage_conditions`
--

DROP TABLE IF EXISTS `storage_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `storage_conditions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `store`
--

DROP TABLE IF EXISTS `store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` int(11) DEFAULT NULL,
  `name` varchar(127) NOT NULL,
  `address` varchar(127) DEFAULT NULL,
  `postcode` varchar(6) DEFAULT NULL,
  `phone` varchar(16) DEFAULT NULL,
  `phone2` varchar(16) DEFAULT NULL,
  `fax` varchar(16) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `postcode` (`postcode`),
  KEY `fk_store_person1` (`owner`),
  CONSTRAINT `fk_store_person1` FOREIGN KEY (`owner`) REFERENCES `person` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `threshold`
--

DROP TABLE IF EXISTS `threshold`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `threshold` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `demographic_group` int(11) DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `nutrient` int(11) NOT NULL,
  `min` float DEFAULT NULL,
  `best` float DEFAULT NULL,
  `max` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `demographic_group` (`demographic_group`,`user`,`nutrient`),
  KEY `fk_threshold_nutrient1` (`nutrient`),
  KEY `fk_threshold_users1` (`user`),
  KEY `fk_threshold_users2` (`user`),
  KEY `fk_threshold_demographic_group1` (`demographic_group`),
  CONSTRAINT `fk_threshold_demographic_group1` FOREIGN KEY (`demographic_group`) REFERENCES `demographic_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_threshold_nutrient1` FOREIGN KEY (`nutrient`) REFERENCES `nutrient` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_threshold_users1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_threshold_users2` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=3225 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `user_right`
--

DROP TABLE IF EXISTS `user_right`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_right` (
  `user` int(11) NOT NULL,
  `right` int(11) NOT NULL,
  PRIMARY KEY (`user`,`right`),
  KEY `fk_user_right_right1` (`right`),
  KEY `fk_user_right_users1` (`user`),
  CONSTRAINT `fk_user_right_right1` FOREIGN KEY (`right`) REFERENCES `right` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_right_users1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(12) NOT NULL,
  `password` varchar(40) NOT NULL,
  `email` varchar(128) NOT NULL,
  `registered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmation` varchar(40) DEFAULT NULL,
  `confirmed` timestamp NULL DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT '0',
  `timezone` varchar(32) DEFAULT NULL,
  `birth` date DEFAULT NULL,
  `gender` tinyint(1) DEFAULT NULL COMMENT 'female?',
  `pregnancy` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:no;1:pregnant;2:lactating',
  `demographic_group` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`,`email`,`confirmation`),
  KEY `fk_users_demographic_group1` (`demographic_group`),
  KEY `registered` (`registered`,`confirmed`,`active`),
  CONSTRAINT `fk_users_demographic_group1` FOREIGN KEY (`demographic_group`) REFERENCES `demographic_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-09-01 21:33:14
