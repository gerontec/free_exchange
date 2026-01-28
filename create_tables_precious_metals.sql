-- ============================================================
-- Precious Metals Exchange - Database Schema
-- ============================================================

-- Object: em_metals (Reference table for precious metals types)
CREATE TABLE `em_metals` (
  `metal_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `symbol` varchar(10) NOT NULL COMMENT 'Chemical symbol (Au, Ag, Pt, Pd)',
  `name_de` varchar(50) NOT NULL,
  `name_en` varchar(50) NOT NULL,
  `description_de` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `density` decimal(6,3) DEFAULT NULL COMMENT 'Density in g/cm³',
  `melting_point` decimal(7,2) DEFAULT NULL COMMENT 'Melting point in °C',
  `sort_order` int(11) DEFAULT 0,
  `aktiv` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`metal_id`),
  UNIQUE KEY `symbol` (`symbol`),
  KEY `idx_aktiv` (`aktiv`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_units (Units of measurement for precious metals)
CREATE TABLE `em_units` (
  `unit_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unit_code` varchar(10) NOT NULL COMMENT 'g, kg, oz, toz (troy ounce)',
  `name_de` varchar(50) NOT NULL,
  `name_en` varchar(50) NOT NULL,
  `grams_equivalent` decimal(12,6) NOT NULL COMMENT 'Conversion to grams',
  `is_troy` tinyint(1) DEFAULT 0 COMMENT 'Troy weight system',
  `aktiv` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`unit_id`),
  UNIQUE KEY `unit_code` (`unit_code`),
  KEY `idx_aktiv` (`aktiv`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_markets (Market price reference data)
CREATE TABLE `em_markets` (
  `market_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `metal_id` int(10) unsigned NOT NULL,
  `market_name` varchar(50) NOT NULL COMMENT 'LBMA, COMEX, SGE, etc.',
  `currency_code` varchar(3) DEFAULT 'USD',
  `price_per_oz` decimal(12,2) DEFAULT NULL COMMENT 'Spot price per troy ounce',
  `price_updated_at` timestamp NULL DEFAULT NULL,
  `aktiv` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`market_id`),
  KEY `metal_id` (`metal_id`),
  KEY `idx_market` (`market_name`),
  KEY `idx_updated` (`price_updated_at`),
  CONSTRAINT `em_markets_ibfk_1` FOREIGN KEY (`metal_id`) REFERENCES `em_metals` (`metal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_listings (Main listings table for precious metals)
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
  `purity` decimal(6,3) DEFAULT 999.900 COMMENT 'Feingehalt z.B. 999.9 für 24k Gold',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_images (Images for precious metals listings)
CREATE TABLE `em_images` (
  `image_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` int(10) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(500) NOT NULL,
  `image_type` enum('main','detail','certificate','serial','packaging','assay') DEFAULT 'detail',
  `sort_order` tinyint(3) unsigned DEFAULT 0,
  `caption` varchar(255) DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `idx_listing` (`listing_id`),
  KEY `idx_type` (`image_type`),
  KEY `idx_sort` (`sort_order`),
  CONSTRAINT `em_images_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `em_listings` (`listing_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_favorites (User favorites for precious metals listings)
CREATE TABLE `em_favorites` (
  `favorite_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `listing_id` int(10) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`favorite_id`),
  UNIQUE KEY `unique_favorite` (`user_id`,`listing_id`),
  KEY `listing_id` (`listing_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `em_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `em_favorites_ibfk_2` FOREIGN KEY (`listing_id`) REFERENCES `em_listings` (`listing_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_messages (Messages between users about precious metals listings)
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

-- Object: em_ratings (User ratings for precious metals transactions)
CREATE TABLE `em_ratings` (
  `rating_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` int(10) unsigned NOT NULL,
  `rater_id` int(10) unsigned NOT NULL,
  `rated_user_id` int(10) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL CHECK (`rating` between 1 and 5),
  `transaction_type` enum('kauf','verkauf','tausch') NOT NULL,
  `comment` text DEFAULT NULL,
  `communication_rating` tinyint(3) unsigned DEFAULT NULL CHECK (`communication_rating` between 1 and 5),
  `item_condition_rating` tinyint(3) unsigned DEFAULT NULL CHECK (`item_condition_rating` between 1 and 5),
  `authenticity_rating` tinyint(3) unsigned DEFAULT NULL CHECK (`authenticity_rating` between 1 and 5),
  `packaging_rating` tinyint(3) unsigned DEFAULT NULL CHECK (`packaging_rating` between 1 and 5),
  `would_deal_again` tinyint(1) DEFAULT 1,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`rating_id`),
  UNIQUE KEY `unique_rating` (`listing_id`,`rater_id`),
  KEY `rater_id` (`rater_id`),
  KEY `idx_rated_user` (`rated_user_id`),
  KEY `idx_rating` (`rating`),
  CONSTRAINT `em_ratings_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `em_listings` (`listing_id`) ON DELETE CASCADE,
  CONSTRAINT `em_ratings_ibfk_2` FOREIGN KEY (`rater_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `em_ratings_ibfk_3` FOREIGN KEY (`rated_user_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_requests (Buy requests for precious metals)
CREATE TABLE `em_requests` (
  `request_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `buyer_id` int(10) unsigned NOT NULL,
  `metal_id` int(10) unsigned DEFAULT NULL,
  `search_title` varchar(255) DEFAULT NULL,
  `form_wanted` enum('barren','muenzen','granulat','schmuck','other') DEFAULT NULL,
  `quantity_min` decimal(15,6) DEFAULT NULL,
  `quantity_max` decimal(15,6) DEFAULT NULL,
  `unit_id` int(10) unsigned DEFAULT NULL,
  `purity_min` decimal(6,3) DEFAULT NULL COMMENT 'Minimum purity required',
  `budget_min` decimal(12,2) DEFAULT NULL,
  `budget_max` decimal(12,2) DEFAULT NULL,
  `currency_code` varchar(3) DEFAULT 'EUR',
  `manufacturer_preferred` varchar(100) DEFAULT NULL,
  `certification_required` varchar(100) DEFAULT NULL,
  `plz_von` varchar(10) DEFAULT NULL,
  `plz_bis` varchar(10) DEFAULT NULL,
  `umkreis_km` smallint(5) unsigned DEFAULT NULL,
  `shipping_acceptable` tinyint(1) DEFAULT 1,
  `vault_storage_required` tinyint(1) DEFAULT 0,
  `description_de` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `aktiv` tinyint(1) DEFAULT 1,
  `fulfilled` tinyint(1) DEFAULT 0,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  `aktualisiert_am` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `metal_id` (`metal_id`),
  KEY `unit_id` (`unit_id`),
  KEY `idx_form` (`form_wanted`),
  KEY `idx_budget` (`budget_min`,`budget_max`),
  KEY `idx_aktiv` (`aktiv`),
  CONSTRAINT `em_requests_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `lg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `em_requests_ibfk_2` FOREIGN KEY (`metal_id`) REFERENCES `em_metals` (`metal_id`) ON DELETE SET NULL,
  CONSTRAINT `em_requests_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `em_units` (`unit_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_price_history (Track historical prices for market analysis)
CREATE TABLE `em_price_history` (
  `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `metal_id` int(10) unsigned NOT NULL,
  `market_id` int(10) unsigned DEFAULT NULL,
  `price_per_oz` decimal(12,2) NOT NULL,
  `currency_code` varchar(3) DEFAULT 'USD',
  `recorded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `metal_id` (`metal_id`),
  KEY `market_id` (`market_id`),
  KEY `idx_recorded` (`recorded_at`),
  KEY `idx_metal_date` (`metal_id`,`recorded_at`),
  CONSTRAINT `em_price_history_ibfk_1` FOREIGN KEY (`metal_id`) REFERENCES `em_metals` (`metal_id`),
  CONSTRAINT `em_price_history_ibfk_2` FOREIGN KEY (`market_id`) REFERENCES `em_markets` (`market_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_v_active_listings (View for active precious metals listings)
CREATE VIEW `em_v_active_listings` AS
SELECT
  l.`listing_id`,
  l.`user_id`,
  l.`metal_id`,
  l.`listing_type`,
  l.`quantity`,
  l.`unit_id`,
  l.`price_per_unit`,
  l.`currency_code`,
  l.`total_price`,
  l.`purity`,
  l.`form`,
  l.`manufacturer`,
  l.`certification`,
  l.`year_minted`,
  l.`condition_rating`,
  l.`title_de`,
  l.`title_en`,
  l.`description_de`,
  l.`description_en`,
  l.`shipping_possible`,
  l.`shipping_cost`,
  l.`pickup_only`,
  l.`vault_storage_available`,
  l.`price_negotiable`,
  l.`premium_over_spot`,
  l.`erstellt_am`,
  l.`aktualisiert_am`,
  m.`symbol` AS metal_symbol,
  m.`name_de` AS metal_name_de,
  m.`name_en` AS metal_name_en,
  u.`unit_code`,
  u.`name_de` AS unit_name_de,
  u.`name_en` AS unit_name_en,
  usr.`name` AS seller_name,
  usr.`email` AS seller_email,
  a.`plz`,
  a.`ort`,
  a.`land`,
  (SELECT COUNT(*) FROM em_images WHERE em_images.listing_id = l.listing_id) AS image_count,
  (SELECT AVG(em_ratings.rating) FROM em_ratings WHERE em_ratings.rated_user_id = l.user_id) AS seller_rating
FROM em_listings l
JOIN em_metals m ON l.metal_id = m.metal_id
JOIN em_units u ON l.unit_id = u.unit_id
JOIN lg_users usr ON l.user_id = usr.user_id
LEFT JOIN lg_adressen a ON l.adresse_id = a.adresse_id
WHERE l.aktiv = 1 AND l.sold = 0
ORDER BY l.erstellt_am DESC;

-- Object: em_v_user_stats (View for user statistics in precious metals)
CREATE VIEW `em_v_user_stats` AS
SELECT
  u.`user_id`,
  u.`name`,
  u.`email`,
  COUNT(DISTINCT l.listing_id) AS total_listings,
  COUNT(DISTINCT CASE WHEN l.sold = 1 THEN l.listing_id END) AS sold_count,
  AVG(r.rating) AS avg_rating,
  COUNT(DISTINCT r.rating_id) AS rating_count,
  SUM(CASE WHEN l.sold = 1 THEN l.total_price ELSE 0 END) AS total_sales_value
FROM lg_users u
LEFT JOIN em_listings l ON u.user_id = l.user_id
LEFT JOIN em_ratings r ON u.user_id = r.rated_user_id
GROUP BY u.user_id;
