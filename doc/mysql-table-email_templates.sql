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
-- Dumping data for table `email_templates`
--

LOCK TABLES `email_templates` WRITE;
/*!40000 ALTER TABLE `email_templates` DISABLE KEYS */;
INSERT INTO `email_templates` VALUES (53,'signup','Sent when a new user signs up.','Account confirmation','This email is to confirm that you requested to be registered. If you did not\r\nask for this email, you can ignore it.\r\n\r\nRegistration was from IP %IP% at %DATE%.\r\n\r\nPlease follow this link:\r\n\r\n    %LINK%','<p>This email is to confirm that you requested to be registered. If you did not ask for this email, you can ignore it.</p>\r\n<p>Registration was from IP %IP% at %DATE%.</p>\r\n<p>Please follow this link:</p>\r\n<p style=\"margin-left: 40px;\"><a href=\"%LINK%\">%LINK%</a></p>\r\n',NULL,NULL),(54,'password-reset','Sent when a user requests a password reset.','Password reset','This email is to give you instructions to reset your password. If you did not\r\nask for this email, you can ignore it.\r\n\r\nRequest was from IP %IP% at %DATE%.\r\n\r\nPlease follow this link:\r\n\r\n    %LINK%\r\n','<p>This email is to give you instructions to reset your password. If you did not ask for this email, you can ignore it.</p>\r\n<p>Request was from IP %IP% at %DATE%.</p>\r\n<p>Please follow this link:</p>\r\n<p style=\"margin-left: 40px;\"><a href=\"%LINK%\">%LINK%</a></p>',NULL,NULL);
/*!40000 ALTER TABLE `email_templates` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-09-05 23:02:05
