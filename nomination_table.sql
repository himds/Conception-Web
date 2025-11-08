-- Table des nominations (指名)
CREATE TABLE IF NOT EXISTS `nomination` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `annonce_id` INT UNSIGNED NOT NULL,
  `demenageur_id` INT UNSIGNED NOT NULL,
  `etat` ENUM('en_attente','accepte','refuse') NOT NULL DEFAULT 'en_attente',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nom_annonce` (`annonce_id`),
  KEY `idx_nom_demenageur` (`demenageur_id`),
  CONSTRAINT `fk_nom_annonce` FOREIGN KEY (`annonce_id`) REFERENCES `annonce`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_nom_demenageur` FOREIGN KEY (`demenageur_id`) REFERENCES `compte`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uniq_nom_annonce_demenageur` (`annonce_id`, `demenageur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des messages entre client et déménageur (基于指名)
CREATE TABLE IF NOT EXISTS `message` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nomination_id` INT UNSIGNED NOT NULL,
  `expediteur_id` INT UNSIGNED NOT NULL,
  `contenu` TEXT NOT NULL,
  `lu` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_msg_nomination` (`nomination_id`),
  KEY `idx_msg_expediteur` (`expediteur_id`),
  CONSTRAINT `fk_msg_nomination` FOREIGN KEY (`nomination_id`) REFERENCES `nomination`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_expediteur` FOREIGN KEY (`expediteur_id`) REFERENCES `compte`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 修改offre表，添加nomination_id字段（如果还没有）
-- 注意：MySQL不支持ALTER TABLE ... ADD COLUMN IF NOT EXISTS
-- 使用存储过程来安全地添加列、索引和约束

DELIMITER $$

-- 存储过程：安全地添加列到offre表
DROP PROCEDURE IF EXISTS AddColumnIfNotExists$$
CREATE PROCEDURE AddColumnIfNotExists(
    IN dbName VARCHAR(64),
    IN tableName VARCHAR(64),
    IN columnName VARCHAR(64),
    IN columnDefinition TEXT
)
BEGIN
    DECLARE columnExists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO columnExists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = dbName
      AND TABLE_NAME = tableName
      AND COLUMN_NAME = columnName;
    
    IF columnExists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD COLUMN `', columnName, '` ', columnDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

-- 存储过程：安全地添加索引
DROP PROCEDURE IF EXISTS AddIndexIfNotExists$$
CREATE PROCEDURE AddIndexIfNotExists(
    IN dbName VARCHAR(64),
    IN tableName VARCHAR(64),
    IN indexName VARCHAR(64),
    IN indexDefinition TEXT
)
BEGIN
    DECLARE indexExists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO indexExists
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = dbName
      AND TABLE_NAME = tableName
      AND INDEX_NAME = indexName;
    
    IF indexExists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD ', indexDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

-- 存储过程：安全地添加外键约束
DROP PROCEDURE IF EXISTS AddForeignKeyIfNotExists$$
CREATE PROCEDURE AddForeignKeyIfNotExists(
    IN dbName VARCHAR(64),
    IN tableName VARCHAR(64),
    IN constraintName VARCHAR(64),
    IN foreignKeyDefinition TEXT
)
BEGIN
    DECLARE constraintExists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO constraintExists
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = dbName
      AND TABLE_NAME = tableName
      AND CONSTRAINT_NAME = constraintName;
    
    IF constraintExists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD CONSTRAINT `', constraintName, '` ', foreignKeyDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- 使用存储过程添加offre表的nomination_id列
CALL AddColumnIfNotExists(DATABASE(), 'offre', 'nomination_id', 'INT UNSIGNED DEFAULT NULL');
CALL AddIndexIfNotExists(DATABASE(), 'offre', 'idx_offre_nomination', 'KEY `idx_offre_nomination` (`nomination_id`)');
CALL AddForeignKeyIfNotExists(DATABASE(), 'offre', 'fk_offre_nomination', 'FOREIGN KEY (`nomination_id`) REFERENCES `nomination`(`id`) ON DELETE SET NULL');

-- 使用存储过程添加evaluation表的nomination_id列
CALL AddColumnIfNotExists(DATABASE(), 'evaluation', 'nomination_id', 'INT UNSIGNED DEFAULT NULL');
CALL AddIndexIfNotExists(DATABASE(), 'evaluation', 'idx_eval_nomination', 'KEY `idx_eval_nomination` (`nomination_id`)');
CALL AddForeignKeyIfNotExists(DATABASE(), 'evaluation', 'fk_eval_nomination', 'FOREIGN KEY (`nomination_id`) REFERENCES `nomination`(`id`) ON DELETE SET NULL');

-- 清理存储过程（可选）
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;
DROP PROCEDURE IF EXISTS AddIndexIfNotExists;
DROP PROCEDURE IF EXISTS AddForeignKeyIfNotExists;


