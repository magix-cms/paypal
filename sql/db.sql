CREATE TABLE IF NOT EXISTS `mc_paypal` (
  `id_paypal` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `clientId` varchar(150) DEFAULT NULL,
  `clientSecret` varchar(150) DEFAULT NULL,
  `mode` enum('sandbox','live') NOT NULL,
  `log` smallint(3) UNSIGNED DEFAULT '0',
  PRIMARY KEY (`id_paypal`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mc_paypal_history` (
    `id_paypal_h` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_h` varchar(50) NOT NULL,
    `status_h` varchar(30) NOT NULL,
    `date_register` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_paypal_h`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;