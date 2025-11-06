<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 1) {
    $_SESSION['erreur'] = "Accès refusé";
    header('Location: connexion.php');
    exit;
  }

  require_once('param.inc.php');

  $clientId = (int)$_SESSION['user']['id'];
  $offreId = isset($_POST['offre_id']) ? (int)$_POST['offre_id'] : 0;
  $note = isset($_POST['note']) ? (int)$_POST['note'] : 0;
  $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';

  if($offreId <= 0 || $note < 1 || $note > 5){
    $_SESSION['erreur'] = "Paramètres invalides";
    header('Location: index.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: evaluation_create.php?offre_id='.$offreId);
    exit;
  }

  // 验证该出价属于当前客户的已结案公告，且出价已被接受
  $sql = "SELECT o.demenageur_id, a.id AS annonce_id FROM offre o JOIN annonce a ON a.id=o.annonce_id WHERE o.id=? AND a.client_id=? AND a.statut='cloture' AND o.etat='accepte'";
  if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("ii", $offreId, $clientId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if(!$row){
      $_SESSION['erreur'] = "Accès refusé";
      header('Location: annonces.php');
      exit;
    }
    $demenageurId = (int)$row['demenageur_id'];
  }

  // 已评价则阻止重复
  if($stmt = $mysqli->prepare("SELECT id FROM evaluation WHERE offre_id=? LIMIT 1")){
    $stmt->bind_param("i", $offreId);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->fetch_assoc()){
      $_SESSION['erreur'] = "Évaluation déjà envoyée";
      $stmt->close();
      header('Location: annonces.php');
      exit;
    }
    $stmt->close();
  }

  if($stmt = $mysqli->prepare("INSERT INTO evaluation(offre_id, client_id, demenageur_id, note, commentaire) VALUES (?,?,?,?,?)")){
    $stmt->bind_param("iiiis", $offreId, $clientId, $demenageurId, $note, $commentaire);
    if($stmt->execute()){
      $_SESSION['message'] = "Évaluation enregistrée";
    } else {
      $_SESSION['erreur'] = "Impossible d'enregistrer l'évaluation";
    }
    $stmt->close();
  }

  header('Location: annonces.php');
  exit;
?>


