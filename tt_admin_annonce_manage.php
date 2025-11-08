<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 3) {
    $_SESSION['erreur'] = "Accès refusé";
    header('Location: connexion.php');
    exit;
  }

  require_once('param.inc.php');

  $annonceId = isset($_POST['annonce_id']) ? (int)$_POST['annonce_id'] : 0;
  $action = isset($_POST['action']) ? $_POST['action'] : '';

  if($annonceId <= 0 || !in_array($action, ['cloture', 'supprimer'], true)){
    $_SESSION['erreur'] = "Paramètres invalides";
    header('Location: admin.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: admin.php');
    exit;
  }

  // 验证公告存在
  if($stmt = $mysqli->prepare("SELECT id FROM annonce WHERE id=?")){
    $stmt->bind_param("i", $annonceId);
    $stmt->execute();
    $res = $stmt->get_result();
    if(!$res->fetch_assoc()){
      $_SESSION['erreur'] = "Annonce introuvable";
      $stmt->close();
      header('Location: admin.php');
      exit;
    }
    $stmt->close();
  }

  if($action === 'supprimer'){
    // 删除公告（CASCADE会删除相关的offres, nominations, images等）
    if($stmt = $mysqli->prepare("DELETE FROM annonce WHERE id=?")){
      $stmt->bind_param("i", $annonceId);
      if($stmt->execute()){
        $_SESSION['message'] = "Annonce supprimée avec succès";
      } else {
        $_SESSION['erreur'] = "Impossible de supprimer l'annonce";
      }
      $stmt->close();
    }
  } else if($action === 'cloture'){
    // 关闭公告
    if($stmt = $mysqli->prepare("UPDATE annonce SET statut='cloture' WHERE id=?")){
      $stmt->bind_param("i", $annonceId);
      if($stmt->execute()){
        $_SESSION['message'] = "Annonce clôturée avec succès";
      } else {
        $_SESSION['erreur'] = "Impossible de clôturer l'annonce";
      }
      $stmt->close();
    }
  }

  $mysqli->close();
  header('Location: admin.php');
  exit;
?>

