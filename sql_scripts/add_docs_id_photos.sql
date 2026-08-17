ALTER TABLE `gh3sp_docs_u`
    ADD COLUMN `id_photo_front` VARCHAR(64) DEFAULT NULL COMMENT 'buletin recto, file in /media/docs_id',
    ADD COLUMN `id_photo_back`  VARCHAR(64) DEFAULT NULL COMMENT 'buletin verso',
    ADD COLUMN `id_photo_at`    DATETIME    DEFAULT NULL COMMENT 'last upload';
