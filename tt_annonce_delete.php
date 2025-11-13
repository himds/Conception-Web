<?php
  session_start();
  // 只有客户角色（role=1）才能删除公告
  // Seul le rôle client (role=1) peut supprimer des annonces
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 1) {
    $_SESSION['erreur'] = "Accès refusé";
    header('Location: connexion.php');
    exit;
  }

  require_once('param.inc.php');

  $clientId = (int)$_SESSION['user']['id'];
  $annonceId = isset($_POST['annonce_id']) ? (int)$_POST['annonce_id'] : 0;

  if($annonceId <= 0){
    $_SESSION['erreur'] = "Paramètres invalides";
    header('Location: mes_annonces.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: mes_annonces.php');
    exit;
  }

  // Vérifier que l'annonce appartient au client connecté
  // Vérifier que l'annonce appartient au client connecté
  // 安全措施：确保用户只能删除自己的公告
  // Mesure de sécurité : s'assurer que les utilisateurs ne peuvent supprimer que leurs propres annonces
  if($stmt = $mysqli->prepare("SELECT id, statut FROM annonce WHERE id=? AND client_id=?")){
    $stmt->bind_param("ii", $annonceId, $clientId);
    $stmt->execute();
    $res = $stmt->get_result();
    $annonce = $res->fetch_assoc();
    $stmt->close();
    if(!$annonce){
      $_SESSION['erreur'] = "Annonce introuvable ou accès refusé";
      header('Location: mes_annonces.php');
      exit;
    }
    
    // 只能取消状态为 publie 或 brouillon 的公告
    // Ne peut annuler que les annonces avec le statut 'publie' ou 'brouillon'
    // 已关闭的公告不能删除
    // Les annonces fermées ne peuvent pas être supprimées
    if($annonce['statut'] !== 'publie' && $annonce['statut'] !== 'brouillon'){
      $_SESSION['erreur'] = "Seules les annonces publiées ou en brouillon peuvent être annulées";
      header('Location: mes_annonces.php');
      exit;
    }
  }

  // 删除公告（CASCADE会删除相关的offres, nominations, images等）
  // Supprimer l'annonce (CASCADE supprimera les offres, nominations, images associées, etc.)
  // 数据库外键约束会自动删除关联数据
  // Les contraintes de clé étrangère de la base de données supprimeront automatiquement les données associées
  if($stmt = $mysqli->prepare("DELETE FROM annonce WHERE id=? AND client_id=?")){
    $stmt->bind_param("ii", $annonceId, $clientId);
    if($stmt->execute()){
      $_SESSION['message'] = "Annonce annulée avec succès";
    } else {
      $_SESSION['erreur'] = "Impossible d'annuler l'annonce";
    }
    $stmt->close();
  }

  $mysqli->close();
  header('Location: mes_annonces.php');
  exit;
?>

