<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 1) {
    $_SESSION['erreur'] = "Accès refusé";
    header('Location: connexion.php');
    exit;
  }

  require_once('param.inc.php');

  $clientId = (int)$_SESSION['user']['id'];
  $nominationId = isset($_POST['nomination_id']) ? (int)$_POST['nomination_id'] : 0;
  $offreId = isset($_POST['offre_id']) ? (int)$_POST['offre_id'] : 0;
  $annonceId = isset($_POST['annonce_id']) ? (int)$_POST['annonce_id'] : 0;

  if($nominationId <= 0 || $offreId <= 0 || $annonceId <= 0){
    $_SESSION['erreur'] = "Paramètres invalides";
    header('Location: annonce_offres.php?id='.$annonceId);
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: annonce_offres.php?id='.$annonceId);
    exit;
  }

  // 验证所有权
  if($stmt = $mysqli->prepare("SELECT a.id FROM annonce a JOIN nomination n ON n.annonce_id=a.id WHERE n.id=? AND a.client_id=? AND n.etat='accepte'")){
    $stmt->bind_param("ii", $nominationId, $clientId);
    $stmt->execute();
    $res = $stmt->get_result();
    if(!$res->fetch_assoc()){
      $_SESSION['erreur'] = "Accès refusé";
      $stmt->close();
      $mysqli->close();
      header('Location: annonces.php');
      exit;
    }
    $stmt->close();
  }

  // 重定向到评分页面
  header('Location: evaluation_create.php?nomination_id='.$nominationId);
  exit;
?>






