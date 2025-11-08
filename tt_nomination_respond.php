<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 2) {
    $_SESSION['erreur'] = "Accès refusé";
    header('Location: connexion.php');
    exit;
  }

  require_once('param.inc.php');

  $demenageurId = (int)$_SESSION['user']['id'];
  $nominationId = isset($_POST['nomination_id']) ? (int)$_POST['nomination_id'] : 0;
  $action = isset($_POST['action']) ? $_POST['action'] : '';

  if($nominationId <= 0 || !in_array($action, ['accepte', 'refuse'], true)){
    $_SESSION['erreur'] = "Paramètres invalides";
    header('Location: mes_nominations.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: mes_nominations.php');
    exit;
  }

  // 验证指名属于当前搬家工人
  if($stmt = $mysqli->prepare("SELECT id FROM nomination WHERE id=? AND demenageur_id=? AND etat='en_attente'")){
    $stmt->bind_param("ii", $nominationId, $demenageurId);
    $stmt->execute();
    $res = $stmt->get_result();
    if(!$res->fetch_assoc()){
      $_SESSION['erreur'] = "Nomination introuvable ou déjà traitée";
      $stmt->close();
      header('Location: mes_nominations.php');
      exit;
    }
    $stmt->close();
  }

  // 更新指名状态
  if($action === 'refuse'){
    // 拒绝：删除指名
    if($stmt = $mysqli->prepare("DELETE FROM nomination WHERE id=? AND demenageur_id=?")){
      $stmt->bind_param("ii", $nominationId, $demenageurId);
      if($stmt->execute()){
        $_SESSION['message'] = "Nomination refusée";
      } else {
        $_SESSION['erreur'] = "Impossible de refuser la nomination";
      }
      $stmt->close();
    }
  } else {
    // 接受：更新状态
    if($stmt = $mysqli->prepare("UPDATE nomination SET etat='accepte' WHERE id=? AND demenageur_id=?")){
      $stmt->bind_param("ii", $nominationId, $demenageurId);
      if($stmt->execute()){
        $_SESSION['message'] = "Nomination acceptée ! Vous pouvez maintenant faire une offre.";
      } else {
        $_SESSION['erreur'] = "Impossible d'accepter la nomination";
      }
      $stmt->close();
    }
  }

  $mysqli->close();
  header('Location: mes_nominations.php');
  exit;
?>



