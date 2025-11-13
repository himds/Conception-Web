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
  $annonceId = isset($_POST['annonce_id']) ? (int)$_POST['annonce_id'] : 0;
  $prix = isset($_POST['prix_eur']) ? (float)$_POST['prix_eur'] : -1;
  $message = isset($_POST['message']) ? trim($_POST['message']) : '';

  if($nominationId <= 0 || $annonceId <= 0 || $prix < 0){
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

  // 检查账户是否被停用 / Vérifier si le compte est désactivé
  // 被停用的账户不能创建报价
  // Les comptes désactivés ne peuvent pas créer d'offres
  $checkActif = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
  if($checkActif && $checkActif->num_rows > 0) {
    if($stmtCheck = $mysqli->prepare("SELECT actif FROM compte WHERE id=?")){
      $stmtCheck->bind_param("i", $demenageurId);
      $stmtCheck->execute();
      $resCheck = $stmtCheck->get_result();
      if($userRow = $resCheck->fetch_assoc()){
        if(isset($userRow['actif']) && $userRow['actif'] == 0){
          $_SESSION['erreur'] = "Votre compte a été désactivé. Vous ne pouvez pas créer d'offre.";
          header('Location: mes_nominations.php');
          exit;
        }
      }
      $stmtCheck->close();
    }
  }

  // 验证指名 / Vérifier la nomination
  // 只能为已接受的指名创建报价
  // Ne peut créer une offre que pour une nomination acceptée
  if($stmt = $mysqli->prepare("SELECT id FROM nomination WHERE id=? AND demenageur_id=? AND etat='accepte'")){
    $stmt->bind_param("ii", $nominationId, $demenageurId);
    $stmt->execute();
    $res = $stmt->get_result();
    if(!$res->fetch_assoc()){
      $_SESSION['erreur'] = "Nomination introuvable ou non acceptée";
      $stmt->close();
      $mysqli->close();
      header('Location: mes_nominations.php');
      exit;
    }
    $stmt->close();
  }

  // 检查是否已有报价 / Vérifier s'il existe déjà une offre
  // 如果已存在，则更新；否则创建新的
  // Si elle existe, la mettre à jour ; sinon créer une nouvelle
  $existingOffreId = null;
  if($stmt = $mysqli->prepare("SELECT id FROM offre WHERE nomination_id=?")){
    $stmt->bind_param("i", $nominationId);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
      $existingOffreId = (int)$row['id'];
    }
    $stmt->close();
  }

  if($existingOffreId){
    // 更新现有报价
    if($stmt = $mysqli->prepare("UPDATE offre SET prix_eur=?, message=? WHERE id=?")){
      $stmt->bind_param("dsi", $prix, $message, $existingOffreId);
      if($stmt->execute()){
        $_SESSION['message'] = "Offre mise à jour";
      } else {
        $_SESSION['erreur'] = "Impossible de mettre à jour l'offre";
      }
      $stmt->close();
    }
  } else {
    // 创建新报价
    if($stmt = $mysqli->prepare("INSERT INTO offre(annonce_id, demenageur_id, nomination_id, prix_eur, message, etat) VALUES (?,?,?,?,?,'propose')")){
      $stmt->bind_param("iiids", $annonceId, $demenageurId, $nominationId, $prix, $message);
      if($stmt->execute()){
        $_SESSION['message'] = "Offre envoyée";
      } else {
        $_SESSION['erreur'] = "Impossible d'envoyer l'offre";
      }
      $stmt->close();
    }
  }

  $mysqli->close();
  header('Location: mes_nominations.php');
  exit;
?>



