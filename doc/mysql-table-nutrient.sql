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
-- Dumping data for table `nutrient`
--

LOCK TABLES `nutrient` WRITE;
/*!40000 ALTER TABLE `nutrient` DISABLE KEYS */;
INSERT INTO `nutrient` VALUES (1,10,1,'ENERC_KCAL','energy','Energy','kcal',0,0),(2,20,1,'WATER','water','Water','g',0,0),(3,30,1,'PROCNT','proteins','Protein','g',0,0),(4,40,1,'CHOAVL','carbohydrates','Carbohydrates','g',0,0),(5,50,1,'SUGAR','sugars','Sugars','g',0,0),(6,60,1,'FAT','fats','Fats','g',0,0),(7,70,1,'FASAT','fats_saturated','Saturated fats','g',0,0),(9,90,1,'FAMS','fats_monounsaturated','Monounsaturated fats','g',0,0),(10,100,1,'FAPU','fats_polyunsaturated','Polyunsaturated fats','g',0,0),(11,110,1,'FAPUN6F','fats_polyunsaturated_n6','n-6 polyunsaturated fats','g',0,0),(12,120,1,'FAPUN3F','fats_polyunsaturated_n3','n-3 polyunsaturated fats','g',0,0),(13,130,1,'FIBTG','total_fiber','Fiber','g',0,0),(14,140,3,'NA','sodium','Na','mg',1,0),(15,150,1,'ASH','ash','Ash','g',0,0),(16,160,3,'CA','calcium','Calcium','mg',1,0),(17,170,3,'P','phosphorus','Phosphorus','mg',1,0),(18,180,3,'FE','iron','Iron','mg',1,0),(19,190,2,'VITA','a','Vitamin A','μg RAE',1,0),(20,200,2,'THIA','b1','Thiamin (B₁)','mg',1,0),(21,210,2,'RIBF','b2','Riboflavin (B₂)','mg',1,0),(22,220,2,'NIAEQ','b3','Niacin (B₃) equiv.','mg NE',1,0),(23,230,2,'PANTAC','b5','Pantothenic acid (B₅)','mg',1,0),(24,240,2,'VITB6A','b6','Pyridoxine (B₆)','mg',1,0),(25,250,2,'BIOT','b7','Biotin (B₇ / H)','μg',1,0),(26,260,2,'FOLDFE','b9','Folic acid (B₉) or DFE','μg DFE',1,0),(27,270,2,'VITB12','b12','Cobalamin (B₁₂)','μg',1,0),(28,280,2,'VITC','c','Vitamin C','mg',1,0),(29,290,2,'VITD','d','Vitamin D','μg',1,0),(30,300,2,'VITE','e','Vitamin E','mg',1,0),(31,275,2,'CHOLN','choline','Choline','mg',1,0),(32,320,2,'CHOLE','cholesterol','Cholesterol','mg',1,0),(33,165,3,'MG','magnesium','Magnesium','mg',1,0),(34,175,3,'ZN','zinc','Zinc','mg',1,0),(35,350,3,'MN','manganese','Manganese','μg',1,0),(36,360,3,'CU','copper','Copper','mg',1,0),(37,370,3,'SE','selenium','Selenium','μg',1,0),(38,125,1,'FATRNF','fats_trans','Trans fats','g',0,0),(39,305,2,'VITK','k','Vitamin K','μg',1,0),(40,135,3,'K','potassium','Potassium','mg',1,0),(41,185,3,'FD','fluoride','Fluoride','μg',1,0),(42,365,3,'ID','iodine','Iodine','μg',1,0),(43,137,3,'CLD','chloride','Cl⁻','mg',1,0),(44,380,3,'MO','molybdenium','Molybdenium','μg',1,0),(45,390,3,'CR','chromium','Chromium','μg',1,0),(46,193,4,'RETOL','retinol','Retinol','μg',1,0),(47,400,4,'SI','silicon','Silicon','μg',1,0),(48,410,4,'NI','nickel','Nickel','μg',1,0),(49,420,4,'B','boron','Boron','μg',1,0),(50,430,4,'V','vanadium','Vanadium','μg',1,0),(51,440,4,'','sulfate','Sulfate','mg',1,0),(52,105,1,'','fats_polyunsaturated_n9','n-9 polyunsaturated fats','g',0,0),(53,194,4,'CARTA','alpha_carotene','α-carotene','μg',1,0),(54,195,4,'CARTB','beta_carotene','β-carotene','μg',1,0),(55,196,4,'CRYPX','beta_cryptoxanthin','β-cryptoxanthin','μg',1,0),(56,197,4,'LYCPN','lycopene','Lycopene','μg',1,0),(57,198,4,'LUT+ZEA','lutein_zeaxanthin','Lutein+zeaxanthin','μg',1,0),(58,263,4,'FOLAC','folic_acid','Folic acid (synthetic)','μg',1,0),(59,267,4,'FOLFD','folate','Folate (food)','μg',1,0);
/*!40000 ALTER TABLE `nutrient` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-09-09  9:31:49
