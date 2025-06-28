-- Add import_country_id column to cars table
-- Simple version that avoids information_schema queries

-- Try to add the column - will fail silently if it already exists
ALTER TABLE cars ADD COLUMN import_country_id INT NULL;

-- Try to add the foreign key constraint - will fail silently if it already exists
-- or if the column doesn't exist
ALTER TABLE cars ADD CONSTRAINT fk_car_import_country 
FOREIGN KEY (import_country_id) REFERENCES countries(id);
