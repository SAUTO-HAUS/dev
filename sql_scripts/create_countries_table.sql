-- Create countries table
CREATE TABLE IF NOT EXISTS countries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  code VARCHAR(2) NOT NULL,
  flag VARCHAR(100) NULL,
  is_european TINYINT(1) DEFAULT 0
);

-- Insert European countries
INSERT INTO countries (name, code, flag, is_european) VALUES
('Austria', 'AT', 'at.svg', 1),
('Belgium', 'BE', 'be.svg', 1),
('Bulgaria', 'BG', 'bg.svg', 1),
('Croatia', 'HR', 'hr.svg', 1),
('Cyprus', 'CY', 'cy.svg', 1),
('Czech Republic', 'CZ', 'cz.svg', 1),
('Denmark', 'DK', 'dk.svg', 1),
('Estonia', 'EE', 'ee.svg', 1),
('Europe', 'EU', 'eu.svg', 1),
('Finland', 'FI', 'fi.svg', 1),
('France', 'FR', 'fr.svg', 1),
('Germany', 'DE', 'de.svg', 1),
('Greece', 'GR', 'gr.svg', 1),
('Hungary', 'HU', 'hu.svg', 1),
('Ireland', 'IE', 'ie.svg', 1),
('Italy', 'IT', 'it.svg', 1),
('Latvia', 'LV', 'lv.svg', 1),
('Lithuania', 'LT', 'lt.svg', 1),
('Luxembourg', 'LU', 'lu.svg', 1),
('Malta', 'MT', 'mt.svg', 1),
('Netherlands', 'NL', 'nl.svg', 1),
('Poland', 'PL', 'pl.svg', 1),
('Portugal', 'PT', 'pt.svg', 1),
('Moldova', 'MD', 'md.svg', 1),
('Romania', 'RO', 'ro.svg', 1),
('Slovakia', 'SK', 'sk.svg', 1),
('Slovenia', 'SI', 'si.svg', 1),
('Spain', 'ES', 'es.svg', 1),
('Sweden', 'SE', 'se.svg', 1),
('United Kingdom', 'GB', 'gb.svg', 1),
('United States', 'US', 'us.svg', 1);


-- Insert some non-European countries
INSERT INTO countries (name, code, flag, is_european) VALUES
('Canada', 'CA', 'ca.svg', 0),
('Japan', 'JP', 'jp.svg', 0),
('China', 'CN', 'cn.svg', 0),
('Australia', 'AU', 'au.svg', 0),
('Brazil', 'BR', 'br.svg', 0),
('India', 'IN', 'in.svg', 0),
('South Africa', 'ZA', 'za.svg', 0),
('Mexico', 'MX', 'mx.svg', 0),
('Russia', 'RU', 'ru.svg', 0);
