-- Object: em_currency
CREATE TABLE `em_currency` (
  `code` char(3) NOT NULL COMMENT 'Währungskürzel wie USD, EUR, CNY',
  `base_code` char(3) NOT NULL DEFAULT 'USD' COMMENT 'Basiswährung für den Kurs',
  `rate` decimal(18,8) NOT NULL COMMENT 'Wechselkurs (1 Basis = X Einheiten)',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`code`),
  KEY `idx_base` (`base_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Object: em_current_prices
CREATE TABLE `em_current_prices` (
  `current_price_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `metal_id` int(10) unsigned NOT NULL,
  `market_id` int(10) unsigned NOT NULL,
  `unit_id` int(10) unsigned NOT NULL,
  `price_type` enum('realtime','fixing_am','fixing_pm') DEFAULT 'realtime',
  `price` decimal(12,2) NOT NULL,
  `currency_code` varchar(3) NOT NULL,
  `bid` decimal(12,2) DEFAULT NULL,
  `ask` decimal(12,2) DEFAULT NULL,
  `change_24h` decimal(8,2) DEFAULT NULL,
  `change_percent_24h` decimal(6,2) DEFAULT NULL,
  `high_24h` decimal(12,2) DEFAULT NULL,
  `low_24h` decimal(12,2) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`current_price_id`),
  UNIQUE KEY `idx_full_pivot` (`metal_id`,`market_id`,`unit_id`,`price_type`,`currency_code`),
  KEY `metal_id` (`metal_id`),
  KEY `market_id` (`market_id`),
  KEY `unit_id` (`unit_id`),
  KEY `idx_updated` (`updated_at`),
  CONSTRAINT `em_current_prices_ibfk_1` FOREIGN KEY (`metal_id`) REFERENCES `em_metals` (`metal_id`) ON DELETE CASCADE,
  CONSTRAINT `em_current_prices_ibfk_2` FOREIGN KEY (`market_id`) REFERENCES `em_markets` (`market_id`) ON DELETE CASCADE,
  CONSTRAINT `em_current_prices_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `em_units` (`unit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1493 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_favorites
