SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `civicrm_digitalcurrency_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL,
  `addr_source` varchar(255) NOT NULL,
  `trxn_hash` varchar(255) NOT NULL,
  `value_input` decimal(20,8) NOT NULL,
  `value_output` decimal(20,8) NOT NULL,
  `timestamp` timestamp,
  `is_processed` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `trxn_hash` (`trxn_hash`),
  KEY `provider` (`provider`),
  KEY `addr_source` (`addr_source`),
  KEY `timestamp` (`timestamp`),
  KEY `is_processed` (`is_processed`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
COMMIT;
