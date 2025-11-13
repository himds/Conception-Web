-- Script SQL pour cr茅er des utilisateurs de test
-- 10 clients (role=1), 10 d茅m茅nageurs (role=2), 1 administrateur (role=3)
-- Mot de passe par d茅faut pour tous: password123

USE `bdd`;

-- 10 Clients (role=1)
INSERT INTO compte (nom, prenom, email, password, role) VALUES
('Dupont', 'Jean', 'client1@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 1),
('Martin', 'Marie', 'client2@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 1),
('Bernard', 'Pierre', 'client3@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 1),
('Dubois', 'Sophie', 'client4@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 1),
('Lefebvre', 'Thomas', 'client5@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 1),
('Moreau', 'Julie', 'client6@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 1),
('Laurent', 'Michel', 'client7@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 1),
('Simon', 'Catherine', 'client8@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 1),
('Michel', 'Philippe', 'client9@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 1),
('Garcia', 'Isabelle', 'client10@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 1);

-- 10 D茅m茅nageurs (role=2)
INSERT INTO compte (nom, prenom, email, password, role) VALUES
('Transport', 'Rapide', 'demenageur1@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 2),
('D茅m茅nagement', 'Express', 'demenageur2@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 2),
('Service', 'Pro', 'demenageur3@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 2),
('Movers', 'France', 'demenageur4@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 2),
('Relocation', 'Expert', 'demenageur5@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 2),
('D茅m茅nage', 'Plus', 'demenageur6@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 2),
('Fast', 'Move', 'demenageur7@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 2),
('Easy', 'Relocation', 'demenageur8@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 2),
('Top', 'Movers', 'demenageur9@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 2),
('Pro', 'D茅m茅nagement', 'demenageur10@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 2);

-- 1 Administrateur (role=3)
INSERT INTO compte (nom, prenom, email, password, role) VALUES
('Admin', 'Syst猫me', 'admin@test.com', '$2y$10$testusersalt000000000upUhGVh505UUClNyeanVEGbLpcaJIU9q', 3);

-- V茅rification
SELECT 
    role,
    CASE 
        WHEN role = 1 THEN 'Client'
        WHEN role = 2 THEN 'D茅m茅nageur'
        WHEN role = 3 THEN 'Administrateur'
        ELSE 'Autre'
    END as type_compte,
    COUNT(*) as nombre
FROM compte
WHERE email LIKE '%@test.com'
GROUP BY role;
