-- SQL de création de la base de données pour la plateforme de déménagement

-- Remarque: ajustez le nom de la base si nécessaire
CREATE DATABASE IF NOT EXISTS `bdd` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `bdd`;

-- Table des comptes utilisateurs
-- role: 0=non activé, 1=Utilisateur (Client), 2=Déménageur (Service de déménagement), 3=Administrateur
CREATE TABLE IF NOT EXISTS `compte` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` TINYINT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des annonces de déménagement (créées par des clients)
CREATE TABLE IF NOT EXISTS `annonce` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` INT UNSIGNED NOT NULL,
  `titre` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `date_debut` DATETIME NOT NULL,
  `ville_depart` VARCHAR(120) NOT NULL,
  `ville_arrivee` VARCHAR(120) NOT NULL,
  `depart_type` ENUM('maison','appartement') NOT NULL,
  `depart_etage` TINYINT DEFAULT NULL,
  `depart_ascenseur` TINYINT(1) NOT NULL DEFAULT 0,
  `arrivee_type` ENUM('maison','appartement') NOT NULL,
  `arrivee_etage` TINYINT DEFAULT NULL,
  `arrivee_ascenseur` TINYINT(1) NOT NULL DEFAULT 0,
  `volume_m3` DECIMAL(6,2) DEFAULT NULL,
  `poids_kg` INT DEFAULT NULL,
  `nb_demenageurs` TINYINT DEFAULT NULL,
  `statut` ENUM('brouillon','publie','cloture') NOT NULL DEFAULT 'publie',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  CONSTRAINT `fk_annonce_client` FOREIGN KEY (`client_id`) REFERENCES `compte`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des images liées aux annonces
CREATE TABLE IF NOT EXISTS `annonce_image` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `annonce_id` INT UNSIGNED NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_annonce_img` (`annonce_id`),
  CONSTRAINT `fk_image_annonce` FOREIGN KEY (`annonce_id`) REFERENCES `annonce`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des offres/prix proposés par les déménageurs
CREATE TABLE IF NOT EXISTS `offre` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `annonce_id` INT UNSIGNED NOT NULL,
  `demenageur_id` INT UNSIGNED NOT NULL,
  `prix_eur` DECIMAL(10,2) NOT NULL,
  `message` TEXT,
  `etat` ENUM('propose','accepte','refuse') NOT NULL DEFAULT 'propose',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_offre_annonce` (`annonce_id`),
  KEY `idx_offre_demenageur` (`demenageur_id`),
  CONSTRAINT `fk_offre_annonce` FOREIGN KEY (`annonce_id`) REFERENCES `annonce`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_offre_demenageur` FOREIGN KEY (`demenageur_id`) REFERENCES `compte`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (Bonus) Table de questions/réponses entre déménageurs et clients
CREATE TABLE IF NOT EXISTS `qa` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `annonce_id` INT UNSIGNED NOT NULL,
  `auteur_id` INT UNSIGNED NOT NULL,
  `contenu` TEXT NOT NULL,
  `type` ENUM('question','reponse') NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_qa_annonce` (`annonce_id`),
  CONSTRAINT `fk_qa_annonce` FOREIGN KEY (`annonce_id`) REFERENCES `annonce`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qa_auteur` FOREIGN KEY (`auteur_id`) REFERENCES `compte`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (Bonus) Table d'évaluations des déménageurs
CREATE TABLE IF NOT EXISTS `evaluation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `offre_id` INT UNSIGNED NOT NULL,
  `client_id` INT UNSIGNED NOT NULL,
  `demenageur_id` INT UNSIGNED NOT NULL,
  `note` TINYINT NOT NULL CHECK (`note` BETWEEN 1 AND 5),
  `commentaire` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_eval_offre` (`offre_id`),
  CONSTRAINT `fk_eval_offre` FOREIGN KEY (`offre_id`) REFERENCES `offre`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


