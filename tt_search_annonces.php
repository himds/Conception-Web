<?php
session_start();
require_once('param.inc.php');

header('Content-Type: application/json');

$ville_depart = trim($_GET['ville_depart'] ?? '');
$ville_arrivee = trim($_GET['ville_arrivee'] ?? '');

$mysqli = @new mysqli($host, $login, $passwd, $dbname);
if($mysqli->connect_error){
  echo json_encode(['success' => false, 'message' => 'Problème de BDD']);
  exit;
}

// Construire la requête SQL
$sql = "SELECT a.id, a.titre, a.ville_depart, a.ville_arrivee, a.date_debut 
        FROM annonce a 
        JOIN compte c ON c.id = a.client_id 
        WHERE a.statut='publie'";

// Vérifier si le champ actif existe
$checkActif = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
if($checkActif && $checkActif->num_rows > 0) {
  $sql .= " AND (c.actif IS NULL OR c.actif = 1)";
}

// Si une ville de départ est saisie, la ville d'arrivée doit également être saisie
if(!empty($ville_depart)) {
  if(empty($ville_arrivee)) {
    echo json_encode(['success' => false, 'message' => 'Si vous entrez une ville de départ, vous devez également entrer une ville d\'arrivée']);
    exit;
  }
  // Correspondre simultanément aux villes de départ et d'arrivée
  $ville_depart = $mysqli->real_escape_string($ville_depart);
  $ville_arrivee = $mysqli->real_escape_string($ville_arrivee);
  $sql .= " AND a.ville_depart LIKE '%{$ville_depart}%' AND a.ville_arrivee LIKE '%{$ville_arrivee}%'";
} elseif(!empty($ville_arrivee)) {
  // Cas où seule la ville d'arrivée est saisie
  $ville_arrivee = $mysqli->real_escape_string($ville_arrivee);
  $sql .= " AND a.ville_arrivee LIKE '%{$ville_arrivee}%'";
}
// Si les deux sont vides, afficher toutes les annonces

$sql .= " ORDER BY a.created_at DESC LIMIT 20";
$res = $mysqli->query($sql);

$annonces = [];
if($res && $res->num_rows > 0) {
  while($row = $res->fetch_assoc()) {
    $annonces[] = [
      'id' => $row['id'],
      'titre' => htmlspecialchars($row['titre']),
      'ville_depart' => htmlspecialchars($row['ville_depart']),
      'ville_arrivee' => htmlspecialchars($row['ville_arrivee']),
      'date_debut' => htmlspecialchars($row['date_debut'])
    ];
  }
}

echo json_encode(['success' => true, 'annonces' => $annonces, 'count' => count($annonces)]);
$mysqli->close();
?>

