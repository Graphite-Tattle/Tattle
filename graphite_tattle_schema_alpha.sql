-- MySQL dump 10.13  Distrib 5.1.58, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: graphite_tattle
-- ------------------------------------------------------
-- Server version	5.1.58-1ubuntu1

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
-- Table structure for table `check_results`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `check_results` (
  `result_id` int(11) NOT NULL AUTO_INCREMENT,
  `check_id` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `value` decimal(20,3) DEFAULT NULL,
  `state` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `acknowledged` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`result_id`),
  KEY `check_id` (`check_id`),
  CONSTRAINT `check_results_ibfk_1` FOREIGN KEY (`check_id`) REFERENCES `checks` (`check_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `checks`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `checks` (
  `check_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `target` varchar(1000) NOT NULL,
  `error` decimal(20,3) NOT NULL,
  `warn` decimal(20,3) NOT NULL,
  `sample` varchar(255) NOT NULL DEFAULT '-5minute',
  `baseline` varchar(255) NOT NULL DEFAULT 'average',
  `visibility` int(11) NOT NULL DEFAULT '0',
  `over_under` int(11) NOT NULL DEFAULT '0',
  `enabled` varchar(45) NOT NULL DEFAULT '1',
  `last_check_status` int(11) DEFAULT NULL,
  `last_check_value` int(11) DEFAULT NULL,
  `last_check_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `repeat_delay` int(11) NOT NULL DEFAULT '60',
  PRIMARY KEY (`check_id`),
  UNIQUE KEY `user_id` (`user_id`,`name`),
  CONSTRAINT `checks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dashboards`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dashboards` (
  `dashboard_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(1000) NOT NULL DEFAULT '',
  `columns` int(11) NOT NULL DEFAULT '2',
  `background_color` varchar(15) NOT NULL DEFAULT '000000',
  `graph_height` int(11) NOT NULL DEFAULT '300',
  `graph_width` int(11) NOT NULL DEFAULT '300',
  PRIMARY KEY (`dashboard_id`),
  UNIQUE KEY `user_id` (`user_id`,`name`),
  CONSTRAINT `dashboards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `graphs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `graphs` (
  `graph_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `area` varchar(45) NOT NULL,
  `vtitle` varchar(45) DEFAULT NULL,
  `description` varchar(1000) NOT NULL DEFAULT '',
  `dashboard_id` int(11) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`graph_id`),
  UNIQUE KEY `dashboard_id` (`dashboard_id`,`name`),
  CONSTRAINT `graphs_ibfk_1` FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`dashboard_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lines`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lines` (
  `line_id` int(11) NOT NULL AUTO_INCREMENT,
  `color` varchar(45) DEFAULT NULL,
  `target` varchar(1000) NOT NULL DEFAULT '',
  `alias` varchar(255) DEFAULT NULL,
  `graph_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`line_id`),
  UNIQUE KEY `graph_id` (`graph_id`,`alias`),
  CONSTRAINT `lines_ibfk_1` FOREIGN KEY (`graph_id`) REFERENCES `graphs` (`graph_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subscriptions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscriptions` (
  `subscription_id` int(11) NOT NULL AUTO_INCREMENT,
  `check_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `threshold` int(11) NOT NULL DEFAULT '0',
  `method` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `frequency` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`subscription_id`),
  KEY `check_id` (`check_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`check_id`) REFERENCES `checks` (`check_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(100) NOT NULL COMMENT 'This hash is generated using fCryptography::hashPassword()',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2011-10-27  2:55:42
