-- Region landing-page traffic (/ordercars/korea|europe|usa|china).
-- One row per view; the report aggregates views and distinct visitors.
-- Safe to run more than once.

CREATE TABLE IF NOT EXISTS `gh3sp_region_views` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `region`     VARCHAR(16)  NOT NULL,
  `lang`       VARCHAR(5)   NOT NULL DEFAULT '',
  -- sha256(ip + user agent + daily salt): lets us count people without storing IPs.
  `visitor`    CHAR(64)     NOT NULL,
  `created_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `region_created` (`region`, `created_at`),
  KEY `created` (`created_at`),
  KEY `visitor_created` (`visitor`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
