-- Add last_edited_by column to docs_ctlg table to track who last modified each document
ALTER TABLE gh3sp_docs_ctlg ADD COLUMN `last_edited_by` INT(11) DEFAULT NULL AFTER `adm`;

-- Add index for better performance on last_edited_by lookups
ALTER TABLE gh3sp_docs_ctlg ADD INDEX `idx_last_edited_by` (`last_edited_by`);

-- Update existing records to set last_edited_by same as adm (creator) initially
UPDATE gh3sp_docs_ctlg SET `last_edited_by` = `adm` WHERE `last_edited_by` IS NULL;
