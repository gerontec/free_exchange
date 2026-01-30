-- ============================================================================
-- EDELMETALL-BÖRSE (Precious Metals Exchange) - Tabellenstruktur
-- Prefix: em_ (Edelmetalle / Precious Metals)
-- Unterstützt: Gold, Silber mit Einheiten kg, g, oz
-- Märkte: London, New York, Shanghai
-- ============================================================================

-- Object: em_metals
-- Edelmetalle (Gold, Silber, Platin, Palladium)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_units
-- Maßeinheiten für Edelmetalle
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_markets
-- Handelsplätze (London, New York, Shanghai)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_listings
-- Kauf- und Verkaufsangebote für Edelmetalle
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_prices
-- Preis-Historie für Edelmetalle (Real-time & Fixing)
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

-- Object: em_current_prices
-- Aktuelle Preise (View oder Materialized Table für schnellen Zugriff)
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
  UNIQUE KEY `unique_current_price` (`metal_id`,`market_id`,`unit_id`,`price_type`),
  KEY `metal_id` (`metal_id`),
  KEY `market_id` (`market_id`),
  KEY `unit_id` (`unit_id`),
  KEY `idx_updated` (`updated_at`),
  CONSTRAINT `em_current_prices_ibfk_1` FOREIGN KEY (`metal_id`) REFERENCES `em_metals` (`metal_id`) ON DELETE CASCADE,
  CONSTRAINT `em_current_prices_ibfk_2` FOREIGN KEY (`market_id`) REFERENCES `em_markets` (`market_id`) ON DELETE CASCADE,
  CONSTRAINT `em_current_prices_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `em_units` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Object: em_transactions
-- Abgeschlossene Transaktionen
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

-- Object: em_images
-- Bilder für Edelmetall-Angebote
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

-- Object: em_favorites
-- Favoriten/Watchlist für Edelmetall-Angebote
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

-- Object: em_messages
-- Nachrichten zwischen Käufern und Verkäufern
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

-- Object: em_ratings
-- Bewertungen für Transaktionen
CREATE TABLE `em_ratings` (
  `rating_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint(20) unsigned NOT NULL,
  `rater_id` int(10) unsigned NOT NULL,
  `rated_user_id` int(10) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `transaction_type` enum('verkauf','kauf','tausch') NOT NULL,
  `comment` text DEFAULT NULL,
  `communication_rating` tinyint(3) unsigned DEFAULT NULL CHECK (`communication_rating` BETWEEN 1 AND 5),
  `product_rating` tinyint(3) unsigned DEFAULT NULL CHECK (`product_rating` BETWEEN 1 AND 5),
  `shipping_rating` tinyint(3) unsigned DEFAULT NULL CHECK (`shipping_rating` BETWEEN 1 AND 5),
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

-- Object: em_price_alerts
-- Preis-Benachrichtigungen für User
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

-- ============================================================================
-- VIEWS für einfacheren Zugriff
-- ============================================================================

-- Object: em_v_active_listings
-- Aktive Angebote mit allen relevanten Details
CREATE VIEW `em_v_active_listings` AS
SELECT
    l.listing_id,
    l.user_id AS seller_id,
    l.listing_type,
    l.quantity,
    l.price_per_unit,
    l.total_price,
    l.currency_code,
    l.purity,
    l.form,
    l.manufacturer,
    l.title_de,
    l.description_de,
    l.aktiv,
    l.sold,
    l.erstellt_am,
    m.symbol AS metal_symbol,
    m.name_de AS metal_name,
    u.code AS unit_code,
    u.name_de AS unit_name,
    user.name AS seller_name,
    user.email AS seller_email,
    a.plz,
    a.ort,
    a.land,
    (SELECT COUNT(*) FROM em_images WHERE em_images.listing_id = l.listing_id) AS image_count,
    (SELECT AVG(rating) FROM em_ratings WHERE em_ratings.rated_user_id = l.user_id) AS seller_rating,
    DATEDIFF(NOW(), l.erstellt_am) AS tage_alt
FROM em_listings l
JOIN em_metals m ON l.metal_id = m.metal_id
JOIN em_units u ON l.unit_id = u.unit_id
JOIN lg_users user ON l.user_id = user.user_id
LEFT JOIN lg_adressen a ON l.adresse_id = a.adresse_id
WHERE l.aktiv = 1 AND l.sold = 0
ORDER BY l.erstellt_am DESC;