CREATE TABLE `em_favorites` (
  `favorite_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `listing_id` int(10) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `price_alert_enabled` tinyint(1) DEFAULT 0,
  `price_alert_target` decimal(12,2) DEFAULT NULL COMMENT 'Preis-Alarm bei Erreichen',
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`favorite_id`),
  UNIQUE KEY `unique_favorite` (`user_id`,`listing_id`),
  KEY `listing_id` (`listing_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_price_alert` (`price_alert_enabled`),
  CONSTRAINT `em_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `em_favorites_ibfk_2` FOREIGN KEY (`listing_id`) REFERENCES `em_listings` (`listing_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_images
CREATE TABLE `em_images` (
  `image_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` int(10) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(500) NOT NULL,
  `image_type` enum('main','detail','certificate','serial','packaging','other') DEFAULT 'detail',
  `sort_order` tinyint(3) unsigned DEFAULT 0,
  `caption_de` varchar(255) DEFAULT NULL,
  `caption_en` varchar(255) DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `idx_listing` (`listing_id`),
  KEY `idx_type` (`image_type`),
  KEY `idx_sort` (`sort_order`),
  CONSTRAINT `em_images_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `em_listings` (`listing_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_listings
CREATE TABLE `em_listings` (
  `listing_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `metal_id` int(10) unsigned NOT NULL,
  `listing_type` enum('verkauf','kauf','tausch') DEFAULT 'verkauf',
  `quantity` decimal(15,6) NOT NULL COMMENT 'Menge',
  `unit_id` int(10) unsigned NOT NULL,
  `price_per_unit` decimal(12,2) NOT NULL COMMENT 'Preis pro Einheit in EUR/USD',
  `currency_code` varchar(3) DEFAULT 'EUR',
  `total_price` decimal(15,2) GENERATED ALWAYS AS (`quantity` * `price_per_unit`) STORED,
  `purity` decimal(6,3) DEFAULT 999.000 COMMENT 'Feingehalt z.B. 999.9 für 24k Gold',
  `form` enum('barren','muenzen','granulat','schmuck','other') DEFAULT 'barren',
  `manufacturer` varchar(100) DEFAULT NULL COMMENT 'Hersteller/Prägestätte',
  `certification` varchar(100) DEFAULT NULL COMMENT 'z.B. LBMA, SGE zertifiziert',
  `serial_numbers` text DEFAULT NULL COMMENT 'Seriennummern falls vorhanden',
  `year_minted` year(4) DEFAULT NULL,
  `condition_rating` enum('neu','sehr_gut','gut','gebraucht') DEFAULT 'neu',
  `title_de` varchar(255) NOT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `description_de` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `adresse_id` int(10) unsigned DEFAULT NULL,
  `shipping_possible` tinyint(1) DEFAULT 0,
  `shipping_cost` decimal(8,2) DEFAULT NULL,
  `insured_shipping` tinyint(1) DEFAULT 0,
  `pickup_only` tinyint(1) DEFAULT 1,
  `vault_storage_available` tinyint(1) DEFAULT 0 COMMENT 'Tresor-Lagerung möglich',
  `minimum_order` decimal(15,6) DEFAULT NULL COMMENT 'Mindestbestellmenge',
  `price_negotiable` tinyint(1) DEFAULT 0,
  `market_price_reference_id` int(10) unsigned DEFAULT NULL COMMENT 'Referenz zu em_markets für Preisbildung',
  `premium_over_spot` decimal(6,2) DEFAULT NULL COMMENT 'Aufschlag über Spotpreis in %',
  `aktiv` tinyint(1) DEFAULT 1,
  `sold` tinyint(1) DEFAULT 0,
  `sold_at` timestamp NULL DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  `aktualisiert_am` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`listing_id`),
  KEY `user_id` (`user_id`),
  KEY `metal_id` (`metal_id`),
  KEY `unit_id` (`unit_id`),
  KEY `adresse_id` (`adresse_id`),
  KEY `market_price_reference_id` (`market_price_reference_id`),
  KEY `idx_listing_type` (`listing_type`),
  KEY `idx_aktiv` (`aktiv`),
  KEY `idx_sold` (`sold`),
  KEY `idx_form` (`form`),
  KEY `idx_price` (`price_per_unit`),
  KEY `idx_currency` (`currency_code`),
  KEY `idx_created` (`erstellt_am`),
  FULLTEXT KEY `idx_search` (`title_de`,`description_de`,`manufacturer`),
  CONSTRAINT `em_listings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `em_listings_ibfk_2` FOREIGN KEY (`metal_id`) REFERENCES `em_metals` (`metal_id`),
  CONSTRAINT `em_listings_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `em_units` (`unit_id`),
  CONSTRAINT `em_listings_ibfk_4` FOREIGN KEY (`adresse_id`) REFERENCES `lg_adressen` (`adresse_id`) ON DELETE SET NULL,
  CONSTRAINT `em_listings_ibfk_5` FOREIGN KEY (`market_price_reference_id`) REFERENCES `em_markets` (`market_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_markets
CREATE TABLE `em_markets` (
  `market_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL COMMENT 'LBMA, COMEX, SGE',
  `name` varchar(100) NOT NULL,
  `city` varchar(50) NOT NULL,
  `country` varchar(3) NOT NULL COMMENT 'ISO 3166-1 Alpha-3',
  `timezone` varchar(50) NOT NULL COMMENT 'z.B. Europe/London',
  `currency_code` varchar(3) NOT NULL DEFAULT 'USD' COMMENT 'ISO 4217',
  `fixing_times` varchar(255) DEFAULT NULL COMMENT 'JSON Array der Fixing-Zeiten',
  `trading_hours` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `aktiv` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`market_id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_aktiv` (`aktiv`),
  KEY `idx_city` (`city`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_messages
CREATE TABLE `em_messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` int(10) unsigned NOT NULL,
  `sender_id` int(10) unsigned NOT NULL,
  `receiver_id` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `idx_listing` (`listing_id`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_receiver` (`receiver_id`),
  KEY `idx_unread` (`receiver_id`,`read_at`),
  CONSTRAINT `em_messages_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `em_listings` (`listing_id`) ON DELETE CASCADE,
  CONSTRAINT `em_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `em_messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_metals
CREATE TABLE `em_metals` (
  `metal_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `symbol` varchar(10) NOT NULL COMMENT 'XAU, XAG, XPT, XPD',
  `name_de` varchar(50) NOT NULL,
  `name_en` varchar(50) NOT NULL,
  `description_de` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `atomic_number` tinyint(3) unsigned DEFAULT NULL,
  `density_g_cm3` decimal(6,3) DEFAULT NULL,
  `sort_order` tinyint(3) unsigned DEFAULT 0,
  `aktiv` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`metal_id`),
  UNIQUE KEY `symbol` (`symbol`),
  KEY `idx_aktiv` (`aktiv`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_price_alerts
CREATE TABLE `em_price_alerts` (
  `alert_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `metal_id` int(10) unsigned NOT NULL,
  `market_id` int(10) unsigned DEFAULT NULL,
  `alert_type` enum('above','below','change_percent') DEFAULT 'below',
  `target_price` decimal(12,2) NOT NULL,
  `currency_code` varchar(3) DEFAULT 'EUR',
  `triggered` tinyint(1) DEFAULT 0,
  `triggered_at` timestamp NULL DEFAULT NULL,
  `aktiv` tinyint(1) DEFAULT 1,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`alert_id`),
  KEY `user_id` (`user_id`),
  KEY `metal_id` (`metal_id`),
  KEY `market_id` (`market_id`),
  KEY `idx_aktiv` (`aktiv`),
  KEY `idx_triggered` (`triggered`),
  CONSTRAINT `em_price_alerts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `em_price_alerts_ibfk_2` FOREIGN KEY (`metal_id`) REFERENCES `em_metals` (`metal_id`) ON DELETE CASCADE,
  CONSTRAINT `em_price_alerts_ibfk_3` FOREIGN KEY (`market_id`) REFERENCES `em_markets` (`market_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_prices
CREATE TABLE `em_prices` (
  `price_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `metal_id` int(10) unsigned NOT NULL,
  `market_id` int(10) unsigned NOT NULL,
  `unit_id` int(10) unsigned NOT NULL,
  `price_type` enum('realtime','fixing_am','fixing_pm') DEFAULT 'realtime',
  `price` decimal(12,2) NOT NULL COMMENT 'Preis in Markt-Währung',
  `currency_code` varchar(3) NOT NULL,
  `bid` decimal(12,2) DEFAULT NULL COMMENT 'Geldkurs',
  `ask` decimal(12,2) DEFAULT NULL COMMENT 'Briefkurs',
  `spread` decimal(8,4) GENERATED ALWAYS AS (`ask` - `bid`) STORED,
  `change_24h` decimal(8,2) DEFAULT NULL COMMENT 'Änderung 24h',
  `change_percent_24h` decimal(6,2) DEFAULT NULL,
  `volume_24h` decimal(20,6) DEFAULT NULL COMMENT 'Handelsvolumen 24h',
  `high_24h` decimal(12,2) DEFAULT NULL,
  `low_24h` decimal(12,2) DEFAULT NULL,
  `open_24h` decimal(12,2) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `fixing_date` date DEFAULT NULL COMMENT 'Datum des Fixings',
  `fixing_time` time DEFAULT NULL COMMENT 'Uhrzeit des Fixings',
  `source` varchar(100) DEFAULT NULL COMMENT 'Datenquelle/API',
  PRIMARY KEY (`price_id`),
  UNIQUE KEY `unique_price_entry` (`metal_id`,`market_id`,`unit_id`,`price_type`,`timestamp`),
  KEY `metal_id` (`metal_id`),
  KEY `market_id` (`market_id`),
  KEY `unit_id` (`unit_id`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_price_type` (`price_type`),
  KEY `idx_fixing_date` (`fixing_date`),
  KEY `idx_metal_market` (`metal_id`,`market_id`,`timestamp`),
  CONSTRAINT `em_prices_ibfk_1` FOREIGN KEY (`metal_id`) REFERENCES `em_metals` (`metal_id`) ON DELETE CASCADE,
  CONSTRAINT `em_prices_ibfk_2` FOREIGN KEY (`market_id`) REFERENCES `em_markets` (`market_id`) ON DELETE CASCADE,
  CONSTRAINT `em_prices_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `em_units` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_ratings
CREATE TABLE `em_ratings` (
  `rating_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint(20) unsigned NOT NULL,
  `rater_id` int(10) unsigned NOT NULL,
  `rated_user_id` int(10) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL CHECK (`rating` between 1 and 5),
  `transaction_type` enum('verkauf','kauf','tausch') NOT NULL,
  `comment` text DEFAULT NULL,
  `communication_rating` tinyint(3) unsigned DEFAULT NULL CHECK (`communication_rating` between 1 and 5),
  `product_rating` tinyint(3) unsigned DEFAULT NULL CHECK (`product_rating` between 1 and 5),
  `shipping_rating` tinyint(3) unsigned DEFAULT NULL CHECK (`shipping_rating` between 1 and 5),
  `would_deal_again` tinyint(1) DEFAULT 1,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`rating_id`),
  UNIQUE KEY `unique_rating` (`transaction_id`,`rater_id`),
  KEY `rater_id` (`rater_id`),
  KEY `idx_rated_user` (`rated_user_id`),
  KEY `idx_rating` (`rating`),
  CONSTRAINT `em_ratings_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `em_transactions` (`transaction_id`) ON DELETE CASCADE,
  CONSTRAINT `em_ratings_ibfk_2` FOREIGN KEY (`rater_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `em_ratings_ibfk_3` FOREIGN KEY (`rated_user_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_transactions
CREATE TABLE `em_transactions` (
  `transaction_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` int(10) unsigned NOT NULL,
  `seller_id` int(10) unsigned NOT NULL,
  `buyer_id` int(10) unsigned NOT NULL,
  `metal_id` int(10) unsigned NOT NULL,
  `quantity` decimal(15,6) NOT NULL,
  `unit_id` int(10) unsigned NOT NULL,
  `price_per_unit` decimal(12,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `currency_code` varchar(3) NOT NULL,
  `transaction_type` enum('verkauf','kauf','tausch') NOT NULL,
  `payment_method` enum('ueberweisung','bar','crypto','other') DEFAULT NULL,
  `payment_status` enum('pending','paid','refunded','cancelled') DEFAULT 'pending',
  `shipping_method` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `delivery_status` enum('pending','shipped','in_transit','delivered','pickup') DEFAULT 'pending',
  `delivered_at` timestamp NULL DEFAULT NULL,
  `contract_pdf_path` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  `aktualisiert_am` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  KEY `listing_id` (`listing_id`),
  KEY `seller_id` (`seller_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `metal_id` (`metal_id`),
  KEY `unit_id` (`unit_id`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_delivery_status` (`delivery_status`),
  KEY `idx_created` (`erstellt_am`),
  CONSTRAINT `em_transactions_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `em_listings` (`listing_id`) ON DELETE CASCADE,
  CONSTRAINT `em_transactions_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `em_transactions_ibfk_3` FOREIGN KEY (`buyer_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `em_transactions_ibfk_4` FOREIGN KEY (`metal_id`) REFERENCES `em_metals` (`metal_id`),
  CONSTRAINT `em_transactions_ibfk_5` FOREIGN KEY (`unit_id`) REFERENCES `em_units` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_units
CREATE TABLE `em_units` (
  `unit_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL COMMENT 'kg, g, oz',
  `name_de` varchar(50) NOT NULL,
  `name_en` varchar(50) NOT NULL,
  `grams_per_unit` decimal(12,6) NOT NULL COMMENT 'Umrechnungsfaktor zu Gramm',
  `sort_order` tinyint(3) unsigned DEFAULT 0,
  `aktiv` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`unit_id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_aktiv` (`aktiv`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_v_active_listings
CREATE ALGORITHM=UNDEFINED DEFINER=`gh`@`localhost` SQL SECURITY DEFINER VIEW `em_v_active_listings` AS select `l`.`listing_id` AS `listing_id`,`l`.`user_id` AS `seller_id`,`l`.`listing_type` AS `listing_type`,`l`.`quantity` AS `quantity`,`l`.`price_per_unit` AS `price_per_unit`,`l`.`total_price` AS `total_price`,`l`.`currency_code` AS `currency_code`,`l`.`purity` AS `purity`,`l`.`form` AS `form`,`l`.`manufacturer` AS `manufacturer`,`l`.`title_de` AS `title_de`,`l`.`description_de` AS `description_de`,`l`.`aktiv` AS `aktiv`,`l`.`sold` AS `sold`,`l`.`erstellt_am` AS `erstellt_am`,`m`.`symbol` AS `metal_symbol`,`m`.`name_de` AS `metal_name`,`u`.`code` AS `unit_code`,`u`.`name_de` AS `unit_name`,`user`.`name` AS `seller_name`,`user`.`email` AS `seller_email`,`a`.`plz` AS `plz`,`a`.`ort` AS `ort`,`a`.`land` AS `land`,(select count(0) from `em_images` where `em_images`.`listing_id` = `l`.`listing_id`) AS `image_count`,(select avg(`em_ratings`.`rating`) from `em_ratings` where `em_ratings`.`rated_user_id` = `l`.`user_id`) AS `seller_rating`,to_days(current_timestamp()) - to_days(`l`.`erstellt_am`) AS `tage_alt` from ((((`em_listings` `l` join `em_metals` `m` on(`l`.`metal_id` = `m`.`metal_id`)) join `em_units` `u` on(`l`.`unit_id` = `u`.`unit_id`)) join `lg_users` `user` on(`l`.`user_id` = `user`.`user_id`)) left join `lg_adressen` `a` on(`l`.`adresse_id` = `a`.`adresse_id`)) where `l`.`aktiv` = 1 and `l`.`sold` = 0 order by `l`.`erstellt_am` desc;

-- Object: em_v_latest_prices
CREATE ALGORITHM=UNDEFINED DEFINER=`gh`@`localhost` SQL SECURITY DEFINER VIEW `em_v_latest_prices` AS select `cp`.`current_price_id` AS `current_price_id`,`m`.`symbol` AS `metal_symbol`,`m`.`name_de` AS `metal_name`,`mk`.`code` AS `market_code`,`mk`.`name` AS `market_name`,`mk`.`city` AS `market_city`,`u`.`code` AS `unit_code`,`cp`.`price_type` AS `price_type`,`cp`.`price` AS `price`,`cp`.`currency_code` AS `currency_code`,`cp`.`bid` AS `bid`,`cp`.`ask` AS `ask`,`cp`.`change_24h` AS `change_24h`,`cp`.`change_percent_24h` AS `change_percent_24h`,`cp`.`high_24h` AS `high_24h`,`cp`.`low_24h` AS `low_24h`,`cp`.`updated_at` AS `updated_at` from (((`em_current_prices` `cp` join `em_metals` `m` on(`cp`.`metal_id` = `m`.`metal_id`)) join `em_markets` `mk` on(`cp`.`market_id` = `mk`.`market_id`)) join `em_units` `u` on(`cp`.`unit_id` = `u`.`unit_id`)) where `m`.`aktiv` = 1 and `mk`.`aktiv` = 1 order by `m`.`sort_order`,`mk`.`code`,`cp`.`price_type`;

-- Object: em_v_user_transactions
CREATE ALGORITHM=UNDEFINED DEFINER=`gh`@`localhost` SQL SECURITY DEFINER VIEW `em_v_user_transactions` AS select `t`.`transaction_id` AS `transaction_id`,`t`.`transaction_type` AS `transaction_type`,`t`.`quantity` AS `quantity`,`t`.`price_per_unit` AS `price_per_unit`,`t`.`total_price` AS `total_price`,`t`.`currency_code` AS `currency_code`,`t`.`payment_status` AS `payment_status`,`t`.`delivery_status` AS `delivery_status`,`t`.`erstellt_am` AS `erstellt_am`,`m`.`symbol` AS `metal_symbol`,`m`.`name_de` AS `metal_name`,`u`.`code` AS `unit_code`,`seller`.`name` AS `seller_name`,`buyer`.`name` AS `buyer_name`,`l`.`title_de` AS `listing_title` from (((((`em_transactions` `t` join `em_metals` `m` on(`t`.`metal_id` = `m`.`metal_id`)) join `em_units` `u` on(`t`.`unit_id` = `u`.`unit_id`)) join `lg_users` `seller` on(`t`.`seller_id` = `seller`.`user_id`)) join `lg_users` `buyer` on(`t`.`buyer_id` = `buyer`.`user_id`)) join `em_listings` `l` on(`t`.`listing_id` = `l`.`listing_id`)) order by `t`.`erstellt_am` desc;

-- Object: gun_categories
CREATE TABLE `gun_categories` (
  `category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name_de` varchar(100) NOT NULL,
  `name_en` varchar(100) NOT NULL,
  `description_de` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `license_required` enum('none','kleiner_waffenschein','waffenschein','wbk') DEFAULT 'none',
  `sort_order` int(11) DEFAULT 0,
  `aktiv` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`category_id`),
  KEY `idx_license` (`license_required`),
  KEY `idx_aktiv` (`aktiv`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: gun_favorites
CREATE TABLE `gun_favorites` (
  `favorite_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `listing_id` int(10) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`favorite_id`),
  UNIQUE KEY `unique_favorite` (`user_id`,`listing_id`),
  KEY `listing_id` (`listing_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `gun_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `gun_favorites_ibfk_2` FOREIGN KEY (`listing_id`) REFERENCES `gun_listings` (`listing_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: gun_images
CREATE TABLE `gun_images` (
  `image_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` int(10) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(500) NOT NULL,
  `image_type` enum('main','detail','proof_mark','serial','case','accessories') DEFAULT 'detail',
  `sort_order` tinyint(3) unsigned DEFAULT 0,
  `caption` varchar(255) DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `idx_listing` (`listing_id`),
  KEY `idx_type` (`image_type`),
  KEY `idx_sort` (`sort_order`),
  CONSTRAINT `gun_images_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `gun_listings` (`listing_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: gun_listings
CREATE TABLE `gun_listings` (
  `listing_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `manufacturer_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `caliber` varchar(50) DEFAULT NULL,
  `barrel_length_cm` decimal(5,2) DEFAULT NULL,
  `weight_kg` decimal(6,3) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `year_manufactured` year(4) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `condition_rating` enum('neu','wie_neu','sehr_gut','gut','gebraucht') DEFAULT 'gebraucht',
  `rounds_fired` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `price_negotiable` tinyint(1) DEFAULT 0,
  `includes_case` tinyint(1) DEFAULT 0,
  `includes_magazines` int(11) DEFAULT 0,
  `includes_accessories` text DEFAULT NULL,
  `has_wbk` tinyint(1) DEFAULT 0,
  `wbk_transferable` tinyint(1) DEFAULT 0,
  `proof_marks` varchar(255) DEFAULT NULL,
  `description_de` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `technical_specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`technical_specs`)),
  `adresse_id` int(10) unsigned DEFAULT NULL,
  `shipping_possible` tinyint(1) DEFAULT 0,
  `shipping_cost` decimal(8,2) DEFAULT NULL,
  `pickup_only` tinyint(1) DEFAULT 1,
  `listing_type` enum('verkauf','tausch','suche') DEFAULT 'verkauf',
  `aktiv` tinyint(1) DEFAULT 1,
  `sold` tinyint(1) DEFAULT 0,
  `sold_at` timestamp NULL DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  `aktualisiert_am` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`listing_id`),
  KEY `seller_id` (`seller_id`),
  KEY `adresse_id` (`adresse_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_manufacturer` (`manufacturer_id`),
  KEY `idx_caliber` (`caliber`),
  KEY `idx_price` (`price`),
  KEY `idx_listing_type` (`listing_type`),
  KEY `idx_aktiv` (`aktiv`),
  KEY `idx_sold` (`sold`),
  KEY `idx_expires` (`expires_at`),
  FULLTEXT KEY `idx_search` (`title`,`model`,`description_de`),
  CONSTRAINT `gun_listings_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `gun_listings_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `gun_categories` (`category_id`),
  CONSTRAINT `gun_listings_ibfk_3` FOREIGN KEY (`manufacturer_id`) REFERENCES `gun_manufacturers` (`manufacturer_id`) ON DELETE SET NULL,
  CONSTRAINT `gun_listings_ibfk_4` FOREIGN KEY (`adresse_id`) REFERENCES `lg_adressen` (`adresse_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: gun_manufacturers
CREATE TABLE `gun_manufacturers` (
  `manufacturer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `country` varchar(2) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `aktiv` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`manufacturer_id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_country` (`country`),
  KEY `idx_aktiv` (`aktiv`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: gun_messages
CREATE TABLE `gun_messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` int(10) unsigned NOT NULL,
  `sender_id` int(10) unsigned NOT NULL,
  `receiver_id` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `idx_listing` (`listing_id`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_receiver` (`receiver_id`),
  KEY `idx_unread` (`receiver_id`,`read_at`),
  CONSTRAINT `gun_messages_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `gun_listings` (`listing_id`) ON DELETE CASCADE,
  CONSTRAINT `gun_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `gun_messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: gun_ratings
CREATE TABLE `gun_ratings` (
  `rating_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` int(10) unsigned NOT NULL,
  `rater_id` int(10) unsigned NOT NULL,
  `rated_user_id` int(10) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL CHECK (`rating` between 1 and 5),
  `transaction_type` enum('kauf','verkauf','tausch') NOT NULL,
  `comment` text DEFAULT NULL,
  `communication_rating` tinyint(3) unsigned DEFAULT NULL CHECK (`communication_rating` between 1 and 5),
  `item_condition_rating` tinyint(3) unsigned DEFAULT NULL CHECK (`item_condition_rating` between 1 and 5),
  `shipping_rating` tinyint(3) unsigned DEFAULT NULL CHECK (`shipping_rating` between 1 and 5),
  `would_deal_again` tinyint(1) DEFAULT 1,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`rating_id`),
  UNIQUE KEY `unique_rating` (`listing_id`,`rater_id`),
  KEY `rater_id` (`rater_id`),
  KEY `idx_rated_user` (`rated_user_id`),
  KEY `idx_rating` (`rating`),
  CONSTRAINT `gun_ratings_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `gun_listings` (`listing_id`) ON DELETE CASCADE,
  CONSTRAINT `gun_ratings_ibfk_2` FOREIGN KEY (`rater_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `gun_ratings_ibfk_3` FOREIGN KEY (`rated_user_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: gun_requests
CREATE TABLE `gun_requests` (
  `request_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `buyer_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `manufacturer_id` int(10) unsigned DEFAULT NULL,
  `search_title` varchar(255) DEFAULT NULL,
  `model_wanted` varchar(100) DEFAULT NULL,
  `caliber_wanted` varchar(50) DEFAULT NULL,
  `condition_min` enum('neu','wie_neu','sehr_gut','gut','gebraucht') DEFAULT 'gebraucht',
  `budget_min` decimal(10,2) DEFAULT NULL,
  `budget_max` decimal(10,2) DEFAULT NULL,
  `plz_von` varchar(10) DEFAULT NULL,
  `plz_bis` varchar(10) DEFAULT NULL,
  `umkreis_km` smallint(5) unsigned DEFAULT NULL,
  `requires_wbk` tinyint(1) DEFAULT 0,
  `shipping_acceptable` tinyint(1) DEFAULT 1,
  `description_de` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `aktiv` tinyint(1) DEFAULT 1,
  `fulfilled` tinyint(1) DEFAULT 0,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  `aktualisiert_am` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `manufacturer_id` (`manufacturer_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_caliber` (`caliber_wanted`),
  KEY `idx_budget` (`budget_min`,`budget_max`),
  KEY `idx_aktiv` (`aktiv`),
  CONSTRAINT `gun_requests_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `gun_requests_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `gun_categories` (`category_id`) ON DELETE SET NULL,
  CONSTRAINT `gun_requests_ibfk_3` FOREIGN KEY (`manufacturer_id`) REFERENCES `gun_manufacturers` (`manufacturer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: gun_v_active_listings
CREATE ALGORITHM=UNDEFINED DEFINER=`gh`@`localhost` SQL SECURITY DEFINER VIEW `gun_v_active_listings` AS select `l`.`listing_id` AS `listing_id`,`l`.`seller_id` AS `seller_id`,`l`.`category_id` AS `category_id`,`l`.`manufacturer_id` AS `manufacturer_id`,`l`.`title` AS `title`,`l`.`model` AS `model`,`l`.`caliber` AS `caliber`,`l`.`barrel_length_cm` AS `barrel_length_cm`,`l`.`weight_kg` AS `weight_kg`,`l`.`capacity` AS `capacity`,`l`.`year_manufactured` AS `year_manufactured`,`l`.`serial_number` AS `serial_number`,`l`.`condition_rating` AS `condition_rating`,`l`.`rounds_fired` AS `rounds_fired`,`l`.`price` AS `price`,`l`.`price_negotiable` AS `price_negotiable`,`l`.`includes_case` AS `includes_case`,`l`.`includes_magazines` AS `includes_magazines`,`l`.`includes_accessories` AS `includes_accessories`,`l`.`has_wbk` AS `has_wbk`,`l`.`wbk_transferable` AS `wbk_transferable`,`l`.`proof_marks` AS `proof_marks`,`l`.`description` AS `description`,`l`.`technical_specs` AS `technical_specs`,`l`.`adresse_id` AS `adresse_id`,`l`.`shipping_possible` AS `shipping_possible`,`l`.`shipping_cost` AS `shipping_cost`,`l`.`pickup_only` AS `pickup_only`,`l`.`listing_type` AS `listing_type`,`l`.`aktiv` AS `aktiv`,`l`.`sold` AS `sold`,`l`.`sold_at` AS `sold_at`,`l`.`erstellt_am` AS `erstellt_am`,`l`.`aktualisiert_am` AS `aktualisiert_am`,`l`.`expires_at` AS `expires_at`,`c`.`name_de` AS `category_name`,`c`.`license_required` AS `license_required`,`m`.`name` AS `manufacturer_name`,`m`.`country` AS `manufacturer_country`,`u`.`name` AS `seller_name`,`u`.`email` AS `seller_email`,`a`.`plz` AS `plz`,`a`.`ort` AS `ort`,`a`.`land` AS `land`,(select count(0) from `gun_images` where `wagodb`.`gun_images`.`listing_id` = `l`.`listing_id`) AS `image_count`,(select avg(`wagodb`.`gun_ratings`.`rating`) from `gun_ratings` where `wagodb`.`gun_ratings`.`rated_user_id` = `l`.`seller_id`) AS `seller_rating` from ((((`gun_listings` `l` join `gun_categories` `c` on(`l`.`category_id` = `c`.`category_id`)) left join `gun_manufacturers` `m` on(`l`.`manufacturer_id` = `m`.`manufacturer_id`)) join `lg_users` `u` on(`l`.`seller_id` = `u`.`user_id`)) left join `lg_adressen` `a` on(`l`.`adresse_id` = `a`.`adresse_id`)) where `l`.`aktiv` = 1 and `l`.`sold` = 0 order by `l`.`erstellt_am` desc;

-- Object: gun_v_user_stats
CREATE ALGORITHM=UNDEFINED DEFINER=`gh`@`localhost` SQL SECURITY DEFINER VIEW `gun_v_user_stats` AS select `u`.`user_id` AS `user_id`,`u`.`name` AS `name`,`u`.`email` AS `email`,count(distinct `l`.`listing_id`) AS `total_listings`,count(distinct case when `l`.`sold` = 1 then `l`.`listing_id` end) AS `sold_count`,avg(`r`.`rating`) AS `avg_rating`,count(distinct `r`.`rating_id`) AS `rating_count` from ((`lg_users` `u` left join `gun_listings` `l` on(`u`.`user_id` = `l`.`seller_id`)) left join `gun_ratings` `r` on(`u`.`user_id` = `r`.`rated_user_id`)) group by `u`.`user_id`;

-- Object: lg_adressen
CREATE TABLE `lg_adressen` (
  `adresse_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `strasse` varchar(255) NOT NULL,
  `hausnummer` varchar(20) DEFAULT NULL,
  `plz` varchar(10) NOT NULL,
  `ort` varchar(100) NOT NULL,
  `land` varchar(3) DEFAULT 'DE' COMMENT 'ISO 3166-1 Alpha-2 oder Alpha-3 Code',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`adresse_id`),
  KEY `idx_plz` (`plz`),
  KEY `idx_ort` (`ort`),
  KEY `idx_geo` (`latitude`,`longitude`),
  KEY `idx_land` (`land`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: lg_bilder
CREATE TABLE `lg_bilder` (
  `bild_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lagerraum_id` int(10) unsigned NOT NULL,
  `dateiname` varchar(255) NOT NULL,
  `pfad` varchar(500) NOT NULL,
  `reihenfolge` tinyint(3) unsigned DEFAULT 0,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  `beschreibung_de` varchar(255) DEFAULT NULL,
  `beschreibung_en` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`bild_id`),
  KEY `idx_lagerraum` (`lagerraum_id`),
  KEY `idx_reihenfolge` (`reihenfolge`),
  CONSTRAINT `lg_bilder_ibfk_1` FOREIGN KEY (`lagerraum_id`) REFERENCES `lg_lagerraeume` (`lagerraum_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: lg_kontaktanfragen
CREATE TABLE `lg_kontaktanfragen` (
  `anfrage_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lagerraum_id` int(10) unsigned NOT NULL,
  `interessent_id` int(10) unsigned NOT NULL,
  `nachricht` text DEFAULT NULL,
  `gelesen` tinyint(1) DEFAULT 0,
  `beantwortet` tinyint(1) DEFAULT 0,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`anfrage_id`),
  KEY `idx_lagerraum` (`lagerraum_id`),
  KEY `idx_interessent` (`interessent_id`),
  KEY `idx_gelesen` (`gelesen`),
  KEY `idx_erstellt` (`erstellt_am`),
  CONSTRAINT `lg_kontaktanfragen_ibfk_1` FOREIGN KEY (`lagerraum_id`) REFERENCES `lg_lagerraeume` (`lagerraum_id`) ON DELETE CASCADE,
  CONSTRAINT `lg_kontaktanfragen_ibfk_2` FOREIGN KEY (`interessent_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: lg_lagerraeume
CREATE TABLE `lg_lagerraeume` (
  `lagerraum_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `anbieter_id` int(10) unsigned NOT NULL,
  `adresse_id` int(10) unsigned NOT NULL,
  `anzahl_raeume` tinyint(3) unsigned DEFAULT 1,
  `qm_gesamt` decimal(8,2) NOT NULL,
  `qm_pro_raum` decimal(8,2) DEFAULT NULL,
  `hoehe_meter` decimal(4,2) DEFAULT NULL,
  `preis_pro_qm` decimal(8,2) NOT NULL,
  `preis_gesamt` decimal(10,2) GENERATED ALWAYS AS (`qm_gesamt` * `preis_pro_qm`) STORED,
  `beheizt` tinyint(1) DEFAULT 0,
  `klimatisiert` tinyint(1) DEFAULT 0,
  `strom_vorhanden` tinyint(1) DEFAULT 1,
  `wasser_vorhanden` tinyint(1) DEFAULT 0,
  `rampe_vorhanden` tinyint(1) DEFAULT 0,
  `rolltor` tinyint(1) DEFAULT 0,
  `zugang_24_7` tinyint(1) DEFAULT 0,
  `alarm_vorhanden` tinyint(1) DEFAULT 0,
  `video_ueberwachung` tinyint(1) DEFAULT 0,
  `verfuegbar_ab` date DEFAULT NULL,
  `mindestmietdauer_monate` tinyint(3) unsigned DEFAULT 1,
  `bemerkung_de` text DEFAULT NULL,
  `bemerkung_en` text DEFAULT NULL,
  `typ` enum('angebot','nachfrage') DEFAULT 'angebot',
  `aktiv` tinyint(1) DEFAULT 1,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  `aktualisiert_am` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`lagerraum_id`),
  KEY `anbieter_id` (`anbieter_id`),
  KEY `adresse_id` (`adresse_id`),
  KEY `idx_typ` (`typ`),
  KEY `idx_aktiv` (`aktiv`),
  KEY `idx_preis` (`preis_pro_qm`),
  KEY `idx_qm` (`qm_gesamt`),
  KEY `idx_verfuegbar` (`verfuegbar_ab`),
  KEY `idx_created` (`erstellt_am`),
  CONSTRAINT `lg_lagerraeume_ibfk_1` FOREIGN KEY (`anbieter_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `lg_lagerraeume_ibfk_2` FOREIGN KEY (`adresse_id`) REFERENCES `lg_adressen` (`adresse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: lg_sessions
CREATE TABLE `lg_sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `lg_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: lg_suchanfragen
CREATE TABLE `lg_suchanfragen` (
  `anfrage_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `suchender_id` int(10) unsigned NOT NULL,
  `plz_von` varchar(10) DEFAULT NULL,
  `plz_bis` varchar(10) DEFAULT NULL,
  `umkreis_km` smallint(5) unsigned DEFAULT NULL,
  `ort_wunsch_de` varchar(100) DEFAULT NULL,
  `ort_wunsch_en` varchar(100) DEFAULT NULL,
  `land_wunsch` varchar(3) DEFAULT 'DE',
  `qm_min` decimal(8,2) DEFAULT NULL,
  `qm_max` decimal(8,2) DEFAULT NULL,
  `preis_max` decimal(8,2) DEFAULT NULL,
  `anzahl_raeume_min` tinyint(3) unsigned DEFAULT 1,
  `beheizt_gewuenscht` tinyint(1) DEFAULT 0,
  `zugang_24_7_gewuenscht` tinyint(1) DEFAULT 0,
  `aktiv` tinyint(1) DEFAULT 1,
  `bemerkung_en` text DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  `aktualisiert_am` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`anfrage_id`),
  KEY `suchender_id` (`suchender_id`),
  KEY `idx_aktiv` (`aktiv`),
  KEY `idx_plz` (`plz_von`,`plz_bis`),
  KEY `idx_ort` (`ort_wunsch_de`),
  KEY `idx_qm` (`qm_min`,`qm_max`),
  KEY `idx_preis` (`preis_max`),
  KEY `idx_land` (`land_wunsch`),
  CONSTRAINT `lg_suchanfragen_ibfk_1` FOREIGN KEY (`suchender_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: lg_users
CREATE TABLE `lg_users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `oauth_provider` varchar(20) DEFAULT NULL,
  `oauth_id` varchar(255) DEFAULT NULL,
  `telefon` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  `aktualisiert_am` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `aktiv` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `idx_oauth` (`oauth_provider`,`oauth_id`),
  KEY `idx_email` (`email`),
  KEY `idx_aktiv` (`aktiv`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: lg_v_nachfragen
CREATE ALGORITHM=UNDEFINED DEFINER=`gh`@`localhost` SQL SECURITY DEFINER VIEW `lg_v_nachfragen` AS select `s`.`anfrage_id` AS `anfrage_id`,`s`.`land_wunsch` AS `land_wunsch`,`s`.`plz_von` AS `plz_von`,`s`.`plz_bis` AS `plz_bis`,`s`.`ort_wunsch` AS `ort_wunsch`,`s`.`umkreis_km` AS `umkreis_km`,`s`.`qm_min` AS `qm_min`,`s`.`qm_max` AS `qm_max`,`s`.`preis_max` AS `preis_max`,`s`.`beheizt_gewuenscht` AS `beheizt_gewuenscht`,`s`.`zugang_24_7_gewuenscht` AS `zugang_24_7_gewuenscht`,`u`.`email` AS `email`,`u`.`telefon` AS `telefon`,`u`.`name` AS `suchender_name`,`s`.`erstellt_am` AS `erstellt_am` from (`lg_suchanfragen` `s` join `lg_users` `u` on(`s`.`suchender_id` = `u`.`user_id`)) where `s`.`aktiv` = 1 and `u`.`aktiv` = 1 order by `s`.`erstellt_am` desc;

