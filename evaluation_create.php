<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 1) {
    header('Location: connexion.php');
    exit;
  }

  $titre = "Évaluer le déménageur";
  include('header.inc.php');
  include('menu.inc.php');
  include('message.inc.php');
  require_once('param.inc.php');

  $offreId = isset($_GET['offre_id']) ? (int)$_GET['offre_id'] : 0;
  $nominationId = isset($_GET['nomination_id']) ? (int)$_GET['nomination_id'] : 0;

  if($offreId <= 0 && $nominationId <= 0){
    echo '<div class="alert alert-danger">Paramètres invalides.</div>';
    include('footer.inc.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    echo '<div class="alert alert-danger">Problème de BDD.</div>';
    include('footer.inc.php');
    exit;
  }

  $offre = null;
  $nomination = null;

  if($nominationId > 0){
    // 通过指名ID获取信息
    $sql = "SELECT n.id AS nomination_id, n.demenageur_id, o.id AS offre_id, a.id AS annonce_id, a.titre, a.client_id 
            FROM nomination n 
            JOIN annonce a ON a.id=n.annonce_id 
            LEFT JOIN offre o ON o.nomination_id=n.id 
            WHERE n.id=? AND a.client_id=? AND n.etat='accepte' AND (o.etat='accepte' OR o.id IS NULL)";
    if($stmt = $mysqli->prepare($sql)){
      $stmt->bind_param("ii", $nominationId, $_SESSION['user']['id']);
      $stmt->execute();
      $res = $stmt->get_result();
      $nomination = $res->fetch_assoc();
      $stmt->close();
      if($nomination){
        $offreId = (int)$nomination['offre_id'];
        $offre = $nomination;
      }
    }
  } else {
    // 通过出价ID获取信息（原有逻辑）
    $sql = "SELECT o.id, o.demenageur_id, o.nomination_id, a.id AS annonce_id, a.titre FROM offre o JOIN annonce a ON a.id=o.annonce_id WHERE o.id=? AND a.client_id=? AND a.statut='cloture' AND o.etat='accepte'";
    if($stmt = $mysqli->prepare($sql)){
      $stmt->bind_param("ii", $offreId, $_SESSION['user']['id']);
      $stmt->execute();
      $res = $stmt->get_result();
      $offre = $res->fetch_assoc();
      $stmt->close();
      if($offre && isset($offre['nomination_id']) && $offre['nomination_id']){
        $nominationId = (int)$offre['nomination_id'];
      }
    }
  }

  if(!$offre){
    echo '<div class="alert alert-danger">Accès refusé.</div>';
    include('footer.inc.php');
    exit;
  }

  // 检查是否已评价
  $hasEvaluation = false;
  if($nominationId > 0){
    if($stmt = $mysqli->prepare("SELECT id FROM evaluation WHERE nomination_id=?")){
      $stmt->bind_param("i", $nominationId);
      $stmt->execute();
      $res = $stmt->get_result();
      $hasEvaluation = $res->num_rows > 0;
      $stmt->close();
    }
  } else if($offreId > 0){
    if($stmt = $mysqli->prepare("SELECT id FROM evaluation WHERE offre_id=?")){
      $stmt->bind_param("i", $offreId);
      $stmt->execute();
      $res = $stmt->get_result();
      $hasEvaluation = $res->num_rows > 0;
      $stmt->close();
    }
  }

  if($hasEvaluation){
    echo '<div class="index-page-background">';
    echo '<div class="container-fluid my-4">';
    echo '<div class="alert alert-warning">Vous avez déjà évalué ce déménageur.</div>';
    echo '<div class="mb-3">';
    echo '<a href="mes_annonces.php" class="btn btn-outline-secondary">← Retour à mes annonces</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    include('footer.inc.php');
    exit;
  }

  echo '<div class="index-page-background">';
  echo '<div class="container-fluid my-4">';
  // 返回按钮
  echo '<div class="mb-3">';
  echo '<a href="mes_annonces.php" class="btn btn-outline-secondary">← Retour à mes annonces</a>';
  echo '</div>';
  echo '<h1>Évaluer le déménageur</h1>';
  echo '<p class="text-muted">Annonce: '.htmlspecialchars($offre['titre']).'</p>';
  echo '<hr class="section-divider">';

  echo '<form method="POST" action="tt_evaluation_create.php">';
  if($offreId > 0){
    echo '<input type="hidden" name="offre_id" value="'.(int)$offreId.'">';
  }
  if($nominationId > 0){
    echo '<input type="hidden" name="nomination_id" value="'.(int)$nominationId.'">';
  }
  echo '<div class="row my-3">';
  echo '  <div class="col-md-3">';
  echo '    <label class="form-label" for="note">Note (1-5)</label>';
  echo '    <input class="form-control" type="number" min="1" max="5" id="note" name="note" required>';
  echo '  </div>';
  echo '</div>';
  echo '<div class="mb-3 p-3 rounded bg-orange-100">';
  echo '  <label class="form-label" for="commentaire">Commentaire</label>';
  echo '  <textarea class="form-control" id="commentaire" name="commentaire" rows="3"></textarea>';
  echo '</div>';
  echo '<button class="btn btn-orange" type="submit">Envoyer l\'évaluation</button>';
  echo '</form>';
  echo '</div>';
  echo '</div>';

  include('footer.inc.php');
?>


