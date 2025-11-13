<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 3) {
    $_SESSION['erreur'] = "Accès refusé";
    header('Location: connexion.php');
    exit;
  }

  require_once('param.inc.php');

  $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
  $action = isset($_POST['action']) ? $_POST['action'] : '';

  if($userId <= 0 || !in_array($action, ['activer', 'desactiver'], true)){
    $_SESSION['erreur'] = "Paramètres invalides";
    header('Location: admin.php');
    exit;
  }

  // 不能停用自己的账户 / Ne peut pas désactiver son propre compte
  // 安全措施：防止管理员意外停用自己
  // Mesure de sécurité : empêcher l'administrateur de se désactiver accidentellement
  if($userId === (int)$_SESSION['user']['id']){
    $_SESSION['erreur'] = "Vous ne pouvez pas désactiver votre propre compte";
    header('Location: admin.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: admin.php');
    exit;
  }

  // 检查 actif 字段是否存在，如果不存在则创建
  // Vérifier si le champ actif existe, le créer s'il n'existe pas
  $checkActif = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
  if(!$checkActif || $checkActif->num_rows === 0) {
    $mysqli->query("ALTER TABLE compte ADD COLUMN actif TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=actif, 0=désactivé'");
    $mysqli->query("ALTER TABLE compte ADD INDEX idx_actif (actif)");
  }

  // 验证用户存在且不是管理员 / Vérifier que l'utilisateur existe et n'est pas administrateur
  // 安全措施：防止停用管理员账户
  // Mesure de sécurité : empêcher la désactivation des comptes administrateur
  if($stmt = $mysqli->prepare("SELECT id, role FROM compte WHERE id=?")){
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();
    if(!$user){
      $_SESSION['erreur'] = "Utilisateur introuvable";
      header('Location: admin.php');
      exit;
    }
    // 不能停用管理员账户 / Ne peut pas désactiver un compte administrateur
    // 管理员账户（role=3）不能被停用
    // Les comptes administrateur (role=3) ne peuvent pas être désactivés
    if((int)$user['role'] === 3){
      $_SESSION['erreur'] = "Impossible de désactiver un compte administrateur";
      header('Location: admin.php');
      exit;
    }
  }

  // 更新账户状态 / Mettre à jour le statut du compte
  // 1=激活 / actif, 0=停用 / désactivé
  $newStatus = $action === 'activer' ? 1 : 0;
  if($stmt = $mysqli->prepare("UPDATE compte SET actif=? WHERE id=?")){
    $stmt->bind_param("ii", $newStatus, $userId);
    if($stmt->execute()){
      $_SESSION['message'] = $action === 'activer' ? "Compte activé avec succès" : "Compte désactivé avec succès";
    } else {
      $_SESSION['erreur'] = "Impossible de modifier le statut du compte";
    }
    $stmt->close();
  }

  $mysqli->close();
  header('Location: admin.php');
  exit;
?>

