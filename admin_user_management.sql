-- 添加 actif 字段到 compte 表（用于停用/启用账户）
-- 使用存储过程安全地添加字段

DELIMITER $$

DROP PROCEDURE IF EXISTS AddActifColumnIfNotExists$$
CREATE PROCEDURE AddActifColumnIfNotExists()
BEGIN
    DECLARE columnExists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO columnExists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'compte'
      AND COLUMN_NAME = 'actif';
    
    IF columnExists = 0 THEN
        ALTER TABLE `compte` ADD COLUMN `actif` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=actif, 0=désactivé';
        ALTER TABLE `compte` ADD INDEX `idx_actif` (`actif`);
    END IF;
END$$

DELIMITER ;

-- 执行存储过程
CALL AddActifColumnIfNotExists();

-- 清理存储过程
DROP PROCEDURE IF EXISTS AddActifColumnIfNotExists;

