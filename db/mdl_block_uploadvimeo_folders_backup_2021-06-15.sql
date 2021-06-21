-- --------------------------------------------------------
-- Servidor:                     127.0.0.1
-- Versão do servidor:           10.4.11-MariaDB - mariadb.org binary distribution
-- OS do Servidor:               Win64
-- HeidiSQL Versão:              10.1.0.5464
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Copiando estrutura do banco de dados para moodle
CREATE DATABASE IF NOT EXISTS `moodle` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
USE `moodle`;

-- Copiando estrutura para tabela moodle.mdl_block_uploadvimeo_folders
CREATE TABLE IF NOT EXISTS `mdl_block_uploadvimeo_folders` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `userid` bigint(10) NOT NULL,
  `clientid` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `foldername` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `folderid` bigint(10) NOT NULL,
  `timecreated` bigint(10) NOT NULL,
  `timecreatedvimeo` bigint(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl_blocuplofold_cliuse_uix` (`clientid`,`userid`),
  KEY `mdl_blocuplofold_fol_ix` (`foldername`),
  KEY `mdl_blocuplofold_clifol_ix` (`clientid`,`foldername`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='Folder (project) for user in vimeo';

-- Copiando dados para a tabela moodle.mdl_block_uploadvimeo_folders: ~6 rows (aproximadamente)
/*!40000 ALTER TABLE `mdl_block_uploadvimeo_folders` DISABLE KEYS */;
REPLACE INTO `mdl_block_uploadvimeo_folders` (`id`, `userid`, `clientid`, `foldername`, `folderid`, `timecreated`, `timecreatedvimeo`) VALUES
	(1, 3, 'c4cd26bb9cac576870200a71965fc7f614ac6fef', 'MoodleUpload_angela', 1631548, 1601392559, 1585866267),
	(2, 3, '86735c153060ee440657fb7dae7b155f77020dbe', 'MoodleUpload_angela', 1803166, 1601396847, 1588200350),
	(3, 17, '86735c153060ee440657fb7dae7b155f77020dbe', 'MoodleUpload_f17330', 2517848, 1601397680, 1599599295),
	(4, 18, '86735c153060ee440657fb7dae7b155f77020dbe', 'MoodleUpload_professorteste', 1854111, 1601398332, 1588889248),
	(5, 19, '86735c153060ee440657fb7dae7b155f77020dbe', 'MoodleUpload_f20111', 2448818, 1606314304, 1598467689),
	(6, 20, '86735c153060ee440657fb7dae7b155f77020dbe', 'MoodleUpload_angela-araujo@outlook.com', 3461495, 1611154395, 1611154299);
/*!40000 ALTER TABLE `mdl_block_uploadvimeo_folders` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
