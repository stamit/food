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
-- Dumping data for table `demographic_group`
--

LOCK TABLES `demographic_group` WRITE;
/*!40000 ALTER TABLE `demographic_group` DISABLE KEYS */;
INSERT INTO `demographic_group` VALUES (1,0.583333,1,NULL,0),(2,1,4,NULL,0),(3,4,9,NULL,0),(4,9,14,0,0),(5,14,19,0,0),(6,19,31,0,0),(7,31,51,0,0),(8,51,71,0,0),(9,71,NULL,0,0),(10,9,14,1,0),(11,14,19,1,0),(12,19,31,1,0),(13,31,51,1,0),(14,51,71,1,0),(15,71,NULL,1,0),(16,14,19,1,1),(17,19,31,1,1),(18,31,51,1,1),(19,14,19,1,2),(20,19,31,1,2),(21,31,51,1,2),(22,NULL,0.583333,NULL,0);
/*!40000 ALTER TABLE `demographic_group` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-09-05  4:10:59
