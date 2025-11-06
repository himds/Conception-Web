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
  if($offreId <= 0){
    echo '<div class="alert alert-danger">Offre introuvable.</div>';
    include('footer.inc.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    echo '<div class="alert alert-danger">Problème de BDD.</div>';
    include('footer.inc.php');
    exit;
  }

  // 验证该出价属于当前客户的已结案公告，且出价已被接受
  $sql = "SELECT o.id, o.demenageur_id, a.id AS annonce_id, a.titre FROM offre o JOIN annonce a ON a.id=o.annonce_id WHERE o.id=? AND a.client_id=? AND a.statut='cloture' AND o.etat='accepte'";
  if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("ii", $offreId, $_SESSION['user']['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $offre = $res->fetch_assoc();
    $stmt->close();
    if(!$offre){
      echo '<div class="alert alert-danger">Accès refusé.</div>';
      include('footer.inc.php');
      exit;
    }
  }

  echo '<h1>Évaluer le déménageur</h1>';
  echo '<p class="text-muted">Annonce: '.htmlspecialchars($offre['titre']).'</p>';
  echo '<hr class="section-divider">';

  echo '<form method="POST" action="tt_evaluation_create.php">';
  echo '<input type="hidden" name="offre_id" value="'.(int)$offreId.'">';
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

  include('footer.inc.php');
?>