-- Object: em_v_latest_prices
-- Aktuellste Preise für alle Metalle und Märkte
CREATE VIEW `em_v_latest_prices` AS
SELECT
    cp.current_price_id,
    m.symbol AS metal_symbol,
    m.name_de AS metal_name,
    mk.code AS market_code,
    mk.name AS market_name,
    mk.city AS market_city,
    u.code AS unit_code,
    cp.price_type,
    cp.price,
    cp.currency_code,
    cp.bid,
    cp.ask,
    cp.change_24h,
    cp.change_percent_24h,
    cp.high_24h,
    cp.low_24h,
    cp.updated_at
FROM em_current_prices cp
JOIN em_metals m ON cp.metal_id = m.metal_id
JOIN em_markets mk ON cp.market_id = mk.market_id
JOIN em_units u ON cp.unit_id = u.unit_id
WHERE m.aktiv = 1 AND mk.aktiv = 1
ORDER BY m.sort_order, mk.code, cp.price_type;

-- Object: em_v_user_transactions
-- User-Transaktionsübersicht
CREATE VIEW `em_v_user_transactions` AS
SELECT
    t.transaction_id,
    t.transaction_type,
    t.quantity,
    t.price_per_unit,
    t.total_price,
    t.currency_code,
    t.payment_status,
    t.delivery_status,
    t.erstellt_am,
    m.symbol AS metal_symbol,
    m.name_de AS metal_name,
    u.code AS unit_code,
    seller.name AS seller_name,
    buyer.name AS buyer_name,
    l.title_de AS listing_title
FROM em_transactions t
JOIN em_metals m ON t.metal_id = m.metal_id
JOIN em_units u ON t.unit_id = u.unit_id
JOIN lg_users seller ON t.seller_id = seller.user_id
JOIN lg_users buyer ON t.buyer_id = buyer.user_id
JOIN em_listings l ON t.listing_id = l.listing_id
ORDER BY t.erstellt_am DESC;

-- ============================================================================
-- STAMMDATEN (Initial Data)
-- ============================================================================

-- Edelmetalle einfügen
INSERT INTO `em_metals` (`symbol`, `name_de`, `name_en`, `description_de`, `description_en`, `atomic_number`, `density_g_cm3`, `sort_order`, `aktiv`) VALUES
('XAU', 'Gold', 'Gold', 'Gold ist ein chemisches Element mit dem Elementsymbol Au und der Ordnungszahl 79.', 'Gold is a chemical element with the symbol Au and atomic number 79.', 79, 19.320, 1, 1),
('XAG', 'Silber', 'Silver', 'Silber ist ein chemisches Element mit dem Elementsymbol Ag und der Ordnungszahl 47.', 'Silver is a chemical element with the symbol Ag and atomic number 47.', 47, 10.490, 2, 1),
('XPT', 'Platin', 'Platinum', 'Platin ist ein chemisches Element mit dem Elementsymbol Pt und der Ordnungszahl 78.', 'Platinum is a chemical element with the symbol Pt and atomic number 78.', 78, 21.450, 3, 1),
('XPD', 'Palladium', 'Palladium', 'Palladium ist ein chemisches Element mit dem Elementsymbol Pd und der Ordnungszahl 46.', 'Palladium is a chemical element with the symbol Pd and atomic number 46.', 46, 12.020, 4, 1);

-- Einheiten einfügen
INSERT INTO `em_units` (`code`, `name_de`, `name_en`, `grams_per_unit`, `sort_order`, `aktiv`) VALUES
('kg', 'Kilogramm', 'Kilogram', 1000.000000, 1, 1),
('g', 'Gramm', 'Gram', 1.000000, 2, 1),
('oz', 'Feinunze (Troy)', 'Troy Ounce', 31.103476, 3, 1);

-- Märkte einfügen
INSERT INTO `em_markets` (`code`, `name`, `city`, `country`, `timezone`, `currency_code`, `fixing_times`, `trading_hours`, `website`, `aktiv`) VALUES
('LBMA', 'London Bullion Market Association', 'London', 'GBR', 'Europe/London', 'USD', '["10:30", "15:00"]', 'Mo-Fr 08:00-16:30 GMT', 'https://www.lbma.org.uk', 1),
('COMEX', 'COMEX (New York)', 'New York', 'USA', 'America/New_York', 'USD', '["13:30"]', 'Mo-Fr 08:20-13:30 EST', 'https://www.cmegroup.com', 1),
('SGE', 'Shanghai Gold Exchange', 'Shanghai', 'CHN', 'Asia/Shanghai', 'CNY', '["10:15", "14:15"]', 'Mo-Fr 09:00-15:30 CST', 'https://www.sge.com.cn', 1);

