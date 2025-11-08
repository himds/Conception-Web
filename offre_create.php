<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 2) {
    header('Location: connexion.php');
    exit;
  }

  $titre = "Faire une offre";
  include('header.inc.php');
  include('menu.inc.php');
  include('message.inc.php');
  require_once('param.inc.php');

  $demenageurId = (int)$_SESSION['user']['id'];
  $nominationId = isset($_GET['nomination_id']) ? (int)$_GET['nomination_id'] : 0;

  if($nominationId <= 0){
    $_SESSION['erreur'] = "Nomination introuvable";
    header('Location: mes_nominations.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    echo '<div class="alert alert-danger">Problème de BDD.</div>';
    include('footer.inc.php');
    exit;
  }

  // 验证指名
  $sql = "SELECT n.id, n.etat, a.id AS annonce_id, a.titre, a.ville_depart, a.ville_arrivee, a.date_debut, c.prenom, c.nom 
          FROM nomination n 
          JOIN annonce a ON a.id=n.annonce_id 
          JOIN compte c ON c.id=a.client_id 
          WHERE n.id=? AND n.demenageur_id=? AND n.etat='accepte'";
  
  if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("ii", $nominationId, $demenageurId);
    $stmt->execute();
    $res = $stmt->get_result();
    $nomination = $res->fetch_assoc();
    $stmt->close();
    
    if(!$nomination){
      $_SESSION['erreur'] = "Nomination introuvable ou non acceptée";
      $mysqli->close();
      header('Location: mes_nominations.php');
      exit;
    }
  }

  // 检查是否已有报价
  $existingOffre = null;
  if($stmt = $mysqli->prepare("SELECT id, prix_eur, message FROM offre WHERE nomination_id=?")){
    $stmt->bind_param("i", $nominationId);
    $stmt->execute();
    $res = $stmt->get_result();
    $existingOffre = $res->fetch_assoc();
    $stmt->close();
  }

  echo '<div class="index-page-background">';
  echo '<div class="container-fluid my-4">';
  // 返回按钮
  echo '<div class="mb-3">';
  echo '<a href="mes_nominations.php" class="btn btn-outline-secondary">← Retour à mes nominations</a>';
  echo '</div>';
  echo '<h1>Faire une offre</h1>';
  echo '<div class="card mb-3">';
  echo '<div class="card-body">';
  echo '<h5 class="card-title">'.htmlspecialchars($nomination['titre']).'</h5>';
  echo '<p class="card-text"><strong>Client:</strong> '.htmlspecialchars($nomination['prenom'].' '.$nomination['nom']).'</p>';
  echo '<p class="card-text"><strong>Itinéraire:</strong> '.htmlspecialchars($nomination['ville_depart'].' → '.$nomination['ville_arrivee']).'</p>';
  echo '<p class="card-text"><strong>Date:</strong> '.htmlspecialchars($nomination['date_debut']).'</p>';
  echo '</div>';
  echo '</div>';

  echo '<form method="POST" action="tt_offre_create_nomination.php">';
  echo '<input type="hidden" name="nomination_id" value="'.(int)$nominationId.'">';
  echo '<input type="hidden" name="annonce_id" value="'.(int)$nomination['annonce_id'].'">';
  
  echo '<div class="mb-3">';
  echo '<label class="form-label" for="prix_eur">Prix (€)</label>';
  echo '<input class="form-control" type="number" step="0.01" min="0" id="prix_eur" name="prix_eur" value="'.($existingOffre ? htmlspecialchars($existingOffre['prix_eur']) : '').'" required>';
  echo '</div>';
  
  echo '<div class="mb-3">';
  echo '<label class="form-label" for="message">Message (optionnel)</label>';
  echo '<textarea class="form-control" id="message" name="message" rows="3">'.($existingOffre ? htmlspecialchars($existingOffre['message']) : '').'</textarea>';
  echo '</div>';
  
  echo '<button class="btn btn-primary" type="submit">'.($existingOffre ? 'Modifier l\'offre' : 'Envoyer l\'offre').'</button>';
  echo ' <a class="btn btn-outline-secondary" href="mes_nominations.php">Annuler</a>';
  echo '</form>';
  echo '</div>';
  echo '</div>';

  $mysqli->close();
  include('footer.inc.php');
?>


