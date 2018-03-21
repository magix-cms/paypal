CREATE TABLE IF NOT EXISTS `mc_paypal` (
  `id_paypal` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `clientId` varchar(150) DEFAULT NULL,
  `clientSecret` varchar(150) DEFAULT NULL,
  `mode` enum('sandbox','live') NOT NULL,
  `log` smallint(3) UNSIGNED DEFAULT '0',
  PRIMARY KEY (`id_paypal`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `mc_admin_access` (`id_role`, `id_module`, `view`, `append`, `edit`, `del`, `action`)
  SELECT 1, m.id_module, 1, 1, 1, 1, 1 FROM mc_module as m WHERE name = 'paypal';