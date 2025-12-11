/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.22-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: brechokokero
-- ------------------------------------------------------
-- Server version	10.6.22-MariaDB-0ubuntu0.22.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `idCategoria` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`idCategoria`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Bermudas e Shorts'),(2,'Blazers'),(3,'Blusas e Camisas'),(4,'Calcas'),(5,'Casacos e Jaquetas'),(6,'Conjuntos'),(7,'Saias'),(8,'Sapatos'),(9,'Social'),(10,'Vestidos');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itenspedido`
--

DROP TABLE IF EXISTS `itenspedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `itenspedido` (
  `idItemPedido` int(11) NOT NULL AUTO_INCREMENT,
  `idPedido` int(11) DEFAULT NULL,
  `quantidade` int(11) DEFAULT NULL,
  PRIMARY KEY (`idItemPedido`),
  KEY `idPedido` (`idPedido`),
  CONSTRAINT `itensPedido_ibfk_1` FOREIGN KEY (`idPedido`) REFERENCES `pedidos` (`idPedido`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itenspedido`
--

LOCK TABLES `itenspedido` WRITE;
/*!40000 ALTER TABLE `itenspedido` DISABLE KEYS */;
/*!40000 ALTER TABLE `itenspedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nivelusuarios`
--

DROP TABLE IF EXISTS `nivelusuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nivelusuarios` (
  `idNivelUsuario` int(11) NOT NULL AUTO_INCREMENT,
  `nivel` varchar(20) DEFAULT NULL COMMENT '{Cliente, Administrador}',
  PRIMARY KEY (`idNivelUsuario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nivelusuarios`
--

LOCK TABLES `nivelusuarios` WRITE;
/*!40000 ALTER TABLE `nivelusuarios` DISABLE KEYS */;
INSERT INTO `nivelusuarios` VALUES (1,'Cliente'),(2,'Administrador');
/*!40000 ALTER TABLE `nivelusuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `idPedido` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` int(11) DEFAULT NULL,
  `dtPedido` datetime DEFAULT NULL,
  `telefoneEntrega` varchar(15) DEFAULT NULL,
  `valorTotal` decimal(12,2) DEFAULT NULL,
  `valorFrete` decimal(10,2) DEFAULT 0.00,
  `qtdItens` int(11) DEFAULT NULL,
  PRIMARY KEY (`idPedido`),
  KEY `idUsuario` (`idUsuario`),
  CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produtos`
--

DROP TABLE IF EXISTS `produtos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `produtos` (
  `idProduto` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) DEFAULT NULL,
  `marca` varchar(100) DEFAULT 'Sem estoque',
  `tamanho` varchar(10) DEFAULT NULL,
  `idCategoria` int(11) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `imagem` varchar(255) DEFAULT 'img/default.jpg',
  `promocao` tinyint(1) DEFAULT 0,
  `estoque` int(11) NOT NULL DEFAULT 0,
  `estado` varchar(20) NOT NULL DEFAULT 'Novo',
  `vendido` tinyint(4) NOT NULL DEFAULT 0,
  `data_venda` datetime DEFAULT NULL,
  PRIMARY KEY (`idProduto`),
  KEY `idCategoria` (`idCategoria`),
  CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`idCategoria`) REFERENCES `categorias` (`idCategoria`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produtos`
--

LOCK TABLES `produtos` WRITE;
/*!40000 ALTER TABLE `produtos` DISABLE KEYS */;
INSERT INTO `produtos` VALUES (16,'Camisa Brasil 1998','Sem Marca','L',3,'Camisa Brasil de 1998, relíquia original, alguns fios puxados, um pouco e desgaste na numeração, porém em ótimo estado!',130.00,'produto_1764729596_4069.jpeg',0,1,'Usado',0,NULL),(17,'Camisa de Tricot Cobalto','Sem Marca','G',3,'Camisa de Tricot Vintage, muito bem feita, confortável, perfeito estado.',60.00,'produto_1764731007_3650.jpeg',0,1,'Usado',0,NULL),(18,'Camisa Creme','Christian Ritz','P/M',3,'Camisa bem leve confortável, perfeito estado.',50.00,'produto_1764731103_6613.jpeg',0,1,'Usado',0,NULL),(19,'Camisa Vintage Ciano','Sem Marca','46/G',3,'Camisa Ana Cecília, CGC com ombreiras, detalhe bordado no bolso, leve queimado na parte de trás.',40.00,'produto_1764731168_2170.jpeg',0,1,'Usado',0,NULL),(20,'Camisa Costa do Sauípe','Sem Marca','M',3,'Camisa Vermelha de tricot, muito confortável, perfeito estado.',60.00,'produto_1764731221_3147.jpeg',0,1,'Usado',0,NULL),(21,'Camisa Reversível','Adidas','3XL',3,'Camisa Reversível, podendo ser usado os dois lados, perfeito estado.',90.00,'produto_1764731298_1248.jpeg',0,1,'Usado',0,NULL),(22,'Camisa Bengals (Rudi Johnson)','NFL','XL',3,'Camisa Original NFL (Rudi Johnson) anos 2000, em ótimo estado, apenas um detalhe na parte de trás do braco esquerdo.',180.00,'produto_1764731346_2789.jpeg',0,2,'Usado',0,NULL);
/*!40000 ALTER TABLE `produtos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `idUsuario` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) DEFAULT NULL,
  `senha` varchar(64) NOT NULL,
  `idNivelUsuario` int(11) DEFAULT 1,
  `nome` varchar(50) DEFAULT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`idUsuario`),
  UNIQUE KEY `email` (`email`),
  KEY `idNivelUsuario` (`idNivelUsuario`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`idNivelUsuario`) REFERENCES `nivelusuarios` (`idNivelUsuario`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (16,'projetoptivoleibol@gmail.com','$2y$10$UgiuNoTFF9PXeOEake0z1OuApQ90cIpGXy2JUJic9Es2zyYjuc6tq',2,'vebgtech',NULL),(18,'usuario@teste.com','$2y$10$V948ZfryZswjW/M2X8FnIenzrEAnudOCqljznxncPCm.7y0kPBC4m',1,'usuario comum',NULL),(19,'admin@teste.com','$2y$10$dLRlD3QP31.Ollb0H/OQYu17nKc1AXQuaLM9Fk3OHK1Vf.ZXeHOnq',2,'Admin',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-10 15:52:01
