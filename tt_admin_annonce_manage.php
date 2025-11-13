<?php
  session_start();
  // 管理员权限检查 / Vérification des permissions administrateur
  // 只有管理员角色（role=3）才能管理公告
  // Seul le rôle administrateur (role=3) peut gérer les annonces
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 3) {
    $_SESSION['erreur'] = "Accès refusé";
    header('Location: connexion.php');
    exit;
  }

  require_once('param.inc.php');

  $annonceId = isset($_POST['annonce_id']) ? (int)$_POST['annonce_id'] : 0;
  $action = isset($_POST['action']) ? $_POST['action'] : '';

  // 验证参数 / Valider les paramètres
  // action可以是 'cloture'（关闭）或 'supprimer'（删除）
  // action peut être 'cloture' (fermer) ou 'supprimer' (supprimer)
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

  // 验证公告存在 / Vérifier que l'annonce existe
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
    // Supprimer l'annonce (CASCADE supprimera les offres, nominations, images associées, etc.)
    // 管理员可以删除任何公告
    // L'administrateur peut supprimer n'importe quelle annonce
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
    // 关闭公告 / Fermer l'annonce
    // 将公告状态改为 'cloture'（已关闭）
    // Changer le statut de l'annonce à 'cloture' (fermée)
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

