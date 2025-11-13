<?php
// Script PHP pour générer le SQL d'insertion des utilisateurs de test
// Exécuter: php generate_test_users.php > insert_test_users.sql

$password = 'password123';
// Utiliser crypt() avec un salt fixe pour compatibilité avec PHP 5.4
// Le hash généré sera compatible avec password_verify() de PHP 5.5+
$salt = '$2y$10$' . str_pad('testusersalt', 22, '0', STR_PAD_RIGHT);
$password_hash = crypt($password, $salt);

echo "-- Script SQL pour créer des utilisateurs de test\n";
echo "-- 10 clients (role=1), 10 déménageurs (role=2), 1 administrateur (role=3)\n";
echo "-- Mot de passe par défaut pour tous: {$password}\n\n";
echo "USE `bdd`;\n\n";

// 10 Clients
echo "-- 10 Clients (role=1)\n";
echo "INSERT INTO compte (nom, prenom, email, password, role) VALUES\n";
$clients = [
    ['Dupont', 'Jean'],
    ['Martin', 'Marie'],
    ['Bernard', 'Pierre'],
    ['Dubois', 'Sophie'],
    ['Lefebvre', 'Thomas'],
    ['Moreau', 'Julie'],
    ['Laurent', 'Michel'],
    ['Simon', 'Catherine'],
    ['Michel', 'Philippe'],
    ['Garcia', 'Isabelle']
];

for ($i = 0; $i < count($clients); $i++) {
    $client = $clients[$i];
    $email = 'client' . ($i + 1) . '@test.com';
    $comma = $i < count($clients) - 1 ? ',' : ';';
    echo "('{$client[0]}', '{$client[1]}', '{$email}', '{$password_hash}', 1){$comma}\n";
}

echo "\n-- 10 Déménageurs (role=2)\n";
echo "INSERT INTO compte (nom, prenom, email, password, role) VALUES\n";
$demenageurs = [
    ['Transport', 'Rapide'],
    ['Déménagement', 'Express'],
    ['Service', 'Pro'],
    ['Movers', 'France'],
    ['Relocation', 'Expert'],
    ['Déménage', 'Plus'],
    ['Fast', 'Move'],
    ['Easy', 'Relocation'],
    ['Top', 'Movers'],
    ['Pro', 'Déménagement']
];

for ($i = 0; $i < count($demenageurs); $i++) {
    $demenageur = $demenageurs[$i];
    $email = 'demenageur' . ($i + 1) . '@test.com';
    $comma = $i < count($demenageurs) - 1 ? ',' : ';';
    echo "('{$demenageur[0]}', '{$demenageur[1]}', '{$email}', '{$password_hash}', 2){$comma}\n";
}

echo "\n-- 1 Administrateur (role=3)\n";
echo "INSERT INTO compte (nom, prenom, email, password, role) VALUES\n";
echo "('Admin', 'Système', 'admin@test.com', '{$password_hash}', 3);\n\n";

echo "-- Vérification\n";
echo "SELECT \n";
echo "    role,\n";
echo "    CASE \n";
echo "        WHEN role = 1 THEN 'Client'\n";
echo "        WHEN role = 2 THEN 'Déménageur'\n";
echo "        WHEN role = 3 THEN 'Administrateur'\n";
echo "        ELSE 'Autre'\n";
echo "    END as type_compte,\n";
echo "    COUNT(*) as nombre\n";
echo "FROM compte\n";
echo "WHERE email LIKE '%@test.com'\n";
echo "GROUP BY role;\n";

