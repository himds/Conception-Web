<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 2) {
    header('Location: connexion.php');
    exit;
  }

  $titre = "Mes offres";
  include('header.inc.php');
  include('menu.inc.php');
  include('message.inc.php');
  require_once('param.inc.php');

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    echo '<div class="alert alert-danger">Problème de BDD.</div>';
    include('footer.inc.php');
    exit;
  }

  $sql = "SELECT o.id, o.prix_eur, o.message, o.etat, o.created_at, a.titre, a.ville_depart, a.ville_arrivee, a.date_debut FROM offre o JOIN annonce a ON a.id=o.annonce_id WHERE o.demenageur_id=? ORDER BY o.created_at DESC";
  if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("i", $_SESSION['user']['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    echo '<h1>Mes offres</h1>';
    if($res->num_rows === 0){
      echo '<div class="alert alert-info">Aucune offre.</div>';
    } else {
      echo '<div class="table-responsive">';
      echo '<table class="table table-striped">';
      echo '<thead><tr><th>Annonce</th><th>Itinéraire</th><th>Date</th><th>Prix (€)</th><th>État</th></tr></thead>';
      echo '<tbody>';
      while($row = $res->fetch_assoc()){
        echo '<tr>';
        echo '<td>'.htmlspecialchars($row['titre']).'</td>';
        echo '<td>'.htmlspecialchars($row['ville_depart']).' → '.htmlspecialchars($row['ville_arrivee']).'</td>';
        echo '<td>'.htmlspecialchars($row['date_debut']).'</td>';
        echo '<td>'.htmlspecialchars((string)$row['prix_eur']).'</td>';
        echo '<td>'.htmlspecialchars($row['etat']).'</td>';
        echo '</tr>';
      }
      echo '</tbody></table></div>';
    }
    $stmt->close();
  }

  include('footer.inc.php');
?>


