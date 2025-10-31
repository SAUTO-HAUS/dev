-- Add columns for Romanian and Russian country names
ALTER TABLE countries 
ADD COLUMN name_ro VARCHAR(100) NULL AFTER name,
ADD COLUMN name_ru VARCHAR(100) NULL AFTER name_ro;

-- Rename the existing name column to name_en for clarity
ALTER TABLE countries 
CHANGE COLUMN name name_en VARCHAR(100) NOT NULL;

-- Update with Romanian names
UPDATE countries SET name_ro = 'Austria' WHERE code = 'AT';
UPDATE countries SET name_ro = 'Belgia' WHERE code = 'BE';
UPDATE countries SET name_ro = 'Bulgaria' WHERE code = 'BG';
UPDATE countries SET name_ro = 'Croația' WHERE code = 'HR';
UPDATE countries SET name_ro = 'Cipru' WHERE code = 'CY';
UPDATE countries SET name_ro = 'Republica Cehă' WHERE code = 'CZ';
UPDATE countries SET name_ro = 'Danemarca' WHERE code = 'DK';
UPDATE countries SET name_ro = 'Estonia' WHERE code = 'EE';
UPDATE countries SET name_ro = 'Europa' WHERE code = 'EU';
UPDATE countries SET name_ro = 'Finlanda' WHERE code = 'FI';
UPDATE countries SET name_ro = 'Franța' WHERE code = 'FR';
UPDATE countries SET name_ro = 'Germania' WHERE code = 'DE';
UPDATE countries SET name_ro = 'Grecia' WHERE code = 'GR';
UPDATE countries SET name_ro = 'Ungaria' WHERE code = 'HU';
UPDATE countries SET name_ro = 'Irlanda' WHERE code = 'IE';
UPDATE countries SET name_ro = 'Italia' WHERE code = 'IT';
UPDATE countries SET name_ro = 'Letonia' WHERE code = 'LV';
UPDATE countries SET name_ro = 'Lituania' WHERE code = 'LT';
UPDATE countries SET name_ro = 'Luxemburg' WHERE code = 'LU';
UPDATE countries SET name_ro = 'Malta' WHERE code = 'MT';
UPDATE countries SET name_ro = 'Olanda' WHERE code = 'NL';
UPDATE countries SET name_ro = 'Polonia' WHERE code = 'PL';
UPDATE countries SET name_ro = 'Portugalia' WHERE code = 'PT';
UPDATE countries SET name_ro = 'România' WHERE code = 'RO';
UPDATE countries SET name_ro = 'Slovacia' WHERE code = 'SK';
UPDATE countries SET name_ro = 'Slovenia' WHERE code = 'SI';
UPDATE countries SET name_ro = 'Spania' WHERE code = 'ES';
UPDATE countries SET name_ro = 'Suedia' WHERE code = 'SE';
UPDATE countries SET name_ro = 'Marea Britanie' WHERE code = 'GB';
UPDATE countries SET name_ro = 'Moldova' WHERE code = 'MD';
UPDATE countries SET name_ro = 'Elveția' WHERE code = 'CH';
UPDATE countries SET name_ro = 'Norvegia' WHERE code = 'NO';
UPDATE countries SET name_ro = 'SUA' WHERE code = 'US';


-- Update with Russian names
UPDATE countries SET name_ru = 'Австрия' WHERE code = 'AT';
UPDATE countries SET name_ru = 'Бельгия' WHERE code = 'BE';
UPDATE countries SET name_ru = 'Болгария' WHERE code = 'BG';
UPDATE countries SET name_ru = 'Хорватия' WHERE code = 'HR';
UPDATE countries SET name_ru = 'Кипр' WHERE code = 'CY';
UPDATE countries SET name_ru = 'Чехия' WHERE code = 'CZ';
UPDATE countries SET name_ru = 'Дания' WHERE code = 'DK';
UPDATE countries SET name_ru = 'Эстония' WHERE code = 'EE';
UPDATE countries SET name_ru = 'Европа' WHERE code = 'EU';
UPDATE countries SET name_ru = 'Финляндия' WHERE code = 'FI';
UPDATE countries SET name_ru = 'Франция' WHERE code = 'FR';
UPDATE countries SET name_ru = 'Германия' WHERE code = 'DE';
UPDATE countries SET name_ru = 'Греция' WHERE code = 'GR';
UPDATE countries SET name_ru = 'Венгрия' WHERE code = 'HU';
UPDATE countries SET name_ru = 'Ирландия' WHERE code = 'IE';
UPDATE countries SET name_ru = 'Италия' WHERE code = 'IT';
UPDATE countries SET name_ru = 'Латвия' WHERE code = 'LV';
UPDATE countries SET name_ru = 'Литва' WHERE code = 'LT';
UPDATE countries SET name_ru = 'Люксембург' WHERE code = 'LU';
UPDATE countries SET name_ru = 'Мальта' WHERE code = 'MT';
UPDATE countries SET name_ru = 'Нидерланды' WHERE code = 'NL';
UPDATE countries SET name_ru = 'Польша' WHERE code = 'PL';
UPDATE countries SET name_ru = 'Португалия' WHERE code = 'PT';
UPDATE countries SET name_ru = 'Румыния' WHERE code = 'RO';
UPDATE countries SET name_ru = 'Словакия' WHERE code = 'SK';
UPDATE countries SET name_ru = 'Словения' WHERE code = 'SI';
UPDATE countries SET name_ru = 'Испания' WHERE code = 'ES';
UPDATE countries SET name_ru = 'Швеция' WHERE code = 'SE';
UPDATE countries SET name_ru = 'Великобритания' WHERE code = 'GB';
UPDATE countries SET name_ru = 'Молдова' WHERE code = 'MD';
UPDATE countries SET name_ru = 'Швейцария' WHERE code = 'CH';
UPDATE countries SET name_ru = 'Норвегия' WHERE code = 'NO';
UPDATE countries SET name_ru = 'США' WHERE code = 'US';

